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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * @var string $mode
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'end_signup' && !empty($_REQUEST['payment_id'])) {
        if (fn_validate_paypal_signup_request($_REQUEST)) {
            $payment_id = $_REQUEST['payment_id'];
            $payment_data = fn_get_payment_method_data($payment_id);

            if (fn_validate_paypal_config_request($_REQUEST)
                && !empty($payment_data['processor_params'])
            ) {
                $payment_data['processor_params']['merchant_id'] = $_REQUEST['merchant_id'];
                $payment_data['processor_params']['username'] = $_REQUEST['user_name'];
                $payment_data['processor_params']['password'] = $_REQUEST['password'];
                $payment_data['processor_params']['signature'] = $_REQUEST['signature'];
                $payment_data['processor_params']['mode'] = $_REQUEST['mode'];

                $payment_data['processor_params']['authentication_method'] = 'signature';
                $payment_data['status'] = 'A';

                fn_update_payment($payment_data, $payment_id);

                fn_paypal_add_signup_message($payment_id, 'N', __('addons.paypal.signup_completed', array('[payment]' => $payment_data['payment'])));
            }

            if (!empty($_REQUEST['message_code'])) {
                $message = __("addons.paypal.signup_messages.{$_REQUEST['message_code']}", array(
                    '[product]' => PRODUCT_NAME,
                ), DEFAULT_LANGUAGE);
                fn_paypal_add_signup_message($payment_id, 'W', $message, 'K');
            } elseif (!empty($_REQUEST['message'])) {
                fn_paypal_add_signup_message($payment_id, 'W', $_REQUEST['message'], 'K');
            }

            if (!empty($_REQUEST['error_code'])) {
                $message = __("addons.paypal.signup_errors.{$_REQUEST['error_code']}", array(), DEFAULT_LANGUAGE);
                fn_paypal_add_signup_message($payment_id, 'E', $message, 'K');
            }

            echo 'OK';
            exit;
        }
    }
    echo 'ERROR';
    exit;
}

if ($mode == 'end_signup' && !empty($_REQUEST['validation']) && !empty($_REQUEST['payment_id'])) {
    $is_ekey_valid = fn_get_ekeys(array(
        'object_id' => $_REQUEST['payment_id'],
        'ekey' => $_REQUEST['validation'],
        'object_type' => 'I',
    ));

    if ($is_ekey_valid) {
        $redirection_area = 'A';
        if (fn_allowed_for('MULTIVENDOR')) {
            $payment_data = fn_get_payment_method_data($_REQUEST['payment_id']);
            if ($payment_data['company_id']) {
                $redirection_area = 'V';
            }
        }

        return array(
            CONTROLLER_STATUS_REDIRECT,
            fn_url("payments.manage?paypal_signup_for={$_REQUEST['payment_id']}", $redirection_area),
            true
        );
    }
}

return array(CONTROLLER_STATUS_NO_PAGE);

