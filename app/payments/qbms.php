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

if (!defined('BOOTSTRAP')) {
    require './init_payment.php';
}

use Tygh\Http;
use Tygh\Registry;

/**
 * Class QuickbooksAuth
 *
 * Performs OAuth authentication and requests signing
 */
class QuickbooksAuth
{
    /**
     * OAuth Authorize URL
     */
    const OAUTH_AUTHORIZE_URL = 'https://appcenter.intuit.com/connect/oauth2';

    /**
     * OAuth Scope that identifies the QuickBooks Online API/Payments
     */
    const OAUTH_SCOPE = 'com.intuit.quickbooks.payment';

    /**
     * OAuth Token Endpoint URL
     */
    const OAUTH_TOKEN_ENDPOINT_URL = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';

    /**
     * @var string OAuth Client ID  - obtained from Qucikbooks Dashboard
     */
    public $client_id;

    /**
     * @var string OAuth Access Token
     */
    public $token;

    /**
     * @var string OAuth Refresh Token
     */
    public $refresh_token;

    /**
     * @var string Realm ID (ex-Customer ID)
     */
    public $realm_id;

    /**
     * @var int Payment identifier
     */
    private $payment_id;

    /**
     * @var string OAuth Client Secret - obtained from Qucikbooks Dashboard
     */
    private $client_secret;

    /**
     * QuickbooksAuth constructor
     *
     * @param int   $payment_id Payment method identifier
     * @param array $auth_data  Processor parameters
     */
    public function __construct($payment_id = 0, $auth_data = array())
    {
        $this->client_id       = empty($auth_data['oauth_client_id'])       ? '' : $auth_data['oauth_client_id'];
        $this->client_secret   = empty($auth_data['oauth_client_secret'])   ? '' : $auth_data['oauth_client_secret'];
        $this->token           = empty($auth_data['access_token'])          ? '' : $auth_data['access_token'];
        $this->refresh_token   = empty($auth_data['refresh_token'])         ? '' : $auth_data['refresh_token'];
        $this->realm_id        = empty($auth_data['realm_id'])              ? '' : $auth_data['realm_id'];
        $this->payment_id      = empty($payment_id)                         ?  0 : $payment_id;
    }

    /**
     * Get OAuth Authorization URL
     *
     * @return string URL with build parameters
     */
    public function getAuthorizationURL($state)
    {
        $parameters = [
            'client_id' => $this->client_id,
            'scope' => self::OAUTH_SCOPE,
            'redirect_uri' => self::getCallbackUrl($this->payment_id),
            'response_type' => 'code',
            'state' => $state,
        ];

        return self::OAUTH_AUTHORIZE_URL . '?' . http_build_query($parameters, null, '&', PHP_QUERY_RFC1738);
    }

    /**
     * Get OAuth callback URL
     *
     * @param  int    $payment_id Payment identifier
     * @return string OAuth callback URL
     */
    public static function getCallbackUrl($payment_id = 0)
    {
        return fn_payment_url('current', basename(__FILE__)) . '?qb_action=auth_callback&payment_id=' . $payment_id;
    }

    /**
     * Get OAuth Access token data
     *
     * @param string $code OAuth authorization code
     *
     * @return array OAuth access token data
     */
    public function getAccessToken($code)
    {
        $parameters = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => self::getCallbackUrl($this->payment_id)
        ];

        $authorization_header_info = $this->generateAuthorizationHeader();

        $access_token = Http::post(
            self::OAUTH_TOKEN_ENDPOINT_URL,
            $parameters,
            [
                'headers' => [
                    'Accept: application/json',
                    'Authorization: ' . $authorization_header_info,
                    'Content-Type: application/x-www-form-urlencoded'
                ]
            ]
            );

        $access_token = json_decode($access_token, 1);

        $access_token['token_expire_time'] = TIME + $access_token['x_refresh_token_expires_in'];

