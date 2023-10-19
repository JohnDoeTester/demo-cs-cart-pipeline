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
use Tygh\Addons\ProductVariations\ServiceProvider;

require_once(__DIR__ . '/functions.php');

/**
 * This schema describes the data sources that will be used during the syncing of variation data.
 * Example:
 *  MainTable::create('products', 'product_id', ['product_type', 'parent_product_id', 'product_code', 'timestamp', 'updated_timestamp']),
 *  It describes the 'products' table with the 'product_id' primary key, and excludes the following fields during syncing: ['product_type', 'parent_product_id', 'product_code', 'timestamp', 'updated_timestamp']
 *
 * Here are the possible data source descriptions:
 *  * \Tygh\Addons\ProductVariations\Product\Sync\Table\MainTable - describes the basic table - 'products' - and is applicable only to tables with one-to-one relationship by the primary key.
 *  * \Tygh\Addons\ProductVariations\Product\Sync\Table\OneToManyViaPrimaryKeyTable - describes the tables with one-to-many relationship via the composite primary key (for example, the 'product descriptions' table).
 *  * \Tygh\Addons\ProductVariations\Product\Sync\Table\OneToManyViaFieldTable - describes the tables with one-to-many relationship via a separate field (for example, the 'buy_together' table in the buy_together add-on).
 *      Use with caution, because this approach requires an additional table for proper mapping of data.
 *  * \Tygh\Addons\ProductVariations\Product\Sync\Table\OneToManyViaRelationTable - describes the tables with one-to-many relationship via another table (for example, the 'buy_together_descriptions' table in the buy_together add-on).
 *      Use with caution, because this approach requires an additional table for proper mapping of data.
 *
 *  * \Tygh\Addons\ProductVariations\Product\Sync\CallableSyncItem - serves to sync/copy arbitrary data types; to achieve this, create your own handler function and specify it in the CallableSyncItem constructor.
 *  * Any other custom data sources that implement the following interface: \Tygh\Addons\ProductVariations\Product\Sync\ISyncItem
 */

$excluded_fields = Type::create(Type::PRODUCT_TYPE_VARIATION)->getFields();

$schema = [
    'products'                      => MainTable::create('products', 'product_id', $excluded_fields),
    'product_descriptions'          => OneToManyViaPrimaryKeyTable::create('product_descriptions', ['product_id', 'lang_code'], 'product_id', $excluded_fields),
    'product_global_option_links'   => OneToManyViaPrimaryKeyTable::create('product_global_option_links', ['product_id', 'option_id'], 'product_id'),
    'products_categories'           => OneToManyViaPrimaryKeyTable::create('products_categories', ['product_id', 'category_id'], 'product_id', $excluded_fields, ['after_sync_callback' => 'fn_product_variations_sync_after_sync_categories']),
    'products_categories_position'  => OneToManyViaPrimaryKeyTable::create('products_categories', ['product_id', 'category_id'], 'product_id'),
    'product_popularity'            => OneToManyViaPrimaryKeyTable::create('product_popularity', ['product_id'], 'product_id'),
    'bm_block_statuses'             => CallableSyncItem::create('fn_product_variations_sync_bm_block_statuses'),
    'bm_blocks_content'             => CallableSyncItem::create('fn_product_variations_sync_bm_blocks_content'),
    'bm_locations'                  => CallableSyncItem::create('fn_product_variations_sync_bm_locations'),
    'product_tabs'                  => CallableSyncItem::create('fn_product_variations_sync_product_tabs'),
];

if (fn_allowed_for('ULTIMATE')) {
    $schema['ult_product_descriptions'] = OneToManyViaPrimaryKeyTable::create('ult_product_descriptions', ['product_id', 'lang_code', 'company_id'], 'product_id', $excluded_fields);
}

if (!ServiceProvider::isAllowOwnImages()) {
    $schema['images_links'] = OneToManyViaPrimaryKeyTable::create(
        'images_links',
        ['object_id', 'image_id', 'detailed_id'],
        'object_id',
        ['pair_id'],
        ['conditions' => ['object_type' => 'product'], 'after_sync_callback' => 'fn_product_variations_sync_delete_variation_images']
    );
}

if (!ServiceProvider::isAllowOwnFeatures()) {
    $schema['product_features_values'] = OneToManyViaPrimaryKeyTable::create(
        'product_features_values',
        ['product_id', 'feature_id', 'variant_id', 'lang_code'],
        'product_id', [],
        ['conditions' => 'fn_product_variations_get_product_sync_feature_conditions']
    );
}

return $schema;
