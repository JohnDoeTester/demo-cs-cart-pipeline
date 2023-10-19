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

/**
 * Class OneToManyViaPrimaryKeyTable
 *
 * @package Tygh\Addons\ProductVariations\Product\Sync\Table
 * @example product_descriptions
 */
class OneToManyViaPrimaryKeyTable extends ATable
{
    public static function create($table_id, array $primary_key, $product_id_field, array $excluded_fields = [], $params = [])
    {
        return new self(ServiceProvider::getQueryFactory(), $table_id, $primary_key, $product_id_field, $excluded_fields, $params);
    }

    public function sync($source_product_id, array $destination_product_ids, array $conditions = [])
    {
        $this->validateConditions($conditions);

        $table_conditions = $this->prepareTableConditions($source_product_id, $destination_product_ids);

        $query = $this->createQuery($this->table_id, $table_conditions, ['*']);
        $query->addConditions($conditions);
        $query->addConditions([$this->product_id_field => $source_product_id]);

        $source_data_list = $query->select(function ($item) {
            return $this->convertPrimaryKeyValueToHash($item);
        });

        $query = $this->createQuery($this->table_id, $table_conditions, $this->primary_key);
        $query->addConditions($conditions);
        $query->addInCondition($this->product_id_field, $destination_product_ids);

        $products_data_map = $query->select(function ($item) use ($source_product_id) {
            $key = $this->convertPrimaryKeyValueToHash($item);
            $product_id = $item[$this->product_id_field];
            $item[$this->product_id_field] = $source_product_id;

            return [$this->convertPrimaryKeyValueToHash($item), $key, $product_id];
        });

        list($update_list, $insert_list, $delete_list) = $this->diff($source_data_list, $products_data_map, $destination_product_ids);

        $this->executeUpdate($update_list, $source_data_list, $table_conditions);
        $this->executeInsert($insert_list, $source_data_list);
        $this->executeDelete($delete_list, $table_conditions);

        $this->executeAfterSyncCallback($source_product_id, $destination_product_ids, $source_data_list, $update_list, $insert_list, $delete_list);
    }

    protected function executeUpdate(array $update_list, array $source_product_data_list, $table_conditions)
    {
        foreach ($update_list as $key => $product_ids) {
            $primary_key_conditions = $this->convertPrimaryKeyValuesToPrimaryKeyConditions(
                $this->convertHashListToPrimaryKeyValues(array_keys($product_ids))
            );

            $update_query = $this->createQuery($this->table_id, $table_conditions);
            $update_query->addConditions($primary_key_conditions);
            $update_query->update($this->cleanUpData($source_product_data_list[$key]));
        }
    }

    protected function executeInsert(array $insert_list, array $source_product_data_list)
    {
        $insert_data = [];

        foreach ($insert_list as $key => $product_ids) {
            $data = $this->cleanUpData($source_product_data_list[$key]);

            foreach ($product_ids as $product_key => $product_id) {
                $data[$this->product_id_field] = $product_id;
                $insert_data[] = $data;
            }
        }

        if ($insert_data) {
            $insert_query = $this->createQuery($this->table_id);
            $insert_query->multipleInsert(array_values($insert_data));
        }
    }

    protected function executeDelete(array $delete_list, $table_conditions)
    {
        foreach ($delete_list as $key => $product_ids) {
            $primary_key_conditions = $this->convertPrimaryKeyValuesToPrimaryKeyConditions(
                $this->convertHashListToPrimaryKeyValues(array_keys($product_ids))
            );

            $delete_query = $this->createQuery($this->table_id, $table_conditions);
            $delete_query->addConditions($primary_key_conditions);
            $delete_query->delete();
        }
    }
}