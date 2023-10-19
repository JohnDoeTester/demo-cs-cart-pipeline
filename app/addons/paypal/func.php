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

use Tygh\Embedded;
use Tygh\Enum\ImagePairTypes;
use Tygh\Enum\OrderStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Http;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

require_once dirname(__FILE__) . "/paypal_express.functions.php";

function fn_paypal_delete_payment_processors()
{
    db_query("DELETE FROM ?:payment_descriptions WHERE payment_id IN (SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('paypal.php', 'paypal_pro.php', 'payflow_pro.php', 'paypal_express.php', 'paypal_advanced.php')))");
    db_query("DELETE FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('paypal.php', 'paypal_pro.php', 'payflow_pro.php', 'paypal_express.php', 'paypal_advanced.php'))");
    db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('paypal.php', 'paypal_pro.php', 'payflow_pro.php', 'paypal_express.php', 'paypal_advanced.php')");
}

/**
 * The "get_checkout_payment_buttons" hook handler.
 *
 * Actions performed:
 *   - Adds PayPal checkout buttons on the cart page.
 *
 * @see \fn_get_checkout_payment_buttons()
 */
function fn_paypal_get_checkout_payment_buttons($cart, $cart_products, $auth, &$checkout_buttons, $checkout_payment_ids, $payment_id, $payment, $checkout_payments)
{
    if (!empty($checkout_buttons[$payment_id])) {
        return;
    }

    if (empty($payment['processor_script'])
        || $payment['processor_script'] !== 'paypal_express.php'
    ) {
        return;
    }

    if (empty($payment['processor_params']['show_cart_button'])
        || !YesNo::toBool($payment['processor_params']['show_cart_button'])
    ) {
        return;
    }

    if (Registry::get('runtime.mode') !== 'cart') {
        return;
    }

    $in_context_checkout_payments_count = array_reduce($checkout_payments, function($in_context_checkout_payments_count, $checkout_payment) {
        $in_context_checkout_payments_count +=
            $checkout_payment['processor_script'] === 'paypal_express.php'
            && isset($checkout_payment['processor_params']['in_context'])
            && $checkout_payment['processor_params']['merchant_id']
            && YesNo::toBool($checkout_payment['processor_params']['in_context']);

        return $in_context_checkout_payments_count;
    }, 0);

    $form_url = fn_url('paypal_express.express');
    $merchant_id = $payment['processor_params']['merchant_id'];
    $is_in_context_checkout = isset($payment['processor_params']['in_context'])
        && YesNo::toBool($payment['processor_params']['in_context'])
        && $merchant_id;
    if ($is_in_context_checkout && $in_context_checkout_payments_count === 1 && !Embedded::isEnabled()) {
        $environment = $payment['processor_params']['mode'] === 'live'
            ? 'production'
            : 'sandbox';

        if ($environment === 'sandbox') {
            fn_set_cookie('PPDEBUG', true);
        }

        $checkout_buttons[$payment_id] = <<<HTML
            <form name="pp_express" id="pp_express_{$payment_id}" action="{$form_url}" method="post">
                <input name="payment_id" value="{$payment_id}" type="hidden" />
            </form>
            <script>
                (function(_, $) {
                    if (window.paypalCheckoutReady) {
                        $.redirect(_.current_url);
                    } else {
                        window.paypalCheckoutReady = function() {
                            paypal.checkout.setup("{$merchant_id}", {
                                environment: "{$environment}",
                                container: "pp_express_{$payment_id}",
                                click: function(e) {
                                    e.preventDefault();
                                    paypal.checkout.initXO();

                                    $.ceAjax("request", "{$form_url}", {
                                        method: "post",
                                        data: {
                                            in_context: 1,
                                            payment_id: "{$payment_id}"
                                        },
                                        callback: function(response) {
                                            if (response.token) {
                                                var url = paypal.checkout.urlPrefix + response.token;
                                                paypal.checkout.startFlow(url);
                                            }
                                            if (response.error) {
                                                paypal.checkout.closeFlow();
                                            }
                                        }
                                    });
                                }
                            });
                        };
                    }
                    $.getScript("//www.paypalobjects.com/api/checkout.js");
                })(Tygh, Tygh.$);
            </script>
HTML;
    } else {
        $checkout_buttons[$payment_id] = <<<HTML
            <form name="pp_express" id="pp_express" action="{$form_url}" method="post">
                <input name="payment_id" value="{$payment_id}" type="hidden" />
                <input src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/checkout-logo-small.png" type="image" />
            </form>
HTML;
    }
}

function fn_paypal_payment_url(&$method, &$script, &$url, &$payment_dir)
{
    if (strpos($script, 'paypal_express.php') !== false) {
        $payment_dir = '/app/addons/paypal/payments/';
    }
}

/**
 * Updates add-on settings.
 *
 * @param array<string, string|array> $settings      Add-on settings
 * @param int|null                    $storefront_id Storefront ID to set settings for
 *
 * @psalm-param array{
 *   pp_logo_update_all_storefronts?: string,
 *   pp_statuses?: array<string>|string,
 * } $settings
 *
 * @internal
 */
function fn_update_paypal_settings(array $settings, $storefront_id = null)
{
    if (isset($settings['pp_statuses'])) {
        $settings['pp_statuses'] = serialize($settings['pp_statuses']);
    }

    $settings_manager = Settings::instance(['storefront_id' => $storefront_id]);
    foreach ($settings as $setting_name => $setting_value) {
        $settings_manager->updateValue($setting_name, $setting_value);
    }

    if (
        isset($settings['pp_logo_update_all_storefronts'])
        && YesNo::toBool($settings['pp_logo_update_all_storefronts'])
    ) {
        list($storefronts,) = StorefrontProvider::getRepository()->find();
        foreach ($storefronts as $storefront) {
            fn_delete_image_pairs($storefront->storefront_id, 'paypal_logo');
        }
    }

    fn_attach_image_pairs('paypal_logo', 'paypal_logo', (int) $storefront_id);
}

/**
 * Gets add-on settings.
 *
 * @param int|null $storefront_id Storefront to get settings for
 *
 * @return array<string, string|array>
 *
 * @psalm-return array{
 *   main_pair: array{
 *     pair_id: int,
 *     object_id: int,
 *     detailed: array{
 *       object_id: int,
 *     },
 *   }|array<empty, empty>,
 *   pp_statuses: array<string, string>,
 *   partial_refund_action: string,
 *   override_customer_info: string,
 * }
 *
 * @internal
 */
