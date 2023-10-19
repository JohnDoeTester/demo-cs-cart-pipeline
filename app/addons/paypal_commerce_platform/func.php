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

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Addons\AXmlScheme;
use Tygh\Addons\PaypalCommercePlatform\Payments\PaypalCommercePlatform;
use Tygh\Addons\PaypalCommercePlatform\ServiceProvider;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Settings;

/**
 * Installs PayPal Commerce Platform payment processor.
 */
function fn_paypal_commerce_platform_install()
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    if (
        !$db->getField(
            'SELECT type FROM ?:payment_processors WHERE processor_script = ?s',
            PaypalCommercePlatform::getScriptName()
        )
    ) {
        $db->query(
            'INSERT INTO ?:payment_processors ?e',
            [
                'processor'          => __('paypal_commerce_platform.paypal_commerce_platform'),
                'processor_script'   => PaypalCommercePlatform::getScriptName(),
                'processor_template' => 'addons/paypal_commerce_platform/views/orders/components/payments/paypal_commerce_platform.tpl',
                'admin_template'     => 'paypal_commerce_platform.tpl',
                'callback'           => 'Y',
                'type'               => 'P',
                'addon'              => PaypalCommercePlatform::getPaymentName(),
            ]
        );
    }

    if (Registry::get('addons.rma.status') !== ObjectStatuses::ACTIVE) {
        return;
    }

    fn_paypal_commerce_platform_set_order_status_on_refund_setting_visibility(true);
}

/**
 * Disables PayPal Commerce Platform payment methods upon add-on uninstallation.
 */
function fn_paypal_commerce_platform_uninstall()
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    $processor_id = $db->getField(
        'SELECT processor_id FROM ?:payment_processors WHERE processor_script = ?s',
        PaypalCommercePlatform::getScriptName()
    );

    if (!$processor_id) {
        return;
    }

    $db->query('DELETE FROM ?:payment_processors WHERE processor_id = ?i', $processor_id);
    $db->query(
        'UPDATE ?:payments SET ?u WHERE processor_id = ?i',
        [
            'processor_id'     => 0,
            'processor_params' => '',
            'status'           => 'D',
        ],
        $processor_id
    );
}

/**
 * The "get_payments" hook handler.
 *
 * Action performed:
 * - Removes PayPal Commerce Platform method from customer area when products' vendor has no PayPal account connected.
 *
 * @param array<string, string> $params    Payment method search parameters
 * @param string                $fields    Fields SQL query part
 * @param string                $join      JOIN SQL query part
 * @param string                $order     ORDER BY SQL query part
 * @param array<string>         $condition SQL query conditions
 * @param string                $having    HAVING SQL query part
 *
 * @see \fn_get_payments()
 */
function fn_paypal_commerce_platform_get_payments(
    array &$params,
    &$fields,
    &$join,
    &$order,
    array &$condition,
    &$having
) {
    if (
        !SiteArea::isStorefront($params['area'])
        || empty(Tygh::$app['session']['cart']['product_groups'])
    ) {
        return;
    }

    foreach (Tygh::$app['session']['cart']['product_groups'] as $product_group) {
        if (!PaypalCommercePlatform::getChargeReceiver($product_group['company_id'])) {
            $condition[] = db_quote(
                '(?:payment_processors.processor_script IS NULL'
                . ' OR ?:payment_processors.processor_script <> ?s)',
                PaypalCommercePlatform::getScriptName()
            );
            break;
        }
    }
}

/**
 * The "rma_update_details_post" hook handler.
 *
 * Actions performed:
 * - Performs refund via PayPal Commerce Platform when returning order/products via RMA add-on.
 *
 * @param array<string, string> $data                   Return data
 * @param bool                  $show_confirmation_page Whether to show confirmation page
 * @param bool                  $show_confirmation      Whether to show confirmation
 * @param bool                  $is_refund              Whether return request is refund request
 * @param array<string, string> $_data                  Initial return data
 * @param string                $confirmed              Whether return request is confirmed
 *
 * @psalm-param array{
 *   change_return_status: array{
 *     paypal_commerce_platform_perform_refund: string,
 *     order_id: int,
 *     return_id: int,
 *     status_to: string,
 *     status_from: string,
 *   }
 * } $data
 *
 * @see \fn_rma_update_details()
 */
