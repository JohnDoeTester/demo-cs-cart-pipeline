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

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

return [
    'simple_mapping_fields'  => [
        'product' => 'Title',
        'full_description' => 'Body (HTML)',
        'price' => 'Variant Price',
        'Images' => 'Image Src',
        'amount' => 'Variant Inventory Qty',
        'page_title' => 'SEO Title',
        'meta_description' => 'SEO Description'
    ],
    'main_product_fields'    => [
        'Handle',
        'Title',
        'Body (HTML)',
        'Vendor'
    ],
    'variation_empty_fields' => [
        'Title',
        'Body (HTML)'
    ],
    'option_name_columns'    => [
        'Option1 Name',
        'Option2 Name',
        'Option3 Name'
    ]
];
