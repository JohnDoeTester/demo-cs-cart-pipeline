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


namespace Tygh\Addons\ProductVariations\HookHandlers;


use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Application;

/**
 * This class describes the hook handlers related to the block manager.
 *
 * @package Tygh\Addons\ProductVariations\HookHandlers
 */
class BlockManagerHookHandler
{
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "update_location" hook handler.
     *
     * Actions performed:
     *  - When the settings of the products.view page are updated, this hook handler will save the list of products to which this page was applied.
     *      This will be necessary later for determining what products were added/removed from the page settings.
     *
     * @see \Tygh\BlockManager\Location::update
     */
    public function onUpdateLocation(&$location_data)
    {
        if (empty($location_data['location_id'])
            || empty($location_data['dispatch'])
            || $location_data['dispatch'] !== 'products.view'
        ) {
            return;
        }

        $query = ServiceProvider::getQueryFactory()->createQuery(
            'bm_locations',
            ['location_id' => $location_data['location_id']],
            ['object_ids']
        );

        $location_data['current_object_ids'] = $query->scalar();
    }

    /**
     * The "update_location_post" hook handler.
     *
     * Actions performed:
     *  - If variations were added or removed when the settings of the products.view page, this hook handler will launch syncing for these products.
     *      This is necessary because the product page of a child variation must not differ from the product page of its parent product.
     *
     * @see \Tygh\BlockManager\Block::update
     */
    public function onUpdateLocationPost($location_data, $lang_code, $location_id)
    {
        if (empty($location_data['dispatch']) || $location_data['dispatch'] !== 'products.view') {
            return;
        }

        $current_product_ids = [];
        $product_ids = empty($location_data['object_ids']) ? [] : fn_explode(',', $location_data['object_ids']);

        if (!empty($location_data['current_object_ids'])) {
            $current_product_ids = fn_explode(',', $location_data['current_object_ids']);
        }

        $deleted_product_ids = array_diff($current_product_ids, $product_ids);
        $added_product_ids = array_diff($product_ids, $current_product_ids);
        $affected_product_ids = array_merge($deleted_product_ids, $added_product_ids);

        if (empty($affected_product_ids)) {
            return;
        }

        $sync_service = ServiceProvider::getSyncService();
        $sync_service->onTableChanged('bm_locations', $affected_product_ids, ['location_id' => $location_id]);
    }

    /**
     * The "update_block_post" hook handler.
     *
     * @see \Tygh\BlockManager\Location::update
     *
     * Actions performed:
     *  - Starts the syncing of the block settings if the content settings of this block change for the parent product.
     *      This is necessary because blocks on the product page of a child variation must not differ from the blocks on the page of its parent product.
     */
    public function onUpdateBlockPost($block_data, $description, $block_id)
    {
        if (empty($block_data['content_data']['object_type'])
            || empty($block_data['content_data']['object_id'])
            || $block_data['content_data']['object_type'] !== 'products'
        ) {
            return;
        }

        $product_id = $block_data['content_data']['object_id'];

        ServiceProvider::getSyncService()->onTableChanged('bm_blocks_content', $product_id, [
            'block_id' => $block_id,
            'snapping_id' => isset($block_data['snapping_id']) ? $block_data['snapping_id'] : null
        ]);
    }

    /**
     * The "update_block_status_post" hook handler.
     *
     * Actions performed:
     *  - Starts the syncing of the block settings if the content settings of this block change for the parent product.
     *      This is necessary because blocks on the product page of a child variation must not differ from the blocks on the page of its parent product.
     *
     * @see \Tygh\BlockManager\Block::updateStatus
     */
    public function onUpdateBlockStatusPost($status_data)
    {
        if (empty($status_data['snapping_id'])
            || empty($status_data['status'])
            || empty($status_data['object_type'])
            || empty($status_data['object_id'])
            || $status_data['object_type'] !== 'products'
        ) {
            return;
        }

        $product_id = $status_data['object_id'];

        ServiceProvider::getSyncService()->onTableChanged('bm_block_statuses', $product_id, [
            'snapping_id' => $status_data['snapping_id']
        ]);
    }

    /**
     * The "update_snapping_pre" hook handler.
     *
     * Actions performed:
     *  - Saves the IDs of products for which this block isn't available when the statuses of blocks for a parent product are updated.
     *      This will be necessary later for determining what products were added/removed from the block settings.
     *
     * @see \Tygh\BlockManager\Block::updateSnapping
     */
    public function onUpdateSnappingPre(&$snapping_data)
    {
        if (empty($snapping_data['snapping_id'])
            || empty($snapping_data['object_type'])
            || $snapping_data['object_type'] !== 'products'
        ) {
            return;
        }

        $query = ServiceProvider::getQueryFactory()->createQuery(
            'bm_block_statuses',
            ['snapping_id' => $snapping_data['snapping_id'], 'object_type' => 'products'],
            ['object_ids']
        );

        $snapping_data['current_object_ids'] = $query->scalar();
    }

    /**
     * The "update_snapping_post" hook handler.
     *
     * Actions performed:
     *  - Starts the syncing of block statuses when the status of a block changes for a parent product.
     *      This is necessary because blocks on the page of a child variation must not differ from the blocks on the page of its parent product.
     *
     * @see \Tygh\BlockManager\Block::updateSnapping
     */
    public function onUpdateSnappingPost($snapping_data)
    {
        if (empty($snapping_data['snapping_id'])
            || empty($snapping_data['object_type'])
            || $snapping_data['object_type'] !== 'products'
        ) {
            return;
        }

        $current_product_ids = [];
        $product_ids = fn_explode(',', $snapping_data['object_ids']);

        if (!empty($snapping_data['current_object_ids'])) {
            $current_product_ids = fn_explode(',', $snapping_data['current_object_ids']);
        }

        $deleted_product_ids = array_diff($current_product_ids, $product_ids);
        $added_product_ids = array_diff($product_ids, $current_product_ids);
        $affected_product_ids = array_merge($deleted_product_ids, $added_product_ids);

        if (empty($affected_product_ids)) {
            return;
        }

        $sync_service = ServiceProvider::getSyncService();
        $sync_service->onTableChanged('bm_block_statuses', $affected_product_ids, ['snapping_id' => $snapping_data['snapping_id']]);
    }
}
