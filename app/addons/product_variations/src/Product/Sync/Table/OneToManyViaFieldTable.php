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



namespace Tygh\Addons\ProductVariations\Product\Sync\Table;

use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Addons\ProductVariations\Tools\QueryFactory;
use Tygh\Addons\ProductVariations\Product\Sync\ProductDataIdentityMapRepository;

/**
 * Class OneToManyViaFieldTable
 *
 * @package Tygh\Addons\ProductVariations\Product\Sync\Table
 * @example buy_together
 */
class OneToManyViaFieldTable extends ATable
{
    protected $data_map_repository;

    public function __construct(
        QueryFactory $query_factory,
        ProductDataIdentityMapRepository $identity_map_repository,
        $table_id,
        array $primary_key,
        $product_id_field,
        array $excluded_fields = [],
        $params = []
    ) {
        $this->data_map_repository = $identity_map_repository;

        parent::__construct($query_factory, $table_id, $primary_key, $product_id_field, $excluded_fields, $params);
    }

    public static function create($table_id, array $primary_key, $product_id_field, array $excluded_fields = [], $params = [])
    {
        return new self(ServiceProvider::getQueryFactory(), ServiceProvider::getDataIdentityMapRepository(), $table_id, $primary_key, $product_id_field, $excluded_fields, $params);
    }

    public function sync($source_product_id, array $destination_product_ids, array $conditions = [])
    {
        $this->validateConditions($conditions);

        $query = $this->createQuery($this->table_id, $this->prepareTableConditions($source_product_id, $destination_product_ids), ['*']);
        $query->addConditions($conditions);
        $query->addConditions([$this->product_id_field => $source_product_id]);

        $source_data_list = $query->select(function ($item) {
            return $this->convertPrimaryKeyValueToHash($item);
        });

        $products_data_map = $this->getProductsDataMap($source_product_id, $destination_product_ids, $conditions);

        list($update_list, $insert_list, $delete_list, $exists_keys) = $this->diff($source_data_list, $products_data_map, $destination_product_ids);

        if (!$conditions) { // if full sync, then delete all unmapped data
            $this->cleanUpTable($destination_product_ids, $exists_keys);
        }

        $this->executeUpdate($update_list, $source_data_list);
        $this->executeInsert($insert_list, $source_data_list, $source_product_id, $products_data_map);
        $this->executeDelete($delete_list);

        $this->executeAfterSyncCallback($source_product_id, $destination_product_ids, $source_data_list, $update_list, $insert_list, $delete_list);
    }

    protected function getProductsDataMap($source_product_id, array $destination_product_ids, array $conditions = [])
    {
        $map_conditions = [
            'product_id' => array_merge([$source_product_id], $destination_product_ids)
        ];

        if ($conditions) {
            $parent_ids = $this->convertPrimaryKeyValuesToHashList($this->convertPrimaryKeyConditionsToPrimaryKeyValues($conditions));
            $map_conditions['parent_id'] = $parent_ids;
        }

        return $this->data_map_repository->find(
            $this->table_id,
            ['id', 'product_id', 'parent_id'],
            $map_conditions,
            ['parent_id', 'id', 'product_id']
        );
    }

    protected function executeUpdate(array $update_list, array $source_product_data_list)
    {
        foreach ($update_list as $key => $product_ids) {
            $primary_key_conditions = $this->convertPrimaryKeyValuesToPrimaryKeyConditions(
                $this->convertHashListToPrimaryKeyValues(array_keys($product_ids))
            );

            $update_query = $this->createQuery($this->table_id);
            $update_query->addConditions($primary_key_conditions);
            $update_query->update($this->cleanUpData($source_product_data_list[$key]));
        }
    }

    protected function executeInsert(array $insert_list, array $source_product_data_list, $source_product_id, $products_data_map)
    {
        $insert_data = [];
        $map_insert_list = [];

        foreach ($insert_list as $key => $product_ids) {
            $data = $this->cleanUpData($source_product_data_list[$key]);

            if (empty($products_data_map[$key][$key])) {
                $map_insert_list[] = ProductDataIdentityMapRepository::createMapRow($key, $key, $source_product_id, $this->table_id);
            }

            foreach ($product_ids as $product_key => $product_id) {
                $data[$this->product_id_field] = $product_id;

                if ($this->hasCompositePrimaryKey()) {
                    $insert_data[] = $data;
                    $id = $this->convertPrimaryKeyValueToHash($data);
                    $map_insert_list[] = ProductDataIdentityMapRepository::createMapRow($id, $key, $product_id, $this->table_id);
                } else {
                    $insert_query = $this->createQuery($this->table_id);
                    $id = $insert_query->insert($data);

                    $map_insert_list[] = ProductDataIdentityMapRepository::createMapRow($id, $key, $product_id, $this->table_id);
                }
            }
        }

        if ($insert_data) {
            $insert_query = $this->createQuery($this->table_id);
            $insert_query->multipleInsert($insert_data);
        }

        if ($map_insert_list) {
            $this->data_map_repository->multipleInsert($map_insert_list);
        }
    }

    protected function executeDelete(array $delete_list)
    {
        $map_ids = [];

        foreach ($delete_list as $key => $product_ids) {
            $ids = array_keys($product_ids);
            $map_ids = array_merge($map_ids, $ids);

            $primary_key_conditions = $this->convertPrimaryKeyValuesToPrimaryKeyConditions(
                $this->convertHashListToPrimaryKeyValues($ids)
            );

            $delete_query = $this->createQuery($this->table_id);
            $delete_query->addConditions($primary_key_conditions);
            $delete_query->delete();
        }

        if ($map_ids) {
            $this->data_map_repository->deleteById($this->table_id, $map_ids);
            $this->data_map_repository->cleanUpMap($this->table_id);
        }
    }

    protected function cleanUpTable($destination_product_ids, $exists_keys)
    {
        $query = $this->createQuery($this->table_id, [$this->product_id_field => $destination_product_ids]);

        if (!empty($exists_keys)) {
            $query->addNotInCondition(
                $this->primary_key,
                $this->convertHashListToPrimaryKeyValues($exists_keys)
            );
        }

        $query->delete();
    }
}