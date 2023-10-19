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

use Tygh\Addons\PaypalCommercePlatform\Payments\PaypalCommercePlatform;
use Tygh\Addons\PaypalCommercePlatform\ServiceProvider;
use Tygh\Enum\NotificationSeverity;
use Tygh\Registry;
use Tygh\Tygh;

/** @var string $mode */

$runtime_company_id = (int) Registry::get('runtime.company_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'paypal_commerce_platform_disconnect') {
        if (!$runtime_company_id) {
            return [CONTROLLER_STATUS_DENIED];
        }

        $company_data = fn_get_company_data($runtime_company_id);

        if (!empty($company_data['paypal_commerce_platform_account_id'])) {
            fn_update_company(
                [
                    'paypal_commerce_platform_account_id' => '',
                    'email'                               => $company_data['email'],
                ],
                $runtime_company_id
            );

            fn_set_notification(
                NotificationSeverity::NOTICE,
                __('notice'),
                __('paypal_commerce_platform.account_disconnected')
            );
        }

        return [CONTROLLER_STATUS_OK, 'companies.update&company_id=' . $runtime_company_id];
    }
}

if ($mode === 'update') {
    /** @var array $company_data */
    $company_data = Tygh::$app['view']->getTemplateVars('company_data');

    if (
        !empty($company_data['company_id'])
        && $runtime_company_id === (int) $company_data['company_id']
    ) {
        if (empty($company_data['paypal_commerce_platform_account_id'])) {
            $authorize_result = ServiceProvider::getOauthHelper()->getAuthorizeUrl();
            if ($authorize_result->isSuccess()) {
                Tygh::$app['view']->assign(
                    'paypal_commerce_platform_connect_url',
                    $authorize_result->getData()
                );
            }
        } else {
            Tygh::$app['view']->assign(
                'paypal_commerce_platform_disconnect_url',
                fn_url('companies.paypal_commerce_platform_disconnect')
            );
        }
    }

    Registry::set(
        'navigation.tabs.paypal_commerce_platform',
        [
            'title' => __('paypal_commerce_platform.paypal'),
            'js'    => true,
        ]
    );
} elseif ($mode === 'paypal_commerce_platform_auth') {
    if (!$runtime_company_id) {
        return [CONTROLLER_STATUS_DENIED];
    }

    $_REQUEST = array_merge(
        [
            'merchantIdInPayPal' => null,
            'merchantId'         => null,
        ],
        $_REQUEST
    );

    $merchant_account_id = $_REQUEST['merchantIdInPayPal'];
    $owner_account_id = PaypalCommercePlatform::getOwnerAccountId();

    if (
        $merchant_account_id
        && $runtime_company_id === (int) $_REQUEST['merchantId']
        && $merchant_account_id !== $owner_account_id
    ) {
        $merchant = ServiceProvider::getOauthHelper()->getAccountInfo($merchant_account_id);

        if ($merchant->isSuccess()) {
            $company_data = fn_get_company_data($runtime_company_id);

            fn_update_company(
                [
                    'paypal_commerce_platform_account_id' => $merchant_account_id,
                    'email'                               => $company_data['email'],
                ],
                $runtime_company_id
            );

            fn_set_notification(
                NotificationSeverity::NOTICE,
                __('notice'),
                __('paypal_commerce_platform.account_connected')
            );
        } else {
            $merchant->showNotifications();
        }
    } elseif ($merchant_account_id === $owner_account_id) {
        fn_set_notification(
            NotificationSeverity::ERROR,
            __('error'),
            __('paypal_commerce_platform.own_account_cant_be_used_for_vendor')
        );
    } else {
        fn_set_notification(
            NotificationSeverity::ERROR,
            __('error'),
            __('paypal_commerce_platform.account_connection_failure')
        );
    }

    return [CONTROLLER_STATUS_OK, 'companies.update&company_id=' . $runtime_company_id];
}
