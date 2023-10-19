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

use Tygh\Enum\UserTypes;
use Tygh\Registry;
use Tygh\Enum\VendorPayoutTypes;
use Tygh\VendorPayouts;

/** @var array $schema */

$schema['vendor_plans'] = false;
$schema['paypal_adaptive'] = false;
$schema['ability_to_modify_styles'] = Registry::get('settings.Vendors.can_edit_styles') === 'Y';
$schema['ability_to_modify_layouts'] = Registry::get('settings.Vendors.can_edit_blocks') === 'Y';

$schema['automatic_vendor_payout_system'] = function () {
    $payouts_repository = VendorPayouts::instance();

    list($payouts) = $payouts_repository->getList(array(
        'payout_type' => array(VendorPayoutTypes::WITHDRAWAL),
        'time_from' => strtotime('-40 days'),
        'time_to' => TIME,
        'items_per_page' => 1,
    ));

    return !empty($payouts);
};

$schema['stripe_connect'] = false;
$schema['vendor_communication'] = false;
$schema['paypal_commerce_platform'] = false;
$schema['vendor_locations'] = false;
$schema['direct_payments'] = false;
$schema['vendor_debt_payout'] = false;
$schema['vendor_categories_fee'] = false;
$schema['vendor_privileges'] = false;
$schema['master_products'] = false;

$schema['mve_storefront_assigned_to_admin'] = static function () {
    return (bool) db_get_field(
        'SELECT COUNT(1) FROM ?:users as users'
        . ' WHERE users.user_type = ?s AND users.storefront_id > 0',
        UserTypes::ADMIN
    );
};

return $schema;