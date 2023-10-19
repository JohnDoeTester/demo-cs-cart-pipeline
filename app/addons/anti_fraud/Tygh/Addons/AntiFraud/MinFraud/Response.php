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

namespace Tygh\Addons\AntiFraud\MinFraud;

/**
 * This class provides a Insights score response
 *
 * @package Tygh\Addons\AntiFraud\MinFraud
 */
class Response
{
    private $id;
    private $risk_score;
    private $credits_remaining;
    private $email_data = array();
    private $shipping_address_data = array();
    private $billing_address_data = array();
    private $ip_address_data = array();
    private $warnings = array();
    private $error_code = null;
    private $error_message = null;

    /**
     * Response constructor
     *
     * @param string $raw_response String of the json response.
     */
    public function __construct($raw_response)
    {
        $data = json_decode($raw_response, true);

        if (!is_array($data)) {
            $this->error_code = json_last_error();
            $this->error_message = json_last_error_msg();
            $data = array();
        }

        if (isset($data['id'])) {
            $this->id = $data['id'];
        }

        if (isset($data['risk_score'])) {
            $this->risk_score = $data['risk_score'];
        }

        if (isset($data['credits_remaining'])) {
            $this->credits_remaining = $data['credits_remaining'];
        }

        if (isset($data['ip_address'])) {
            $this->ip_address_data = $data['ip_address'];
        }

        if (isset($data['email'])) {
            $this->email_data = $data['email'];
        }

        if (isset($data['shipping_address'])) {
            $this->shipping_address_data = $data['shipping_address'];
        }

        if (isset($data['billing_address'])) {
            $this->billing_address_data = $data['billing_address'];
        }

        if (isset($data['warnings'])) {
            foreach ($data['warnings'] as $item) {
                $this->warnings[] = array(
                    'code' => $item['code'],
                    'warning' => $item['warning'],
                );
            }
        }

        if (isset($data['error'])) {
            $this->error_code = $data['code'];
            $this->error_message = $data['error'];
        }
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->error_message !== null;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getRiskScore()
    {
        return $this->risk_score;
    }

    /**
     * @return mixed
     */
    public function getCreditsRemaining()
    {
        return $this->credits_remaining;
    }

    /**
     * @return array
     */
    public function getEmailData()
    {
        return $this->email_data;
    }

    /**
     * @return array
     */
    public function getShippingAddressData()
    {
        return $this->shipping_address_data;
    }

    /**
     * @return array
     */
    public function getBillingAddressData()
    {
        return $this->billing_address_data;
    }

    /**
     * @return array
     */
    public function getIpAddressData()
    {
        return $this->ip_address_data;
    }

    /**
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * @return null
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * @return null
     */
    public function getErrorMessage()
    {
        return $this->error_message;
    }
}