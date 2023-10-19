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

use Stripe\StripeClient;
use Tygh\Addons\StripeConnect\Payments\StripeConnect;
use Tygh\Addons\StripeConnect\ServiceProvider;
use Tygh\Enum\Addons\StripeConnect\AccountTypes;
use Tygh\Enum\NotificationSeverity;
use Tygh\Registry;
use Tygh\Enum\YesNo;

/** @var string $mode */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'stripe_connect_disconnect') {

        $company_id = Registry::get('runtime.company_id');

        $company_data = fn_get_company_data($company_id);

        if (!empty($company_data['stripe_connect_account_id'])) {
            $oauth_helper = ServiceProvider::getOAuthHelper();
            $account_helper = ServiceProvider::getAccountHelper();

            $disconnect_result = $oauth_helper->disconnect($company_data['stripe_connect_account_id']);
            if ($disconnect_result->isSuccess()) {
                fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('stripe_connect.account_disconnected'));
            } else {
                $disconnect_result->showNotifications();
            }

            $account_helper->disconnectAccount($company_id);
        }

        return [CONTROLLER_STATUS_OK, 'companies.update&company_id=' . $company_id];
    }
}

if ($mode == 'update') {

    $company_data = Tygh::$app['view']->getTemplateVars('company_data');
    $processor_params = StripeConnect::getProcessorParameters();

    if (!empty($company_data['company_id'])) {
        if (empty($company_data['stripe_connect_account_id'])) {
            $oauth_helper = ServiceProvider::getOAuthHelper();
            $account_helper = ServiceProvider::getAccountHelper();

            if (YesNo::toBool($processor_params['allow_express_accounts'])) {
                $account_id = $account_helper->getStorageAccountId($company_data['company_id']);
                if ($account_id) {
                    Tygh::$app['view']->assign(
                        'stripe_express_continue_registration_url',
                        fn_url('companies.continue_express_registration')
                    );
                } else {
                    Tygh::$app['view']->assign(
                        'stripe_express_connect_url',
                        fn_url('companies.stripe_account_registration?type=express')
                    );
                }
            } else {
                Tygh::$app['view']->assign(
                    'stripe_standard_create_url',
                    fn_url('companies.stripe_account_registration?type=standard')
                );
            }

            $authorize_standard_result = $oauth_helper->getAuthorizeUrl(AccountTypes::STANDARD);
            if ($authorize_standard_result->isSuccess()) {
                Tygh::$app['view']->assign(
                    'stripe_standard_connect_url',
                    $authorize_standard_result->getData()
                );
            }
        } else {
            Tygh::$app['view']->assign(
                'stripe_disconnect_url',
                fn_url('companies.stripe_connect_disconnect')
            );
        }
    }
} elseif ($mode == 'stripe_connect_auth') {
    $company_id = Registry::get('runtime.company_id');

    if (!empty($_REQUEST['code'])) {

        $oauth_helper = ServiceProvider::getOAuthHelper();
        $account_helper = ServiceProvider::getAccountHelper();

        $token_result = $oauth_helper->getToken($_REQUEST['code']);
        if ($token_result->isSuccess()) {
            $account_id   = $token_result->getData('account_id');
            $account_type = $token_result->getData('account_type');
            $is_express   = AccountTypes::isExpress(AccountTypes::toId($account_type));

            // Clears temporary account ID if exist
            $account_helper->setStorageAccountId($company_id, '');

            // Checks whether the express account registration is complete
            if ($is_express) {
                return [CONTROLLER_STATUS_OK, 'companies.check_express_registration&account_id=' . $account_id];
            }

            /** @var array<string, string> $company_data */
            $company_data = fn_get_company_data($company_id);
            $company_data['stripe_connect_account_id'] = $account_id;
            $company_data['stripe_connect_account_type'] = AccountTypes::STANDARD;

            fn_update_company($company_data, $company_id);
            fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('stripe_connect.account_connected'));
        } else {
            $token_result->showNotifications();
        }
    }

    return [CONTROLLER_STATUS_OK, 'companies.update&company_id=' . $company_id];
} elseif ($mode === 'stripe_connect_express_dashboard') {
    $company_id = Registry::get('runtime.company_id');
    $company_data = fn_get_company_data($company_id);

    if (AccountTypes::isExpress($company_data['stripe_connect_account_type'])) {
        $account_helper = ServiceProvider::getAccountHelper();
        $redirect_url = fn_url('companies.update&company_id=' . $company_id);
        $result = $account_helper->createLoginLink(
            $company_data['stripe_connect_account_id'],
            $redirect_url
        );

        if ($result->isSuccess()) {
            return [CONTROLLER_STATUS_REDIRECT, $result->getData(), true];
        } else {
            $result->showNotifications();
            return [CONTROLLER_STATUS_OK, $redirect_url];
        }
    }
} elseif ($mode === 'check_express_registration') {
    $company_id = fn_get_runtime_company_id();
    $account_helper = ServiceProvider::getAccountHelper();
    $account_id = isset($_REQUEST['account_id'])
            ? $_REQUEST['account_id']
            : $account_helper->getStorageAccountId($company_id);

    if ($account_id) {
        $account_result = $account_helper->retrieveAccount($account_id);

        if ($account_result->isFailure()) {
            return [CONTROLLER_STATUS_OK, 'companies.update&company_id=' . $company_id];
        }

        /** @var \Stripe\Account $account */
        $account = $account_result->getData();

        if ($account->charges_enabled) {
            /** @var array<string, string> $company_data */
            $company_data = fn_get_company_data($company_id);
            $company_data['stripe_connect_account_id'] = $account_id;
            $company_data['stripe_connect_account_type'] = AccountTypes::EXPRESS;
            $account_id = ''; // Registration is completed, temporary saved account ID is not required more.

            fn_update_company($company_data, $company_id);

            fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('stripe_connect.account_connected'));
        } else {
            if ($account_helper->isAccountRejected($account)) {
                fn_set_notification(
                    NotificationSeverity::ERROR,
                    __('error'),
                    __('stripe_connect.account_was_rejected_and_unlinked', ['[account_id]' => $account_id])
                );

                $account_id = ''; // Account is rejected, clears temporary saved account ID. It allows to register again.
            } else {
                // @see https://stripe.com/docs/connect/express-accounts#handle-users-not-completed-onboarding
                fn_set_notification(
                    NotificationSeverity::ERROR,
                    __('error'),
                    __('stripe_connect.registration_is_not_complete_linked', ['[url]' => fn_url('companies.continue_express_registration')])
                );
            }
        }

        // If the onboarding is not complete, we save a temporary account ID.
        // So that we can generate a link to complete the onboarding next time.
        $account_helper->setStorageAccountId($company_id, $account_id);
    }

    return [CONTROLLER_STATUS_OK, 'companies.update&company_id=' . $company_id];
} elseif ($mode === 'continue_express_registration') {
    $company_id = fn_get_runtime_company_id();
    $account_helper = ServiceProvider::getAccountHelper();
    $account_id = $account_helper->getStorageAccountId($company_id);

    if ($account_id) {
        $redirect_url = fn_url('companies.check_express_registration');
        $result = $account_helper->createAccountLink($account_id, $redirect_url);

        if ($result->isSuccess()) {
            // External redirect to continue registration on Stripe side
            return [CONTROLLER_STATUS_REDIRECT, $result->getData(), true];
        } else {
            $result->showNotifications();
            // Clears account ID. It allows to start registration again
            $account_helper->setStorageAccountId($company_id, '');
        }
    }

    return [CONTROLLER_STATUS_OK, 'companies.update&company_id=' . $company_id];
} elseif (
    $mode === 'stripe_account_registration'
    && !empty($_REQUEST['type'])
) {
    $processor_params = StripeConnect::getProcessorParameters();
    $account_helper   = ServiceProvider::getAccountHelper();
    $stripe           = new StripeClient($processor_params['secret_key']);
    $company_id       = fn_get_runtime_company_id();

    $account_settings = ['type' => $_REQUEST['type']];
    if ($_REQUEST['type'] === 'express') {
        $account_settings['capabilities'] = [
            'card_payments' => ['requested' => true],
            'transfers'     => ['requested' => true],
        ];
    }
    $account = $stripe->accounts->create($account_settings);

    $account_link = $account_helper->createAccountLink(
        $account['id'],
        fn_url('companies.connect_onboarding_auth&account_id=' . $account['id']),
        fn_url('companies.check_express_registration&account_id=' . $account['id'])
    );
    if ($account_link->isSuccess()) {
        return [CONTROLLER_STATUS_REDIRECT, $account_link->getData(), true];
    } else {
        $account_link->showNotifications();
        // Clears account ID. It allows to start registration again
        $account_helper->setStorageAccountId($company_id, '');
    }

    return [CONTROLLER_STATUS_OK, 'companies.update&company_id=' . $company_id];
} elseif ($mode === 'connect_onboarding_auth') {
    $company_id = Registry::get('runtime.company_id');
    $account_helper = ServiceProvider::getAccountHelper();
    $account_id = isset($_REQUEST['account_id'])
        ? $_REQUEST['account_id']
        : $account_helper->getStorageAccountId($company_id);

    if (!empty($account_id)) {
        $account_result = $account_helper->retrieveAccount($account_id);
        if ($account_result->isFailure()) {
            return [CONTROLLER_STATUS_OK, 'companies.update&company_id=' . $company_id];
        }
        /** @var \Stripe\Account $account */
        $account_data = $account_result->getData();

        if (!$account_data['details_submitted']) {
            $account_helper->setStorageAccountId($company_id, $account_id);
        }

        $is_express = AccountTypes::isExpress(AccountTypes::toId($account_data['type']));
        $account_helper->setStorageAccountId($company_id, '');

        if ($is_express) {
            return [CONTROLLER_STATUS_OK, 'companies.check_express_registration&account_id=' . $account_id];
        }

        if (!$account_data['details_submitted']) {
            return [CONTROLLER_STATUS_REDIRECT, fn_url('companies.update?company_id=' . $company_id), true];
        }

        /** @var array<string, string> $company_data */
        $company_data = fn_get_company_data($company_id);
        $company_data['stripe_connect_account_id'] = $account_id;
        $company_data['stripe_connect_account_type'] = AccountTypes::STANDARD;
        fn_update_company($company_data, $company_id);
        fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('stripe_connect.account_connected'));
    }

    return [CONTROLLER_STATUS_REDIRECT, fn_url('companies.update?company_id=' . $company_id), true];
}
