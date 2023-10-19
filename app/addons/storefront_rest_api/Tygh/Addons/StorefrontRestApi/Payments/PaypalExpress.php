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

use Tygh\Common\OperationResult;
use Tygh\Tools\Url;

class PaypalExpress implements IRedirectionPayment
{
    protected $order_info = array();

    protected $auth_info = array();

    protected $payment_info = array();

    /** @var \Tygh\Addons\StorefrontRestApi\Payments\RedirectionPaymentDetailsBuilder $details_builder */
    protected $details_builder;

    /** @var \Tygh\Common\OperationResult $preparation_result */
    private $preparation_result;

    /**
     * PaypalExpress constructor.
     */
    public function __construct()
    {
        $this->details_builder = new RedirectionPaymentDetailsBuilder();
        $this->preparation_result = new OperationResult();
    }

    /** @inheritdoc */
    public function getDetails(array $request)
    {
        $paypal_response = $this->requestPaymentToken(
            $this->payment_info['payment_id'],
            $this->order_info['order_id'],
            $this->order_info
        );

        $this->preparation_result->setSuccess($this->isTokenCreated($paypal_response));

        if (isset($paypal_response['TOKEN'])) {
            $payment_link = $this->getPaymentUrl($this->payment_info, $paypal_response['TOKEN']);

            $this->preparation_result->setData(
                $this->details_builder
                    ->setMethod(RedirectionPaymentDetailsBuilder::GET)
                    ->setPaymentUrl($payment_link)
                    ->setReturnUrl(
                        $this->getUrl(
                            ['payment_notification', 'notify'],
                            [
                                'payment'       => 'paypal_express',
                                'order_id'      => $this->order_info['order_id'],
                                'is_mobile_app' => 1,
                            ]
                        )
                    )
                    ->setCancelUrl(
                        $this->getUrl(
                            ['payment_notification', 'cancel'],
                            [
                                'payment'       => 'paypal_express',
                                'order_id'      => $this->order_info['order_id'],
                                'is_mobile_app' => 1,
                            ]
                        )
                    )
                    ->asArray()
            );
        } else {
            $this->preparation_result->setErrors($this->getTokenCreationErrors($paypal_response));
        }

        return $this->preparation_result;
    }

    /** @inheritdoc */
    public function setOrderInfo(array $order_info)
    {
        $this->order_info = $order_info;

        return $this;
    }

    /** @inheritdoc */
    public function setAuthInfo(array $auth)
    {
        $this->auth_info = $auth;

        return $this;
    }

    /** @inheritdoc */
    public function setPaymentInfo(array $payment_info)
    {
        $this->payment_info = $payment_info;

        return $this;
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
        $order_info['extra']['return_url_params'] = [
            'is_mobile_app' => 1,
        ];
        $order_info['extra']['cancel_url_params'] = [
            'is_mobile_app' => 1,
        ];

        return fn_paypal_set_express_checkout($payment_id, $order_id, $order_info);
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
        return fn_paypal_ack_success($paypal_response);
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
        return fn_paypal_get_error($paypal_response, false, 'array');
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
        $payment_link = fn_paypal_payment_form($payment_info, $token, true);

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
        return fn_url(Url::buildUrn($dispatch, $query_params));
    }
}
