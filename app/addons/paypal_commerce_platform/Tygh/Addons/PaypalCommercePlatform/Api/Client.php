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

namespace Tygh\Addons\PaypalCommercePlatform\Api;

use Tygh\Addons\PaypalCommercePlatform\Exception\ApiException;
use Tygh\Addons\PaypalCommercePlatform\Exception\ContentException;
use Tygh\Enum\YesNo;
use Tygh\Http;
use Tygh\Registry;

class Client
{
    /**
     * @var string $client_id REST API application client ID
     */
    protected $client_id;

    /**
     * @var string $secret REST API application secret
     */
    protected $secret;

    /**
     * @var string $access_token OAuth access token
     */
    protected $access_token;

    /**
     * @var int $expiry_time OAuth token expiry time (unixtime)
     */
    protected $expiry_time;

    /**
     * @var bool $is_test If true, sandbox request will be performed
     */
    protected $is_test;

    /**
     * @var string $bn_code Partner integration code
     */
    private $bn_code = 'CSCART_MP_PPCP';

    /**
     * Sandbox URL.
     */
    const URL_TEST = 'https://api.sandbox.paypal.com';

    /**
     * Production URL.
     */
    const URL_LIVE = 'https://api.paypal.com';

    /**
     * Api constructor.
     *
     * @param string      $client_id    REST API application client ID
     * @param string      $secret       REST API application secret
     * @param string      $access_token OAuth access token
     * @param int         $expiry_time  OAuth token expiry time
     * @param bool        $is_test      If true, sandbox request will be performed
     * @param string|null $bn_code      Partner integration code
     */
    public function __construct(
        $client_id,
        $secret,
        $access_token = '',
        $expiry_time = 0,
        $is_test = false,
        $bn_code = null
    ) {
        $this->client_id = $client_id;
        $this->secret = $secret;
        $this->access_token = $access_token;
        $this->expiry_time = $expiry_time;

        if ($bn_code !== null) {
            $this->bn_code = $bn_code;
        }

        $this->setTestMode($is_test);
    }

    /**
     * Checks if OAuth token is expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->expiry_time <= time();
    }

    /**
     * Sets test mode.
     *
     * @param bool $is_test Whether test mode is used
     */
    public function setTestMode($is_test = true)
    {
        $this->is_test = YesNo::toBool($is_test);
    }

    /**
     * Obtains OAuth token.
     *
     * @throws ApiException     If an API error occurred.
     * @throws ContentException If a response is not a valid JSON.
     */
    public function obtainToken()
    {
        $data = [
            'grant_type' => 'client_credentials',
        ];

        $extra = [
            'basic_auth' => [$this->client_id, $this->secret],
        ];

        // disable logging
        $logging = Http::$logging;
        Http::$logging = false;

        /**
         * @psalm-var array{
         *   access_token: string,
         *   expires_in: string
         * } $response
         */
        $response = $this->request('/v1/oauth2/token', $data, $extra, Http::POST);

        // restore logging state
        Http::$logging = $logging;

        $this->access_token = $response['access_token'];
        $this->expiry_time = time() + (int) $response['expires_in'];
    }

    /**
     * Decodes JSON encoded API response, checks if any errors are reported.
     *
     * @param string $response API response
     * @param int    $status   HTTP status
     * @param string $headers  Response headers
     *
     * @return array<string, string> Decoded response
     *
     * @throws \Tygh\Addons\PaypalCommercePlatform\Exception\ApiException If an API error occurred.
     * @throws \Tygh\Addons\PaypalCommercePlatform\Exception\ContentException If a response is not a valid JSON.
     */
    public function decodeResponse($response = '', $status = 200, $headers = '')
    {
        $decoded_response = json_decode($response, true);

        if ($decoded_response === null) {
            throw new ContentException($response);
        }

        if (!$this->isErrorStatus($status)) {
            return $decoded_response;
        }

        $error_message = $response;
        if (isset($decoded_response['message'])) {
            $error_message = $decoded_response['message'];
        } elseif (isset($decoded_response['error_description'])) {
            $error_message = $decoded_response['error_description'];
        }

        $details = [];
        if (isset($decoded_response['details'])) {
            $details = $decoded_response['details'];
        }

        $e = new ApiException($error_message);
        $e->setDetails($details);

        throw $e;
    }

