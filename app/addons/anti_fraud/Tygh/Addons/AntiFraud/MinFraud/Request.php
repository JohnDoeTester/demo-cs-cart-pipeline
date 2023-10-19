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
 * This class provides a Insights score request
 *
 * @package Tygh\Addons\AntiFraud\MinFraud
 */
class Request
{
    public $ip_address;
    public $account_user_id;
    public $email_address;
    public $email_domain;
    public $billing_first_name;
    public $billing_last_name;
    public $billing_address;
    public $billing_address_2;
    public $billing_city;
    public $billing_region;
    public $billing_country;
    public $billing_postal;
    public $shipping_first_name;
    public $shipping_last_name;
    public $shipping_address;
    public $shipping_address_2;
    public $shipping_city;
    public $shipping_region;
    public $shipping_country;
    public $shipping_postal;
    public $order_amount;
    public $order_currency;

    /**
     * Creates instance of Request by order data.
     *
     * @param array $order Order data
     *
     * @return Request
     */
    public static function createFromOrder(array $order)
    {
        $request = new self();

        if (isset($order['ip_address'])) {
            $request->ip_address = $order['ip_address'];
        }

        if (isset($order['user_id'])) {
            $request->account_user_id = $order['user_id'];
        }

        if (isset($order['email'])) {
            $request->email_address = $order['email'];
            $request->email_domain = parse_url('http://' . $order['email'], PHP_URL_HOST);
        }

        if (isset($order['b_firstname'])) {
            $request->billing_first_name = $order['b_firstname'];
        }

        if (isset($order['b_lastname'])) {
            $request->billing_last_name = $order['b_lastname'];
        }

        if (isset($order['b_address'])) {
            $request->billing_address = $order['b_address'];
        }

        if (isset($order['b_address_2'])) {
            $request->billing_address_2 = $order['b_address_2'];
        }

        if (isset($order['b_city'])) {
            $request->billing_city = $order['b_city'];
        }

        if (isset($order['b_state'])) {
            $request->billing_region = $order['b_state'];
        }

        if (isset($order['b_country'])) {
            $request->billing_country = $order['b_country'];
        }

        if (isset($order['b_zipcode'])) {
            $request->billing_postal = $order['b_zipcode'];
        }

        if (isset($order['s_firstname'])) {
            $request->shipping_first_name = $order['s_firstname'];
        }

        if (isset($order['s_lastname'])) {
            $request->shipping_last_name = $order['s_lastname'];
        }

        if (isset($order['s_address'])) {
            $request->shipping_address = $order['s_address'];
        }

        if (isset($order['s_address_2'])) {
            $request->shipping_address_2 = $order['s_address_2'];
        }

        if (isset($order['s_city'])) {
            $request->shipping_city = $order['s_city'];
        }

        if (isset($order['s_state'])) {
            $request->shipping_region = $order['s_state'];
        }

        if (isset($order['s_country'])) {
            $request->shipping_country = $order['s_country'];
        }

        if (isset($order['s_zipcode'])) {
            $request->shipping_postal = $order['s_zipcode'];
        }

        if (isset($order['total'])) {
            $request->order_amount = (float) $order['total'];
        }

        if (isset($order['secondary_currency'])) {
            $request->order_currency = $order['secondary_currency'];
        }

        return $request;
    }

    /**
     * Gets request as json
     *
     * @return string
     */
    public function json()
    {
        $data = array(
            'device' => array(
                'ip_address' => $this->ip_address
            ),
            'email' => array(
                'address' => $this->email_address,
                'domain' => $this->email_domain,
            ),
            'billing' => array(
                'first_name' => $this->billing_first_name,
                'last_name' => $this->billing_last_name,
                'address' => $this->billing_address,
                'address_2' => $this->billing_address_2,
                'city' => $this->billing_city,
                'region' => $this->billing_region,
                'country' => $this->billing_country,
                'postal' => $this->billing_postal,
            ),
            'shipping' => array(
                'first_name' => $this->shipping_first_name,
                'last_name' => $this->shipping_last_name,
                'address' => $this->shipping_address,
                'address_2' => $this->shipping_address_2,
                'city' => $this->shipping_city,
                'region' => $this->shipping_region,
                'country' => $this->shipping_country,
                'postal' => $this->shipping_postal,
            ),
            'order' => array(
                'amount' => $this->order_amount,
                'currency' => $this->order_currency
            ),
            'account' => array(
                'user_id' => $this->account_user_id
            )
        );

        foreach ($data as &$item) {
            $item = array_filter($item);
        }

        return json_encode(array_filter($data));
    }
}