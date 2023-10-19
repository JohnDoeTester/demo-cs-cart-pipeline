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

namespace Tygh\Addons\VendorPlans;

class PriceFormatter
{
    /**
     * @var int
     */
    protected $decimals;

    /**
     * PriceRounder constructor.
     *
     * @param int $decimals
     */
    public function __construct($decimals)
    {
        $this->decimals = $decimals;
    }

    public function round($value)
    {
        $rounded_value = fn_format_rate_value(
            $value,
            'F',
            $this->decimals,
            '.',
            ''
        );

        return (float) $rounded_value;
    }
}