function fn_get_paypal_settings($storefront_id = null)
{
    /**
     * @psalm-var array{
     *   main_pair: array{
     *     pair_id: int,
     *     object_id: int,
     *     detailed: array{
     *       object_id: int,
     *     },
     *   },
     *   pp_statuses: string,
     *   partial_refund_action: string,
     *   override_customer_info: string,
     * } $pp_settings
     */
    $pp_settings = Settings::instance()->getValues('paypal', 'ADDON', false);
    if (!empty($pp_settings['pp_statuses'])) {
        $pp_settings['pp_statuses'] = unserialize($pp_settings['pp_statuses']);
    } else {
        $pp_settings['pp_statuses'] = [];
    }

    if (!$storefront_id && SiteArea::isStorefront(AREA)) {
        $storefront_id = StorefrontProvider::getStorefront()->storefront_id;
    }

    $pp_settings['main_pair'] = fn_get_image_pairs((int) $storefront_id, 'paypal_logo', ImagePairTypes::MAIN, false, true);
    if (!$pp_settings['main_pair']) {
        $fallback_logo = fn_get_image_pairs(0, 'paypal_logo', 'M', false, true);
        if ($fallback_logo) {
            $fallback_logo['pair_id'] = 0;
            $fallback_logo['object_id'] = $storefront_id;
            $fallback_logo['detailed']['object_id'] = $storefront_id;
        }
        $pp_settings['main_pair'] = $fallback_logo;
    }

    /**
     * @psalm-var array{
     *   main_pair: array{
     *     pair_id: int,
     *     object_id: int,
     *     detailed: array{
     *       object_id: int,
     *     },
     *   }|array<empty, empty>,
     *   pp_statuses: array<string, string>,
     *   partial_refund_action: string,
     *   override_customer_info: string,
     * } $pp_settings
     */

    return $pp_settings;
}

function fn_paypal_update_payment_pre(&$payment_data, &$payment_id, &$lang_code, &$certificate_file, &$certificates_dir)
{
    if (!empty($payment_data['processor_id']) && fn_is_paypal_processor($payment_data['processor_id'])) {
        $p_surcharge = floatval($payment_data['p_surcharge']);
        $a_surcharge = floatval($payment_data['a_surcharge']);
        if (!empty($p_surcharge) || !empty($a_surcharge)) {
            fn_set_notification('W', __('attention'), __('addons.paypal.surcharge_policy_notice'), 'K');
        }

        if (!empty($payment_data['processor_params']['layout']) && $payment_data['processor_params']['layout'] == 'minLayout') {
            $payment_data['processor_params']['iframe_mode'] = 'Y';
        }

        // when no icon specified, use default paypal logo
        $img_key = 'payment_image';
        $src_type = !empty($_REQUEST["type_{$img_key}_image_icon"][0]) ? $_REQUEST["type_{$img_key}_image_icon"][0] : 'local';
        if (empty($payment_id)
            && (
                $src_type == 'local' && empty($_FILES["file_{$img_key}_image_icon"]['name'][0])
                || $src_type == 'server' && empty($_REQUEST["file_{$img_key}_image_icon"][0])
            )
        ) {
            $_REQUEST["file_{$img_key}_image_icon"][0] = Registry::get('config.current_location') . fn_get_theme_path('/[relative]/media/images/addons/paypal/logo.png');
            $_REQUEST["type_{$img_key}_image_icon"][0] = 'url';
        }
    }
}

function fn_paypal_rma_update_details_post(&$data, &$show_confirmation_page, &$show_confirmation, &$is_refund, &$_data, &$confirmed)
{
    $change_return_status = $data['change_return_status'];
    if (($show_confirmation == false || ($show_confirmation == true && $confirmed == 'Y')) && $is_refund == 'Y' && !empty($change_return_status['paypal_perform_refund'])) {
        $order_info = fn_get_order_info($change_return_status['order_id']);
        $amount = 0;
        $st_inv = fn_get_statuses(STATUSES_RETURN);
        if ($change_return_status['status_to'] != $change_return_status['status_from'] && $st_inv[$change_return_status['status_to']]['params']['inventory'] != 'D') {
            if (!empty($order_info['payment_method']['processor_id']) && fn_is_paypal_processor($order_info['payment_method']['processor_id']) &&
                !empty($order_info['payment_info']['transaction_id']) &&
                !empty($order_info['payment_method']['processor_params']['username']) && !empty($order_info['payment_method']['processor_params']['password']) &&
                !fn_is_paypal_refund_performed($change_return_status['return_id'])
            ) {
                $return_data = fn_get_return_info($change_return_status['return_id']);

                $request_data = array(
                    'METHOD' => 'RefundTransaction',
                    'VERSION' => '94',
                    'TRANSACTIONID' => $order_info['payment_info']['transaction_id']
                );
                if (!empty($order_info['returned_products'])) {
                    foreach ($order_info['returned_products'] as $cart_id => $product) {
                        if (isset($return_data['items']['A'][$cart_id])) {
                            $amount += $product['subtotal'];
                        }
                    }
                } elseif (!empty($order_info['products'])) {
                    foreach ($order_info['products'] as $cart_id => $product) {
                        if (isset($product['extra']['returns']) && isset($return_data['items']['A'][$cart_id])) {
                            foreach ($product['extra']['returns'] as $return_id => $product_return_data)  {
                                $amount += $return_data['items']['A'][$cart_id]['price'] * $product_return_data['amount'];
                            }
                        }
                    }
                }

                if ($amount != $order_info['subtotal'] || fn_allowed_for('MULTIVENDOR')) {
                    $request_data['REFUNDTYPE'] = 'Partial';
                    $request_data['AMT'] = $amount;
                    $request_data['CURRENCYCODE'] = isset($order_info['payment_method']['processor_params']['currency']) ? $order_info['payment_method']['processor_params']['currency'] : 'USD';
                    $request_data['NOTE'] = !empty($_REQUEST['comment']) ? $_REQUEST['comment'] : '';
                } else {
                    $request_data['REFUNDTYPE'] = 'Full';
                }
                fn_paypal_build_request($order_info['payment_method'], $request_data, $post_url, $cert_file);
                $result = fn_paypal_request($request_data, $post_url, $cert_file);
                if (fn_paypal_ack_success($result)) {
                    $extra = empty($return_data['extra'])? array() : unserialize($return_data['extra']);
                    $extra['paypal_refund_transaction_id'] = $result['REFUNDTRANSACTIONID'];
                    Tygh::$app['db']->query("UPDATE ?:rma_returns SET extra = ?s WHERE return_id = ?i", serialize($extra), $change_return_status['return_id']);

                    fn_set_notification('N', __('notice'), __('addons.paypal.rma.refund_performed'));
                } else {
                    fn_paypal_get_error($result);
                }
            }
        }
    }
}

