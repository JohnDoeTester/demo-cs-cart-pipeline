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

$schema['controllers']['payments'] = array(
    'permissions' => array(
        'GET'  => 'view_payments',
        'POST' => 'manage_payments',
    ),
);

$schema['controllers']['promotions'] = array(
    'permissions' => 'manage_promotions',
);

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;

$schema['controllers']['tools']['modes']['update_status']['param_permissions']['table']['payments'] =
$schema['controllers']['tools']['modes']['update_position']['param_permissions']['table']['payments'] =
    array('permissions' => 'manage_payments');

$schema['controllers']['tools']['modes']['update_status']['condition']['table']['payments'] =
    array(
        'operator' => 'and',
        'function' => array('fn_direct_payments_check_payment_owner', null, $id),
    );

$schema['controllers']['tools']['modes']['update_status']['param_permissions']['table']['promotions'] =
    array(
        'operator' => 'and',
        'function' => array('fn_direct_payments_check_promotion_owner', null, $id),
    );

return $schema;