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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

require_once (__DIR__ . '/blocks.functions.php');

$schema['products']['content']['items']['fillings']['product_variations.variations_filling'] = [
    'params' => [
        'request' => [
            'variations_by_product_id' => '%PRODUCT_ID%',
        ]
    ]
];
/**
 * @psalm-suppress InvalidArrayOffset
 */
$schema['products']['content']['items']['fillings']['manually']['params']['include_child_variations'] = true;

/**
 * @psalm-suppress InvalidArrayOffset
 */
$schema['products']['content']['items']['fillings']['manually']['params']['group_child_variations'] = false;

$schema['products']['cache']['callable_handlers']['variations_current_product_id'] = [
    'fn_product_variations_blocks_get_current_product_id', ['$block_data']
];
$schema['products']['cache']['update_handlers'][] = 'product_features';

$schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'product_variation_group_products';

return $schema;