function fn_validate_paypal_order_info($data, $order_info)
{
    if (empty($data) || empty($order_info)) {
        return false;
    }

    $errors = array();
    $currency_code = null;
    $total = isset($order_info['total']) ? $order_info['total'] : null;

    if (!empty($order_info['payment_method']['processor_params']['currency'])) {
        $currency = fn_paypal_get_valid_currency($order_info['payment_method']['processor_params']['currency']);
        $currency_code = $currency['code'];

        if ($total && $currency_code != CART_PRIMARY_CURRENCY) {
            $total = fn_format_price_by_currency($total, CART_PRIMARY_CURRENCY, $currency_code);
        }
    }

    if (!isset($data['num_cart_items']) || count($order_info['products']) != $data['num_cart_items']) {
        if (
            isset($order_info['payment_method'])
            && isset($order_info['payment_method']['processor_id'])
            && 'paypal.php' == db_get_field("SELECT processor_script FROM ?:payment_processors WHERE processor_id = ?i", $order_info['payment_method']['processor_id'])
        ) {
            list(, $count) = fn_pp_standart_prepare_products($order_info);

            if ($count != $data['num_cart_items']) {
                $errors[] = __('pp_product_count_is_incorrect');
            }
        }
    }

    if (!isset($data['mc_currency']) || $data['mc_currency'] != $currency_code) {
        //if cureency defined in paypal settings do not match currency in IPN
        $errors[] = __('pp_currency_is_incorrect');
    } elseif (!isset($data['mc_gross']) || !isset($total) || (float) $data['mc_gross'] != (float) $total) {
        //if currency is ok, check totals
        $errors[] = __('pp_total_is_incorrect');
    }

    if (!empty($errors)) {
        $pp_response['ipn_errors'] = implode('; ', $errors);
        fn_update_order_payment_info($order_info['order_id'], $pp_response);
        return false;
    }
    return true;
}

function fn_paypal_get_customer_info($data)
{
    $user_data = array();
    if (!empty($data['address_street'])) {
        $user_data['b_address'] = $user_data['s_address'] = $data['address_street'];
    }
    if (!empty($data['address_city'])) {
        $user_data['b_city'] = $user_data['s_city'] = $data['address_city'];
    }
    if (!empty($data['address_state'])) {
        $user_data['b_state'] = $user_data['s_state'] = $data['address_state'];
    }
    if (!empty($data['address_country'])) {
        $user_data['b_country'] = $user_data['s_country'] = $data['address_country'];
    }
    if (!empty($data['address_zip'])) {
        $user_data['b_zipcode'] = $user_data['s_zipcode'] = $data['address_zip'];
    }
    if (!empty($data['contact_phone'])) {
        $user_data['b_phone'] = $user_data['s_phone'] = $data['contact_phone'];
    }
    if (!empty($data['address_country_code'])) {
        $user_data['b_country'] = $user_data['s_country'] = $data['address_country_code'];
    }
    if (!empty($data['first_name'])) {
        $user_data['firstname'] = $data['first_name'];
    }
    if (!empty($data['last_name'])) {
        $user_data['lastname'] = $data['last_name'];
    }
    if (!empty($data['address_name'])) {
        //When customer set a shipping name we should use it
        $_address_name = explode(' ', $data['address_name']);
        $user_data['s_firstname'] = $_address_name[0];
        $user_data['s_lastname'] = $_address_name[1];
    }
    if (!empty($data['payer_business_name'])) {
        $user_data['company'] = $data['payer_business_name'];
    }
    if (!empty($data['payer_email'])) {
        $user_data['email'] = $data['payer_email'];
    }
    if (!empty($user_data) && isset($data['charset'])) {
        array_walk($user_data, 'fn_pp_convert_encoding', $data['charset']);
    }

    return $user_data;
}

function fn_pp_convert_encoding(&$value, $key, $enc_from = 'windows-1252')
{
    $value = fn_convert_encoding($enc_from, 'UTF-8', $value);
}

/**
 * Processes payment notification (IPN).
 *
 * @param int $order_id Order ID the IPN relates to
 * @param array $data IPN payload
 *
 * @return bool Whether IPN was successfully processed
 * @throws \Tygh\Exceptions\DeveloperException
 */
