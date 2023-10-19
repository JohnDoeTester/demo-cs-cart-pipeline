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

namespace Tygh\Addons\PaypalCommercePlatform\Webhook;

class PaymentCapture
{
    /**
     * @var string
     */
    protected $capture_id;

    /**
     * @var string
     */
    protected $capture_status;

    /**
     * @var int
     */
    protected $order_id;

    /**
     * @var float
     */
    protected $total;

    /**
     * @var float
     */
    protected $platform_fee;

    /**
     * PaymentCapture constructor.
     *
     * @param string $capture_id     Capture ID
     * @param string $capture_status Capture status
     * @param int    $order_id       Capture order ID
     * @param float  $total          Capture total
     * @param float  $platform_fee   Capture platform fee
     */
    public function __construct($capture_id, $capture_status, $order_id, $total, $platform_fee)
    {
        $this->capture_id = $capture_id;
        $this->capture_status = $capture_status;
        $this->order_id = $order_id;
        $this->total = $total;
        $this->platform_fee = $platform_fee;
    }

    /**
     * Gets capture ID.
     *
     * @return string
     */
    public function getCaptureId()
    {
        return $this->capture_id;
    }

    /**
     * Gets capture status.
     *
     * @return string
     */
    public function getCaptureStatus()
    {
        return $this->capture_status;
    }

    /**
     * Gets capture order ID.
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Gets data of the referenced order.
     *
     * @return array<string, string>|null
     *
     * @psalm-return array{
     *   payment_id: int,
     *   payment_info: array{
     *     'paypal_commerce_platform.capture_id': string,
     *   },
     *   payment_method: array{
     *     processor_params: array{
     *       access_token: string,
     *       client_id: string,
     *       expiry_time: int,
     *       mode: string,
     *       secret: string,
     *       payer_id: string,
     *       currency: string,
     *     },
     *   },
     * }|null
     */
    public function getOrderInfo()
    {
        /** @psalm-var array{
         *   payment_id: int,
         *   payment_info: array{
         *     'paypal_commerce_platform.capture_id': string,
         *   },
         *   payment_method: array{
         *     processor_params: array{
         *       access_token: string,
         *       client_id: string,
         *       expiry_time: int,
         *       mode: string,
         *       secret: string,
         *       payer_id: string,
         *       currency: string,
         *     },
         *   },
         * }|false $order
         */
        $order = fn_get_order_info($this->getOrderId());
        if (!$order) {
            return null;
        }

        return $order;
    }

    /**
     * Gets capture total.
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Gets capture platform fee.
     *
     * @return float
     */
    public function getPlatformFee()
    {
        return $this->platform_fee;
    }

    /**
     * Gets capture withdrawal amount.
     *
     * @return float
     */
    public function getWithdrawalAmount()
    {
        return $this->getTotal() - $this->getPlatformFee();
    }

    /**
     * Gets ID of the referenced company.
     *
     * @return int|null
     */
    public function getCompanyId()
    {
        $order_info = $this->getOrderInfo();
        if (isset($order_info['company_id'])) {
            return (int) $order_info['company_id'];
        }

        return null;
    }
}
