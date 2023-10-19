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

namespace Tygh\Addons\StorefrontRestApi\Payments;

class RedirectionPaymentDetailsBuilder
{
    /**
     * URL to redirect customer to to perform payment.
     *
     * @var string $payment_url
     */
    private $payment_url;

    /**
     * Query parameters to pass to payment gateway.
     *
     * @var array $query_parameters
     */
    private $query_parameters = array();

    /**
     * Request method to perform request to payment URL
     *
     * @var string $method
     */
    private $method = self::GET;

    /**
     * URL the customer is redirected to when the payment is performed
     *
     * @var string $return_url
     */
    private $return_url;

    /**
     * URL the customer is redirected to when the payment is cancelled
     *
     * @var string $cancel_url
     */
    private $cancel_url;

    const GET = 'GET';
    const POST = 'POST';

    public function setPaymentUrl($url)
    {
        $this->payment_url = $url;

        return $this;
    }

    public function getPaymentUrl()
    {
        return $this->payment_url;
    }

    public function setQueryParameters(array $query_parameters)
    {
        $this->query_parameters = $query_parameters;

        return $this;
    }

    public function getQueryParameters()
    {
        return $this->query_parameters;
    }

    public function setReturnUrl($url)
    {
        $this->return_url = $url;

        return $this;
    }

    public function getReturnUrl()
    {
        return $this->return_url;
    }

    public function setCancelUrl($url)
    {
        $this->cancel_url = $url;

        return $this;
    }

    public function getCancelUrl()
    {
        return $this->cancel_url;
    }

    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function asArray()
    {
        return array(
            'method'           => $this->getMethod(),
            'payment_url'      => $this->getPaymentUrl(),
            'query_parameters' => $this->getQueryParameters(),
            'return_url'       => $this->getReturnUrl(),
            'cancel_url'       => $this->getCancelUrl(),
        );
    }
}