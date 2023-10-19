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
 * Name:     normalize_url<br>
 * Purpose:  Appends protocol to URL if not exists.
 * Example:  {$url|normalize_url:true}
 * -------------------------------------------------------------
 */

use Tygh\Tools\Url;

function smarty_modifier_normalize_url($url, $secure = false)
{
    $url = new Url($url);

    if (!$url->getProtocol()) {
        $url->setProtocol($secure ? 'https' : 'http');
    }

    return $url->build();
}

/* vim: set expandtab: */