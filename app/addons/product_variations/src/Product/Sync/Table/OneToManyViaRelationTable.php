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

class OneToManyViaRelationTable extends ATable
{
    protected $related_table_id;

    protected $relation_link = [];

    protected $relation_conditions = [];

    protected $identity_map_repository;

    public function __construct(
        QueryFactory $query_factory,
        ProductDataIdentityMapRepository $identity_map_repository,
        $table_id,
        array $primary_key,
        $related_table_id,
        $relation_link,
        $product_id_field,
        array $excluded_fields = [],
        array $relation_conditions = [],
        $params = []
    ) {
        $this->related_table_id = $related_table_id;
        $this->relation_link = $relation_link;
        $this->relation_conditions = $relation_conditions;
        $this->identity_map_repository = $identity_map_repository;

        $excluded_fields = array_merge($excluded_fields, array_keys($relation_link));
        $excluded_fields[] = $product_id_field;

        parent::__construct($query_factory, $table_id, $primary_key, $product_id_field, $excluded_fields, $params);
    }


    public static function create(
        $table_id,
        array $primary_key,
        $related_table_id,
        $relation_link,
        $product_id_field,
        array $excluded_fields = [],
        array $relation_conditions = [],
        $params = []
    ) {
        return new self(
            ServiceProvider::getQueryFactory(),
            ServiceProvider::getDataIdentityMapRepository(),
            $table_id,
            $primary_key,
            $related_table_id,
            $relation_link,
            $product_id_field,
            $excluded_fields,
            $relation_conditions,
            $params
        );
    }

    public function sync($source_product_id, array $destination_product_ids, array $conditions = [])
    {
        $this->validateConditions($conditions);

        $source_data_list = $this->getSourceDataList($source_product_id, $destination_product_ids, $conditions);
        $relation_id_parent_map = $this->getRelationIdParentMap($source_data_list, $destination_product_ids);
        $products_data_map = $this->getProductsDataMap($source_product_id, $destination_product_ids, $conditions);

        list($update_list, $insert_list, $delete_list, $exists_keys) = $this->diff($source_data_list, $products_data_map, $destination_product_ids);

        if (!$conditions) {
            $this->cleanUpTable($relation_id_parent_map, $exists_keys);
        }

        $this->executeUpdate($update_list, $source_data_list);
        $this->executeInsert($insert_list, $source_data_list, $relation_id_parent_map, $source_product_id, $products_data_map, !empty($conditions));
        $this->executeDelete($delete_list);

        $this->executeAfterSyncCallback($source_product_id, $destination_product_ids, $source_data_list, $update_list, $insert_list, $delete_list);
    }

    protected function getSourceDataList($source_product_id, $destination_product_ids, array $conditions = [])
    {
        $table_conditions = $this->prepareTableConditions($source_product_id, $destination_product_ids);

        $query = $this->createQuery(
            $this->table_id,
            $table_conditions, ['t1.*', sprintf('t2.%s', $this->product_id_field)], 't1'
        );

        $query->addConditions($conditions);
        $query->addInnerJoin('t2', $this->related_table_id, $this->relation_link, $this->relation_conditions);
        $query->addConditions([$this->product_id_field => $source_product_id], 't2');

        return $query->select(function ($item) {
            return $this->convertPrimaryKeyValueToHash($item);
        });
    }

    protected function getRelationIdParentMap(array $source_data_list, array $destination_product_ids)
    {
        if (empty($source_data_list)) {
            return [];
        }

        $relation_pk_values = [];

        foreach ($source_data_list as $item) {
            $relation_pk_value = [];
            foreach ($this->relation_link as $base_field => $relation_field) {
                $relation_pk_value[$relation_field] = $item[$base_field];
            }

            $relation_pk_values[] = $relation_pk_value;
        }

        $map_conditions = [
            'product_id' => $destination_product_ids,
            'parent_id' => $this->convertPrimaryKeyValuesToHashList($relation_pk_values, $this->relation_link)
        ];

        return $this->identity_map_repository->find(
            $this->related_table_id,
            ['id', 'product_id', 'parent_id'],
            $map_conditions,
            ['parent_id', 'product_id', 'id']
        );
    }

