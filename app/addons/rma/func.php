<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

use Tygh\Enum\InventoryOperations;
use Tygh\Enum\Addons\Rma\RecalculateDataTypes;
use Tygh\Enum\Addons\Rma\RecalculateOperations;
use Tygh\Enum\Addons\Rma\ReturnOperationStatuses;
use Tygh\Enum\OrderDataTypes;
use Tygh\Enum\ReceiverSearchMethods;
use Tygh\Enum\UserTypes;
use Tygh\Enum\VendorPayoutApprovalStatuses;
use Tygh\Enum\VendorPayoutTypes;
use Tygh\Enum\YesNo;
use Tygh\Enum\SiteArea;
use Tygh\Navigation\LastView;
use Tygh\Notifications\Receivers\SearchCondition;
use Tygh\Registry;
use Tygh\VendorPayoutDetailsBuilder;
use Tygh\VendorPayouts;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_get_rma_properties($type = RMA_REASON, $lang_code = CART_LANGUAGE)
{
    $status = (AREA == 'A') ? '' : " AND a.status = 'A'";

    return db_get_hash_array("SELECT a.*, b.property FROM ?:rma_properties AS a LEFT JOIN ?:rma_property_descriptions AS b ON a.property_id = b.property_id AND b.lang_code = ?s WHERE a.type = ?s $status ORDER BY a.position ASC", 'property_id', $lang_code, $type);
}

function fn_rma_delete_property($property_id)
{
    db_query("DELETE FROM ?:rma_properties WHERE property_id = ?i", $property_id);
    db_query("DELETE FROM ?:rma_property_descriptions WHERE property_id = ?i", $property_id);
}

function fn_is_returnable_product($product_id)
{
    $return_info = db_get_row("SELECT is_returnable, return_period  FROM ?:products WHERE product_id = ?i", $product_id);

    return (!empty($return_info) && YesNo::toBool($return_info['is_returnable']) && !empty($return_info['return_period'])) ? $return_info['return_period'] : false;
}

function fn_rma_add_to_cart(&$cart, &$product_id, &$_id)
{
    $return_period = fn_is_returnable_product($product_id);
    if ($return_period && !empty($cart['products'][$_id]['product_id'])) {
        $cart['products'][$_id]['return_period'] = $cart['products'][$_id]['extra']['return_period'] = $return_period;
    }
}

function fn_rma_get_product_data(&$product_id, &$field_list, &$join)
{
    $field_list .= ", ?:products.is_returnable, ?:products.return_period";
}

function fn_check_product_return_period($return_period, $timestamp)
{
    $weekdays = 0;
    $round_the_clock = 60 * 60 * 24;

    if (YesNo::toBool(Registry::get('addons.rma.dont_take_weekends_into_account'))) {
        $passed_days = floor((TIME - $timestamp) / $round_the_clock);
        for ($i = 1; $i <= $passed_days; $i++) {
            if (strstr(SATURDAY.SUNDAY, strftime("%w", $timestamp + $i * $round_the_clock))) {
                $weekdays++;
            }
        }
    }

    return ((($return_period + $weekdays) * $round_the_clock + $timestamp) > TIME) ? true : false;
}

function fn_get_order_returnable_products($order_items, $timestamp)
{
    $item_returns_info = array();
    foreach ((array) $order_items as $k => $v) {
        if (
            isset($v['extra']['return_period'])
            && true === fn_check_product_return_period($v['extra']['return_period'], $timestamp)
            || SiteArea::isAdmin(AREA)
        ) {
            if (!isset($v['extra']['exclude_from_calculate'])) {
                $order_items[$k]['price'] = fn_format_price($v['subtotal'] / $v['amount']);
            }
            if (isset($v['extra']['returns'])) {
                foreach ((array) $v['extra']['returns'] as $return_id => $value) {
                    $item_returns_info[$k][$value['status']] = (isset($item_returns_info[$k][$value['status']]) ? $item_returns_info[$k][$value['status']] : 0) + $value['amount'];
                }
                if (0 >= $order_items[$k]['amount'] = $v['amount'] - array_sum($item_returns_info[$k])) {
                    unset($order_items[$k]);
                }
            }
        } else {
            unset($order_items[$k]);
        }
    }

    return array(
        'items'	            => $order_items,
        'item_returns_info' => $item_returns_info
    );
}

function fn_rma_generate_sections($section)
{
    Registry::set('navigation.dynamic.sections', array (
        'requests' => array (
            'title' => __('return_requests'),
            'href' => "rma.returns",
        ),
    ));

    Registry::set('navigation.dynamic.active_section', $section);

    return true;
}

function fn_rma_get_order_info(&$order, &$additional_data)
{
    if (!empty($order)) {
        $status_data = fn_get_status_params($order['status'], STATUSES_ORDER);

        if (!empty($status_data) && (!empty($status_data['allow_return']) && YesNo::toBool($status_data['allow_return'])) && isset($additional_data[ORDER_DATA_PRODUCTS_DELIVERY_DATE])) {
            $order_returnable_products = fn_get_order_returnable_products($order['products'], $additional_data[ORDER_DATA_PRODUCTS_DELIVERY_DATE]);
            if (!empty($order_returnable_products['items'])) {
                $order['allow_return'] = 'Y';
            }
            if (!empty($order_returnable_products['item_returns_info'])) {
                foreach ($order_returnable_products['item_returns_info'] as $item_id => $returns_info) {
                    $order['products'][$item_id]['returns_info'] = $returns_info;
                }
            }
        }

        if (!empty($additional_data[ORDER_DATA_PRODUCTS_DELIVERY_DATE])) {
            $order['products_delivery_date'] = $additional_data[ORDER_DATA_PRODUCTS_DELIVERY_DATE];
        }

        if (!empty($additional_data[ORDER_DATA_RETURN])) {
            $order_return_info = @unserialize($additional_data[ORDER_DATA_RETURN]);
            $order['return'] = @$order_return_info['return'];
            $order['returned_products'] = @$order_return_info['returned_products'];

            foreach ((array) $order['returned_products'] as $k => $v) {
                $v['product'] = !empty($v['extra']['product']) ? $v['extra']['product'] : fn_get_product_name($v['product_id'], CART_LANGUAGE);
                if (empty($v['product'])) {
                    $v['product'] = strtoupper(__('deleted_product'));
                }
                $v['discount'] = (!empty($v['extra']['discount']) && floatval($v['extra']['discount'])) ? $v['extra']['discount'] : 0 ;

                if (!empty($v['extra']['product_options_value'])) {
                    $v['product_options'] = $v['extra']['product_options_value'];
                }
                $v['subtotal'] = ($v['price'] * $v['amount'] - $v['discount']);
                $order['returned_products'][$k] = $v;
            }
        }

        if (0 < $returns_count = db_get_field("SELECT COUNT(*) FROM ?:rma_returns WHERE order_id = ?i", $order['order_id'])) {
            $order['isset_returns'] = 'Y';
        }
    }
}

function fn_get_return_info($return_id)
{
    if (!empty($return_id)) {
        $return = db_get_row("SELECT * FROM ?:rma_returns WHERE return_id = ?i", $return_id);

        if (empty($return)) {
            return array();
        }

        $return['items'] = db_get_hash_multi_array("SELECT ?:rma_return_products.*, ?:products.product_id as original_product_id FROM ?:rma_return_products LEFT JOIN ?:products ON ?:rma_return_products.product_id = ?:products.product_id WHERE ?:rma_return_products.return_id = ?i", array('type', 'item_id'), $return_id);
        foreach ($return['items'] as $type => $value) {
            foreach ($value as $k => $v) {
                if (0 == floatval($v['price'])) {
                    $return['items'][$type][$k]['price'] = '';
                }

                if (empty($v['original_product_id'])) {
                    $return['items'][$type][$k]['deleted_product'] = true;
                }

                if (empty($v['product'])) {
                    $v['product'] = strtoupper(__('deleted_product'));
                }

                $return['items'][$type][$k]['product_options'] = !empty($return['items'][$type][$k]['product_options']) ? unserialize($return['items'][$type][$k]['product_options']) : array();
            }
        }

        return $return;
    }

    return false;
}

