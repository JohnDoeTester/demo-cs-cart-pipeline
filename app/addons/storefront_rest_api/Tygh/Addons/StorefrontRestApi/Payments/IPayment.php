<?php

namespace Tygh\Addons\StorefrontRestApi\Payments;

interface IPayment
{
    /**
     * @param array $order_info
     *
     * @return \Tygh\Addons\StorefrontRestApi\Payments\IPayment
     */
    public function setOrderInfo(array $order_info);

    /**
     * @param array $auth_info
     *
     * @return \Tygh\Addons\StorefrontRestApi\Payments\IPayment
     */
    public function setAuthInfo(array $auth_info);

    /**
     * @param array $payment_info
     *
     * @return \Tygh\Addons\StorefrontRestApi\Payments\IPayment
     */
    public function setPaymentInfo(array $payment_info);
}