function fn_process_paypal_ipn($order_id, $data)
{
    $order_info = fn_get_order_info($order_id);

    if (!empty($data['txn_id'])
        && (empty($order_info['payment_info']['txn_id'])
            || $data['payment_status'] != PAYPAL_ORDER_STATUS_COMPLETED
            || ($data['payment_status'] == PAYPAL_ORDER_STATUS_COMPLETED
                && $order_info['payment_info']['txn_id'] !== $data['txn_id']
            )
        )
    ) {
        // validate IPN totals
        if (isset($data['txn_type']) && !fn_validate_paypal_order_info($data, $order_info)) {
            return false;
        }

        $pp_settings = fn_get_paypal_settings();

        /** @var \Tygh\Tools\Formatter $formatter */
        $formatter = Tygh::$app['formatter'];

        $pp_response = array();

        // IPN description
        $pp_response['reason_text'] = __('paypal.ipn_transaction_status', array('[status]' => $data['payment_status']));

        // IPN arrival time
        $pp_response['addons.paypal.ipn_receiving_time'] = $formatter->asDatetime(TIME);

        if (!empty($data['protection_eligibility'])) {
            $pp_response['protection_eligibility'] = $data['protection_eligibility'];
        }

        if (!empty($data['payer_email'])) {
            $pp_response['customer_email'] = $data['payer_email'];
        }

        if (!empty($data['payer_id'])) {
            $pp_response['client_id'] = $data['payer_id'];
        }

        $notes = empty($order_info['notes'])
            ? array()
            : (array) $order_info['notes'];

        // customer notes
        if (!empty($data['memo'])) {
            $notes[] = $data['memo'];
        }

        $forced_status = false;
        if ($data['payment_status'] == PAYPAL_ORDER_STATUS_COMPLETED) {
            // save unique ipn id to avoid double ipn processing
            $pp_response['txn_id'] = $data['txn_id'];
            fn_update_order_payment_info($order_id, ['transaction_id' => $data['txn_id']]);
        } elseif ($data['payment_status'] == PAYPAL_ORDER_STATUS_REFUNDED) {
            // partial refunds are reported with "Refunded" status
            $ipn_currency = fn_paypal_get_valid_currency($data['mc_currency']);
            $ipn_currency = $ipn_currency['code'];
            $refunded_amount = fn_format_price_by_currency($data['mc_gross'], $ipn_currency, CART_PRIMARY_CURRENCY);

            if ($order_info['total'] + $refunded_amount > 0) {
                $notes[] = __('addons.paypal.refund_message', array(
                    '[amount]'   => fn_format_price(abs($data['mc_gross'])),
                    '[currency]' => $data['mc_currency'],
                    '[date]'     => $formatter->asDatetime(TIME, Registry::get('settings.Appearance.date_format')),
                    '[time]'     => $formatter->asDatetime(TIME, Registry::get('settings.Appearance.time_format')),
                ));

                if ($pp_settings['partial_refund_action'] == PAYPAL_PARTIAL_REFUND_IGNORE) {
                    $forced_status = fn_pp_get_order_status($order_info);
                } else {
                    $forced_status = $pp_settings['partial_refund_action'];
                }
            }

            if (fn_allowed_for('MULTIVENDOR')) {
                // multiple refunds can be issued - create payout for each one
                $payout_data = array(
                    'order_id'        => $order_id,
                    'company_id'      => $order_info['company_id'],
                    'payout_type'     => \Tygh\Enum\VendorPayoutTypes::ORDER_REFUNDED,
                    'order_amount'    => $refunded_amount,
                    'approval_status' => \Tygh\Enum\VendorPayoutApprovalStatuses::COMPLETED,
                );

                /**
                 * Executes before creating a payout based on the return request, allows to modify the payout data.
                 *
                 * @param int   $order_id    Order ID
                 * @param array $data        IPN request parameters
                 * @param array $order_info  Order info from ::fn_get_order_info()
                 * @param array $payout_data Payout data to be stored in the DB
                 */
                fn_set_hook('process_paypal_ipn_create_payout', $order_id, $data, $order_info, $payout_data);

                \Tygh\VendorPayouts::instance()->update($payout_data);
            }
        }

        $pp_response['order_status'] = $forced_status
            ? $forced_status
            : $pp_settings['pp_statuses'][fn_strtolower($data['payment_status'])];

        fn_update_order_payment_info($order_id, $pp_response);
        fn_change_order_status($order_id, $pp_response['order_status']);

        // collect orders to update data
        $order_ids = array($order_id);
        if (fn_allowed_for('MULTIVENDOR')) {
            $order_ids = db_get_fields(
                'SELECT order_id FROM ?:orders'
                . ' WHERE parent_order_id = ?i'
                . ' OR order_id = ?i',
                $order_id,
                $order_id
            );
        }

        fn_pp_set_customer_notes($order_ids, $notes);

        // update customer information in the orders
        if ($pp_settings['override_customer_info'] == 'Y') {
            $user_data = fn_paypal_get_customer_info($data);
            foreach ($order_ids as $order_to_update_id) {
                fn_update_order_customer_info($user_data, $order_to_update_id);
            }
        }

        if (in_array($pp_response['order_status'], fn_get_order_paid_statuses())) {
            db_query('DELETE FROM ?:user_session_products WHERE order_id = ?i AND type = ?s', $order_id, 'C');
        }

        return true;
    }

    return false;
}

function fn_pp_get_ipn_order_ids($data)
{
    $order_ids = (array)(int)$data['custom'];
    fn_set_hook('paypal_get_ipn_order_ids', $data, $order_ids);

    return $order_ids;
}

function fn_paypal_prepare_checkout_payment_methods(&$cart, &$auth, &$payment_groups)
{
    if (isset($cart['payment_id'])) {
        foreach ($payment_groups as $tab => $payments) {
            foreach ($payments as $payment_id => $payment_data) {
                if (isset(Tygh::$app['session']['pp_express_details'])) {
                    if ($payment_id != $cart['payment_id']) {
                        unset($payment_groups[$tab][$payment_id]);
                    } else {
                        $_tab = $tab;
                    }
                }
            }
        }
        if (isset($_tab)) {
            $_payment_groups = $payment_groups[$_tab];
            $payment_groups = array();
            $payment_groups[$_tab] = $_payment_groups;
        }
    }
}

/**
 * Populates request data with products information for PayPal Standard request.
 *
 * @param array  $order_info      Order information from ::fn_get_order_info
 * @param string $paypal_currency Currency used for order
 * @param int    $max_pp_products Max amount of products in order
 *
 * @return array PayPal request fields populated with products data and products count
 */
