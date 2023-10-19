<?php

namespace Tygh\Addons\StorefrontRestApi\Payments;

use Tygh\Common\OperationResult;

class YandexCheckpoint implements IRedirectionPayment
{
    protected $order_info = array();

    protected $auth_info = array();

    protected $payment_info = array();

    /** @var \Tygh\Addons\StorefrontRestApi\Payments\RedirectionPaymentDetailsBuilder $details_builder */
    protected $details_builder;

    /** @var \Tygh\Common\OperationResult $preparation_result */
    private $preparation_result;

    /**
     * YandexCheckpoint constructor.
     */
    public function __construct()
    {
        $this->details_builder = new RedirectionPaymentDetailsBuilder();
        $this->preparation_result = new OperationResult();
    }

    /** @inheritdoc */
    public function setOrderInfo(array $order_info)
    {
        $this->order_info = $order_info;

        return $this;
    }

    /** @inheritdoc */
    public function setAuthInfo(array $auth_info)
    {
        $this->auth_info = $auth_info;

        return $this;
    }

    /** @inheritdoc */
    public function setPaymentInfo(array $payment_info)
    {
        $this->payment_info = $payment_info;

        return $this;
    }

    /** @inheritdoc */
    public function getDetails(array $request)
    {
        $payment_url = $this->getPaymentUrl();
        $payment_request = $this->getPaymentRequest($request);

        $this->preparation_result->setSuccess(true);

        $this->preparation_result->setData(
            $this->details_builder
                ->setMethod(RedirectionPaymentDetailsBuilder::POST)
                ->setPaymentUrl($payment_url)
                ->setQueryParameters($payment_request)
                ->setReturnUrl($payment_request['shopSuccessURL'])
                ->setCancelUrl($payment_request['shopDefaultUrl'])
                ->asArray()
        );

        $this->setPaymentValidationData($payment_request);

        $this->logPaymentRequest($payment_request);

        return $this->preparation_result;
    }

    /**
     * Gets URL to submit payment request to.
     *
     * @return string URL
     */
    protected function getPaymentUrl()
    {
        $payment_url = fn_rus_payments_yandex_checkpoint_get_payment_url(
            $this->payment_info['processor_params']['mode']
        );

        return $payment_url;
    }

    /**
     * Gets payment request parameters.
     *
     * @param array $request Payment info (submited on checkout page)
     *
     * @return array Payment request
     */
    protected function getPaymentRequest(array $request)
    {
        $payment_request = fn_rus_payments_yandex_checkpoint_get_payment_request(
            $this->order_info,
            $this->payment_info,
            $request
        );

        return $payment_request;
    }

    /**
     * Stores some payment data for further payment request validation.
     *
     * @param array $payment_request Payment request
     *
     * @return bool
     */
    protected function setPaymentValidationData(array $payment_request)
    {
        return fn_rus_payments_yandex_checkpoint_set_payment_validation_data(
            $this->order_info['order_id'],
            $payment_request
        );
    }

    /**
     * Logs payment request.
     *
     * @param array $payment_request Payment request
     *
     * @return bool Whether data was logged
     */
    protected function logPaymentRequest(array $payment_request)
    {
        if (!empty($this->payment_info['processor_params']['logging'])
            && $this->payment_info['processor_params']['logging'] == 'Y'
        ) {
            fn_yandex_money_log_write($payment_request, 'ym_post_data.log');

            return true;
        }

        return false;
    }
}