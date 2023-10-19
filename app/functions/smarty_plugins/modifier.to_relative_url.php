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
 *
 * Type:    modifier
 * Name:    to_relative_url
 * Purpose: Converts an absolute url to a relative
 *
 * @param string $url Absolute url
 *
 * @return string
 */
function smarty_modifier_to_relative_url($url)
{
    $relative_url = '';
    $url = ltrim($url, '\\');
    $parsed_url = parse_url($url);

    if (
        !empty($parsed_url['path'])
        || !empty($parsed_url['query'])
    ) {
        $relative_url = sprintf('%s?%s', ltrim($parsed_url['path'] ?? '', '/'), $parsed_url['query'] ?? '');
    }

    return $relative_url;
}