function fn_pp_standart_prepare_products($order_info, $paypal_currency = '', $max_pp_products = MAX_PAYPAL_PRODUCTS)
{
    if (empty($paypal_currency)) {
        $paypal_currency = !empty($order_info['payment_method']['processor_params']['currency']) ? $order_info['payment_method']['processor_params']['currency'] : CART_PRIMARY_CURRENCY;
    }

    $currency = fn_paypal_get_valid_currency($paypal_currency);
    $post_data = array();
    $product_count = 1;
    $paypal_currency = $currency['code'];

    if ($paypal_currency != CART_PRIMARY_CURRENCY) {
        $post_data['item_name_1'] = __('total_product_cost');
        $post_data['amount_1'] = fn_format_price_by_currency($order_info['total'], CART_PRIMARY_CURRENCY, $paypal_currency);
        $post_data['quantity_1'] = '1';

        return array($post_data, 1);
    }

    $paypal_shipping = fn_order_shipping_cost($order_info);
    $paypal_total = fn_format_price($order_info['total'] - $paypal_shipping, $paypal_currency);
    $use_gift_certificates = !empty($order_info['use_gift_certificates']);

    if (!$use_gift_certificates && $order_info['status'] === OrderStatuses::PARENT) {
        $order_ids = db_get_fields('SELECT order_id FROM ?:orders WHERE parent_order_id = ?i', $order_info['order_id']);

        foreach ($order_ids as $order_id) {
            $order_info = fn_get_order_info($order_id);

            if (!empty($order_info['use_gift_certificates'])) {
                $use_gift_certificates = true;
                break;
            }
        }
    }

    if (!$use_gift_certificates && !((float) $order_info['subtotal_discount']) && empty($order_info['points_info']['in_use']) && count($order_info['products']) < $max_pp_products) {
        $i = 1;
        if (!empty($order_info['products'])) {
            foreach ($order_info['products'] as $k => $v) {
                $suffix = '_'.($i++);
                $v['product'] = strip_tags($v['product']);
                $v['price'] = fn_format_price(($v['subtotal'] - fn_external_discounts($v)) / $v['amount'], $paypal_currency);
                $post_data["item_name$suffix"] = $v['product'];
                $post_data["amount$suffix"] = $v['price'];
                $post_data["quantity$suffix"] = $v['amount'];
                if (!empty($v['product_options'])) {
                    foreach ($v['product_options'] as $_k => $_v) {
                        $_v['option_name'] = strip_tags($_v['option_name']);
                        $_v['variant_name'] = strip_tags($_v['variant_name']);
                        $post_data["on$_k$suffix"] = $_v['option_name'];
                        $post_data["os$_k$suffix"] = $_v['variant_name'];
                    }
                }
            }
        }

        $post_data['tax_cart'] = 0.0;
        if (!empty($order_info['taxes'])) {
            $post_data['shipping_1'] = (float) $order_info['shipping_cost'];
            foreach ($order_info['taxes'] as $tax_id => $tax) {
                if (YesNo::isTrue($tax['price_includes_tax'])) {
                    continue;
                }

                $post_data['tax_cart'] += (float) $tax['tax_subtotal'];
            }
        }

        if ($post_data['tax_cart']) {
            $post_data['tax_cart'] = fn_format_price($post_data['tax_cart'], $paypal_currency);
        } else {
            unset($post_data['tax_cart']);
        }

        // Gift Certificates
        if (!empty($order_info['gift_certificates'])) {
            foreach ($order_info['gift_certificates'] as $k => $v) {
                $suffix = '_' . ($i++);
                $v['amount'] = (!empty($v['extra']['exclude_from_calculate'])) ? 0 : fn_format_price($v['amount'], $paypal_currency);
                $post_data["item_name$suffix"] = $v['gift_cert_code'];
                $post_data["amount$suffix"] = $v['amount'];
                $post_data["quantity$suffix"] = '1';
            }
        }

        if (fn_allowed_for('MULTIVENDOR') && fn_take_payment_surcharge_from_vendor(array())) {
            $take_surcharge = false;
        } else {
            $take_surcharge = true;
        }

        // Payment surcharge
        if ($take_surcharge && floatval($order_info['payment_surcharge'])) {
            $suffix = '_' . ($i++);
            $name = __('surcharge');
            $payment_surcharge_amount = fn_format_price($order_info['payment_surcharge'], $paypal_currency);
            $post_data["item_name$suffix"] = $name;
            $post_data["amount$suffix"] = $payment_surcharge_amount;
            $post_data["quantity$suffix"] = '1';
        }
        $product_count = $i - 1;
    } elseif ($paypal_total <= 0) {
        $post_data['item_name_1'] = __('total_product_cost');
        $post_data['amount_1'] = fn_format_price($order_info['total'], $paypal_currency);
        $post_data['quantity_1'] = '1';
        $post_data['amount'] = fn_format_price($order_info['total'], $paypal_currency);
        $post_data['shipping_1'] = 0;
    } else {
        $post_data['item_name_1'] = __('total_product_cost');
        $post_data['amount_1'] = $paypal_total;
        $post_data['quantity_1'] = '1';
    }

    return array($post_data, $product_count);
}

function fn_pp_save_mode($order_info)
{
    $data['pp_mode'] = 'test';
    if (!empty($order_info['payment_method']) && !empty($order_info['payment_method']['processor_params']) && !empty($order_info['payment_method']['processor_params']['mode'])) {
        $data['pp_mode'] = $order_info['payment_method']['processor_params']['mode'];
    }
    fn_update_order_payment_info($order_info['order_id'], $data);

    return true;
}

function fn_pp_get_mode($order_id)
{
    $result = 'test';
    $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $order_id);
    if (!empty($payment_info)) {
        $payment_info = unserialize(fn_decrypt_text($payment_info));
        if (!empty($payment_info['pp_mode'])) {
            $result = $payment_info['pp_mode'];
        }
    }

    return $result;
}

/**
 * Return available currencies
 * @param string $type Type of paypal (standard|express|payflow|pro|advanced|null)
 * @return array
 */
function fn_paypal_get_currencies($type = null)
{
    $paypal_currencies = fn_get_schema('paypal', 'currencies');

    $currencies = fn_get_currencies();
    $result = array();

    foreach ($paypal_currencies as $key => &$item) {
        $item['active'] = isset($currencies[$key]);

        $item['name'] = __($item['name']);

        if ($type === null || in_array($type, $item['supports'], true)) {
            $result[$key] = $item;
        }
    }

    unset($item);

    return $result;
}

/**
 * Return currency data
 * @param string|int $id
 * @return array|false if no defined return false
 */
function fn_paypal_get_currency($id)
{
    $currencies = fn_paypal_get_currencies();

    if (is_numeric($id)) {
        foreach ($currencies as $currency) {
            if ($currency['id'] == $id) {
                return $currency;
            }
        }
    } elseif (isset($currencies[$id])) {
        return $currencies[$id];
    }

    return false;
}

/**
 * Return valid currency data
 * @param string|int $id
 * @return array
 * ```
 * array(
 *  name => string,
 *  id => int,
 *  active => bool,
 *  code => string
 * )
 * ```
 */
function fn_paypal_get_valid_currency($id)
{
    $currency = fn_paypal_get_currency($id);

    if ($currency === false || !$currency['active']) {
        $currency = fn_paypal_get_currency(CART_PRIMARY_CURRENCY);

        if ($currency === false) {
            $currency = fn_paypal_get_currency('USD');
        }
    }

    return $currency;
}

