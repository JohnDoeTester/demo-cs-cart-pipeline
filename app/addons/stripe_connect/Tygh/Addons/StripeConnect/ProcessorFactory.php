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

namespace Tygh\Addons\StripeConnect;

use Tygh\Addons\StripeConnect\Payments\StripeConnect;
use Tygh\Database\Connection;

class ProcessorFactory
{
    /** @var \Tygh\Database\Connection */
    protected $db;

    /** @var \Tygh\Addons\StripeConnect\PriceFormatter */
    protected $price_formatter;

    /** @var array<string, string> */
    protected $settings;

    /**
     * ProcessorFactory constructor.
     *
     * @param \Tygh\Database\Connection                 $db              Database connection
     * @param \Tygh\Addons\StripeConnect\PriceFormatter $price_formatter Price formatter
     * @param array<string, string>                     $settings        Settings
     */
    public function __construct(
        Connection $db,
        PriceFormatter $price_formatter,
        array $settings
    ) {
        $this->db = $db;
        $this->price_formatter = $price_formatter;
        $this->settings = $settings;
    }

    /**
     * Constructs payment method processor with default components by the payment method ID.
     *
     * @param int                        $payment_id       Payment method ID
     * @param array<string, string>|null $processor_params Payment method configuration
     *
     * @return \Tygh\Addons\StripeConnect\Payments\StripeConnect
     */
    public function getByPaymentId($payment_id, array $processor_params = null)
    {
        return new StripeConnect(
            $payment_id,
            $this->db,
            $this->price_formatter,
            $this->settings,
            $processor_params
        );
    }
}
