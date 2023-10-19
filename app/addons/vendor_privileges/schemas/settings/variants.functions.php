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


/**
 * Prepares array of available vendor administrator user groups
 *
 * @return array
 */
function fn_settings_variants_addons_vendor_privileges_default_vendor_usesrgroup()
{
    $variants = [
        0 => __('none'),
    ];

    if (!defined('USERGROUP_TYPE_VENDOR')) {
        return $variants;
    }

    $params = [
        'type'   => USERGROUP_TYPE_VENDOR,
        'status' => 'A',
    ];

    $usergroups = fn_get_usergroups($params);

    foreach ($usergroups as $usergroup) {
        $variants[$usergroup['usergroup_id']] = $usergroup['usergroup'];
    }

    return $variants;
}