/**
 * Overrides user existence check results for guest customers who returned from Express Checkout
 *
 * @param int $user_id User ID
 * @param array $user_data User authentication data
 * @param boolean $is_exist True if user with specified email already exists
 */
function fn_paypal_is_user_exists_post($user_id, $user_data, &$is_exist)
{
    if (!$user_id && $is_exist) {
        if (isset(Tygh::$app['session']['pp_express_details']['token']) &&
            (empty($user_data['register_at_checkout']) || $user_data['register_at_checkout'] != 'Y') &&
            empty($user_data['password1']) && empty($user_data['password2'])) {
            $is_exist = false;
        }
    }
}

/**
 * Provide token and handle errors for checkout with In-Context checkout
 *
 * @param array $cart   Cart data
 * @param array $auth   Authentication data
 * @param array $params Request parameters
 */
function fn_paypal_checkout_place_orders_pre_route(&$cart, $auth, $params)
{
    $cart = empty($cart) ? array() : $cart;
    $payment_id = (empty($params['payment_id']) ? $cart['payment_id'] : $params['payment_id']);
    $processor_data = fn_get_processor_data($payment_id);

    if (!empty($processor_data['processor_script']) && $processor_data['processor_script'] == 'paypal_express.php' &&
        isset($params['in_context_order']) && $processor_data['processor_params']['in_context'] == 'Y'
    ) {
        // parent order has the smallest identifier of all the processed orders
        $order_id = min($cart['processed_order_id']);
        $result = fn_paypal_set_express_checkout($payment_id, $order_id, array(), $cart, AREA);

        if (fn_paypal_ack_success($result) && !empty($result['TOKEN'])) {
            // set token for in-context checkout
            Tygh::$app['ajax']->assign('token', $result['TOKEN']);

        } else {
            // create notification
            fn_paypal_get_error($result);
            Tygh::$app['ajax']->assign('error', true);
        }
        exit;
    }
}

/**
 * Checks if payment processor is the one provided by the add-on.
 *
 * @param int $processor_id
 *
 * @return bool True if processor is PayPal-based
 */
function fn_is_paypal_processor($processor_id = 0)
{
    return (bool) db_get_field("SELECT 1 FROM ?:payment_processors WHERE processor_id = ?i AND addon = ?s", $processor_id, 'paypal');
}

/**
 * Checks if return was already refunded via PayPal
 *
 * @param int $return_id Return identifier
 * @return bool True if refunded
 */
function fn_is_paypal_refund_performed($return_id)
{
    $return_data = fn_get_return_info($return_id);
    $return_data['extra'] = empty($return_data['extra'])? array() : unserialize($return_data['extra']);

    return !empty($return_data['extra']['paypal_refund_transaction_id']);
}

/**
 * Formats phone number for PayPal Standard request.
 *
 * @param string $number Phone number
 * @param string $country Country code accordingly to ISO 3166-1
 * @param array $rules Phone number validation rules
 *
 * @return array phone_a, phone_b and phone_c fields for PayPal Standard request
 */
function fn_pp_format_phone_number($number, $country, $rules = array())
{
    $number = preg_replace('/[^\d\+]/', '', $number);
    $is_international = strpos($number, '+') === 0;
    $local_number = str_replace('+', '', $number);

    $country_detected = false;
    $country_code = $phone_number = $extra = '';

    if (empty($rules)) {
        $rules = fn_get_schema('paypal', 'phone_validation_rules');
    }
    if (isset($rules[$country])) {
        $regex = fn_pp_get_phone_validation_rule($rules[$country]);
        foreach($rules[$country] as $int_code) {
            $number_to_validate = $is_international ? $number : "+{$int_code}{$local_number}";
            if (preg_match($regex, $number_to_validate)) {
                $country_code = $int_code;
                $phone_number = ltrim(substr($number_to_validate, strlen($int_code) + 1), '0');
                $country_detected = true;
                break;
            }
        }

    }
    if (!$country_detected) {
        $country_code = (string) substr($local_number, 0, 3);
        $phone_number = (string) substr($local_number, 3);
    } elseif ($country == 'US') {
        $country_code = (string) substr($phone_number, 0, 3);
        $extra = (string) substr($phone_number, 6, 4);
        $phone_number = (string) substr($phone_number, 3, 3);
    } elseif (strlen($country_code) > 3) {
        // country code sent to paypal max length is 3 digits
        $phone_number = substr($country_code, 3) . $phone_number;
        $country_code = substr($country_code, 0, 3);
    }

    return array($country_code, $phone_number, $extra);
}

/**
 * Provides regex to validate phone number for PayPal Standard.
 *
 * @param array $schema Validation schema for the selected country
 *
 * @return string Regex to validate phone number
 */
function fn_pp_get_phone_validation_rule($schema)
{
    return '/\+(' . implode('|', $schema) . ')\d+$/';
}

/**
 * Returns status of the order.
 * If order is the parent order, the status of the its first child is returned.
 *
 * @param array $order_info Order info obtained from ::fn_get_order_info()
 *
 * @return array Status of the order or its first child.
 */
function fn_pp_get_order_status($order_info)
{
    if ($order_info['is_parent_order'] != 'Y') {
        return $order_info['status'];
    }

    return db_get_field(
        "SELECT status"
        . " FROM ?:orders"
        . " WHERE parent_order_id = ?i"
        . " ORDER BY order_id ASC"
        . " LIMIT 1",
        $order_info['order_id']
    );
}

/**
 * Updates customer notes of the order.
 *
 * @param array|int    $order_ids      Order identifiers
 * @param array|string $customer_notes Notes text
 *
 * @return bool Always true
 */
function fn_pp_set_customer_notes($order_ids, $customer_notes)
{
    if (is_array($customer_notes)) {
        $customer_notes = implode(PHP_EOL, $customer_notes);
    }

    $order_ids = (array) $order_ids;

    db_query(
        "UPDATE ?:orders"
        . " SET notes = ?s"
        . " WHERE order_id IN (?n)",
        $customer_notes,
        $order_ids
    );

    return true;
}

/**
 * Checks the total of the specified order against the session's order total to make sure
 * that the order was placed properly.
 *
 * @param int $order_id The identifier of the order.
 *
 * @return bool True if the order total is correct and matches the session's order total; false otherwise.
 */