        return $this->setAccessToken($access_token);
    }

    /**
     * Refresh OAuth Access token data
     *
     * @return array OAuth access token data
     */
    public function refreshAccessToken()
    {
        $parameters = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refresh_token
        ];

        $authorization_header_info = $this->generateAuthorizationHeader();

        $refresh_token = Http::post(
            self::OAUTH_TOKEN_ENDPOINT_URL,
            $parameters,
            [
                'headers' => [
                    'Accept: application/json',
                    'Authorization: ' . $authorization_header_info,
                    'Content-Type: application/x-www-form-urlencoded'
                ]
            ]
        );

        $access_token = json_decode($refresh_token, 1);

        return $this->setAccessToken($access_token);
    }

    /**
     * Store OAuth Access token data
     *
     * @param array $access_token OAuth access token data
     *
     * @return array OAuth access token data
     */
    private function setAccessToken($access_token = array())
    {
        $this->token         = empty($access_token['access_token'])  ? '' : $access_token['access_token'];
        $this->refresh_token = empty($access_token['refresh_token']) ? '' : $access_token['refresh_token'];

        return $access_token;
    }

    /**
     * Generate OAuth Authorization Header
     *
     * @return string OAuth generated authorization header
     */
    private function generateAuthorizationHeader()
    {
        $encoded_client_id_client_secrets = base64_encode($this->client_id . ':' . $this->client_secret);
        $authorization_header = 'Basic ' . $encoded_client_id_client_secrets;
        return $authorization_header;
    }
}

/**
 * Class QuickbooksPaymentMethod
 *
 * Perform payments via Qucikbooks Payments API
 */
class QuickbooksPaymentMethod
{
    /**
     * Payment status
     */
    const PAYMENT_STATUS_DECLINED = 'DECLINED';

    /**
     * @var int Payment identifier
     */
    private $payment_id;

    /**
     * @var string Payment gateway URL
     */
    private $gateway_url;

    /**
     * @var bool True if payments are performed on sandbox
     */
    private $test_mode = false;

    /**
     * @var array Payment processor parameter
     */
    private $processor_params;

    /**
     * QuickbooksPaymentMethod constructor
     *
     * @param int        $payment_id     Payment identifier
     * @param array|null $processor_data Payment processor data
     */
    public function __construct($payment_id = 0, $processor_data = null)
    {
        $this->payment_id = $payment_id;
        if (is_null($processor_data)) {
            $processor_data = fn_get_processor_data($payment_id);
        }
        $this->processor_params = $processor_data['processor_params'];

        $this->test_mode = $this->processor_params['mode'] == 'test';

        if ($this->test_mode) {
            $this->gateway_url = 'https://sandbox.api.intuit.com/quickbooks/v4/payments/charges';
        } else {
            $this->gateway_url = 'https://api.intuit.com/quickbooks/v4/payments/charges';
        }
    }

    /**
     * Prepare card info
     *
     * @param array $order_info Order info
     *
     * @return array Card info
     */
    private function prepareCardData($order_info)
    {
        // Quickbooks requires expYear to be specified in 4-digit format
        $year_prefix = substr(date('Y'), 0, 2);

        return array(
            'expYear' => $year_prefix . $order_info['payment_info']['expiry_year'],
            'expMonth' => $order_info['payment_info']['expiry_month'],
            'address' => $this->prepareAddress($order_info),
            'name' => $order_info['payment_info']['cardholder_name'],
            'cvc' => $order_info['payment_info']['cvv2'],
            'number' => $order_info['payment_info']['card_number']
        );
    }

    /**
     * Prepare card address
     *
     * @param array $order_info Order info
     *
     * @return array Address
     */
    private function prepareAddress($order_info = array())
    {
        $address_fields = array(
            'b_state' => '',
            'b_zipcode' => '',
            'b_address' => '',
            'b_country' => '',
            'b_city' => ''
        );
        $order_info = array_merge($address_fields, $order_info);

        return array(
            'region' => !empty($order_info['b_state']) ? $order_info['b_state'] : $order_info['s_state'],
            'postalCode' => !empty($order_info['b_zipcode']) ? $order_info['b_zipcode'] : $order_info['s_zipcode'],
            'streetAddress' => !empty($order_info['b_address']) ? $order_info['b_address'] : $order_info['s_address'],
            'country' => !empty($order_info['b_country']) ? $order_info['b_country'] : $order_info['s_country'],
            'city' => !empty($order_info['b_city']) ? $order_info['b_city'] : $order_info['s_city'],
        );
    }

    /**
     * Prepare payment request body
     *
     * @param array $order_info Order info
     *
     * @return string JSON-encoded request body
     */
    public function prepareRequestData($order_info)
    {
        return json_encode(array(
            'amount' => $order_info['total'],
            'currency' => CART_SECONDARY_CURRENCY,
            'card' => $this->prepareCardData($order_info),
            'context' => [
                'mobile' => false,
                'isEcommerce' => true
            ]
        ));

    }

