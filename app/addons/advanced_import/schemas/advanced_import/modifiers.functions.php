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
 * 'copyright.txt' FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

defined('BOOTSTRAP') or die('Access denied');

/**
 * Evaluates predicate to boolean value
 *
 * @param string $predicate Predicate expression
 *
 * @return bool
 */
function fn_advance_import_evaluate_predicate_expression($predicate)
{
    $result = false;
    $allowed_operators = array('<=', '>=', '!=', '=', '<', '>'); // composite operators must come before their separate components

    foreach ($allowed_operators as $operator) {
        $operands = explode($operator, $predicate);

        if (count($operands) !== 2) {
            continue;
        }

        list($f_operand, $s_operand) = array_map(function ($operand) {
            return trim(
                trim($operand, ' '),
                '"\'');
        }, $operands);

        switch ($operator) {
            case '=':
                $result = $f_operand == $s_operand;
                break;
            case '!=':
                $result = $f_operand != $s_operand;
                break;
            case '<':
                $result = $f_operand < $s_operand;
                break;
            case '>':
                $result = $f_operand > $s_operand;
                break;
            case '<=':
                $result = $f_operand <= $s_operand;
                break;
            case '>=':
                $result = $f_operand >= $s_operand;
                break;
        }

        break;
    }

    return $result;
}

/**
 * Normalizes numeric value before applying math operation
 *
 * @param mixed $value Numeric value
 *
 * @return float
 */
function fn_advance_import_normalize_numeric_value($value)
{
    if (is_string($value)) {
        return (float) str_replace(',', '.', $value);
    }

    return (float) $value;
}