    protected function getProductsDataMap($source_product_id, array $destination_product_ids, array $conditions)
    {
        $map_conditions = ['product_id' => array_merge([$source_product_id], $destination_product_ids)];

        if ($conditions) {
            $pk_values = $this->convertPrimaryKeyConditionsToPrimaryKeyValues($conditions);
            $pk_hash_list = $this->convertPrimaryKeyValuesToHashList($pk_values);

            $map_conditions['parent_id'] = $pk_hash_list;
        }

        return $this->identity_map_repository->find(
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

    protected function executeInsert(array $insert_list, array $source_product_data_list, $relation_id_parent_map, $source_product_id, $products_data_map, $delete_before_insert = true)
    {
        $insert_data = [];
        $map_insert_list = [];
        $products_insert_pk_list = [];

        foreach ($insert_list as $key => $product_ids) {
            $data = $source_product_data_list[$key];
            $relation_key_value = array_intersect_key($data, array_flip($this->relation_link));
            $relation_key_hash = $this->convertPrimaryKeyValueToHash($relation_key_value, $this->relation_link);

            if (!isset($products_data_map[$key][$key])) {
                $map_insert_list[] = ProductDataIdentityMapRepository::createMapRow($key, $key, $source_product_id, $this->table_id);
            }

            foreach ($product_ids as $product_key => $product_id) {
                if (!isset($relation_id_parent_map[$relation_key_hash][$product_id])) {
                    continue;
                }

                $product_relation_key_hash = $relation_id_parent_map[$relation_key_hash][$product_id];
                $product_relation_key_value = $this->convertHashToPrimaryKeyValue($product_relation_key_hash, $this->relation_link);

                $data = $this->cleanUpData($data);

                foreach ($this->relation_link as $base_field => $relation_field) {
                    $data[$base_field] = $product_relation_key_value[$relation_field];
                }

                if ($this->hasCompositePrimaryKey()) {
                    $insert_data[] = $data;
                    $pk_value = [];

                    foreach ($this->primary_key as $field) {
                        $pk_value[$field] = $data[$field];
                    }
                    $products_insert_pk_list[] = $pk_value;


                    $map_insert_list[] = ProductDataIdentityMapRepository::createMapRow($this->convertPrimaryKeyValueToHash($pk_value), $key, $product_id, $this->table_id);
                } else {
                    $insert_query = $this->createQuery($this->table_id);
                    $id = $insert_query->insert($data);

                    $map_insert_list[] = ProductDataIdentityMapRepository::createMapRow($id, $key, $product_id, $this->table_id);
                }
            }
        }

        if ($delete_before_insert && $this->hasCompositePrimaryKey() && $products_insert_pk_list) {
            $query = $this->createQuery($this->table_id);
            $query->addInCondition($this->primary_key, $products_insert_pk_list);
            $query->delete();
        }

        if ($insert_data) {
            $insert_query = $this->createQuery($this->table_id);
            $insert_query->multipleInsert($insert_data);
        }

        if ($map_insert_list) {
            $this->identity_map_repository->multipleInsert($map_insert_list);
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
            $this->identity_map_repository->deleteById($this->table_id, $map_ids);
            $this->identity_map_repository->cleanUpMap($this->table_id);
        }
    }

    protected function cleanUpTable($relation_id_parent_map, $exists_keys)
    {
        $query = $this->createQuery($this->table_id);

        $relation_link = $this->relation_link;

        if (count($relation_link) === 1) {
            $relation_field = reset($relation_link);
            $base_field = key($relation_link);
            $query->addCondition(sprintf('%s NOT IN (SELECT %s FROM ?:%s)', $base_field, $relation_field, $this->related_table_id));
        } else {
            $query->addCondition(sprintf('(%s) NOT IN (SELECT %s FROM ?:%s)', implode(', ', array_keys($relation_link)), implode(', ', $relation_link), $this->related_table_id));
        }

        $query->delete();

        if ($relation_id_parent_map) {
            $query = $this->createQuery($this->table_id);

            $relation_ids = [];

            foreach ($relation_id_parent_map as $id => $items) {
                $relation_ids = array_merge($relation_ids, $items);
            }

            $query->addInCondition(
                array_keys($this->relation_link),
                $this->convertHashListToPrimaryKeyValues($relation_ids, array_keys($this->relation_link))
            );

            if ($exists_keys) {
                $query->addNotInCondition($this->primary_key, $this->convertHashListToPrimaryKeyValues($exists_keys));
            }

            $query->delete();
        }
    }
}