function fn_paypal_commerce_platform_rma_update_details_post(
    array &$data,
    $show_confirmation_page,
    $show_confirmation,
    $is_refund,
    array $_data,
    $confirmed
) {
    if (empty($data['change_return_status']['paypal_commerce_platform_perform_refund'])) {
        return;
    }

    $change_return_status = $data['change_return_status'];

    /**
     * @psalm-var array{
     *   order_id: int,
     *   payment_id: int,
     *   company_id: int,
     *   payment_method: array{
     *     processor_params: array{
     *       is_paypal_commerce_platform: string,
     *       access_token: string,
     *       client_id: string,
     *       expiry_time: int,
     *       mode: string,
     *       secret: string,
     *       payer_id: string,
     *       currency: string,
     *     },
     *   },
     *   payment_info: array{
     *     'paypal_commerce_platform.capture_id': string,
     *     'paypal_commerce_platform.refund_id': string,
     *   },
     *   returned_products: array{
     *     int, array{
     *       subtotal: float,
     *     }
     *   },
     * } $order_info
     */
    $order_info = fn_get_order_info($change_return_status['order_id']);
    $return_statuses = fn_get_statuses(STATUSES_RETURN);

    if (
        $change_return_status['status_to'] === $change_return_status['status_from']
        || $return_statuses[$change_return_status['status_to']]['params']['inventory'] === 'D'
        || empty($order_info['payment_method']['processor_params']['is_paypal_commerce_platform'])
        || empty($order_info['payment_info']['paypal_commerce_platform.capture_id'])
        || !empty($order_info['payment_info']['paypal_commerce_platform.refund_id'])
    ) {
        return;
    }

    $amount = 0;

    $return_data = fn_get_return_info($change_return_status['return_id']);

    if (!empty($order_info['returned_products'])) {
        foreach ($order_info['returned_products'] as $cart_id => $product) {
            if (!isset($return_data['items']['A'][$cart_id])) {
                continue;
            }
            $amount += $product['subtotal'];
        }
    } elseif (!empty($order_info['products'])) {
        foreach ($order_info['products'] as $cart_id => $product) {
            if (!isset($product['extra']['returns']) || !isset($return_data['items']['A'][$cart_id])) {
                continue;
            }
            foreach ($product['extra']['returns'] as $product_return_data) {
                $amount += $return_data['items']['A'][$cart_id]['price'] * $product_return_data['amount'];
            }
        }
    }

    if (!$amount) {
        return;
    }

    $processor_factory = ServiceProvider::getProcessorFactory();

    $processor = $processor_factory->getByPaymentId(
        $order_info['payment_id'],
        $order_info['payment_method']['processor_params']
    );

    $refund = $processor->refund($order_info, $amount);

    if (!$refund->isSuccess()) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), (string) $refund->getFirstError());

        return;
    }

    fn_update_order_payment_info(
        $order_info['order_id'],
        [
            'paypal_commerce_platform.refund_id' => $refund->getData(),
        ]
    );

    fn_set_notification(
        NotificationSeverity::NOTICE,
        __('notice'),
        __('paypal_commerce_platform.rma.refund_performed')
    );
}

/**
 * The "get_companies" hook handler.
 *
 * Actions performed:
 * - Adds PayPal Commerce Platform columns into the list of selected from ?:companies table fields.
 *
 * @param array<string, string> $params    Search params
 * @param array<string>         $fields    Fields to get from the database
 * @param array<string, string> $sortings  Available sortings
 * @param string                $condition SQL query conditions
 * @param string                $join      JOIN SQL query part
 * @param array<string, string> $auth      Current user auth data
 * @param string                $lang_code Two-letter language code
 * @param string                $group     GROUP BY SQL query part
 *
 * @see \fn_get_companies()
 */
function fn_paypal_commerce_platform_get_companies(
    array $params,
    array &$fields,
    array $sortings,
    $condition,
    $join,
    array $auth,
    $lang_code,
    $group
) {
    $fields[] = db_quote('?:companies.paypal_commerce_platform_account_id');
}

/**
 * The "vendor_data_premoderation_diff_company_data_post" hook handler.
 *
 * Actions performed:
 * - Ignores PayPal account changes when checking for company data changes.
 *
 * @param array<string, string> $company_data      New company data
 * @param array<string, string> $orig_company_data Original company data
 * @param array<string, string> $company_data_diff Diff between original and new company data
 *
 * @see \fn_vendor_data_premoderation_diff_company_data()
 */
