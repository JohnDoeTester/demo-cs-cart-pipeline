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

defined('BOOTSTRAP') or die('Access denied');
/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier<br>
 * Name:     unset_key<br>
 * Purpose:  destroys the specified array variable by key
 * Example:  {$a|unset_key:$b}
 * -------------------------------------------------------------
 * phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
 *
 * @param mixed[]    $array The array to work on
 * @param string|int $key   The variable to be delete
 *
 * @return mixed[]
 *
 * @package Smarty
 *
 * @subpackage plugins
 */
function smarty_modifier_unset_key(array $array, $key)
{
    unset($array[$key]);
    return $array;
}
