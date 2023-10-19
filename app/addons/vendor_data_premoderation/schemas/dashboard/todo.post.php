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
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Tools\Url;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @var array<string, array> $schema
 */

$schema['vendor_data_premoderation_products_disapproved'] = [
    'type'             => NotificationSeverity::NOTICE,
    'area'             => SiteArea::VENDOR_PANEL,
    'content_callback' => static function ($auth) {
        if (!fn_check_permissions('products', 'update', 'admin')) {
            return false;
        }

        $product_ids = db_get_fields(
            'SELECT product_id FROM ?:products WHERE company_id = ?s AND status = ?s',
            $auth['company_id'],
            ProductStatuses::DISAPPROVED
        );

        if (empty($product_ids)) {
            return false;
        }

        $products_count = count($product_ids);

        return [
            'text' => __('vendor_data_premoderation.dashboard.todo.products_disapproved', [$products_count]),
            'action_url' => Url::buildUrn(['products', 'manage'], ['pid' => $product_ids])
        ];
    }
];

$schema['vendor_data_premoderation_pending_vendors'] = [
    'type'             => NotificationSeverity::NOTICE,
    'area'             => SiteArea::ADMIN_PANEL,
    'content_callback' => static function ($auth) {
        if (!fn_check_permissions('companies', 'update', 'admin')) {
            return false;
        }

        $companies_count = db_get_field(
            'SELECT COUNT(*) FROM ?:companies WHERE status = ?s',
            ObjectStatuses::PENDING
        );

        if (empty($companies_count)) {
            return false;
        }

        return [
            'text' => __('vendor_data_premoderation.dashboard.todo.pending_vendors', [$companies_count]),
            'action_url' => Url::buildUrn(['companies', 'manage'], ['status' => ObjectStatuses::PENDING])
        ];
    }
];

$schema['vendor_data_premoderation_products_require_approval'] = [
    'type'             => NotificationSeverity::NOTICE,
    'area'             => SiteArea::ADMIN_PANEL,
    'content_callback' => static function ($auth) {
        if (!fn_check_permissions('premoderation', 'm_approve', 'admin')) {
            return false;
        }

        $products_count = db_get_field(
            'SELECT COUNT(*) FROM ?:products WHERE status = ?s',
            ProductStatuses::REQUIRES_APPROVAL
        );

        if (empty($products_count)) {
            return false;
        }

        return [
            'text' => __('vendor_data_premoderation.dashboard.todo.products_require_approval', [$products_count]),
            'action_url' => Url::buildUrn(['products', 'manage'], ['status' => ProductStatuses::REQUIRES_APPROVAL])
        ];
    }
];

return $schema;