function fn_paypal_commerce_platform_vendor_data_premoderation_diff_company_data_post(
    array $company_data,
    array $orig_company_data,
    array &$company_data_diff
) {
    unset($company_data_diff['paypal_commerce_platform_account_id']);
}

/**
 * The "update_addon_status_post" hook handler.
 *
 * Actions performed:
 * - Hides 'Order status on refund' setting for PayPal Commerce Platform add-on.
 *
 * @param string                  $addon             Add-on name
 * @param string                  $status            New addon status - "A" for enabled, "D" for disabled
 * @param bool                    $show_notification Display notification if set to true
 * @param bool                    $on_install        If status was changed right after install process
 * @param bool                    $allow_unmanaged   Whether to allow change status for unmanaged addons in non-console environment
 * @param string                  $old_status        Previous addon status - "A" for enabled, "D" for disabled
 * @param \Tygh\Addons\AXmlScheme $scheme            Add-on scheme
 */
function fn_paypal_commerce_platform_update_addon_status_post(
    $addon,
    $status,
    $show_notification,
    $on_install,
    $allow_unmanaged,
    $old_status,
    AXmlScheme $scheme
) {
    if ($addon !== 'rma') {
        return;
    }

    fn_paypal_commerce_platform_set_order_status_on_refund_setting_visibility($status === ObjectStatuses::ACTIVE);
}

/**
 * Hides or shows the "Order status on refund" add-on setting.
 *
 * @param bool $is_visible Whether setting is visible
 *
 * @internal
 */
function fn_paypal_commerce_platform_set_order_status_on_refund_setting_visibility($is_visible)
{
    $settings_manager = Settings::instance();
    /** @var int $setting_id */
    $setting_id = $settings_manager->getId('rma_refunded_order_status', 'paypal_commerce_platform');
    /** @var array<string, string> $data */
    $data = $settings_manager->getData($setting_id);

    $data['type'] = $is_visible
        ? 'S'
        : 'D';

    $settings_manager->update($data);
}

/**
 * The "save_log" hook handler.
 *
 * Actions performed:
 * - Adds PayPal Debug ID response header value to the logged HTTP request.
 *
 * @param string                $type                Log type
 * @param string                $action              Event action
 * @param string                $data                Request data
 * @param int                   $user_id             Logged in user ID
 * @param array<string, string> $content             Logged data
 * @param string                $event_type          Event type
 * @param string                $object_primary_keys Object primary key names
 */
function fn_paypal_commerce_platform_save_log($type, $action, $data, $user_id, array &$content, $event_type, $object_primary_keys)
{
    if (
        $type !== 'requests'
        || $action !== 'http'
    ) {
        return;
    }

    /** @var string $request_debug_id */
    $request_debug_id = Registry::ifGet('runtime.paypal_commerce_platform.debug_id', '');
    if (!$request_debug_id) {
        return;
    }

    $content['paypal_commerce_platform.debug_id'] = $request_debug_id;
    Registry::del('runtime.paypal_commerce_platform.debug_id');
}

/**
 * Checks if is allowed disburse payouts by order info.
 *
 * @param array<string, array<string, string|int>> $order_info Order info
 *
 * @return bool
 *
 * @psalm-param array{
 *  payment_method?: array{
 *   processor_params?: array{
 *    is_paypal_commerce_platform?: string
 *    delay_disburse_of_payouts?: string
 *   }
 *  },
 *  payment_info?: array{
 *    paypal_commerce_platform.payout_id?: string,
 *    paypal_commerce_platform.capture_id?: string,
 *    paypal_commerce_platform.refund_id?: string
 *   }
 *  }
 * } $order_info Order info
 */
function fn_paypal_commerce_platform_is_allowed_disburse_payouts_by_order_info(array $order_info)
{
    return $order_info
        && isset($order_info['payment_method']['processor_params']['is_paypal_commerce_platform'])
        && YesNo::toBool($order_info['payment_method']['processor_params']['is_paypal_commerce_platform'])
        && isset($order_info['payment_method']['processor_params']['delay_disburse_of_payouts'])
        && YesNo::toBool($order_info['payment_method']['processor_params']['delay_disburse_of_payouts'])
        && !isset($order_info['payment_info']['paypal_commerce_platform.payout_id'])
        && isset($order_info['payment_info']['paypal_commerce_platform.capture_id'])
        && !isset($order_info['payment_info']['paypal_commerce_platform.refund_id']);
}
