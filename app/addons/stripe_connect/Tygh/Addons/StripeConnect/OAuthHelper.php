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
use Stripe\OAuth;
use Stripe\Stripe;
use Tygh\Common\OperationResult;
use Tygh\Enum\Addons\StripeConnect\AccountTypes;

class OAuthHelper
{
    /**
     * Add-on config.
     *
     * @var array $config
     */
    protected $config;

    /**
     * OAuth redirect URI.
     *
     * @var string $redirect_uri
     */
    protected $redirect_uri;

    /**
     * AuthHelper constructor.
     *
     * @param array  $config Add-on config
     * @param string $redirect_uri
     */
    public function __construct(array $config, $redirect_uri)
    {
        $this->config = $config;
        $this->redirect_uri = $redirect_uri;

        Stripe::setApiKey($this->config['secret_key']);
        Stripe::setClientId($this->config['client_id']);
    }

    /**
     * Provides URL to initiate OAuth flow to connect vendor to the store.
     *
     * @param string                $account_type Account type
     * @param array<string, string> $stripe_user  Prefilled stripe user data.
     *
     * @return OperationResult
     */
    public function getAuthorizeUrl($account_type = AccountTypes::EXPRESS, array $stripe_user = [])
    {
        $params = [
            'redirect_uri' => $this->redirect_uri,
            'scope'        => 'read_write',
        ];

        // @see https://stripe.com/docs/connect/oauth-reference#get-authorize
        if (!empty($stripe_user)) {
            $params['stripe_user'] = $stripe_user;
        }

        $options = [];
        if (AccountTypes::isExpress($account_type)) {
            // phpcs:ignore CodeSniffer.NamingConventions.SnakeCase.NotSnakeCase
            $options['connect_base'] = Stripe::$connectBase . '/express';

            $account_helper = ServiceProvider::getAccountHelper();
            $params['suggested_capabilities'] = $account_helper->getExpressCapabilities();
        }

        $result = new OperationResult();

        try {
            $url = OAuth::authorizeUrl($params, $options);
            $result->setSuccess(true);
            $result->setData($url);
        } catch (Exception $e) {
            $result->setSuccess(false);
            $result->addError($e->getCode(), $e->getMessage());
            Logger::logException($e);
        }

        return $result;
    }

    /**
     * Obtains OAuth token.
     *
     * @param string $code Auth code
     *
     * @return \Tygh\Common\OperationResult
     *
     * @throws \Stripe\Exception\ApiErrorException Stripe exception.
     */
    public function getToken($code)
    {
        $params = array(
            'code'       => $code,
            'grant_type' => 'authorization_code',
        );

        try {
            $decoded_response = Oauth::token($params);
        } catch (Exception $e) {
            $decoded_response['error_description'] = $e->getMessage();
            $decoded_response['error'] = $e->getCode();
            Logger::logException($e);
        }

        $result = new OperationResult();

        if (isset($decoded_response['stripe_user_id'])) {
            if ($this->isOwnAccount($decoded_response['stripe_user_id'])) {
                $result->setSuccess(false);
                $result->addError(0, __('stripe_connect.own_account_cant_be_used_for_vendor'));
            } else {
                $result->setSuccess(true);
                $result->setData($decoded_response['stripe_user_id'], 'account_id');
                if (isset($decoded_response['scope'])) {
                    $result->setData($decoded_response['scope'], 'account_type');
                }
            }
        } elseif (isset($decoded_response['error_description'])) {
            $result->setSuccess(false);
            $result->addError($decoded_response['error'], $decoded_response['error_description']);
            Logger::log(Logger::ACTION_FAILURE, $decoded_response['error_description']);
        } else {
            $result->setSuccess(false);
        }

        return $result;
    }

    /**
     * Disconnects Stripe account.
     *
     * @param $stripe_user_id
     *
     * @return \Tygh\Common\OperationResult
     */
    public function disconnect($stripe_user_id)
    {
        $params = array(
            'stripe_user_id' => $stripe_user_id,
        );

        $result = new OperationResult();

        try {
            OAuth::deauthorize($params);
            $result->setSuccess(true);
        } catch (Exception $e) {
            $result->setSuccess(false);
            $result->addError($e->getCode(), $e->getMessage());
            Logger::logException($e);
        }

        return $result;
    }

    /**
     * Checks if account is the account of store's owner.
     * This method is used to prevent vendors from connecting admin account.
     *
     * @param string $stripe_user_id
     *
     * @return bool
     *
     * @throws \Stripe\Exception\ApiErrorException Stripe exception.
     */
    protected function isOwnAccount($stripe_user_id)
    {
        $root_account = Account::retrieve();

        return $root_account->id == $stripe_user_id;
    }
}