function fn_return_product_routine($return_id, $item_id, $item, $direction)
{

    $reverse = array(
        ReturnOperationStatuses::APPROVED => ReturnOperationStatuses::DECLINED,
        ReturnOperationStatuses::DECLINED => ReturnOperationStatuses::APPROVED
    );

    if (!empty($return_id) && !empty($item_id) && !empty($direction) && !empty($item)) {
        $is_amount = db_get_field("SELECT amount FROM ?:rma_return_products WHERE return_id = ?i AND item_id = ?i AND type = ?s", $return_id, $item_id, $direction);
        if (($item['previous_amount'] - $item['amount']) <= 0) {
            if (empty($is_amount)) {
                db_query('UPDATE ?:rma_return_products SET ?u WHERE return_id = ?i AND item_id = ?i AND type = ?s', array('type' => $direction), $return_id, $item_id, $reverse[$direction]);
            } else {
                db_query("DELETE FROM ?:rma_return_products WHERE return_id = ?i AND item_id = ?i AND type = ?s", $return_id, $item_id, $reverse[$direction]);
            }
        } else {
            $_data = db_get_row("SELECT * FROM ?:rma_return_products WHERE return_id = ?i AND item_id = ?i AND type = ?s", $return_id, $item_id, $reverse[$direction]);
            db_query('UPDATE ?:rma_return_products SET ?u WHERE return_id = ?i AND item_id = ?i AND type = ?s', array('amount' => $_data['amount'] - $item['amount']), $return_id, $item_id, $reverse[$direction]);

            if (empty($is_amount)) {
                $_data['amount'] = $item['amount'];
                $_data['type'] = $direction;
                db_query("REPLACE INTO ?:rma_return_products ?e", $_data);
            }
        }
        if (!empty($is_amount)) {
            db_query('UPDATE ?:rma_return_products SET ?u WHERE return_id = ?i AND item_id = ?i AND type = ?s', array('amount' => $is_amount + $item['amount']), $return_id, $item_id, $direction);
        }
    }

    return false;
}

function fn_delete_return($return_id)
{

    $items = db_get_array("SELECT item_id, ?:order_details.extra, ?:order_details.order_id FROM ?:order_details LEFT JOIN ?:rma_returns ON ?:order_details.order_id = ?:rma_returns.order_id WHERE  return_id = ?i", $return_id);
    foreach ($items as $item) {
        $extra = unserialize($item['extra']);
        if (isset($extra['returns'])) {
            unset($extra['returns']);
        }
        db_query('UPDATE ?:order_details SET ?u WHERE item_id = ?i AND order_id = ?i', array('extra' => serialize($extra)), $item['item_id'],  $item['order_id']);
    }

    db_query("DELETE FROM ?:rma_returns WHERE return_id = ?i", $return_id);
    db_query("DELETE FROM ?:rma_return_products WHERE return_id = ?i", $return_id);
}

/**
 * @deprecated since 4.12.1. Use fn_rma_send_notification instead.
 */
function fn_send_return_mail(&$return_info, &$order_info, $force_notification = [], $area = AREA)
{
    fn_rma_send_notification($return_info, $order_info, $force_notification);
}

/**
 * Sends notification about current return request status.
 *
 * @param array      $return_info        Return request info
 * @param array      $order_info         Associated order info
 * @param array|bool $force_notification Notification rules
 */
function fn_rma_send_notification($return_info, $order_info, $force_notification = [])
{
    /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
    $event_dispatcher = Tygh::$app['event.dispatcher'];

    /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
    $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];
    $notification_rules = $notification_settings_factory->create($force_notification);

    $data = [
        'order_info' => $order_info,
        'return_info' => $return_info,
    ];

    $status_id = strtolower($return_info['status']);
    $event_id = "rma.status_changed.{$status_id}";

    $event_dispatcher->dispatch($event_id, $data, $notification_rules);
}

/**
 * Process parameters of rma.update_details POST requests.
 *
 * @param array<string> $data Request parameters.
 *
 * @return bool True if refund confirmation page should be shown, false otherwise.
 */
