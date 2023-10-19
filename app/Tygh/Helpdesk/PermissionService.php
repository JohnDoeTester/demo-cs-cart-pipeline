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

namespace Tygh\Helpdesk;

use Tygh\Common\OperationResult;
use Tygh\Http;

class PermissionService
{
    /**
     * @var bool
     */
    protected $is_logging_enabled;

    /**
     * @var \Tygh\Http
     */
    private $network_client;

    /**
     * @var string
     */
    private $license_number;

    /**
     * @var array<string, string|int>
     */
    private $auth;

    /**
     * @var string
     */
    private $store_domain;

    /**
     * @var string
     */
    private $service_url;

    /**
     * PermissionService constructor.
     *
     * @param array<string, string|int> $auth               Current user authentication details
     * @param string                    $store_domain       Store domain
     * @param \Tygh\Http                $network_client     Network client instance
     * @param string                    $service_url        API service URL
     * @param string                    $license_number     Store license
     * @param bool                      $is_logging_enabled Whether logging enabled for Hel Desk requests
     */
    public function __construct(
        array $auth,
        $store_domain,
        Http $network_client,
        $service_url,
        $license_number,
        $is_logging_enabled = false
    ) {
        $this->auth = $auth;
        $this->store_domain = $store_domain;
        $this->network_client = $network_client;
        $this->service_url = $service_url;
        $this->license_number = $license_number;
        $this->is_logging_enabled = $is_logging_enabled;
    }

    /**
     * @return bool
     */
    public function isAccountConnected()
    {
        $response = $this->request(
            Http::GET,
            'is_connected',
            [
                'user_id'      => $this->auth['helpdesk_user_id'],
                'email'        => $this->auth['email'],
                'store_domain' => $this->store_domain,
            ]
        );

        return $response->getData('is_connected', false);
    }

    /**
     * @param array<string> $requested_permissions Requested permissions
     *
     * @return bool
     */
    public function hasPermissions(array $requested_permissions)
    {
        $response = $this->request(
            Http::GET,
            'has_permissions',
            [
                'user_id'               => $this->auth['helpdesk_user_id'],
                'email'                 => $this->auth['email'],
                'store_domain'          => $this->store_domain,
                'requested_permissions' => $requested_permissions,
            ]
        );

        return $response->getData('has_permissions', false);
    }

    /**
     * @param array<int> $user_ids Connected user account IDs
     *
     * @return bool
     */
    public function reportConnection(array $user_ids)
    {
        $response = $this->request(
            Http::POST,
            'report_connected_accounts',
            [
                'user_ids'     => $user_ids,
                'store_domain' => $this->store_domain,
            ]
        );

        return $response->isSuccess();
    }

    /**
     * @param string               $method Request method
     * @param string               $action API action
     * @param array<string, mixed> $data   Request data
     *
     * @return \Tygh\Common\OperationResult
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     */
    private function request($method, $action, array $data)
    {
        $result = new OperationResult(false);

        $action_url = $this->getActionUrl($action);
        $extra = [
            'headers' => [
                'X-Auth-Token: ' . $this->license_number,
            ],
        ];

        $network = $this->network_client;
        $current_logging_state = $network::$logging;
        $network::$logging = $this->is_logging_enabled;

        switch ($method) {
            case Http::GET:
                $response = $network::get($action_url, $data, $extra);
                break;
            case Http::POST:
                $response = $network::post($action_url, $data, $extra);
                break;
            case Http::PUT:
                $response = $network::put($action_url, $data, $extra);
                break;
            default:
                $response = '';
                break;
        }

        $network::$logging = $current_logging_state;

        $response = json_decode($response, true);
        if ($response === null) {
            return $result;
        }

        $result->setSuccess($response['is_success']);
        $result->setMessages($response['messages']);
        $result->setErrors($response['errors']);
        $result->setData($response['data']);

        return $result;
    }

    /**
     * @param string $action API action
     *
     * @return string
     */
    private function getActionUrl($action)
    {
        return fn_link_attach($this->service_url, 'dispatch=user_permissions.' . $action);
    }
}