function fn_paypal_order_total_is_correct($order_id)
{
    $order_info = fn_get_order_info($order_id);

    if (strpos(mb_strtolower($order_info['payment_method']['processor']), 'paypal') !== false) {
        if (!empty(Tygh::$app['session']['cart']['paypal_total'])
            && Tygh::$app['session']['cart']['paypal_total'] != Tygh::$app['session']['cart']['total']
        ) {
            Tygh::$app['session']['cart']['paypal_total'] = Tygh::$app['session']['cart']['total'];

            return true;
        }

        if (!empty(Tygh::$app['session']['cart']['total'])) {
            Tygh::$app['session']['cart']['paypal_total'] = Tygh::$app['session']['cart']['total'];
        }
    }

    return false;
}

/**
 * Checks if PayPal IPN for the order is received by searching for the IPN receiving time
 * in the order's payment information.
 *
 * @param int $order_id The identifier of the order.
 *
 * @return bool True if IPN was received
 */
function fn_is_paypal_ipn_received($order_id)
{
    $order_info = fn_get_order_info($order_id);

    return !empty($order_info['payment_info']['addons.paypal.ipn_receiving_time']);
}

/**
 * Hook handler: clears the cart in the session if IPN for placed orders is already received.
 *
 * @param array $auth       Current user session data
 * @param array $user_info  User infromation obtained from ::fn_get_user_short_info
 * @param bool  $first_init True if stored in session data used to log in the user
 */
function fn_paypal_user_init(&$auth, &$user_info, &$first_init)
{
    $orders_list = array();
    if (!empty(Tygh::$app['session']['cart']['processed_order_id'])) {
        $orders_list = array_merge($orders_list, (array)Tygh::$app['session']['cart']['processed_order_id']);
    }
    if (!empty(Tygh::$app['session']['cart']['failed_order_id'])) {
        $orders_list = array_merge($orders_list, (array)Tygh::$app['session']['cart']['failed_order_id']);
    }
    foreach ($orders_list as $order_id) {
        if (fn_is_paypal_ipn_received($order_id)) {
            fn_clear_cart(Tygh::$app['session']['cart']);
            break;
        }

        if (fn_paypal_order_total_is_correct($order_id)) {
            unset(Tygh::$app['session']['cart']['processed_order_id']);
            break;
        }
    }
}

/**
 * Gets list of order identifiers whose IPN is currently processing.
 *
 * @return array Order identifiers
 */
function fn_pp_get_locked_orders()
{
    $orders_ids = fn_get_storage_data('paypal_locked_orders');

    if ($orders_ids) {
        return explode(',', $orders_ids);
    }

    return array();
}

/**
 * Marks or unmarks orders as processors of IPN.
 *
 * @param array $orders_ids        Orders' identifiers
 * @param bool  $are_locked        True IPN for the orders is currently processing, false if processing is finished
 * @param array $locked_orders_ids Currently locked orders (leave empty to fetch from the DB)
 * @return array
 */
function fn_pp_set_orders_lock($orders_ids = array(), $are_locked = true, $locked_orders_ids = array())
{
    $orders_ids = (array)$orders_ids;

    if (!$locked_orders_ids) {
        $locked_orders_ids = fn_pp_get_locked_orders();
    }

    if ($are_locked) {
        $orders_ids = array_unique(array_merge($locked_orders_ids, $orders_ids));
    } else {
        $orders_ids = array_diff($locked_orders_ids, $orders_ids);
    }

    fn_set_storage_data('paypal_locked_orders', implode(',', $orders_ids), true);

    return array_values($orders_ids);
}

/**
 * Checks if IPN for the order is currently processing.
 *
 * @param int $order_id Order identifier
 *
 * @return bool True if IPN is processing, false otherwise
 */
function fn_pp_is_order_locked($order_id = 0)
{
    $locked_order_ids = fn_pp_get_locked_orders();

    return in_array($order_id, $locked_order_ids);
}

/**
 * Checks if IPN is sent by PayPal.
 *
 * @param array $data Payload
 *
 * @return array Validation result, orders processed in the IPN and payload for ::fn_process_paypal_ipn()
 */
function fn_pp_validate_ipn_payload($data)
{
    $result = '';
    $order_ids = array();

    unset($data['dispatch']);
    $data['cmd'] = '_notify-validate';
    $data = array_merge(array('cmd' => '_notify-validate'), $data);
    // the txn_type variable absent in case of refund
    if (!isset($data['txn_type']) || in_array($data['txn_type'], array('cart', 'express_checkout', 'web_accept', 'pro_api'))) {
        $order_ids = fn_pp_get_ipn_order_ids($data);
        // lock orders while processing IPN
        fn_pp_set_orders_lock($order_ids, true);
        $mode = fn_pp_get_mode(reset($order_ids));
        $url = ($mode == 'test') ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
        $extra = [
            'headers' => fn_paypal_get_http_headers(),
        ];
        $result = Http::post($url, $data, $extra);
    }

    return array($result, $order_ids, $data);
}

/**
 * Gets http headers for requests to the PayPal service
 *
 * @return array
 */
function fn_paypal_get_http_headers()
{
    $user_agent = PRODUCT_NAME . '/' . PRODUCT_VERSION;
    $headers = [
        "User-Agent: {$user_agent}"
    ];

    return $headers;
}

/**
 * Validates configuration data sent from the intermediate server.
 *
 * @param array $request Request from the intermediate server
 *
 * @return bool True if request is valid
 */
function fn_validate_paypal_signup_request($request = array())
{
    if (isset($request['nonce'])) {
        $nonce = $request['nonce'];

        unset($request['dispatch'], $request['nonce'], $request['validation']);
        ksort($request);

        $request_hash = sha1(http_build_query($request));

        $data = array(
            'dispatch' => 'paypal_connector.validate',
            'request_hash' => $request_hash,
            'nonce' => $nonce
        );

        Registry::set('log_cut', true);
        $extra = [
            'headers' => fn_paypal_get_http_headers(),
        ];
        $result = Http::post(
            fn_get_paypal_signup_server_url(),
            $data,
            $extra
        );

        return $result == 'OK';
    }

    return false;
}

