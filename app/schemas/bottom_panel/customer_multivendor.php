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

$schema['products.view']['to_vendor']  = 'fn_bottom_panel_mve_get_product_url_params';
$schema['orders.details']['to_vendor'] = 'fn_bottom_panel_mve_get_order_url_params';
$schema['pages.view']['to_vendor']     = 'fn_bottom_panel_mve_get_page_url_params';

$schema['companies.view'] = [
    'from' => [
        'dispatch' => 'companies.view',
        'company_id'
    ],
    'to_admin' => [
        'dispatch' => 'companies.update',
        'company_id' => '%company_id%'
    ],
    'to_vendor' => 'fn_bottom_panel_mve_get_company_url_params'
];

$schema['companies.catalog'] = [
    'from' => [
        'dispatch' => 'companies.catalog',
    ],
    'to_admin' => [
        'dispatch' => 'companies.manage'
    ]
];

$schema['companies.products'] = [
    'from' => [
        'dispatch' => 'companies.products',
        'company_id'
    ],
    'to_admin' => [
        'dispatch'   => 'products.manage',
        'company_id' => '%company_id%'
    ],
    'to_vendor' => 'fn_bottom_panel_mve_get_company_products_url_params'
];

return $schema;