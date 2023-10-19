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

namespace Tygh\Addons\StripeConnect;

use Exception;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Stripe;
use Tygh\Common\OperationResult;
use Tygh\Enum\Addons\StripeConnect\RejectedReasons;
use Tygh\Enum\SiteArea;
use Tygh\Addons\StripeConnect\Payments\StripeConnect;

class AccountHelper
{
    /**
     * Add-on config.
     *
     * @var array<string, string> $config
     */
    protected $config;

    /**
     * Capabilities for Express accounts
     *
     * @var string[]
     */
    protected $express_capabilities = [
        Account::CAPABILITY_CARD_PAYMENTS,
        Account::CAPABILITY_TRANSFERS
    ];

    /**
     * AccountHelper constructor.
     *
     * @param array<string, string> $config Add-on config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        Stripe::setApiKey($this->config['secret_key']);
        Stripe::setClientId($this->config['client_id']);
        Stripe::setApiVersion(StripeConnect::API_VERSION);
    }


    /**
     * Creates a link for a vendor to login to their Stripe dashboard.
     * Available only for Express accounts.
     *
     * @param string $stripe_connect_account_id Stripe account ID
     * @param string $redirect_url              Link to the store address from the Stripe dashboard
     *
     * @return OperationResult
     */
    public function createLoginLink($stripe_connect_account_id, $redirect_url)
    {
        $result = new OperationResult();

        try {
            $link = Account::createLoginLink($stripe_connect_account_id, [
                'redirect_url' => $redirect_url
            ]);
            $result->setSuccess(true);
            $result->setData($link->url);
        } catch (Exception $e) {
            $result->setSuccess(false);
            $result->addError($e->getCode(), $e->getMessage());
            Logger::logException($e);
        }

        return $result;
    }

    /**
     * Retrieves Stripe account info
     *
     * @param string $stripe_connect_account_id Stripe account ID
     *
     * @return OperationResult
     */
    public function retrieveAccount($stripe_connect_account_id)
    {
        $result = new OperationResult();

        try {
            $account = Account::retrieve($stripe_connect_account_id);
            $result->setSuccess(true);
            $result->setData($account);
        } catch (Exception $e) {
            $result->setSuccess(false);
            $result->addError($e->getCode(), $e->getMessage());
            Logger::logException($e);
        }

        return $result;
    }

    /**
     * Creates link to Stripe registration page.
     * Allows continue registration if it is not complete.
     *
     * @param string      $stripe_connect_account_id Stripe account ID
     * @param string      $return_url                Redirect url to the store after registration
     * @param string|null $refresh_url               Refresh url to the store after registration
     *
     * @return OperationResult
     */
    public function createAccountLink($stripe_connect_account_id, $return_url, $refresh_url = '')
    {
        $result = new OperationResult();

        try {
            $refresh_url = !empty($refresh_url) ? $refresh_url : $return_url;
            $account_link = AccountLink::create([
                'account'     => $stripe_connect_account_id,
                'refresh_url' => $refresh_url,
                'return_url'  => $return_url,
                'type'        => 'account_onboarding'
            ]);
            $result->setSuccess(true);
            $result->setData($account_link->url);
        } catch (Exception $e) {
            $result->setSuccess(false);
            $result->addError($e->getCode(), $e->getMessage());
            Logger::logException($e, [
                'account_id' => $stripe_connect_account_id
            ]);
        }

        return $result;
    }

    /**
     * Allows to prefill Stripe account data before registration.
     *
     * @param int $company_id Company ID
     *
     * @return array<string, string>
     */
    public function prefillAccountData($company_id)
    {
        $company_data = fn_get_company_data($company_id);
        $user_id = fn_get_company_admin_user_id($company_id);
        $user_data = fn_get_user_info($user_id);

        // @see https://stripe.com/docs/connect/oauth-reference#get-authorize
        return [
            'business_name'       => $company_data['company'],
            'email'               => $company_data['email'],
            'phone_number'        => $company_data['phone'],
            'first_name'          => $user_data['firstname'],
            'last_name'           => $user_data['lastname'],
            'product_description' => strip_tags($company_data['company_description']),
            'url'                 => fn_url('', SiteArea::STOREFRONT)
        ];
    }

    /**
     * Whether an account has been rejected.
     *
     * @param Account $account Stripe account
     *
     * @return bool
     */
    public function isAccountRejected(Account $account)
    {
        // @see https://stripe.com/docs/api/accounts/object?lang=php#account_object-requirements-disabled_reason
        return in_array(
            $account->requirements->disabled_reason,
            RejectedReasons::getAll()
        );
    }


    /**
     * Whether an account has valid capabilities.
     *
     * @param Account $account Stripe account
     *
     * @return bool
     */
    public function hasValidCapabilities(Account $account)
    {
        $required_capabilities = $this->getExpressCapabilities();

        $account_capabilities = $account->capabilities->toArray();
        $active_account_capabilities = array_filter($account_capabilities, static function ($status) {
            return $status === Account::CAPABILITY_STATUS_ACTIVE;
        });

        return empty(
            array_diff(
                $required_capabilities,
                array_keys($active_account_capabilities)
            )
        );
    }

    /**
     * Clear company Stripe account data in store.
     *
     * @param int $company_id Company ID
     *
     * @return void
     */
    public function disconnectAccount($company_id)
    {
        fn_update_company(
            [
                'stripe_connect_account_id'   => '',
                'stripe_connect_account_type' => ''
            ],
            $company_id
        );
    }

    /**
     * Gets Express accounts capabilities.
     *
     * @return string[]
     */
    public function getExpressCapabilities()
    {
        return $this->express_capabilities;
    }

    /**
     * Stores temporary Stripe account ID by company
     *
     * @param int    $company_id Company ID
     * @param string $account_id Account ID
     *
     * @return int
     */
    public function setStorageAccountId($company_id, $account_id)
    {
        return fn_set_storage_data($this->getAccountStorageKey($company_id), $account_id);
    }

    /**
     * Gets temporary Stripe account ID by company
     *
     * @param int $company_id Company ID
     *
     * @return string
     */
    public function getStorageAccountId($company_id)
    {
        return fn_get_storage_data($this->getAccountStorageKey($company_id));
    }

    /**
     * Returns the storage key for storing temporary Stripe account data
     *
     * @param int $company_id Company ID
     *
     * @return string
     */
    protected function getAccountStorageKey($company_id)
    {
        return 'stripe_connect_account_' . $company_id;
    }
}
