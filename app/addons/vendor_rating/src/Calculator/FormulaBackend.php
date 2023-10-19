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

use DivisionByZeroError;
use Exception;
use socialist\formula\Formula;
use Tygh\Addons\VendorRating\Exception\CalculationException;

/**
 * Class FormulaBackend implements a calculator backend that uses advanced formula evaluation.
 *
 * @package Tygh\Addons\VendorRating\Calculator
 */
class FormulaBackend implements BackendInterface
{
    /**
     * @param string                                          $formula
     * @param \Tygh\Addons\VendorRating\Calculator\Variable[] $variables
     *
     * @return float|int
     * @throws \Tygh\Addons\VendorRating\Exception\CalculationException
     * @throws \DivisionByZeroError
     */
    public function evaluate($formula, array $variables)
    {
        $formula = new Formula($formula);
        foreach ($variables as $variable) {
            $formula->setVariable($variable->getShortCode(), $variable->getValue());
        }

        try {
            $result = @$formula->calculate();
            if (is_infinite($result)) {
                throw new DivisionByZeroError();
            }
        } catch (Exception $e) {
            throw new CalculationException($e->getMessage(), $e->getCode());
        }

        return $result;
    }
}