function fn_rma_update_details($data)
{
    fn_set_hook('rma_update_details_pre', $data);
    $change_return_status = $data['change_return_status'];

    $_data = array();
    $show_confirmation_page = false;
    if (isset($data['comment'])) {
        $_data['comment'] = $data['comment'];
    }

    $is_refund = fn_is_refund_action($change_return_status['action']);
    $confirmed = isset($data['confirmed']) ? $data['confirmed'] : '';
    $st_inv = fn_get_statuses(STATUSES_RETURN);
    $show_confirmation = false;
    if ((
            ($change_return_status['recalculate_order'] === RecalculateOperations::MANUALLY && YesNo::toBool($is_refund))
            || $change_return_status['recalculate_order'] === RecalculateOperations::AUTO
        ) &&
        $change_return_status['status_to'] !== $change_return_status['status_from'] &&
        !(
            $st_inv[$change_return_status['status_from']]['params']['inventory'] === InventoryOperations::DECREASED
            && $change_return_status['status_to'] === ReturnOperationStatuses::REQUESTED) &&
        !(
            $st_inv[$change_return_status['status_to']]['params']['inventory'] === InventoryOperations::DECREASED
            && $change_return_status['status_from'] === ReturnOperationStatuses::REQUESTED) &&
        !(
            $st_inv[$change_return_status['status_to']]['params']['inventory'] === InventoryOperations::DECREASED
            && $st_inv[$change_return_status['status_from']]['params']['inventory'] === InventoryOperations::DECREASED
        )
    ) {
        $show_confirmation = true;
    }

    $old_order_info = fn_get_order_info($change_return_status['order_id']);

    if ($show_confirmation == true) {
        if (YesNo::toBool($confirmed)) {
            fn_rma_recalculate_order($change_return_status['order_id'], $change_return_status['recalculate_order'], $change_return_status['return_id'], $is_refund, $change_return_status);
            $_data['status'] = $change_return_status['status_to'];
        } else {
            $change_return_status['inventory_to'] = $st_inv[$change_return_status['status_to']]['params']['inventory'];
            $change_return_status['inventory_from'] = $st_inv[$change_return_status['status_from']]['params']['inventory'];
            Tygh::$app['session']['change_return_status'] = $change_return_status;
            $show_confirmation_page = true;
        }
    } else {
        $_data['status'] = $change_return_status['status_to'];
    }

    if (!empty($_data)) {
        db_query("UPDATE ?:rma_returns SET ?u WHERE return_id = ?i", $_data, $change_return_status['return_id']);
    }

    if ((!$show_confirmation || ($show_confirmation && YesNo::toBool($confirmed))) && $change_return_status['status_from'] != $change_return_status['status_to']) {
        $order_items = db_get_hash_single_array("SELECT item_id, extra FROM ?:order_details WHERE ?:order_details.order_id = ?i", array('item_id', 'extra'), $change_return_status['order_id']);

        foreach ($order_items as $item_id => $extra) {
            $extra = @unserialize($extra);
            if (isset($extra['returns'][$change_return_status['return_id']])) {
                $extra['returns'][$change_return_status['return_id']]['status'] = $change_return_status['status_to'];
                db_query('UPDATE ?:order_details SET ?u WHERE item_id = ?i AND order_id = ?i', array('extra' => serialize($extra)), $item_id, $change_return_status['order_id']);
            }
        }

        $return_info = fn_get_return_info($change_return_status['return_id']);
        $order_info = fn_get_order_info($change_return_status['order_id']);
        if (empty($order_info)) {
            return $show_confirmation_page;
        }
        fn_rma_send_notification($return_info, $order_info, fn_get_notification_rules($change_return_status));

        if (
            fn_allowed_for('MULTIVENDOR')
            && YesNo::toBool($is_refund)
            && (
                $change_return_status['status_to'] === ReturnOperationStatuses::COMPLETED
                || $change_return_status['status_from'] === ReturnOperationStatuses::COMPLETED
            )
        ) {
            $payout_data = [
                'order_id' => $change_return_status['order_id'],
                'company_id' => $order_info['company_id'],
                'payout_type' => VendorPayoutTypes::ORDER_REFUNDED,
                'approval_status' => VendorPayoutApprovalStatuses::COMPLETED,
            ];
            $payout_builder = new VendorPayoutDetailsBuilder();
            $payout_data['details'] = $payout_builder->createDetails($order_info);
            if (!empty($old_order_info)) {
                $payout_data['old_details'] = $payout_builder->createDetails($old_order_info);
                $updated_details = $payout_builder->createUpdatedDetails($order_info, $payout_data['old_details']);
                $payout_amount = $updated_details['order_products_cost']
                    + $updated_details['included_product_tax_amount'];
            } else {
                $payout_amount = 0;
                if (!empty($return_info['items']['A'])) {
                    foreach ($return_info['items']['A'] as $product_info) {
                        $payout_amount += (int) $product_info['amount'] * (float) $product_info['price'];
                    }
                }
            }

            $return_percentage = 1;
            $returned_value = abs($payout_amount);
            if ($order_info['subtotal'] > 0) {
                if ($change_return_status['recalculate_order'] === RecalculateOperations::AUTO) {
                    $return_percentage = $returned_value / ($order_info['subtotal'] + $returned_value);
                    $subtotal_discount = $order_info['subtotal_discount'] - round($order_info['subtotal_discount'] * $return_percentage, 2);
                    $payout_data['details']['order_products_discount'] = $subtotal_discount;

                    db_query('UPDATE ?:orders SET subtotal_discount = ?d WHERE order_id = ?i', $subtotal_discount, $order_info['order_id']);
                } else {
                    $return_percentage = $returned_value / $order_info['subtotal'];
                }
            } elseif ($change_return_status['recalculate_order'] === RecalculateOperations::AUTO) {
                db_query('UPDATE ?:orders SET subtotal_discount = 0 WHERE order_id = ?i', $order_info['order_id']);
            }
            $discount_correction = round($order_info['subtotal_discount'] * $return_percentage, 2);
            if ($payout_amount < 0) {
                $payout_amount += $discount_correction;
            } else {
                $payout_amount -= $discount_correction;
            }

            $payout_data['is_percent_commission_included'] = $change_return_status['recalculate_order'] !== RecalculateOperations::NOT_RECALCULATE;
            $payout_data['order_amount'] = $payout_amount;
            if (!empty($updated_details)) {
                $payout_data['order_amount'] +=
                    $updated_details['order_shipping_cost']
                    + $updated_details['shipping_tax_amount']
                    + $updated_details['not_included_product_tax_amount'];
            }
            $payout_data['return_percentage'] = $return_percentage;

            /**
             * Executes before creating a payout based on the return request, allows to modify the payout data.
             *
             * @param array $data           Request parameters
             * @param array $order_info     Order information from ::fn_get_orders()
             * @param array $return_info    Return request from ::fn_get_return_info()
             * @param array $payout_data    Payout data to be stored in the DB
             * @param array $old_order_info Order information before refund
             */
            fn_set_hook('rma_update_details_create_payout', $data, $order_info, $return_info, $payout_data, $old_order_info);

            VendorPayouts::instance()->update($payout_data);
        }
    }

    fn_set_hook('rma_update_details_post', $data, $show_confirmation_page, $show_confirmation, $is_refund, $_data, $confirmed);

    return $show_confirmation_page;
}

function fn_is_refund_action($action)
{
    return 	db_get_field("SELECT update_totals_and_inventory FROM ?:rma_properties WHERE property_id = ?i", $action);
}

function fn_rma_delete_gift_certificate(&$gift_cert_id, &$extra)
{

    $potentional_certificates = array();

    if (isset($extra['return_id'])) {
        $potentional_certificates[$extra['return_id']] = db_get_field("SELECT extra FROM ?:rma_returns WHERE return_id = ?i", $extra['return_id']);
    } else {
        $potentional_certificates = db_get_hash_single_array("SELECT return_id, extra FROM ?:rma_returns WHERE extra IS NOT NULL", array('return_id', 'extra'));
    }

    if (!empty($potentional_certificates)) {
        foreach ($potentional_certificates as $return_id => $return_extra) {
            $return_extra = @unserialize($return_extra);
            if (isset($return_extra['gift_certificates'])) {
                foreach ((array) $return_extra['gift_certificates'] as $k => $v) {
                    if ($k == $gift_cert_id) {
                        unset($return_extra['gift_certificates'][$k]);
                        if (empty($return_extra['gift_certificates'])) {
                            unset($return_extra['gift_certificates']);
                        }
                        db_query('UPDATE ?:rma_returns SET ?u WHERE return_id = ?i', array('extra' => serialize($return_extra)), $return_id);
                        break;
                    }
                }
            }
        }
    }
}

function fn_rma_declined_product_correction($order_id, $item_id, $available_amount, $amount)
{
    $declined_items_amount = db_get_field("SELECT SUM(?:rma_return_products.amount) FROM ?:rma_return_products LEFT JOIN ?:rma_returns ON ?:rma_returns.return_id = ?:rma_return_products.return_id AND ?:rma_returns.order_id = ?i  WHERE ?:rma_return_products.item_id = ?i AND ?:rma_return_products.type = ?s GROUP BY ?:rma_return_products.item_id", $order_id, $item_id, ReturnOperationStatuses::DECLINED);
    if ($available_amount - $amount >= $declined_items_amount) {
        return true;
    } else {
        $declined_items	 = db_get_hash_array("SELECT ?:rma_return_products.return_id, item_id, amount FROM ?:rma_return_products LEFT JOIN ?:rma_returns ON ?:rma_returns.return_id = ?:rma_return_products.return_id AND ?:rma_returns.order_id = ?i WHERE ?:rma_return_products.item_id = ?i AND ?:rma_return_products.type = ?s", 'return_id', $order_id, $item_id, ReturnOperationStatuses::DECLINED);
        foreach ($declined_items as $return_id => $v) {
            $difference = $v['amount'] - $amount;
            if ($difference > 0) {
                db_query('UPDATE ?:rma_return_products SET ?u WHERE return_id = ?i AND item_id = ?i AND type = ?s', array('amount' => $difference), $return_id, $v['item_id'], ReturnOperationStatuses::DECLINED);

                return true;
            } elseif ($difference <= 0) {
                db_query("DELETE FROM ?:rma_return_products WHERE return_id = ?i AND item_id = ?i AND type = ?s", $return_id, $v['item_id'], ReturnOperationStatuses::DECLINED);
                if ($difference == 0) {
                    return true;
                }
            }
        }
    }
}

