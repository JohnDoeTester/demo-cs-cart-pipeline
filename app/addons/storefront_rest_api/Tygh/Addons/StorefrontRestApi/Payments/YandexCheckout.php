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

use Exception;
use Tygh\Addons\YandexCheckout\Payments\YandexCheckout as YandexPayment;
use Tygh\Addons\YandexCheckout\ServiceProvider;
use Tygh\Common\OperationResult;

class YandexCheckout implements IRedirectionPayment
{
    /** @var array<string, float|string> $order_info */
    protected $order_info = [];

    /** @var array<string, string|int|array<string|int, int>> $auth_info */
    protected $auth_info = [];

    /** @var array<string, array<string, string>> $payment_info */
    protected $payment_info = [];

    /** @var \Tygh\Addons\StorefrontRestApi\Payments\RedirectionPaymentDetailsBuilder $details_builder */
    protected $details_builder;

    /** @var \Tygh\Common\OperationResult $preparation_result */
    private $preparation_result;

    /**
     * YandexCheckout constructor.
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
        $processor_params = $this->payment_info['processor_params'];

        $payment = new YandexPayment(
            empty($processor_params['shop_id']) ? null : $processor_params['shop_id'],
            empty($processor_params['scid']) ? null : $processor_params['scid'],
            ServiceProvider::getReceiptService()
        );

        try {
            $response = $payment->createPayment($this->order_info, $this->payment_info['processor_params']);
            /** @var \YooKassa\Model\Confirmation\ConfirmationRedirect $confirmation */
            $confirmation = $response->getConfirmation();
            $confirmation_url = $confirmation->getConfirmationUrl();

            fn_update_order_payment_info((int) $this->order_info['order_id'], ['payment_id' => $response->getId()]);

            $this->preparation_result->setSuccess(true);

            $this->preparation_result->setData(
                $this->details_builder
                    ->setMethod(RedirectionPaymentDetailsBuilder::GET)
                    ->setPaymentUrl($confirmation_url)
                    // FIXME: Add better successful return detection
                    ->setReturnUrl(fn_url('auth.login_form'))
                    ->setCancelUrl(fn_url('checkout.cart'))
                    ->asArray()
            );
        } catch (Exception $exception) {
            $payment->getLogger()->logException($exception);
            $this->preparation_result->addError((string) $exception->getCode(), $exception->getMessage());
            $this->preparation_result->setSuccess(false);
        }

        return $this->preparation_result;
    }
}
