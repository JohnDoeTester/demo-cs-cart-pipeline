<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier<br>
 * Name:     sizeof<br>
 * Purpose:  Counts all elements in an array, or something in an object
 * Example:  {$array|sizeof}
 * -------------------------------------------------------------
 */

/**
 * Counts all elements in an array, or something in an object
 *
 * @param mixed $value
 *
 * @return int
 */
function smarty_modifier_sizeof($value)
{
    if (is_array($value) || (is_object($value) && $value instanceof Countable)) {
        return count($value);
    }

    return 0;
}