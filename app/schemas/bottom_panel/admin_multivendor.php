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

use Tygh\Registry;

include_once(Registry::get('config.dir.schemas') . 'bottom_panel/vendor.functions.php');

/**
 * @psalm-var array $schema
 */

$schema['products.update']['to_vendor'] = 'fn_bottom_panel_mve_get_product_url_params';
$schema['orders.details']['to_vendor']  = 'fn_bottom_panel_mve_get_order_url_params';
$schema['pages.update']['to_vendor']    = 'fn_bottom_panel_mve_get_page_url_params';
$schema['orders.manage']['to_customer']['storefront_id'] = '%storefront_id%';

$schema['companies.update'] = [
    'from' => [
        'dispatch' => 'companies.update',
        'company_id'
    ],
    'to_customer' => [
        'dispatch' => 'companies.view',
        'company_id' => '%company_id%'
    ],
    'to_vendor' => 'fn_bottom_panel_mve_get_company_url_params'
];

$schema['companies.manage'] = [
    'from' => [
        'dispatch' => 'companies.manage',
    ],
    'to_customer' => [
        'dispatch' => 'companies.catalog'
    ]
];

return $schema;