    /**
     * Perform payment
     *
     * @param array $order_info Order info
     *
     * @return array $pp_response
     */
    public function charge($order_info)
    {
        $request_id = $this->processor_params['order_prefix'] . $order_info['order_id'] . '_' . TIME;
        $request_data = $this->prepareRequestData($order_info);

        $qa = new QuickbooksAuth($this->payment_id, $this->processor_params);

        // do not log request to hide card data
        Registry::set('log_cut', true);

        $response = Http::post(
            $this->gateway_url,
            $request_data,
            [
                'headers' => [
                    'Authorization: Bearer ' . $qa->token,
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Request-Id: ' . $request_id
                ]
            ]
        );

        return $this->processPaymentResponse($response);
    }

    /**
     * Process payment request response
     *
     * @param string $response Response text
     *
     * @return array Result of performing request
     */
    public function processPaymentResponse($response = '')
    {
        $result = array(
            'order_status' => 'P',
            'reason_text' => ''
        );

        $response = json_decode($response, 1);

        if ($this->paymentResponseHasErrors($response)) {
            if (empty($response['errors'])) {
                $response['errors'] = array();
            }
            $result['order_status'] = 'F';
            $result['reason_text'] = $this->getErrorMessage($response);
        } else {
            $result['transaction_id'] = $response['id'];
        }

        return $result;
    }

    /**
     * Check if payment response has errors
     *
     * @param array $response Response data
     *
     * @return bool True if response has errors
     */
    private function paymentResponseHasErrors($response)
    {
        return
            empty($response) || !empty($response['errors']) || empty($response['id']) ||
            (isset($response['status']) && $response['status'] == self::PAYMENT_STATUS_DECLINED);
    }

    /**
     * Process payment error
     *
     * @param array $response Response data
     *
     * @return string Error message
     */
    private function getErrorMessage($response = array())
    {
        $message = array();
        if (isset($response['status']) && $response['status'] == self::PAYMENT_STATUS_DECLINED) {
            $message[] = __('text_transaction_declined');
        } elseif (empty($response['errors'])) {
            $message[] = 'Payment gateway error';
        }
        foreach ($response['errors'] as $error) {
            $message[] =  "{$error['code']}: {$error['message']}" . (empty($error['moreInfo']) ? '' : " {$error['moreInfo']}");
        }

        return implode(', ', $message);
    }
}

/*****************************************************************************/

$qb_action = (!empty($_REQUEST['qb_action'])) ? $_REQUEST['qb_action'] : 'pay';
if (isset($_REQUEST['oauth_verifier'])) {
    $qb_action = 'auth_callback';
}

switch ($qb_action) {
    case 'auth_start':
        $payment_id = $_REQUEST['payment_id'];
        $payment_data = fn_get_processor_data($payment_id);

        $auth_provider = new QuickbooksAuth($payment_id, $payment_data['processor_params']);

        $state = sha1(openssl_random_pseudo_bytes(1024));
        $auth_url = $auth_provider->getAuthorizationURL($state);

        if ($auth_url) {
            Tygh::$app['session']['quickbooks_auth_state'] = $state;
            fn_redirect($auth_url, true);
        }

        exit;

    case 'auth_callback':
        $payment_id = $_REQUEST['payment_id'];
        $payment_data = fn_get_payment_method_data($payment_id);

        if (isset($_REQUEST['code'])) {
            $code = $_REQUEST['code'];
            $response_state = $_REQUEST['state'];
            if (strcmp(Tygh::$app['session']['quickbooks_auth_state'], $response_state) !== 0) {
                throw new Exception('The state is not correct from Intuit Server');
            }

            $auth_provider = new QuickbooksAuth($payment_id, $payment_data['processor_params']);
            $access_token = $auth_provider->getAccessToken($code);

            foreach ($access_token as $field => $value) {
                $payment_data['processor_params'][$field] = $value;
            }
            fn_update_payment($payment_data, $payment_id);

            // close auth pop-up
            echo '<script>window.open("", "_parent", ""); window.close();</script>';
        }

        exit;

    default:
    case 'pay':
        $payment_id = $_REQUEST['payment_id'];
        $payment_data = fn_get_payment_method_data($payment_id);

        $qa = new QuickbooksAuth($payment_id, $payment_data['processor_params']);

        if (!empty($qa->refresh_token)) {
            $refresh_token = $qa->refreshAccessToken();

            foreach ($refresh_token as $field => $value) {
                $payment_data['processor_params'][$field] = $value;
            }
            fn_update_payment($payment_data, $payment_id);
        }

        $qb = new QuickbooksPaymentMethod($order_info['payment_id']);
        $pp_response = $qb->charge($order_info);
        break;
}
