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

use Tygh\Addons\VendorRating\Exception\UnknownVariableException;
use Tygh\Addons\VendorRating\Exception\VariableCountException;

/**
 * Class Calculator operates mathematical formulas with variables in them.
 *
 * @package Tygh\Addons\VendorRating\Calculator
 */
class Calculator
{
    /**
     * @var \Tygh\Addons\VendorRating\Calculator\BackendInterface
     */
    protected $calculator_backend;

    public function __construct(BackendInterface $calculator_backend)
    {
        $this->calculator_backend = $calculator_backend;
    }

    /**
     * @param string        $formula
     * @param int[]|float[] $variable_values
     *
     * @return float
     * @throws \Tygh\Addons\VendorRating\Exception\CalculationException When expression can't be calculated
     * @throws \Tygh\Addons\VendorRating\Exception\VariableCountException When too many variables passed to the function
     * @throws \Tygh\Addons\VendorRating\Exception\UnknownVariableException When unknown variables are used in schema
     */
    public function calculate($formula, array $variable_values)
    {
        $variables = $this->initVariables($variable_values);

        $short_formula = $this->setVariables($formula, $variables);

        $result = $this->calculator_backend->evaluate($short_formula, $variables);

        return $result;
    }

    /**
     * @param int[]|float[] $variable_values
     *
     * @return \Tygh\Addons\VendorRating\Calculator\Variable[]
     * @throws \Tygh\Addons\VendorRating\Exception\VariableCountException
     */
    public function initVariables(array $variable_values)
    {
        if (count($variable_values) > count(range('a', 'z'))) {
            $exception = new VariableCountException();
            $exception->setAllowedVariablesCount(count(range('a', 'z')));
            $exception->setPassedVariablesCount(count($variable_values));
            throw $exception;
        }

        uksort(
            $variable_values,
            function ($v1, $v2) {
                if (strlen($v1) === strlen($v2)) {
                    return 0;
                }
                return strlen($v1) < strlen($v2) ? 1 : -1;
            }
        );

        $rebuilt_variables = [];
        $short_code = 'a';
        foreach ($variable_values as $long_code => $value) {
            $rebuilt_variables[] = new Variable($short_code, $long_code, $value);
            $short_code++;
        }

        return $rebuilt_variables;
    }

    /**
     * @param string                                          $formula
     * @param \Tygh\Addons\VendorRating\Calculator\Variable[] $variables
     *
     * @return string
     * @throws \Tygh\Addons\VendorRating\Exception\UnknownVariableException
     */
    public function setVariables($formula, array $variables)
    {
        $replacements = [];
        foreach ($variables as $variable) {
            $replacements[$variable->getLongCode()] = $variable->getShortCode();
        }

        foreach ($this->extractVariables($formula) as $long_code) {
            if (!isset($replacements[$long_code])) {
                $exception = new UnknownVariableException();
                $exception->setVariable($long_code);
                throw $exception;
            }
        }

        $formula = strtr($formula, $replacements);

        $formula .= ' + 0';

        return $formula;
    }

    /**
     * @param string $formula
     *
     * @return string[]
     */
    public function extractVariables($formula)
    {
        if (preg_match_all('/[a-z]+/i', $formula, $matches)) {
            return array_unique($matches[0]);
        }

        return [];
    }
}
