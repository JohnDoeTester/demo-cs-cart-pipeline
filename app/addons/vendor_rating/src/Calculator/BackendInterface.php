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
 * Interface BackendInterface describes a backend for calculator that can evaluate formulas with variables.
 *
 * @package Tygh\Addons\VendorRating\Calculator
 */
interface BackendInterface
{
    /**
     * @param string                                          $formula
     * @param \Tygh\Addons\VendorRating\Calculator\Variable[] $variables
     *
     * @return int|float
     * @throws \Tygh\Addons\VendorRating\Exception\CalculationException
     * @throws \DivisionByZeroError
     */
    public function evaluate($formula, array $variables);
}
