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

use Tygh\Enum\Addons\VendorDataPremoderation\ProductStatuses;
use Tygh\Enum\UserTypes;
use Tygh\Enum\VendorStatuses;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
$addon_setting = Registry::get('addons.vendor_data_premoderation');

if (Tygh::$app['session']['auth']['user_type'] === UserTypes::ADMIN) {
    if (
        $addon_setting['products_prior_approval'] === 'none'
        && $addon_setting['products_updates_approval'] === 'none'
        && $addon_setting['vendor_profile_updates_approval'] === 'none'
    ) {
        return $schema;
    }

    $schema['central']['vendors']['items']['vendor_data_premoderation.moderation'] = [
        'href'     => ($addon_setting['products_prior_approval'] !== 'none' || $addon_setting['products_updates_approval'] !== 'none')
            ? 'products.manage?status=' . ProductStatuses::REQUIRES_APPROVAL
            : 'companies.manage?status=' . VendorStatuses::PENDING,
        'position' => 110,
    ];

    if ($addon_setting['products_prior_approval'] !== 'none' || $addon_setting['products_updates_approval'] !== 'none') {
        $schema['central']['vendors']['items']['vendor_data_premoderation.moderation']['subitems']['products'] = [
            'href'     => 'products.manage?status=' . ProductStatuses::REQUIRES_APPROVAL,
            'alt'      => 'products.manage?status=' . ProductStatuses::DISAPPROVED,
            'position' => 10,
        ];
    }

    if ($addon_setting['vendor_profile_updates_approval'] !== 'none' || $addon_setting['vendors_prior_approval'] !== 'none') {
        $schema['central']['vendors']['items']['vendor_data_premoderation.moderation']['subitems']['vendors'] = [
            'href'     => 'companies.manage?status[]=' . VendorStatuses::PENDING . '&status[]=' . VendorStatuses::NEW_ACCOUNT,
            'position' => 20,
        ];
    }
} else {
    if ($addon_setting['products_prior_approval'] === 'none' && $addon_setting['products_updates_approval'] === 'none') {
        return $schema;
    }

    if (!isset($schema['central']['products']['items']['products']['subitems'])) {
        $schema['central']['products']['items']['products']['subitems'] = [];
    }

    $schema['central']['products']['items']['products']['subitems']['vendor_data_premoderation.require_approval'] = [
        'href'     => 'products.manage?status=' . ProductStatuses::REQUIRES_APPROVAL,
        'position' => 10,
    ];

    $schema['central']['products']['items']['products']['subitems']['vendor_data_premoderation.require_vendor_action'] = [
        'href'     => 'products.manage?status=' . ProductStatuses::DISAPPROVED,
        'position' => 20,
    ];
}

return $schema;
