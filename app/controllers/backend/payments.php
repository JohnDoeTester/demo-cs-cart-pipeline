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

use Tygh\Enum\NotificationSeverity;
use Tygh\Http;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_trusted_vars("processor_params", "payment_data");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    //
    // Update payment method
    //
    if ($mode == 'update') {
        $payment_id = fn_update_payment($_REQUEST['payment_data'], $_REQUEST['payment_id']);
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['payment_id'])) {
            $result = fn_delete_payment($_REQUEST['payment_id']);

            if ($result) {
                fn_set_notification('N', __('notice'), __('text_payment_have_been_deleted'));
            } else {
                fn_set_notification('W', __('warning'), __('text_payment_have_not_been_deleted'));
            }
        }
    }

    if ($mode == 'delete_certificate') {
        if (!empty($_REQUEST['payment_id'])) {
            $payment_data = fn_get_payment_method_data($_REQUEST['payment_id']);

            if ($payment_data['processor_params']['certificate_filename']) {
                fn_rm(Registry::get('config.dir.certificates') . $_REQUEST['payment_id']);
                $payment_data['processor_params']['certificate_filename'] = '';

                fn_update_payment($payment_data, $_REQUEST['payment_id']);
            }
        }

        return array(CONTROLLER_STATUS_REDIRECT, 'payments.processor?payment_id=' . $_REQUEST['payment_id']);
    }

    if (
        $mode === 'm_delete'
        && !empty($_REQUEST['payment_ids'])
    ) {
        foreach ((array) $_REQUEST['payment_ids'] as $payment_id) {
            fn_delete_payment($payment_id);
        }

        fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('text_payment_have_been_deleted'));
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['payment_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = $_REQUEST['status'];

        foreach ((array) $_REQUEST['payment_ids'] as $payment_id) {
            fn_tools_update_status(
                [
                    'table'             => 'payments',
                    'status'            => $status_to,
                    'id_name'           => 'payment_id',
                    'id'                => $payment_id,
                    'show_error_notice' => false,
                ]
            );
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('payments.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    return [CONTROLLER_STATUS_OK, 'payments.manage'];
}


// If any method is selected - show it's settings
if ($mode == 'processor') {
    $processor_data = fn_get_processor_data($_REQUEST['payment_id']);

    // We're selecting new processor
    if (!empty($_REQUEST['processor_id']) && (empty($processor_data['processor_id']) || $processor_data['processor_id'] != $_REQUEST['processor_id'])) {
        $processor_data = db_get_row("SELECT * FROM ?:payment_processors WHERE processor_id = ?i", $_REQUEST['processor_id']);
        $processor_data['processor_params'] = array();
        $processor_data['currencies'] = (!empty($processor_data['currencies'])) ? explode(',', $processor_data['currencies']) : array();
    }

    if (!empty($processor_data) && $processor_data['callback'] == "Y") {
        Tygh::$app['view']->assign('curl_info', Http::getCurlInfo($processor_data['processor']));
    }

    if (!empty($processor_data['processor_params']['certificate_filename'])) {
        $processor_data['processor_params']['certificate_filename'] = fn_basename($processor_data['processor_params']['certificate_filename']);
    }

    $view = Tygh::$app['view'];

    $processor_template = $processor_data['admin_template'];

    if ($view->templateExists('views/payments/components/cc_processors/' . $processor_template)) {
        $view->assign('processor_template', 'views/payments/components/cc_processors/' . $processor_template);
    } else {
        // Check if add-ons have required template
        $addons = Registry::get('addons');
        foreach ($addons as $addon_id => $addon) {
            if ($view->templateExists('addons/' . $addon_id . '/views/payments/components/cc_processors/' . $processor_template)) {
                $view->assign('processor_template', 'addons/' . $addon_id . '/views/payments/components/cc_processors/' . $processor_template);
                break;
            }
        }
    }

    $view->assign([
        'processor_params' => $processor_data['processor_params'],
        'processor_name' => $processor_data['processor'],
        'callback' => $processor_data['callback'],
        'payment_id' => $_REQUEST['payment_id'],
        'processor_data' => $processor_data
    ]);

// Show methods list
} elseif ($mode == 'manage') {
    $params = $_REQUEST;
    $payments = fn_get_payments($params);

    Tygh::$app['view']->assign(['usergroups', fn_get_payment_usergroups(),
        'payments'                 => $payments,
        'templates'                => fn_get_payment_templates(),
        'payment_processors'       => fn_get_payment_processors_by_category(),
        'is_allow_update_payments' => fn_check_permissions('payments', 'update', 'admin', Http::POST),
    ]);

    if (fn_allowed_for('MULTIVENDOR')) {
        Tygh::$app['view']->assign('selected_storefront_id', empty($_REQUEST['storefront_id']) ? 0 : (int) $_REQUEST['storefront_id']);
    }
} elseif ($mode == 'update') {
    $payment = fn_get_payment_method_data($_REQUEST['payment_id'], DESCR_SL);
    $payment['icon'] = fn_get_image_pairs($payment['payment_id'], 'payment', 'M', true, true, DESCR_SL);

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $view->assign([
        'usergroups'         => fn_get_payment_usergroups(),
        'payment'            => $payment,
        'templates'          => fn_get_payment_templates($payment),
        'payment_processors' => fn_get_payment_processors_by_category(),
        'taxes'              => fn_get_taxes(),
    ]);

    if (fn_allowed_for('ULTIMATE')) {
        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        list($is_sharing_enabled, $is_shared) = $repository->getSharingDetails(['payment_ids' => $payment['payment_id']]);

        $view->assign([
            'is_sharing_enabled' => $is_sharing_enabled,
            'is_shared'          => $is_shared,
        ]);
    }

    if (Registry::get('runtime.company_id') && Registry::get('runtime.company_id') != $payment['company_id']) {
        Tygh::$app['view']->assign('hide_for_vendor', true);
    }

}
