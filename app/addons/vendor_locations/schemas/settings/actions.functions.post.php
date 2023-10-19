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

use Tygh\Addons\VendorLocations\Enum\FilterTypes;

/**
 * Disables filters on disable add-on
 *
 * @param string $status
 */
function fn_settings_actions_addons_post_vendor_locations($status)
{
    if ($status !== 'D') {
        return;
    }

    list($filters) = fn_get_product_filters(array('field_type' => FilterTypes::all()));

    foreach ($filters as $filter) {
        if ($filter['status'] === 'D') {
            continue;
        }
        fn_tools_update_status(array(
            'id' => $filter['filter_id'],
            'id_name' => 'filter_id',
            'status' => 'D',
            'table' => 'product_filters',
        ));
    }
}
