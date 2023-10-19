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

class LicenseActivateMailRequester
{
    /**
     * @var array<string, string|int>
     */
    private $auth;

    /**
     * @var string
     */
    private $service_url;

    /**
     * @var \Tygh\Http
     */
    private $network_client;

    /**
     * @var string
     */
    private $license_number;

    /**
     * LicenseActivateMailRequester constructor.
     *
     * @param array<string, string|int> $auth           Current user authentication details
     * @param string                    $service_url    API service URL
     * @param \Tygh\Http                $network_client Network client instance
     * @param string                    $license_number Store license
     */
    public function __construct(array $auth, $service_url, Http $network_client, $license_number)
    {
        $this->auth = $auth;
        $this->network_client = $network_client;
        $this->service_url = $service_url;
        $this->license_number = $license_number;
    }

    /**
     * Sends a request to receive a mail-message with a link to activate the license.
     *
     * @return OperationResult
     */
    public function requestMail()
    {
        $network_client = $this->network_client;

        $result = new OperationResult(false);
        $request_data = [
            'email'          => $this->auth['email'],
            'license_number' => $this->license_number,
        ];

        $response = $network_client::post($this->getActivateMailUrl(), $request_data);

        $response = json_decode($response, true);
        if ($response === null) {
            return $result;
        }

        $result->setSuccess($response['is_success']);
        $result->setErrors($response['errors']);
        $result->setData($response['data']);

        return $result;
    }

    /**
     * @return string
     */
    private function getActivateMailUrl()
    {
        return fn_link_attach($this->service_url, 'dispatch=licenses.send_activate_mail');
    }
}
