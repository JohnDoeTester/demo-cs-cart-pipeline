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

use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Addons\MasterProducts\Product\Repository;

/**
 * Updates mater product status for vendor products
 *
 * @param int   $product_id
 * @param int[] $destination_product_ids
 */
function fn_master_products_sync_product_status($product_id, $destination_product_ids)
{
    $query = ServiceProvider::getQueryFactory()->createQuery(
        Repository::TABLE_PRODUCTS,
        ['product_id' => $product_id],
        ['status']
    );

    $status = $query->scalar();

    $query = ServiceProvider::getQueryFactory()->createQuery(
        Repository::TABLE_PRODUCTS,
        ['product_id' => $destination_product_ids]
    );

    $query->update(['master_product_status' => $status]);
}

function fn_master_products_sync_update_products_count($source_product_id, $destination_product_ids, $source_data_list, $update_pk_list, $insert_pk_list, $delete_pk_list)
{
    $category_ids = array_merge(array_column($insert_pk_list, 'category_id'), array_column($delete_pk_list, 'category_id'));

    if ($category_ids) {
        fn_update_product_count($category_ids);
    }
}

/**
 * Sync product tabs data
 *
 * @param int                   $product_id              Product identifier
 * @param array<int>            $destination_product_ids Destination product identifiers
 * @param array<string, string> $conditions              Condition of the sync
 */
function fn_master_products_sync_product_tabs($product_id, array $destination_product_ids, array $conditions)
{
    $query = ServiceProvider::getQueryFactory()->createQuery('product_tabs', [], ['tab_id', 'product_ids']);
    $query->addCondition("product_ids != ''");

    if (isset($conditions['tab_id'])) {
        $query->addConditions(['tab_id' => $conditions['tab_id']]);
    }

    $list = $query->select();

    foreach ($list as $item) {
        $item['product_ids'] = $product_ids = fn_explode(',', $item['product_ids']);

        if (!in_array($product_id, $product_ids)) {
            $product_ids = array_diff($product_ids, $destination_product_ids);
        } elseif (array_diff($destination_product_ids, $product_ids)) {
            $product_ids = array_merge($product_ids, $destination_product_ids);
            $product_ids = array_unique($product_ids);
        }

        if ($product_ids === $item['product_ids']) {
            continue;
        }
        $query = ServiceProvider::getQueryFactory()->createQuery('product_tabs');
        $query->addConditions(['tab_id' => $item['tab_id']]);
        $query->update(['product_ids' => implode(',', $product_ids)]);
    }
}

/**
 * Sync block statuses data
 *
 * @param int                   $product_id              Product identifier
 * @param array<int>            $destination_product_ids Destination product identifiers
 * @param array<string, string> $conditions              Condition of the sync
 *
 * @return void
 */
function fn_master_products_sync_bm_block_statuses($product_id, array $destination_product_ids, array $conditions)
{
    $query = ServiceProvider::getQueryFactory()->createQuery(
        'bm_block_statuses',
        ['object_type' => 'products'],
        ['*']
    );

    $query->addCondition("object_ids != ''");

    if (isset($conditions['snapping_id'])) {
        $query->addConditions(['snapping_id' => $conditions['snapping_id']]);
    }

    $list = $query->select();

    foreach ($list as $item) {
        $product_ids = $item['object_ids'] = fn_explode(',', $item['object_ids']);

        if (!in_array($product_id, $product_ids)) {
            $product_ids = array_diff($product_ids, $destination_product_ids);
        } elseif (array_diff($destination_product_ids, $product_ids)) {
            $product_ids = array_merge($product_ids, $destination_product_ids);
            $product_ids = array_unique($product_ids);
        }

        if ($product_ids === $item['object_ids']) {
            continue;
        }
        $query = ServiceProvider::getQueryFactory()->createQuery('bm_block_statuses');
        $query->addConditions(['snapping_id' => $item['snapping_id'], 'object_type' => 'products']);

        if ($product_ids) {
            $query->update(['object_ids' => implode(',', $product_ids)]);
        } else {
            $query->delete();
        }
    }
}

/**
 * Sync block content data
 *
 * @param int                   $product_id              Product identifier
 * @param array<int>            $destination_product_ids Destination product identifiers
 * @param array<string, string> $conditions              Condition of the sync
 *
 * @return void
 */
function fn_master_products_sync_bm_blocks_content($product_id, array $destination_product_ids, array $conditions)
{
    $query = ServiceProvider::getQueryFactory()->createQuery(
        'bm_blocks_content',
        ['object_type' => 'products', 'object_id' => $product_id],
        ['*']
    );

    if (isset($conditions['block_id'])) {
        $query->addConditions(['block_id' => $conditions['block_id']]);
    }

    if (isset($conditions['snapping_id'])) {
        $query->addConditions(['snapping_id' => $conditions['snapping_id']]);
    }

    if (isset($conditions['lang_code'])) {
        $query->addConditions(['lang_code' => $conditions['lang_code']]);
    }

    $data = $query->select();

    if ($data) {
        foreach ($destination_product_ids as $destination_product_id) {
            foreach ($data as $fields) {
                $fields['object_id'] = $destination_product_id;
                db_replace_into('bm_blocks_content', $fields);
            }
        }
    } else {
        $query = ServiceProvider::getQueryFactory()->createQuery(
            'bm_blocks_content',
            ['object_type' => 'products', 'object_id' => $destination_product_ids]
        );

        if (isset($conditions['block_id'])) {
            $query->addConditions(['block_id' => $conditions['block_id']]);
        }

        if (isset($conditions['snapping_id'])) {
            $query->addConditions(['snapping_id' => $conditions['snapping_id']]);
        }

        if (isset($conditions['lang_code'])) {
            $query->addConditions(['lang_code' => $conditions['lang_code']]);
        }

        $query->delete();
    }
}

/**
 * Sync block location data
 *
 * @param int                   $product_id              Product identifier
 * @param array<int>            $destination_product_ids Destination product identifiers
 * @param array<string, string> $conditions              Condition of the sync
 *
 * @return void
 */
function fn_master_products_sync_bm_locations($product_id, array $destination_product_ids, array $conditions)
{
    $query = ServiceProvider::getQueryFactory()->createQuery(
        'bm_locations',
        ['dispatch' => 'products.view'],
        ['location_id', 'object_ids']
    );

    if (isset($conditions['location_id'])) {
        $query->addConditions(['location_id' => $conditions['location_id']]);
    }

    $list = $query->select();

    foreach ($list as $item) {
        $item['object_ids'] = $product_ids = fn_explode(',', $item['object_ids']);

        if (!in_array($product_id, $product_ids)) {
            $product_ids = array_diff($product_ids, $destination_product_ids);
        } elseif (array_diff($destination_product_ids, $product_ids)) {
            $product_ids = array_merge($product_ids, $destination_product_ids);
            $product_ids = array_unique($product_ids);
        }

        if ($product_ids === $item['object_ids']) {
            continue;
        }
        $query = ServiceProvider::getQueryFactory()->createQuery('bm_locations');
        $query->addConditions(['location_id' => $item['location_id']]);
        $query->update(['object_ids' => implode(',', $product_ids)]);
    }
}
