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

namespace Tygh\Addons\PaypalCheckout;

use Tygh\Addons\PaypalCheckout\Api\ClientWrapper;
use Tygh\Addons\PaypalCheckout\Payments\PaypalCheckout;
use Tygh\Database\Connection;

class ProcessorFactory
{
    /** @var \Tygh\Database\Connection */
    protected $db;

    /** @var array<string, string> */
    protected $status_conversion_schema;

    /** @var string */
    protected $tax_calculation_method;

    /**
     * ProcessorFactory constructor.
     *
     * @param \Tygh\Database\Connection $db                       Database connection
     * @param array<string, string>     $status_conversion_schema Order status conversion schema
     * @param string                    $tax_calculation_method   Tax calculation method. Either
     *                                                            unit_price or subtotal
     */
    public function __construct(
        Connection $db,
        array $status_conversion_schema,
        $tax_calculation_method
    ) {
        $this->db = $db;
        $this->status_conversion_schema = $status_conversion_schema;
        $this->tax_calculation_method = $tax_calculation_method;
    }

    /**
     * Constructs payment method processor with default components by the payment method ID.
     *
     * @param int                        $payment_id       Payment method ID
     * @param array<string, string>|null $processor_params Payment method configuration
     *
     * @psalm-param array{
     *   access_token: string,
     *   client_id: string,
     *   expiry_time: int,
     *   mode: string,
     *   secret: string,
     *   payer_id: string,
     *   currency: string
     * } $processor_params
     *
     * @return \Tygh\Addons\PaypalCheckout\Payments\PaypalCheckout
     */
    public function getByPaymentId($payment_id, array $processor_params = null)
    {
        if ($processor_params === null) {
            $processor_params = PaypalCheckout::getProcessorParameters($payment_id);
        }

        return new PaypalCheckout(
            $payment_id,
            $processor_params,
            $this->status_conversion_schema,
            $this->db,
            new ClientWrapper($payment_id, $processor_params, $this->db),
            $this->tax_calculation_method
        );
    }
}
