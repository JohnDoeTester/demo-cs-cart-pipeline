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

namespace Tygh\Marketplace;

use Exception;
use Tygh\Api\Response;
use Tygh\Common\OperationResult;
use Tygh\Http;
use Tygh\Registry;

/**
 * Class Client connects to CS-Cart Marketplace and executes all data exchanges.
 *
 * @package Tygh\Marketplace
 */
class Client
{
    const CACHE_TAG = 'marketplace_client_cache';

    const CACHE_PERIOD = SECONDS_IN_DAY;

    const CONNECTION_TIMEOUT = 2;

    const EXECUTION_TIMEOUT = 5;

    /** @var string|null $client_id Client identifier */
    protected $client_id;

    /** @var string $url Marketplace url */
    protected $url;

    /** @var int $helpdesk_user_id Helpdesk user ID */
    protected $helpdesk_user_id;

    /**
     * Client constructor.
     *
     * @param string   $url              Marketplace url.
     * @param string   $license_code     Client identifier.
     * @param int|null $helpdesk_user_id Helpdesk user ID.
     */
    public function __construct($url, $license_code, $helpdesk_user_id = null)
    {
        $this->url = $url;
        $this->client_id = !empty($license_code) ? md5($license_code) : null;
        $this->helpdesk_user_id = (int) $helpdesk_user_id;
    }

    /**
     * Makes request to server.
     *
     * @param string       $address Server url address.
     * @param string       $type    Request type.
     * @param array<mixed> $data    Request payload.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
     *
     * @return mixed
     */
    protected function request($address, $type, array $data = [])
    {
        if (!$this->client_id) {
            return null;
        }

        $extra = [
            'headers' => [
                'Content-type: application/json',
                sprintf('Marketplace-Client-Id: %s', $this->client_id),
                sprintf('Marketplace-Helpdesk-User-Id: %d', $this->helpdesk_user_id),
            ],
            'connection_timeout' => self::CONNECTION_TIMEOUT,
            'execution_timeout'  => self::EXECUTION_TIMEOUT,
        ];
        $log_cut = Registry::ifGet('log_cut', false);
        $show_marketplace_logs = Registry::get('config.tweaks.show_marketplace_logs');
        Registry::set('log_cut', empty($show_marketplace_logs));
        switch ($type) {
            case Http::GET:
                $answer = Http::get($address, $data, $extra);
                break;
            case Http::POST:
                $data = json_encode($data);
                $answer = Http::post($address, $data, $extra);
                break;
            case Http::PUT:
                $data = json_encode($data);
                $answer = Http::put($address, $data, $extra);
                break;
            case Http::DELETE:
                $answer = Http::delete($address, $extra);
                break;
            default:
                $answer = '';
                break;
        }
        Registry::set('log_cut', $log_cut);
        $answer = json_decode($answer, true);
        return $answer;
    }

    /**
     * Makes request to server with cache mechanism.
     *
     * @param string                     $method_name      Server url address.
     * @param string                     $method_type      Request type.
     * @param array<mixed>               $data             Request payload.
     * @param array<string|int|string[]> $cache_conditions Cache conditions.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
     *
     * @return mixed
     */
    protected function requestWithCache($method_name, $method_type, array $data = [], array $cache_conditions = [])
    {
        $cache_key = str_replace('/', '-', trim($method_name, '/'));
        $cache_key .= '-' . md5(json_encode($data));

        $default_cache_conditions = [
            'ttl'             => self::CACHE_PERIOD,
            'update_handlers' => [],
        ];
        $cache_conditions = array_merge($default_cache_conditions, $cache_conditions);

        $response = Registry::getOrSetCache(
            [self::CACHE_TAG, $cache_key],
            $cache_conditions,
            ['lang', 'time'],
            function () use ($method_name, $method_type, $data) {
                return $this->request($this->url . $method_name, $method_type, $data);
            }
        );
        // FIXME: Need better solution for handling failed requests
        if (
            empty($response)
            || (isset($response['status']) && is_numeric($response['status']) && $response['status'] > Response::STATUS_OK)
        ) {
            return [];
        }
        return $response;
    }

