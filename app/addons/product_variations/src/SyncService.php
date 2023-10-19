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


namespace Tygh\Addons\ProductVariations;


use Tygh\Addons\ProductVariations\Product\ProductIdMap;

/**
 * The service that handles syncing of data of variations
 *
 * @package Tygh\Addons\ProductVariations
 */
class SyncService
{
    const MAX_SIZE_SYNC_EVENT_BUFFER = 100;

    /** @var callable */
    protected $sync_schema_factory;

    /** @var callable */
    protected $copy_schema_factory;

    /** @var null|\Tygh\Addons\ProductVariations\Product\Sync\ISyncItem[] */
    protected $sync_schema;

    /** @var null|\Tygh\Addons\ProductVariations\Product\Sync\ISyncItem[] */
    protected $copy_schema;

    /** @var \Tygh\Addons\ProductVariations\Product\ProductIdMap */
    protected $product_id_map;

    /** @var array  */
    protected $sync_events_buffer = [];

    /**
     * SyncService constructor.
     *
     * @param \Tygh\Addons\ProductVariations\Product\ProductIdMap $product_id_map
     * @param callable                                            $sync_schema_factory
     * @param callable                                            $copy_schema_factory
     */
    public function __construct(ProductIdMap $product_id_map, callable $sync_schema_factory, callable $copy_schema_factory)
    {
        $this->product_id_map = $product_id_map;
        $this->sync_schema_factory = $sync_schema_factory;
        $this->copy_schema_factory = $copy_schema_factory;

        register_shutdown_function(function() {
            $this->flushSyncEventBuffer();
        });
    }

    /**
     * Executes sync data for specific table
     *
     * @param string $table_id                  Table name
     * @param int    $source_product_id         Source product identifier
     * @param array  $destination_product_ids   List of destination product identifiers
     * @param array  $conditions                Primary key conditions
     */
    public function sync($table_id, $source_product_id, array $destination_product_ids, array $conditions = [])
    {
        $source_product_id = (int) $source_product_id;
        $destination_product_ids = array_map('intval', $destination_product_ids);

        $schema = $this->getSyncSchema();

        if (!isset($schema[$table_id])) {
            return;
        }

        $schema[$table_id]->sync($source_product_id, $destination_product_ids, $conditions);

        $this->addSyncEventToBuffer($table_id, $source_product_id, $destination_product_ids, $conditions);
    }

    /**
     * Executes sync the product data by all tables, described on sync schema
     *
     * @param int   $source_product_id          Source product identifier
     * @param array $destination_product_ids    List of destination product identifiers
     * @param array $table_ids                  List of table names
     */
    public function syncAll($source_product_id, array $destination_product_ids, array $table_ids = [])
    {
        $schema = $this->getSyncSchema();

        foreach ($schema as $table_id => $table) {
            if ($table_ids && !in_array($table_id, $table_ids, true)) {
                continue;
            }

            $table->sync($source_product_id, $destination_product_ids);
        }
    }

    /**
     * Executes sync the product data by all tables, described on copy schema
     *
     * @param int   $source_product_id          Source product identifier
     * @param array $destination_product_ids    List of destination product identifiers
     */
    public function copyAll($source_product_id, array $destination_product_ids)
    {
        $schema = $this->getCopySchema();

        foreach ($schema as $table_id => $table) {
            $table->sync($source_product_id, $destination_product_ids);
        }
    }

    /**
     * Starts the data syncing. This method should be called on the event that changes data.
     *
     * @param string    $table_id   Table name
     * @param int|int[] $product_id Changed product identifier(s)
     * @param array     $conditions Primary key conditions
     */
    public function onTableChanged($table_id, $product_id, array $conditions = [])
    {
        $product_ids = (array) $product_id;

        if (count($product_ids) > 1) {
            $this->product_id_map->addProductIdsToPreload($product_ids);
        }

        foreach ($product_ids as $product_id) {
            $parent_product_id = null;

            if ($this->product_id_map->isParentProduct($product_id)) {
                $parent_product_id = $product_id;
            } elseif ($this->product_id_map->isChildProduct($product_id)) {
                $parent_product_id = $this->product_id_map->getParentProductId($product_id);
            } else {
                continue;
            }

            $children_ids = $this->product_id_map->getProductChildrenIds($parent_product_id);

            if ($children_ids) {
                $this->sync($table_id, $parent_product_id, $children_ids, $conditions);
            }
        }
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Sync\ISyncItem[]|null
     */
    protected function getSyncSchema()
    {
        if ($this->sync_schema === null) {
            $this->sync_schema = call_user_func($this->sync_schema_factory);
        }

        return $this->sync_schema;
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Sync\ISyncItem[]|null
     */
    protected function getCopySchema()
    {
        if ($this->copy_schema === null) {
            $this->copy_schema = call_user_func($this->copy_schema_factory);
        }

        return $this->copy_schema;
    }

    /**
     * @param string $table_id
     * @param int    $source_product_id
     * @param int[]  $destination_product_ids
     * @param array  $conditions
     */
    protected function addSyncEventToBuffer($table_id, $source_product_id, $destination_product_ids, $conditions)
    {
        if ($this->isNeedToFlushSyncEventBuffer()) {
            $this->flushSyncEventBuffer();
        }

        $this->sync_events_buffer[] = [
            'table_id'                => $table_id,
            'source_product_id'       => $source_product_id,
            'destination_product_ids' => $destination_product_ids,
            'conditions'              => $conditions
        ];
    }

    /**
     * @return bool
     */
    protected function isNeedToFlushSyncEventBuffer()
    {
        return count($this->sync_events_buffer) > self::MAX_SIZE_SYNC_EVENT_BUFFER;
    }

    protected function flushSyncEventBuffer()
    {
        $events = $this->sync_events_buffer;
        $this->sync_events_buffer = [];

        if (empty($events)) {
            return;
        }

        /**
         * Executes after the processed syncing events; allows to react to synced data.
         *
         * @param \Tygh\Addons\ProductVariations\SyncService $this     Instance of the data syncing service
         * @param array                                      $events   Data syncing events
         */
        fn_set_hook('variation_sync_flush_sync_events', $this, $events);
    }
}
