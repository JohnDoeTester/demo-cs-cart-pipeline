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
 * 'copyright.txt' FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/


use Tygh\Addons\ProductVariations\Product\Sync\Table\MainTable;
use Tygh\Addons\ProductVariations\Product\Sync\Table\OneToManyViaPrimaryKeyTable;
use Tygh\Addons\ProductVariations\Product\Sync\CallableSyncItem;
use Tygh\Addons\ProductVariations\Product\Type\Type;

require_once __DIR__ . '/functions.php';

$excluded_fields = Type::create(PRODUCT_TYPE_VENDOR_PRODUCT_OFFER)->getFields();

$schema = [
    'products'                      => MainTable::create('products', 'product_id', $excluded_fields),
    'product_descriptions'          => OneToManyViaPrimaryKeyTable::create('product_descriptions', ['product_id', 'lang_code'], 'product_id', $excluded_fields),
    'product_global_option_links'   => OneToManyViaPrimaryKeyTable::create('product_global_option_links', ['product_id', 'option_id'], 'product_id'),
    'products_categories'           => OneToManyViaPrimaryKeyTable::create('products_categories', ['product_id', 'category_id'], 'product_id', [], ['after_sync_callback' => 'fn_master_products_sync_update_products_count']),
    'images_links'                  => OneToManyViaPrimaryKeyTable::create('images_links', ['object_id', 'image_id', 'detailed_id'], 'object_id', ['pair_id'], ['conditions' => ['object_type' => 'product']]),
    'product_features_values'       => OneToManyViaPrimaryKeyTable::create('product_features_values', ['product_id', 'feature_id', 'variant_id', 'lang_code'], 'product_id'),
    'product_status'                => CallableSyncItem::create('fn_master_products_sync_product_status'),
    'product_popularity'            => OneToManyViaPrimaryKeyTable::create('product_popularity', ['product_id'], 'product_id'),
    'product_tabs'                  => CallableSyncItem::create('fn_master_products_sync_product_tabs'),
    'bm_block_statuses'             => CallableSyncItem::create('fn_master_products_sync_bm_block_statuses'),
    'bm_blocks_content'             => CallableSyncItem::create('fn_master_products_sync_bm_blocks_content'),
    'bm_locations'                  => CallableSyncItem::create('fn_master_products_sync_bm_locations'),
];

return $schema;