function fn_rma_change_order_status(&$status_to, &$status_from, &$order_info)
{

    $status_data = fn_get_status_params($status_to, STATUSES_ORDER);

    if (!empty($status_data) && (!empty($status_data['allow_return']) && YesNo::toBool($status_data['allow_return']))) {
        $_data = array(
            'order_id' => $order_info['order_id'],
            'type' => ORDER_DATA_PRODUCTS_DELIVERY_DATE,
            'data' => TIME
        );
        db_query("REPLACE INTO ?:order_data ?e", $_data);
    } else {
        db_query("DELETE FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_info['order_id'], ORDER_DATA_PRODUCTS_DELIVERY_DATE);
    }
}

/**
 * Updates taxes amounts and order totals when recalculating an order on the refund.
 *
 * @param array        $taxes_list     Stored taxes list from an order
 * @param int          $item_id        Cart ID of the product
 * @param int          $old_amount     Old product amount
 * @param int          $new_amount     New product amount
 * @param array        $current_order  Current order totals
 * @param float|null   $price          Returned product price
 * @param float[]|null $original_order Original order totals
 *
 * @return bool Always true
 */
function fn_rma_update_order_taxes(
    &$taxes_list,
    $item_id,
    $old_amount,
    $new_amount,
    &$current_order,
    $price = null,
    array $original_order = null
) {
    static $original_taxes_list;
    if (is_array($taxes_list)) {
        if ($original_taxes_list === null) {
            $original_taxes_list = $taxes_list;
        }
        foreach ($taxes_list as $k => &$tax) {
            $tax_changed = false;
            $old_tax_amount = $new_tax_amount = null;

            if (isset($tax['applies']['P_' . $item_id])) {
                $tax_changed = true;
                $old_tax_amount = $tax['applies']['P_' . $item_id];
                $new_tax_amount = fn_format_price($old_tax_amount * $new_amount / $old_amount);
                $tax['applies']['P_' . $item_id] = $new_tax_amount;
                $tax['tax_subtotal'] -=  ($old_tax_amount - $new_tax_amount);
            } elseif (isset($original_taxes_list[$k]['applies']['P'])
                && !empty($original_taxes_list[$k]['applies']['items']['P'][$item_id])
                && $price !== null
                && $original_order !== null
            ) {
                $tax_changed = true;
                $price_percentage = $price / $original_order['subtotal'];
                $old_tax_amount = fn_format_price(
                    $original_taxes_list[$k]['applies']['P']
                    * $price_percentage
                    * $old_amount
                );
                $new_tax_amount = fn_format_price(
                    $original_taxes_list[$k]['applies']['P']
                    * $price_percentage
                    * $new_amount
                );
                $tax['applies']['P'] -= ($old_tax_amount - $new_tax_amount);
                $tax['tax_subtotal'] -= ($old_tax_amount - $new_tax_amount);
                if ($new_amount == 0) {
                    unset($tax['applies']['items']['P'][$item_id]);
                }
            }
            if ($tax_changed && $tax['price_includes_tax'] == 'N' && isset($new_tax_amount) && isset($old_tax_amount)) {
                $current_order['total'] -= ($old_tax_amount - $new_tax_amount);
            }
        }
        unset($tax, $old_tax_amount, $new_tax_amount);
    }

    return true;
}


/**
 * Calculates and updates tax rates with take into account tax settings
 *
 * @param array $tax_data      Information about taxes from order_data
 * @param array $shipping_cost List of shipping chosen in the order
 * @param array $order         Information about subtotal and total of order
 *
 * @return bool Always true
 */
function fn_update_shipping_taxes(&$tax_data, $shipping_cost, &$order)
{
    if (is_array($tax_data) && is_array($shipping_cost)) {
        foreach ($shipping_cost as $shipping_id => $shipping_data) {
            foreach ($shipping_data['rates'] as $group_key => $rate) {
                foreach ($tax_data as $tax_id => &$tax) {
                    if (isset($tax['applies']['S_' . $group_key . '_' . $shipping_id])) {
                        $old_tax_rate = $tax['applies']['S_' . $group_key . '_' . $shipping_id];
                        $current_tax_rate = fn_rma_get_recalculated_shipping_tax_rate($tax, $rate['new']);

                        $tax['applies']['S_' . $group_key . '_' . $shipping_id] = $current_tax_rate;
                        $tax['tax_subtotal'] = array_sum($tax['applies']);

                        if ($tax['price_includes_tax'] === YesNo::NO) {
                            $order['subtotal'] += $current_tax_rate - $old_tax_rate;
                            $order['total'] += $current_tax_rate - $old_tax_rate;
                        }
                    } elseif (
                        isset($tax['applies']['S'])
                        && isset($tax['applies']['items']['S'][$group_key])
                        && is_array($tax['applies']['items']['S'][$group_key])
                        && in_array($shipping_id, array_keys($tax['applies']['items']['S'][$group_key]))
                    ) {
                        $old_tax_rate = fn_rma_get_recalculated_shipping_tax_rate($tax, $rate['old']);
                        $current_tax_rate = fn_rma_get_recalculated_shipping_tax_rate($tax, $rate['new']);

                        $tax['applies']['S'] += ($current_tax_rate - $old_tax_rate);
                        $tax['tax_subtotal'] = array_sum($tax['applies']);

                        if ($tax['price_includes_tax'] === YesNo::NO) {
                            $order['total'] += $current_tax_rate - $old_tax_rate;
                        }
                    }
                }
                unset($tax);
            }
        }
    }

    return true;
}

/**
 * Calculates new tax rate when shipping rates changed
 *
 * @param array $tax  Information about tax
 * @param float $rate Updated shipping rate
 *
 * @return float Updated tax rate
 */
function fn_rma_get_recalculated_shipping_tax_rate(array $tax, $rate)
{
    if ($tax['rate_type'] == 'P') { // Percent dependence
        // If tax is included into the price
        if (YesNo::toBool($tax['price_includes_tax'])) {
            $tax_rate = fn_format_price($rate - $rate / (1 + ($tax['rate_value'] / 100)));
            // If tax is NOT included into the price
        } else {
            $tax_rate = fn_format_price($rate * ($tax['rate_value'] / 100));
        }

    } else {
        $tax_rate = ($rate == 0) ? fn_format_price(0) : fn_format_price($tax['rate_value']);
    }

    return $tax_rate;
}

/**
 * Recalculates total and subtotal order data and change product amount during processing rma request
 *
 * @param array<string, int|float|string|mixed>                           $order       Order data
 * @param array<string, int|float|string|array<string, int|float|string>> $item        Cloned return item, it was changed during recalculate
 * @param array<string, int|float|string|array<string, int|float|string>> $mirror_item Original return item, it was changed during recalculate
 * @param string                                                          $type        Recalculate data type
 * @param array<string, int|string|array<int, float>>                     $ex_data     Extended return data
 * @param array<string, int|string|array<int, float>>                     $order_info  Changed order info
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 *
 * @psalm-param array{
 *     item_id: int,
 *     product_id: int,
 *     amount: int,
 *     extra?: array{
 *         exclude_from_calculate?: bool,
 *         discount?: float,
 *         product_options?: array{int, string|int}},
 *         returns?: array{return_id?: int, array{string, int|string|float}}
 * } $item Cloned return item, it was changed during recalculate
 * @psalm-param array{
 *     item_id: int,
 *     product_id: int,
 *     amount: int,
 *     extra?: array{
 *         exclude_from_calculate?: bool,
 *         discount?: float,
 *         product_options?: array{int, string|int}},
 *         returns?: array{return_id?: int, array{string, int|string|float}}
 * } $mirror_item Original return item, it was changed during recalculate
 */
function fn_rma_recalculate_order_routine(&$order, &$item, $mirror_item, $type = '', $ex_data = [], &$order_info = [])
{
    $amount = 0;

    if (in_array($type, [RecalculateDataTypes::CHANGE_RELATED_DATA, RecalculateDataTypes::CHANGE_ORDER_AND_RELATED])) {
        $amount = fn_rma_recalculate_product_amount(
            (int) $item['item_id'],
            (int) $item['product_id'],
            isset($item['extra']['product_options']) ? $item['extra']['product_options'] : [],
            $type,
            $ex_data,
            $order_info
        );
    }

    if (
        !isset($item['extra']['exclude_from_calculate'])
        && in_array($type, [RecalculateDataTypes::CHANGE_ONLY_ORDER_DATA, RecalculateDataTypes::CHANGE_ORDER_AND_RELATED])
    ) {
        $sign = ($type === RecalculateDataTypes::CHANGE_ONLY_ORDER_DATA) ? 1 : -1;

        $return_id = (!empty($ex_data['return_id'])) ? (int) $ex_data['return_id'] : 0;
        if ($return_id && isset($mirror_item['price']) && isset($mirror_item['extra']['returns'][$return_id]['amount'])) {
            $delta = ($mirror_item['price'] * $mirror_item['extra']['returns'][$return_id]['amount']);
            $subtotal_delta = $order['subtotal'] > 0 ? round($order['subtotal_discount'] * $delta / $order['subtotal'], 2) : 0;
            $order['subtotal_discount'] = $order['subtotal_discount'] - $subtotal_delta;
            $order['subtotal'] = $order['subtotal'] + $sign * $delta;
            $order['total'] = $order['total'] + $sign * $delta;
        }
        $item_discount = isset($item['extra']['discount']) ? $item['extra']['discount'] : 0;
        $_discount = (float) (isset($mirror_item['extra']['discount']) ? $mirror_item['extra']['discount'] : $item_discount);
        $order['discount'] = $order['discount'] + $sign * $_discount * (int) $item['amount'];
        unset($mirror_item['extra']['discount'], $item['extra']['discount']);
    }

    /**
     * Allows to modify related data after recalculating order according return data
     *
     * @param array<string, int|float|string|array<string, int|float|string>> $item        Cloned return item, it was changed during recalculate
     * @param array<string, int|float|string|array<string, int|float|string>> $mirror_item Original return item, it was changed during recalculate
     * @param string                                                          $type        Recalculate data type
     * @param array<string, int|string|array<int, float>>                     $ex_data     Extended return data
     * @param int                                                             $amount      Product return amount
     * @param array<string>                                                   $order       Order data
     * @param array<string, int|string|array<int, float>>                     $order_info  Changed order data
     */
    fn_set_hook('rma_recalculate_order', $item, $mirror_item, $type, $ex_data, $amount, $order, $order_info);
}

/**
 * Recalculates product amount with taking into account specific return data
 *
 * @param int                                         $item_id         Return item identifier
 * @param int                                         $product_id      Product identifier
 * @param array<int, int|string|float>                $product_options List of product option identifiers with values
 * @param string                                      $type            Recalculating type
 * @param array<string, int|string|array<int, float>> $ex_data         Extended return data
 * @param array<string, int|string|array<int, float>> $order_info      Changed order info
 *
 * @return int Returned product amount
 */
function fn_rma_recalculate_product_amount($item_id, $product_id, $product_options, $type, $ex_data, $order_info = [])
{
    $sign = ($type === RecalculateDataTypes::CHANGE_RELATED_DATA) ? '-' : '+';
    $amount = (int) db_get_field(
        'SELECT amount'
        . ' FROM ?:rma_return_products'
        . ' WHERE return_id = ?i AND item_id = ?i AND type = ?s',
        $ex_data['return_id'],
        $item_id,
        ReturnOperationStatuses::APPROVED
    );
    fn_update_product_amount($product_id, $amount, $product_options, $sign, true, $order_info);

    return $amount;
}

/**
 * Recalculates data during processing rma request
 *
 * @param int                                         $order_id         Order identifier
 * @param string                                      $recalculate_type Recalculating type
 * @param int                                         $return_id        Return request identifier
 * @param bool                                        $is_refund        True if product is refunded, false otherwise
 * @param array<string, int|string|array<int, float>> $ex_data          Extended return data
 *
 * @return bool True if recalculating is successful, false otherwise
 */
function fn_rma_recalculate_order($order_id, $recalculate_type, $return_id, $is_refund,  $ex_data)
{
    if (empty($recalculate_type) || empty($return_id) || empty($order_id) || !is_array($ex_data) || ($recalculate_type == RecalculateOperations::MANUALLY && !isset($ex_data['total']))) {
        return false;
    }

    $original_order_data = $order = db_get_row(
        'SELECT total, subtotal, discount, subtotal_discount, shipping_cost, status FROM ?:orders WHERE order_id = ?i',
        $order_id
    );
    $order_items = db_get_hash_array("SELECT * FROM ?:order_details WHERE ?:order_details.order_id = ?i", 'item_id', $order_id);
    $additional_data = db_get_hash_single_array("SELECT type, data FROM ?:order_data WHERE order_id = ?i", array('type', 'data'), $order_id);
    $order_return_info = @unserialize(@$additional_data[ORDER_DATA_RETURN]);
    $order_tax_info = @unserialize(@$additional_data['T']);
    $status_order = $order['status'];
    $order_info = fn_get_order_info($order_id) ?: [];
    unset($order['status']);
    if ($recalculate_type == RecalculateOperations::AUTO) {
        if (YesNo::toBool($is_refund)) {
            $sign = ($ex_data['inventory_to'] == InventoryOperations::INCREASED) ? -1 : 1;
            // What for is this section ???
            if (!empty($order_return_info['returned_products']) && $ex_data['inventory_to'] === InventoryOperations::DECREASED) {
                foreach ($order_return_info['returned_products'] as $item_id => $item) {
                    if (isset($item['extra']['returns'][$return_id])) {
                        $r_item = $o_item = $item;
                        unset($r_item['extra']['returns'][$return_id]);
                        $r_item['amount'] = $item['amount'] - $item['extra']['returns'][$return_id]['amount'];
                        fn_rma_recalculate_order_routine($order, $r_item, $item, RecalculateDataTypes::CHANGE_RELATED_DATA, $ex_data, $order_info);
                        if (empty($r_item['amount'])) {
                            unset($order_return_info['returned_products'][$item_id]);
                        } else {
                            $order_return_info['returned_products'][$item_id] = $r_item;
                        }

                        $o_item['primordial_amount'] = (isset($order_items[$item_id]) ? $order_items[$item_id]['amount'] : 0) + $item['extra']['returns'][$return_id]['amount'];
                        $o_item['primordial_discount'] = @$o_item['extra']['discount'];
                        fn_rma_recalculate_order_routine($order, $o_item, $item, RecalculateDataTypes::CHANGE_ONLY_ORDER_DATA, $ex_data, $order_info);
                        $o_item['amount'] = (isset($order_items[$item_id]) ? $order_items[$item_id]['amount'] : 0) + $item['extra']['returns'][$return_id]['amount'];

                        if (isset($order_items[$item_id]['extra'])) {
                            $o_item['extra'] = @unserialize($order_items[$item_id]['extra']);
                        }
                        $o_item['extra']['returns'][$return_id] = $item['extra']['returns'][$return_id];

                        $o_item['extra'] = serialize($o_item['extra']);
                        if (!isset($order_items[$item_id])) {
                            db_query("REPLACE INTO ?:order_details ?e", $o_item);
                        } else {
                            db_query("UPDATE ?:order_details SET ?u WHERE item_id = ?i AND order_id = ?i", $o_item, $item_id, $order_id);
                        }

                    }
                }
            }

            // Check all the products and update their amount and cost.
            foreach ($order_items as $item_id => $item) {
                $item['extra'] = @unserialize($item['extra']);

                if (isset($item['extra']['returns'][$return_id])) {
                    $o_item = $item;
                    $o_item['amount'] = $o_item['amount'] + $sign * $item['extra']['returns'][$return_id]['amount'];
                    unset($o_item['extra']['returns'][$return_id]);
                    if (empty($o_item['extra']['returns'])) {
                        unset($o_item['extra']['returns']);
                    }

                    fn_rma_recalculate_order_routine($order, $o_item, $item, '', $ex_data, $order_info);
                    if (empty($o_item['amount'])) {
                        db_query("DELETE FROM ?:order_details WHERE item_id = ?i AND order_id = ?i", $item_id, $order_id);
                    } else {
                        $o_item['extra'] = serialize(isset($o_item['extra']) ? $o_item['extra'] : []);
                        db_query("UPDATE ?:order_details SET ?u WHERE item_id = ?i AND order_id = ?i", $o_item, $item_id, $order_id);
                    }

                    if (!isset($order_return_info['returned_products'][$item_id])) {
                        $r_item = $item;
                        unset($r_item['extra']['returns']);
                        $r_item['amount'] = $item['extra']['returns'][$return_id]['amount'];
                    } else {
                        $r_item = $order_return_info['returned_products'][$item_id];
                        $r_item['amount'] = $r_item['amount'] + $item['extra']['returns'][$return_id]['amount'];
                    }
                    fn_rma_recalculate_order_routine($order, $r_item, $item, RecalculateDataTypes::CHANGE_ORDER_AND_RELATED, $ex_data, $order_info);
                    $r_item['extra']['returns'][$return_id] = $item['extra']['returns'][$return_id];
                    $order_return_info['returned_products'][$item_id] = $r_item;
                    fn_rma_update_order_taxes(
                        $order_tax_info,
                        $item_id,
                        $item['amount'],
                        $o_item['amount'],
                        $order,
                        $item['price'],
                        $original_order_data
                    );
                }
            }

            $_ori_data = array(
                'order_id' => $order_id,
                'type' 	   => ORDER_DATA_RETURN,
                'data'     => $order_return_info
            );
        }

        $shipping_info = array();
        if (isset($order_info['product_groups'])) {
            $product_groups = $order_info['product_groups'];
        } elseif (isset($additional_data[OrderDataTypes::GROUPS])) {
            $product_groups = unserialize($additional_data[OrderDataTypes::GROUPS]);
        } else {
            $product_groups = false;
        }
        if ($product_groups) {

            $_total = 0;
            /** @psalm-suppress PossiblyInvalidIterator */
            foreach ($product_groups as $key_group => $group) {
                if (isset($group['chosen_shippings'])) {
                    foreach ($group['chosen_shippings'] as $key_shipping => $shipping) {
                        $_total += $shipping['rate'];
                    }
                }
            }
            /** @psalm-suppress PossiblyInvalidIterator */
            foreach ($product_groups as $key_group => &$group) {
                if (isset($group['chosen_shippings'])) {
                    $shipping_cost = (array) $ex_data['shipping_costs'];
                    foreach ($group['chosen_shippings'] as &$shipping) {
                        $shipping_id = $shipping['shipping_id'];
                        $cost = (float) $shipping_cost[$shipping_id];
                        $old_shipping_rate = $shipping['rate'];
                        /** @psalm-suppress PossiblyInvalidArgument */
                        $new_shipping_rate = fn_format_price($_total ? (($old_shipping_rate / $_total) * $cost) : ($cost / count($product_groups)));

                        $shipping['rate'] = $new_shipping_rate;
                        $group['shippings'][$shipping_id]['rate'] = $new_shipping_rate;
                        if (empty($shipping_info[$shipping_id])) {
                            $shipping_info[$shipping_id] = $group['shippings'][$shipping_id];
                        }
                        $shipping_info[$shipping_id]['rates'][$key_group]['old'] = $old_shipping_rate;
                        $shipping_info[$shipping_id]['rates'][$key_group]['new'] = $new_shipping_rate;
                    }
                    unset($shipping);
                }
            }
            unset($group);

            db_query("UPDATE ?:order_data SET ?u WHERE order_id = ?i AND type = 'G'", array('data' => serialize($product_groups)), $order_id);

            fn_update_shipping_taxes($order_tax_info, $shipping_info, $order);
        }

        $order['total'] -= $order['shipping_cost'];
        $order['shipping_cost'] = (isset($ex_data['shipping_costs']) && is_array($ex_data['shipping_costs']))
            ? array_sum($ex_data['shipping_costs'])
            : $order['shipping_cost'];
        if ($order['total'] < 0) {
            $order['total'] = 0;
            foreach ($order_tax_info as $tax_info) {
                if (!isset($tax_info['applies'])) {
                    continue;
                }
                if (YesNo::toBool($tax_info['price_includes_tax'])) {
                    continue;
                }
                foreach ($tax_info['applies'] as $hash => $amount) {
                    list($code) = explode('_', $hash);
                    $is_shipping_tax = $code === 'S';
                    if ($is_shipping_tax) {
                        $order['total'] += $amount;
                        break;
                    }
                }
            }
        }
        $order['total'] += $order['shipping_cost'];
        unset($order['subtotal_discount']);

        if (!empty($order_tax_info)) {
            db_query("UPDATE ?:order_data SET ?u WHERE order_id = ?i AND type = 'T'", array('data' => serialize($order_tax_info)), $order_id);
        }

    } elseif ($recalculate_type === RecalculateOperations::MANUALLY) {
        $_total = $order['total'];
        $_ex_total = isset($ex_data['total']) ? $ex_data['total'] : 0;
        $_ori_data = [
            'order_id' => $order_id,
            'type'     => ORDER_DATA_RETURN,
            'data'     => [
                'return'            => fn_format_price((float) $_total - (float) $_ex_total),
                'returned_products' => (isset($order_return_info['returned_products'])) ? $order_return_info['returned_products'] : ''
            ]
        ];
        $order['total'] = $_ex_total;

        $return_products = db_get_hash_array(
            'SELECT * FROM ?:rma_return_products WHERE return_id = ?i AND type = ?s',
            'item_id',
            $return_id,
            ReturnOperationStatuses::APPROVED
        );

        foreach ((array) $return_products as $v) {
            $v['extra']['product_options'] = @unserialize($v['extra']['product_options']);
            if (
                $ex_data['inventory_to'] === InventoryOperations::DECREASED
                && $ex_data['inventory_from'] === InventoryOperations::INCREASED
                && $ex_data['status_from'] !== ReturnOperationStatuses::REQUESTED
            ) {
                fn_update_product_amount(
                    $v['product_id'],
                    $v['amount'],
                    @$v['extra']['product_options'],
                    '-',
                    true,
                    $order
                );
            } elseif (
                $ex_data['inventory_to'] === InventoryOperations::INCREASED
                && ($ex_data['status_from'] === ReturnOperationStatuses::REQUESTED || $ex_data['inventory_from'] === InventoryOperations::DECREASED)
            ) {
                fn_update_product_amount(
                    $v['product_id'],
                    $v['amount'],
                    $v['extra']['product_options'],
                    '+',
                    true,
                    $order
                );
            }
        }
    }

    if (YesNo::toBool($is_refund)) {
        if (isset($_ori_data['data']['return']) && floatval($_ori_data['data']['return']) == 0) {
            unset($_ori_data['data']['return']);
        }
        if (empty($_ori_data['data']['returned_products'])) {
            unset($_ori_data['data']['returned_products']);
        }

        if (!empty($_ori_data['data'])) {
            $_ori_data['data'] = serialize($_ori_data['data']);
            db_query("REPLACE INTO ?:order_data ?e", $_ori_data);
        } else {
            db_query("DELETE FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, ORDER_DATA_RETURN);
        }
    }

    foreach ($order as $k => $v) {
        $order[$k] = fn_format_price($v);
    }

    $order['updated_at'] = TIME;

    db_query("UPDATE ?:orders SET ?u WHERE order_id = ?i", $order, $order_id);

    if (fn_allowed_for('MULTIVENDOR')) {
        Tygh::$app['session']['cart'] = isset(Tygh::$app['session']['cart']) ? Tygh::$app['session']['cart'] : array();
        $cart = & Tygh::$app['session']['cart'];
        $auth = & Tygh::$app['session']['auth'];

        $action = 'save';
        fn_mve_place_order($order_id, $action, $status_order, $cart, $auth);
    }

    return true;
}

function fn_rma_get_status_params_definition(&$status_params, &$type)
{
    if ($type == STATUSES_ORDER) {
        $status_params['allow_return'] = array (
                'type' => 'checkbox',
                'label' => 'allow_return_registration'
        );

    } elseif ($type == STATUSES_RETURN) {
        $status_params = array (
            'inventory' => array (
                'type' => 'select',
                'label' => 'inventory',
                'variants' => array (
                    'I' => 'increase',
                    'D' => 'decrease',
                ),
                'not_default' => true
            )
        );
    }

    return true;
}

function fn_rma_delete_order(&$order_id)
{
    $return_ids = db_get_fields("SELECT return_id FROM ?:rma_returns WHERE order_id = ?i", $order_id);
    if (!empty($return_ids)) {
        foreach ($return_ids as $return_id) {
            fn_delete_return($return_id);
        }
    }
}

/**
 * Gets html packing slip.
 *
 * @param array     $return_ids List of return identifiers
 * @param array     $auth       Auth data
 * @param string    $area       Current area
 * @param string    $lang_code  Language code
 *
 * @return string Return html
 */
function fn_rma_print_packing_slips($return_ids, $auth, $area = AREA, $lang_code = CART_LANGUAGE)
{
    /** @var Smarty $view */
    $view = Tygh::$app['view'];
    $html = array();

    if (!is_array($return_ids)) {
        $return_ids = array($return_ids);
    }

    if (Registry::get('settings.Appearance.email_templates') == 'old') {
        $view->assign('reasons', fn_get_rma_properties(RMA_REASON, $lang_code));
        $view->assign('actions', fn_get_rma_properties(RMA_ACTION, $lang_code));
        $view->assign('order_status_descr', fn_get_simple_statuses(STATUSES_RETURN, false, false, $lang_code));
    }

    foreach ($return_ids as $return_id) {
        $return_info = fn_get_return_info($return_id);

        if (empty($return_info)
            || ($area == 'C'
                && ($return_info['user_id'] != $auth['user_id']
                    || !fn_is_order_allowed($return_info['order_id'], $auth)
                ))
        ) {
            continue;
        }

        if (Registry::get('settings.Appearance.email_templates') == 'old') {
            $order_info = fn_get_order_info($return_info['order_id'], false, true, false, true, $lang_code);

            if (empty($order_info)) {
                continue;
            }

            $view->assign('return_info', $return_info);
            $view->assign('order_info', $order_info);
            $view->assign('company_data', fn_get_company_placement_info($order_info['company_id'], $lang_code));

            $html[] = $view->displayMail('addons/rma/print_slip.tpl', false, $area, $order_info['company_id'], $lang_code);
        } else {
            /** @var \Tygh\Addons\Rma\Documents\PackingSlip\Type $rma_packing_slip */
            $rma_packing_slip = Tygh::$app['template.document.rma_packing_slip.type'];
            $result = $rma_packing_slip->renderByReturnId($return_id, 'default', $lang_code);

            if (!$result) {
                continue;
            }

            $view->assign('content', $result);
            $result = $view->displayMail('common/wrap_document.tpl', false, 'A');

            $html[] = $result;
        }

        if ($return_id != end($return_ids)) {
            $html[] = "<div style='page-break-before: always;'>&nbsp;</div>";
        }
    }

    return implode("\n", $html);
}

/**
 * Gets return request name
 *
 * @param int return_id Return identifier
 * @return string Return title
 */
function fn_rma_get_return_name($return_id)
{
    return $return_id;
}

function fn_rma_paypal_get_ipn_order_ids(&$data, &$order_ids)
{
    if (!isset($data['txn_type']) && fn_allowed_for('MULTIVENDOR')) {
        //in MVE we should process refund ipn only for those orders, which was requested and approved by admin
        $child_orders_ids = db_get_fields("SELECT order_id FROM ?:orders WHERE parent_order_id = ?i", $order_ids[0]);
        if (!empty($child_orders_ids)) {
            $orders_to_be_canceled = db_get_fields(
                'SELECT order_id'
                . ' FROM ?:rma_returns'
                . ' WHERE status IN ('
                . ' SELECT ?:statuses.status'
                . ' FROM ?:statuses'
                . ' INNER JOIN ?:status_data'
                . ' ON ?:status_data.status_id = ?:statuses.status_id'
                . ' WHERE type = ?s'
                . ' AND param = ?s'
                . ' AND value = ?s'
                . ' AND ?:statuses.status != ?s)'
                . ' AND order_id in (?n)',
                STATUSES_RETURN,
                'inventory',
                'I',
                ReturnOperationStatuses::REQUESTED,
                $child_orders_ids
            );

            $order_ids = !empty($orders_to_be_canceled) ? $orders_to_be_canceled : $order_ids;
        }
    }
}

/**
 * Hook handler: on reorder product.
 */
function fn_rma_reorder_product($order_info, &$cart, $auth, $product, $amount, $price, $zero_price_action, $k)
{
    unset($cart['products'][$k]['extra']['returns']);
}

/**
 * Gets return requests.
 *
 * @param array       $params Search parameters
 * @param int    $items_per_page Amount of return requests per page
 * @param string $lang_code Two-letter language code
 *
 * @return array Contains two elements: the found return requests and the search parameters with the default values populated
 */
function fn_rma_get_returns($params, $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    // Init filter
    $params = LastView::instance()->update('rma', $params);

    // Set default values to input params
    $default_params = [
        'page' => 1,
        'items_per_page' => $items_per_page
    ];

    $params = array_merge($default_params, $params);

    // Define fields that should be retrieved
    $fields = [
        'DISTINCT ?:rma_returns.return_id',
        '?:rma_returns.order_id',
        '?:rma_returns.timestamp',
        '?:rma_returns.status',
        '?:rma_returns.total_amount',
        '?:rma_property_descriptions.property AS action',
        '?:users.firstname',
        '?:users.lastname'
    ];

    // Define sort fields
    $sortings = [
        'return_id' => '?:rma_returns.return_id',
        'timestamp' => '?:rma_returns.timestamp',
        'order_id' => '?:rma_returns.order_id',
        'status' => '?:rma_returns.status',
        'amount' => '?:rma_returns.total_amount',
        'action' => '?:rma_returns.action',
        'customer' => '?:users.lastname'
    ];

    $sorting = db_sort($params, $sortings, 'timestamp', 'desc');

    $join = $condition = $group = '';

    if (isset($params['cname']) && fn_string_not_empty($params['cname'])) {
        $arr = fn_explode(' ', $params['cname']);
        foreach ($arr as $k => $v) {
            if (!fn_string_not_empty($v)) {
                unset($arr[$k]);
            }
        }
        if (sizeof($arr) == 2) {
            $condition .= db_quote(
                ' AND ((?:users.firstname LIKE ?l AND ?:users.lastname LIKE ?l)'
                . ' OR (?:users.firstname LIKE ?l AND ?:users.lastname LIKE ?l))',
                '%' . $arr[0] . '%',
                '%' . $arr[1] . '%',
                '%' . $arr[1] . '%',
                '%' . $arr[0] . '%'
            );
        } else {
            $condition .= db_quote(
                ' AND (?:users.firstname LIKE ?l OR ?:users.lastname LIKE ?l)',
                '%' . trim($params['cname']) . '%',
                '%' . trim($params['cname']) . '%'
            );
        }
    }

    if (isset($params['email']) && fn_string_not_empty($params['email'])) {
        $condition .= db_quote(' AND ?:users.email LIKE ?l', '%' . trim($params['email']) . '%');
    }

    if (isset($params['rma_amount_from']) && fn_is_numeric($params['rma_amount_from'])) {
        $condition .= db_quote(' AND ?:rma_returns.total_amount >= ?d', $params['rma_amount_from']);
    }

    if (isset($params['rma_amount_to']) && fn_is_numeric($params['rma_amount_to'])) {
        $condition .= db_quote(' AND ?:rma_returns.total_amount <= ?d', $params['rma_amount_to']);
    }

    if (!empty($params['action'])) {
        $condition .= db_quote(' AND ?:rma_returns.action = ?s', $params['action']);
    }

    if (!empty($params['return_id'])) {
        $condition .= db_quote(' AND ?:rma_returns.return_id = ?i', $params['return_id']);
    }

    if (!empty($params['request_status'])) {
        $condition .= db_quote(' AND ?:rma_returns.status IN (?a)', $params['request_status']);
    }

    if (!empty($params['period']) && $params['period'] != 'A') {
        list($params['time_from'], $params['time_to']) = fn_create_periods($params);
        $condition .= db_quote(' AND (?:rma_returns.timestamp >= ?i AND ?:rma_returns.timestamp <= ?i)', $params['time_from'], $params['time_to']);
    }

    if (!empty($params['order_id'])) {
        $condition .= db_quote(' AND ?:rma_returns.order_id = ?i', $params['order_id']);

    } elseif (!empty($params['order_ids'])) {
        $condition .= db_quote(' AND ?:rma_returns.order_id IN (?a)', $params['order_ids']);
    }

    if (!empty($params['user_id'])) {
        $condition .= db_quote(' AND ?:rma_returns.user_id = ?i', $params['user_id']);
    }

    if (!empty($params['order_status'])) {
        $condition .= db_quote(' AND ?:orders.status IN (?a)', $params['order_status']);
    }

    if (!empty($params['p_ids']) || !empty($params['product_view_id'])) {
        $arr = (strpos($params['p_ids'], ',') !== false || !is_array($params['p_ids'])) ? explode(',', $params['p_ids']) : $params['p_ids'];
        if (empty($params['product_view_id'])) {
            $condition .= db_quote(' AND ?:order_details.product_id IN (?n)', $arr);
        } else {
            $condition .= db_quote(' AND ?:order_details.product_id IN (?n)', db_get_fields(fn_get_products(array('view_id' => $params['product_view_id'], 'get_query' => true))));
        }

        $join .= ' LEFT JOIN ?:order_details ON ?:order_details.order_id = ?:orders.order_id';
        $group .=  db_quote(' GROUP BY ?:rma_returns.return_id HAVING COUNT(?:orders.order_id) >= ?i', count($arr));
    }

    if (!empty($params['company_id'])) {
        $condition .= db_quote(' AND ?:orders.company_id = ?i', $params['company_id']);
    }

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(DISTINCT ?:rma_returns.return_id) FROM ?:rma_returns LEFT JOIN ?:rma_return_products ON ?:rma_return_products.return_id = ?:rma_returns.return_id LEFT JOIN ?:rma_property_descriptions ON ?:rma_property_descriptions.property_id = ?:rma_returns.action LEFT JOIN ?:users ON ?:rma_returns.user_id = ?:users.user_id LEFT JOIN ?:orders ON ?:rma_returns.order_id = ?:orders.order_id $join WHERE 1 $condition $group");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $return_requests = db_get_array("SELECT " . implode(', ', $fields) . " FROM ?:rma_returns LEFT JOIN ?:rma_return_products ON ?:rma_return_products.return_id = ?:rma_returns.return_id LEFT JOIN ?:rma_property_descriptions ON (?:rma_property_descriptions.property_id = ?:rma_returns.action AND ?:rma_property_descriptions.lang_code = ?s) LEFT JOIN ?:users ON ?:rma_returns.user_id = ?:users.user_id LEFT JOIN ?:orders ON ?:rma_returns.order_id = ?:orders.order_id $join WHERE 1 $condition $group $sorting $limit", $lang_code);

    LastView::instance()->processResults('rma_returns', $return_requests, $params);

    return array($return_requests, $params);
}

function fn_rma_addon_install()
{
    list($root_admins,) = fn_get_users([
        'is_root' => YesNo::YES,
        'user_type' => UserTypes::ADMIN,
    ], Tygh::$app['session']['auth']);

    foreach ($root_admins as $root_admin) {
        if (!$root_admin['company_id']) {
            fn_update_notification_receiver_search_conditions(
                'group',
                'rma',
                UserTypes::ADMIN,
                [
                    new SearchCondition(ReceiverSearchMethods::USER_ID, $root_admin['user_id']),
                ]
            );
            break;
        }
    }

    if (fn_allowed_for('MULTIVENDOR')) {
        fn_update_notification_receiver_search_conditions(
            'group',
            'rma',
            UserTypes::VENDOR,
            [
                new SearchCondition(ReceiverSearchMethods::VENDOR_OWNER, ReceiverSearchMethods::VENDOR_OWNER),
            ]
        );
    }
}

function fn_rma_addon_uninstall()
{
    fn_update_notification_receiver_search_conditions(
        'group',
        'rma',
        UserTypes::ADMIN,
        []
    );

    fn_update_notification_receiver_search_conditions(
        'group',
        'rma',
        UserTypes::VENDOR,
        []
    );
}

/**
 * The "form_cart_pre_fill" hook handler.
 *
 * Actions performed:
 *  - Removes info about returns if order is copied
 *
 * @see fn_form_cart()
 */
function fn_rma_form_cart_pre_fill($order_id, $cart, $auth, &$order_info, $copy)
{
    if (!$copy || empty($order_info['products'])) {
        return;
    }

    foreach ($order_info['products'] as &$product) {
        if (empty($product['returns_info'])) {
            continue;
        }

        unset($product['returns_info']);
        if (!empty($product['extra']['returns'])) {
            unset($product['extra']['returns']);
        }
    }
    unset($product);
}

/**
 * The "is_cart_empty" hook handler.
 *
 * Actions performed:
 *  - Changes the result when editing an order with all returned products
 *
 * @param array<array-key, mixed> $cart           Data of cart
 * @param bool                    $result         Result
 * @param bool                    $check_excluded If true then products will on checked extra fields 'exclude_from_calculate' and 'parent'
 *
 * @return void
 *
 * @see fn_cart_is_empty()
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
function fn_rma_is_cart_empty(array $cart, &$result, $check_excluded)
{
    if (empty($cart['order_id']) || !defined('ORDER_MANAGEMENT')) {
        return;
    }

    $order_info = fn_get_order_info($cart['order_id']);

    if (empty($order_info['products']) && !empty($order_info['returned_products'])) {
        $result = false;
        return;
    }
}

/**
 * The "update_product_pre" hook handler.
 *
 * Actions performed:
 *  - Changes the result when creating a new product
 *
 * @param array $product_data Product data
 * @param int   $product_id   Product identifier
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 *
 * @return void
 *
 * @param-out array $product_data
 */
function fn_rma_update_product_pre(array &$product_data, $product_id)
{
    if (
        (
            !empty($product_data['is_returnable'])
            && YesNo::isTrue($product_data['is_returnable'])
        )
        || isset($product_data['return_period'])
        || !empty($product_id)
    ) {
        return;
    }

    $product_data['return_period'] = Registry::get('settings.rma.general.return_period');
}
