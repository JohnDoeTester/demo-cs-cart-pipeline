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

return [
    'fields' => [
        'product_id'   => ['title' => __('product_id'), 'sort_by' => ''],
        'product'      => ['title' => __('product_name'), 'sort_by' => 'product'],
        'min_qty'      => ['title' => __('min_order_qty'), 'sort_by' => ''],
        'max_qty'      => ['title' => __('max_order_qty'), 'sort_by' => ''],
        'product_code' => ['title' => __('sku'), 'sort_by' => 'code'],
        'amount'       => ['title' => __('quantity'), 'sort_by' => 'amount'],
        'price'        => ['title' => __('price'), 'sort_by' => 'price'],
        'weight'       => ['title' => __('weight'), 'sort_by' => 'weight'],
        'image'        => ['title' => __('image'), 'sort_by' => ''],
    ],
    'types'  => [
        'xlsx' => [
            'extension' => 'xlsx',
        ],
    ],
];
