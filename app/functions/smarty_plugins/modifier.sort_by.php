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
 * Name:     sort_by<br>
 * Purpose:  allows arrays of named arrays to be sorted by a given field
 * Example:  {$fields|@sort_by:"-name, #age"}
 * ------
 *
 *  -------------------------------------------------------
 */

//
// Modifier: sortby -
//
function smarty_modifier_sort_by($arrData, $sortfields)
{
    array_sort_by_fields($arrData, $sortfields);

    return $arrData;
}

function array_sort_by_fields(&$data, $sortby)
{
    $sortby = fn_explode(',', $sortby);

    uasort($data, function ($a, $b) use ($sortby) {
        foreach ($sortby as $key) {
            $d = 1;

            if (substr($key, 0, 1) == '-') {
                $d = -1;
                $key = substr($key, 1);
            }

            if (substr($key, 0, 1) == '#') {
                $key = substr($key, 1);

                if ($a[$key] > $b[$key]) {
                    return $d;
                } elseif ($a[$key] < $b[$key]) {
                    return $d * -1;
                }
            } elseif (($c = strcasecmp($a[$key], $b[$key])) != 0) {
                return $d * $c;
            }
        }
        return 0;
    });
}

/* vim: set expandtab: */
