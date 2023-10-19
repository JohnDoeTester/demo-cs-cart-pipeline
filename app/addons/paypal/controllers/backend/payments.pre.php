<?php

use Tygh\Http;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * @var string $mode
 * @var string $action
 * @var array $auth
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'update' && ($action == 'paypal_signup_live' || $action == 'paypal_signup_test')) {
        if (empty($_REQUEST['payment_id']) && !empty(Tygh::$app['session'][PAYPAL_STORED_PAYMENT_ID_KEY])) {
            $_REQUEST['payment_id'] = Tygh::$app['session'][PAYPAL_STORED_PAYMENT_ID_KEY];
        }
    }

    return array(CONTROLLER_STATUS_OK);
}

unset(Tygh::$app['session'][PAYPAL_STORED_PAYMENT_ID_KEY]);

