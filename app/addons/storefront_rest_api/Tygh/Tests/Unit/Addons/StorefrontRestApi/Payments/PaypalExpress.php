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

namespace Tygh\Tests\Unit\Addons\StorefrontRestApi\Payments;

use Tygh\Tools\Url;

class PaypalExpress extends \Tygh\Addons\StorefrontRestApi\Payments\PaypalExpress
{
    private $request = array();

    /** @inheritdoc */
    public function getDetails(array $request)
    {
        $this->request = $request;

        return parent::getDetails($request);
    }

    /**
     * Performs request to PayPal to create payment token.
     *
     * @param int   $payment_id Payment ID
     * @param int   $order_id   Order ID
     * @param array $order_info Order info from ::fn_get_order_info
     *
     * @return array See ::fn_paypal_set_express_checkout
     */
    protected function requestPaymentToken($payment_id, $order_id, array $order_info)
    {
        return $this->request['paypal_response'];
    }

    /**
     * Checks if token creation on PayPal succeed.
     *
     * @param array $paypal_response PayPal response. See PaypalExpress::requestPaymentToken
     *
     * @return bool
     */
    protected function isTokenCreated(array $paypal_response)
    {
        return !empty($paypal_response['ACK'])
            && ($paypal_response['ACK'] == 'Success'
                || $paypal_response['ACK'] == 'SuccessWithWarning'
            );
    }

    /**
     * Provides errors reported by PayPal when trying to create payment token.
     *
     * @param array $paypal_response PayPal response. See PaypalExpress::requestPaymentToken
     *
     * @return array
     */
    protected function getTokenCreationErrors(array $paypal_response)
    {
        $error = array();

        if (!empty($paypal_response['L_ERRORCODE0'])) {
            $error[$paypal_response['L_ERRORCODE0']] = $paypal_response['L_SHORTMESSAGE0'] . ': ' . $paypal_response['L_LONGMESSAGE0'];
        }

        return $error;
    }

    /**
     * Provides link to redirect customer to perform payment on PayPal.
     *
     * @param array  $payment_info Payment info
     * @param string $token        Token obtained from PayPal
     *
     * @return string Payment URL
     */
    protected function getPaymentUrl(array $payment_info, $token)
    {
        if ($payment_info['processor_params']['mode'] == 'live') {
            $host = 'https://www.paypal.com';
        } else {
            $host = 'https://www.sandbox.paypal.com';
        }

        $post_data = array(
            'cmd' => '_express-checkout',
            'token' => $token,
        );

        $submit_url = "$host/webscr";

        $payment_link = array(
            'url' => $submit_url,
            'request' => $post_data,
        );

        $url_builder = new Url($payment_link['url']);
        $url_builder->setQueryParams($payment_link['request']);

        return $url_builder->build();
    }

    /**
     * Provides full link with schema, domain, path and query string.
     *
     * @param string|array $dispatch     Dispatch string or array with controller, mode, action
     * @param array        $query_params List of query parameters and their values
     *
     * @return string URL
     */
    protected function getUrl($dispatch, $query_params = array())
    {
        return Url::buildUrn($dispatch, $query_params);
    }
}