    /**
     * Gets categories of addons from Marketplace.
     *
     * @param string $lang_code Two letter Language code
     *
     * @return array<string>
     */
    public function getCategories($lang_code = CART_LANGUAGE)
    {
        $method_name = '/api/marketplace_categories';
        $method_type = Http::GET;
        $data = [
            'lang_code' => $lang_code,
        ];

        $categories = $this->requestWithCache($method_name, $method_type, $data);
        if (empty($categories)) {
            return [];
        }
        return array_column($categories, null, 'category_id');
    }

    /**
     * Gets CS-Cart product versions from Marketplace.
     *
     * @param string $version Current product version.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
     *
     * @return mixed
     */
    public function getProductVersions($version = PRODUCT_VERSION)
    {
        $method_name = '/api/marketplace_product_versions';
        $method_type = Http::GET;
        $data = [
            'product_version' => $version,
        ];
        return $this->requestWithCache($method_name, $method_type, $data);
    }

    /**
     * Gets information about add-on from Marketplace.
     *
     * @param string               $id     Add-on product id at Marketplace.
     * @param array<string, mixed> $params Parameters for request.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
     *
     * @return mixed
     */
    public function getProduct($id = '', array $params = [])
    {
        $method_name = '/api/marketplace_products/' . $id;
        $method_type = Http::GET;
        return $this->requestWithCache($method_name, $method_type, $params);
    }

    /**
     * Adds add-on review to Marketplace.
     *
     * @param string $id      Add-on product id at Marketplace.
     * @param string $value   Review rating.
     * @param string $message Review message.
     *
     * @return OperationResult
     */
    public function setProductReview($id, $value, $message = ' ')
    {
        $method_name = '/api/marketplace_reviews';
        $method_type = Http::POST;
        $data = [
            'object_id'    => $id,
            'rating_value' => $value,
            'message'      => $message,
        ];
        $result = new OperationResult(false);
        $response = $this->request($this->url . $method_name, $method_type, $data);
        if (isset($response['post_id'])) {
            $result->setSuccess(true);
            return $result;
        }
        switch ($response['status']) {
            case Response::STATUS_FORBIDDEN:
                $result->addError((string) Response::STATUS_FORBIDDEN, __('addons.your_account_doesnt_have_this_addon'));
                break;
            case Response::STATUS_CONFLICT:
                $result->addError((string) Response::STATUS_CONFLICT, __('addons.you_already_posted_review'));
                break;
            case Response::STATUS_BAD_REQUEST:
            default:
                $result->addError((string) Response::STATUS_BAD_REQUEST, __('addons.review_bad_request'));
                break;
        }
        return $result;
    }

    /**
     * Sent message to developer into marketplace message center.
     *
     * @param array<string, string> $params Message information.
     *
     * @return OperationResult
     */
    public function sentDeveloperMessage(array $params)
    {
        $method_name = '/api/marketplace_messages';
        $method_type = Http::POST;
        $data = $params;
        $result = new OperationResult(false);
        $response = $this->request($this->url . $method_name, $method_type, $data);
        if (is_numeric($response)) {
            $result->setSuccess(true);
            return $result;
        }
        switch ($response['status']) {
            case Response::STATUS_FORBIDDEN:
                foreach ($response['data'] as $error) {
                    $result->addError($error['code'], $error['message']);
                }
                break;
            case Response::STATUS_BAD_REQUEST:
            default:
                $result->addError((string) Response::STATUS_BAD_REQUEST, __('addons.something_went_wrong'));
                break;
        }
        return $result;
    }

    /**
     * Gets add-ons from the Marketplace matching the search conditions.
     *
     * @param array<string, string> $params Search params.
     *
     * @return array<array<string, string>>
     */
    public function getSearchProducts(array $params)
    {
        $client_id = $this->client_id;
        $this->client_id = 'fake';

        $method_name = '/api/search';
        $method_type = Http::GET;
        $data = $params;
        $products = [];

        try {
            $products = $this->requestWithCache($method_name, $method_type, $data, ['ttl' => SECONDS_IN_HOUR]);
        } catch (Exception $e) {
        }

        $this->client_id = $client_id;

        return $products;
    }
}
