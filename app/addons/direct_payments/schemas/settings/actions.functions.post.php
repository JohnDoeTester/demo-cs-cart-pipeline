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

use Tygh\Enum\ObjectStatuses;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/**
 * The `fn_settings_actions_addons_` handler.
 *
 * Perform product bundle promotions re-assignment when add-on changing its status.
 *
 * @param string $new_status New add-on status.
 * @param string $old_status Old add-on status.
 * @param bool   $on_install True if current status change is part of installation process, false otherwise.
 *
 * @return void
 */
function fn_settings_actions_addons_direct_payments($new_status, $old_status, $on_install)
{
    if ($on_install || empty(Registry::get('addons.product_bundles'))) {
        return;
    }
    if ($new_status === ObjectStatuses::ACTIVE) {
        $promotions_data = db_get_hash_multi_array('SELECT company_id, linked_promotion_id FROM ?:product_bundles', ['company_id']);
        if (empty($promotions_data)) {
            return;
        }
        foreach ($promotions_data as $company_id => $promotion_data) {
            $promotion_ids = array_column($promotion_data, 'linked_promotion_id');
            db_query('UPDATE ?:promotions SET company_id = ?i WHERE promotion_id IN (?n)', $company_id, $promotion_ids);
        }
    } else {
        $promotion_ids = db_get_fields('SELECT linked_promotion_id FROM ?:product_bundles');
        if (empty($promotion_ids)) {
            return;
        }
        db_query('UPDATE ?:promotions SET company_id = ?i WHERE promotion_id IN (?n)', 0, $promotion_ids);
    }
}