function fn_validate_paypal_config_request($request)
{
    foreach (array('merchant_id', 'user_name', 'password', 'signature', 'mode') as $required_field) {
        if (empty($request[$required_field])) {
            return false;
        }
    }

    return true;
}

/**
 * Gets Integrated Sign Up messages (success notices and/or errors).
 *
 * @param int $payment_id Payment method ID to get messages for
 *
 * @return array Messages for the specified payment method
 */
function fn_paypal_get_signup_messages($payment_id = 0)
{
    $messages = json_decode(fn_get_storage_data('paypal_signup_messages'), true) ?: array();

    if (!$payment_id) {
        return $messages;
    }

    return isset($messages[$payment_id]) ? $messages[$payment_id] : array();
}

/**
 * Adds an Integrated Sign Up message to the queue.
 *
 * @param int    $payment_id Payment method ID
 * @param string $type       Type of the message (see ::fn_set_notification)
 * @param string $text       Text of the message
 * @param string $state      State of the message (see ::fn_set_notification)
 */
function fn_paypal_add_signup_message($payment_id, $type = 'N', $text = '', $state = 'I')
{
    $messages = fn_paypal_get_signup_messages();

    if (!isset($messages[$payment_id])) {
        $messages[$payment_id] = array();
    }

    $messages[$payment_id][] = array(
        'type' => $type,
        'text' => $text,
        'state' => $state
    );

    fn_set_storage_data('paypal_signup_messages', json_encode($messages));
}

/**
 * Removes Integrated Sign Up messages for the payment method.
 *
 * @param int $payment_id Payment method ID
 */
function fn_paypal_remove_signup_messages($payment_id)
{
    $messages = fn_paypal_get_signup_messages();

    unset($messages[$payment_id]);

    fn_set_storage_data('paypal_signup_messages', json_encode($messages));
}

/**
 * Builds data for a PayPal Sign Up request.
 *
 * @param int    $company_id     Company ID to get business data
 * @param int    $user_id        User ID to get person data
 * @param int    $payment_id     Payment ID to configure
 * @param string $config_mode    Payment mode: 'test' or 'live'
 * @param string $store_url      Return URL
 * @param string $currency       Currency code to register account for
 * @param string $protocol       Storefront protocol (see ::fn_get_storefront_protocol)
 * @param array  $placement_info Merchant business data (see ::fn_get_company_placement_info)
 * @param array  $user_data      Merchant personal data (see ::fn_get_user_info)
 * @param array  $company_data   Additional company info (see ::fn_get_company_data)
 * @param array  $phones_schema  Phone validation schema (see paypal/phone_validation_rules schema)
 *
 * @return array Data for a PayPal Sign Up Request
 */
function fn_paypal_build_signup_request($company_id, $user_id, $payment_id, $config_mode,
    $store_url = '', $currency = '', $protocol = '',
    $placement_info = array(), $user_data = array(), $company_data = array(), $phones_schema = array()
)
{
    $placement_info = $placement_info ?: fn_get_company_placement_info($company_id);
    $protocol = $protocol ?: fn_get_storefront_protocol($company_id);
    $user_data = $user_data?: fn_get_user_info($user_id);

    if (fn_allowed_for('ULTIMATE')) {
        $company_data = $company_data ?: fn_get_company_data($company_id);
        $alt_company_email = $company_data['email'] ?: $user_data['email'];
        $company_suffix = "&company_id={$company_id}";
    } else {
        $alt_company_email = $user_data['email'];
        $company_suffix = '';
    }

    $validation = fn_generate_ekey($payment_id, 'I', SECONDS_IN_HOUR);
    $validation_suffix = '&validation='. $validation;

    $request_data = array(
        'dispatch' => 'paypal_connector.begin_signup',
        'config_mode' => $config_mode,
        'store_url' => $store_url ?: fn_url("paypal_connector.end_signup?payment_id={$payment_id}{$company_suffix}{$validation_suffix}", 'C', $protocol),
        'preferred_language_code' => $user_data['lang_code'] ?: CART_LANGUAGE,
        'primary_currency_code' => $currency ?: CART_PRIMARY_CURRENCY,
        'given_name' => $user_data['firstname'],
        'surname' => $user_data['lastname'],
        'personal_email_address' => $user_data['email'],
        'customer_service_email_address' => $placement_info['company_support_department'] ?: $alt_company_email,
        'legal_name' => $placement_info['company_name'],
        'home_address[line1]' => $user_data['b_address'] ?: $user_data['s_address'],
        'home_address[line2]' => $user_data['b_address_2'] ?: $user_data['s_address_2'],
        'home_address[city]' => $user_data['b_city'] ?: $user_data['s_city'],
        'home_address[state]' => $user_data['b_state'] ?: $user_data['s_state'],
        'home_address[country_code]' => $user_data['b_country'] ?: $user_data['s_country'],
        'home_address[postal_code]' => $user_data['b_zipcode'] ?: $user_data['s_zipcode'],
        'home_phone[country_code]' => '',
        'home_phone[national_number]' => '',
        'business_address[line1]' => $placement_info['company_address'],
        'business_address[line2]' => '',
        'business_address[city]' => $placement_info['company_city'],
        'business_address[state]' => $placement_info['company_state'],
        'business_address[country_code]' => $placement_info['company_country'],
        'business_address[postal_code]' => $placement_info['company_zipcode'],
        'business_phone[country_code]' => '',
        'business_phone[national_number]' => '',
        'website_urls[]' => $placement_info['company_website'] ?: fn_get_storefront_url($protocol, $company_id),
    );

    $phones = array(
        'home' => $user_data['phone'],
        'business' => $placement_info['company_phone']
    );

    $phones_schema = $phones_schema ?: fn_get_schema('paypal', 'phone_validation_rules');
    foreach ($phones as $type => $phone) {
        $country = $request_data["{$type}_address[country_code]"];
        if (isset($phones_schema[$country])) {
            $phone = fn_pp_format_phone_number($phone, $country, $phones_schema);
            $request_data["{$type}_phone[country_code]"] = $phones_schema[$country][0];
            $request_data["{$type}_phone[national_number]"] = implode('', $phone);
        }
    }

    return $request_data;
}

/**
 * Provides PayPal Sign Up server URL
 *
 * @return string URL
 */
function fn_get_paypal_signup_server_url()
{
    return Registry::get('config.resources.updates_server') . '/index.php';
}
