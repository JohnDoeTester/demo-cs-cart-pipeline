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

use Tygh\Database\Connection;
use Tygh\Http;

class ClientWrapper
{
    /** @var int $payment_id */
    protected $payment_id;

    /** @var array{client_id: string, secret: string, access_token: string, expiry_time: int, mode: string} $processor_params */
    protected $processor_params;

    /** @var \Tygh\Database\Connection $db */
    protected $db;

    /** @var \Tygh\Addons\PaypalCommercePlatform\Api\Client $api_client */
    protected $api_client;

    /**
     * ClientWrapper constructor.
     *
     * @param int                       $payment_id       Payment method ID
     * @param array<string, string|int> $processor_params Payment method processor params
     * @param \Tygh\Database\Connection $db               Database connection
     *
     * @psalm-param array{
     *   client_id: string,
     *   secret: string,
     *   access_token: string,
     *   expiry_time: int,
     *   mode: string
     * } $processor_params
     */
    public function __construct($payment_id, array $processor_params, Connection $db)
    {
        $this->payment_id = $payment_id;
        $this->processor_params = $processor_params;
        $this->db = $db;

        $this->initClient();
    }

    /**
     * Performs API request and updates oauth token.
     *
     * @param string                                     $url    API method URL
     * @param array<string, string|array<string>>|string $data   API request data
     * @param array<string, string>                      $extra  Extra settings for curl
     * @param string                                     $method HTTP method to perform request
     *
     * @psalm-param array{
     *   headers?: array<string>
     * } $extra
     *
     * @return array<string, string|array<string|array<string>>> API response
     *
     * @throws \Tygh\Addons\PaypalCommercePlatform\Exception\ApiException If an API error occurred.
     * @throws \Tygh\Addons\PaypalCommercePlatform\Exception\ContentException If a content is not a valid JSON.
     *
     * @see \Tygh\Addons\PaypalCommercePlatform\Api\Client::signedRequest
     */
    public function request($url = '', $data = [], array $extra = [], $method = Http::POST)
    {
        if (!is_string($data)) {
            $data = json_encode($data);
        }

        list($response, $new_token) = $this->api_client->signedRequest($url, $data, $extra, $method);

        if ($new_token) {
            $this->updateProcessorParameters($new_token);
            $this->initClient();
        }

        return $response;
    }

    /**
     * Initializes API client.
     */
    protected function initClient()
    {
        $this->api_client = new Client(
            $this->processor_params['client_id'],
            $this->processor_params['secret'],
            $this->processor_params['access_token'],
            $this->processor_params['expiry_time'],
            $this->processor_params['mode'] === 'test',
            isset($this->processor_params['bn_code'])
                ? $this->processor_params['bn_code']
                : null
        );
    }

    /**
     * Updates oauth token and expiry time for the payment method.
     *
     * @param array<string, string|int> $parameters New parameters
     *
     * @psalm-param array{
     *   access_token?: string,
     *   expiry_time?: int
     * } $parameters
     */
    protected function updateProcessorParameters(array $parameters)
    {
        foreach ($parameters as $parameter => $value) {
            /** @psalm-suppress PropertyTypeCoercion */
            $this->processor_params[$parameter] = $value;
        }

        $this->db->query(
            'UPDATE ?:payments SET ?u WHERE ?w',
            [
                'processor_params' => serialize($this->processor_params),
            ],
            [
                'payment_id' => $this->payment_id,
            ]
        );
    }

    /**
     * Calculates first-party delegation proof.
     *
     * @param string $payer_id Payer ID to calculate assertion for
     *
     * @return string
     */
    public function getAuthAssertion($payer_id)
    {
        $assertion = '';

        $assertion .= base64_encode(
            json_encode(
                [
                    'alg' => 'none',
                ]
            )
        );

        $assertion .= '.';

        $assertion .= base64_encode(
            json_encode(
                [
                    'iss'      => $this->processor_params['client_id'],
                    'payer_id' => $payer_id,
                ]
            )
        );

        $assertion .= '.';

        return $assertion;
    }
}