    /**
     * Performs API request signed with access token.
     *
     * @param string                                     $url    API method URL
     * @param array<string, string|array<string>>|string $data   API request data
     * @param array<string, string|array<string>>        $extra  Extra settings for curl
     * @param string                                     $method HTTP method to perform request
     *
     * @psalm-param array{
     *   headers?: array<string>
     * } $extra
     *
     * @return array{array<string, string|array<string|array<string>>>, array{access_token?: string, expiry-time?: int}} API response and new token
     *                             data if one is obtained
     *
     * @throws ApiException     If an API error occurred.
     * @throws ContentException If a content is not a valid JSON.
     */
    public function signedRequest($url = '', $data = [], array $extra = [], $method = Http::POST)
    {
        $new_token = [];

        if ($this->isExpired()) {
            $this->obtainToken();
            $new_token = [
                'access_token' => $this->getAccessToken(),
                'expiry_time'  => $this->getExpiryTime(),
            ];
        }

        $extra = array_merge_recursive(
            $extra,
            [
                'headers' => [
                    'PayPal-Request-Id: ' . time(),
                    'Content-type: application/json',
                    'PayPal-Partner-Attribution-Id: ' . $this->bn_code,
                    'Authorization: Bearer ' . $this->access_token,
                ],
            ]
        );

        $extra['headers'] = array_unique($extra['headers']);
        $extra['log_preprocessor'] = static function ($method, $url, $data, $extra, $content) {
            if (preg_match('/paypal-debug-id:\s*(?P<debug_id>\S+)/ui', Http::getHeaders(), $headers)) {
                /** @see \fn_paypal_commerce_platform_save_log() */
                Registry::set('runtime.paypal_commerce_platform.debug_id', $headers['debug_id']);
            }

            return [$url, $data, $content];
        };

        $response = $this->request($url, $data, $extra, $method);

        return [$response, $new_token];
    }

    /**
     * Gets OAuth access token.
     *
     * @return string OAuth access token
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * Gets OAuth access token expiry time.
     *
     * @param string $format Date format
     *
     * @return string|int OAuth access token expiry time
     */
    public function getExpiryTime($format = '')
    {
        if ($format) {
            return date($format, $this->expiry_time);
        }

        return $this->expiry_time;
    }

    /**
     * Checks whether HTTP status code indicates an error.
     *
     * @param int $status Code
     *
     * @return bool
     */
    protected function isErrorStatus($status)
    {
        return $status < 200 || $status > 299;
    }

    /**
     * Checks if the requestor is configured to perform API requests.
     *
     * @return bool
     */
    protected function isConfigured()
    {
        return $this->client_id
            && $this->secret;
    }

    /**
     * Performs request to API endpoint.
     *
     * @param string                                       $url    API method URL
     * @param array<string, string|array<string>>|string   $data   API request data
     * @param array<string, string|array<string>|callable> $extra  Extra settings for curl
     * @param string                                       $method HTTP method to perform request
     *
     * @return array<string, string|array<string|array<string>>> API response
     *
     * @throws ApiException     If an API error occurred.
     * @throws ContentException If a content is not a valid JSON.
     */
    protected function request($url = '', $data = [], array $extra = [], $method = Http::POST)
    {
        if (!$this->isConfigured()) {
            throw new ApiException('Configuration error');
        }

        $service_url = $this->is_test
            ? self::URL_TEST
            : self::URL_LIVE;

        $response = call_user_func(
            ['\\Tygh\\Http', strtolower($method)],
            $service_url . '/' . ltrim($url, '/'),
            $data,
            $extra
        );

        $headers = Http::getHeaders();
        $status = Http::getStatus();

        $response = $this->decodeResponse($response, $status, $headers);

        return $response;
    }
}
