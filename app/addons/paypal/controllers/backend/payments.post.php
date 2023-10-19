<?php

use Tygh\Enum\Addons\Paypal\Processors;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * @var string $mode
 * @var string $action
 * @var array $auth
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'update' && ($action == 'paypal_signup_live' || $action == 'paypal_signup_test')) {

        $company_id = 0;
        if (isset($_REQUEST['payment_data']['company_id'])) {
            $company_id = (int) $_REQUEST['payment_data']['company_id'];
        }

        $company_id = (int) Registry::get('runtime.company_id') ?: $company_id;

        if (empty($_REQUEST['payment_id'])) {
            $payment_id = max(array_keys(fn_get_payment_by_processor($_REQUEST['payment_data']['processor_id'])));
            Tygh::$app['session'][PAYPAL_STORED_PAYMENT_ID_KEY] = $payment_id;
        } else {
            $payment_id = (int) $_REQUEST['payment_id'];
        }

        // disable payment to prevent usage until not configured
        db_query('UPDATE ?:payments SET status = ?s WHERE payment_id = ?i', 'D', $payment_id);

        $config_mode = ($action == 'paypal_signup_live') ? 'live' : 'test';

        $request_data = fn_paypal_build_signup_request($company_id, $auth['user_id'], $payment_id, $config_mode);

        fn_create_payment_form(
            fn_get_paypal_signup_server_url(),
            $request_data,
            '',
            false,
            'post',
            true,
            'form',
            __('addons.paypal.connecting_to_signup_server')
        );
    }
}

if ($mode == 'manage' && !empty($_REQUEST['paypal_signup_for'])) {
    $payment_id = $_REQUEST['paypal_signup_for'];

    $messages = fn_paypal_get_signup_messages($payment_id);

    if ($messages) {
        foreach ($messages as $msg) {
            fn_set_notification($msg['type'], '', $msg['text'], $msg['state']);
        }

        fn_paypal_remove_signup_messages($payment_id);
    }
}

if ($mode == 'processor') {

    $processor_id = null;
    if (isset($_REQUEST['processor_id'])) {
        $processor_id = $_REQUEST['processor_id'];
    } elseif (isset($_REQUEST['payment_id'])) {
        $payment = fn_get_payment_method_data($_REQUEST['payment_id']);
        if (isset($payment['processor_id'])) {
            $processor_id = $payment['processor_id'];
        }
    }

    $is_paypal_processor = false;
    if ($processor_id !== null) {
        $is_paypal_processor = fn_is_paypal_processor($processor_id);
    }

    if ($is_paypal_processor) {
        /** @var string $processor_script */
        $processor_script = db_get_field(
            'SELECT processor_script FROM ?:payment_processors'
            . ' WHERE processor_id = ?i',
            $processor_id
        );

        /** @var array $script_to_type_map */
        $script_to_type_map = Processors::getAllWithTypes();

        if(isset($script_to_type_map[$processor_script])) {
            $type = $script_to_type_map[$processor_script];
        } else {
            $type = null;
        }

        $paypal_currencies = fn_paypal_get_currencies($type);

        /** @var \Tygh\SmartyEngine\Core $view */
        $view = Tygh::$app['view'];

        $view->assign('paypal_currencies', $paypal_currencies);
    }
}
