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

require_once __DIR__ . '/blocks.functions.php';

$schema['products']['content']['items']['fillings']['master_products.vendor_products_filling'] = [
    'params' => [
        'sort_by'                  => 'price',
        'is_vendor_products_list'  => true,
        'include_child_variations' => true,
        'group_child_variations'   => false,
        'request'                  => [
            'vendor_products_by_product_id' => '%PRODUCT_ID%',
            'master_product_combination' => '%combination%',
            'master_product_data' => '%product_data%',
        ],
    ],
];

$schema['products']['cache']['callable_handlers']['current_master_product_id'] = ['fn_master_products_blocks_get_current_master_product_id', ['$block_data']];
$schema['products']['cache']['disable_cache_when']['callable_handlers']['reload_vendor_product_list'] = ['fn_master_products_blocks_disable_cache_handler', ['$block_data']];

/** @psalm-suppress PossiblyInvalidArrayOffset */
$schema['products']['content']['items']['fillings']['manually']['picker_params']['additional_query_params'] = [
    'selecting_for_customer_area' => true
];

return $schema;
