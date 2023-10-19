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

namespace Tygh\Addons\VendorRating\Calculator;

/**
 * Class Variable represents a variable used in a formula.
 *
 * @package Tygh\Addons\VendorRating\Calculator
 */
class Variable
{
    /** @var string */
    protected $short_code;

    /** @var string */
    protected $long_code;

    /** @var int|float */
    protected $value;

    public function __construct($short_code, $long_code, $value)
    {
        $this->short_code = $short_code;
        $this->long_code = $long_code;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getShortCode()
    {
        return $this->short_code;
    }

    /**
     * @return string
     */
    public function getLongCode()
    {
        return $this->long_code;
    }

    /**
     * @return float|int
     */
    public function getValue()
    {
        return $this->value;
    }
}
