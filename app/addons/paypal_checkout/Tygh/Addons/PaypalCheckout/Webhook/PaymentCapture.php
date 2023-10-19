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

namespace Tygh\Addons\PaypalCheckout\Webhook;

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
     * PaymentCapture constructor.
     *
     * @param string $capture_id     Capture ID
     * @param string $capture_status Capture status
     * @param int    $order_id       Capture order ID
     * @param float  $total          Capture total
     */
    public function __construct($capture_id, $capture_status, $order_id, $total)
    {
        $this->capture_id = $capture_id;
        $this->capture_status = $capture_status;
        $this->order_id = $order_id;
        $this->total = $total;
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
     *     'paypal_checkout.capture_id': string,
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
         *     'paypal_checkout.capture_id': string,
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
}
