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

/** @var array $schema */
$schema['controllers']['order_management']['permissions'] = true;
$schema['controllers']['order_management']['condition'] = [
    'operator' => 'and',
    'function' => ['fn_vendor_privileges_check_permission_order_management'],
];

$schema['controllers']['exim']['modes']['import']['param_permissions']['section']['orders']['permissions'] = true;
$schema['controllers']['exim']['modes']['import']['section']['orders']['condition'] = [
    'operator' => 'and',
    'function' => ['fn_vendor_privileges_check_permission_order_management'],
];
$schema['import']['sections']['orders']['permission'] = true;
unset($schema['controllers']['discussion']['modes']['products_and_pages']);
return $schema;
