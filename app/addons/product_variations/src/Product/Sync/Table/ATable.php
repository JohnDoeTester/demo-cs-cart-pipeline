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


use Tygh\Addons\ProductVariations\Product\Sync\ISyncItem;
use Tygh\Addons\ProductVariations\Tools\QueryFactory;
use Tygh\Exceptions\DeveloperException;

/**
 * Class ATable
 *
 * @package Tygh\Addons\ProductVariations\Product\Sync\Table
 */
abstract class ATable implements ISyncItem
{
    /** @var string  */
    const PRIMARY_KEY_DELIMITER = '_';

    /** @var string Table name */
    protected $table_id;

    /** @var string Product id field */
    protected $product_id_field = 'product_id';

    /** @var array Primary key fields */
    protected $primary_key = [];

    /** @var \Tygh\Addons\ProductVariations\Tools\QueryFactory */
    protected $query_factory;

    /** @var array Excluded list fields */
    protected $excluded_fields = [];

    /** @var array Extra conditions */
    protected $conditions = [];

    /** @var callable */
    protected $cleanup_data_callback;

    /** @var callable */
    protected $after_sync_callback;

    /**
     * ATable constructor.
     *
     * @param \Tygh\Addons\ProductVariations\Tools\QueryFactory $query_factory
     * @param string                                            $table_id
     * @param array                                             $primary_key
     * @param string                                            $product_id_field
     * @param array                                             $excluded_fields
     * @param array                                             $params
     *                                                                 * conditions
     *                                                                 * cleanup_data_callback
     *                                                                 * after_sync_callback
     *
     */
    public function __construct(QueryFactory $query_factory, $table_id, array $primary_key, $product_id_field, array $excluded_fields = [], $params = [])
    {
        $this->query_factory = $query_factory;
        $this->table_id = $table_id;
        $this->product_id_field = $product_id_field;
        $this->excluded_fields = $excluded_fields;
        $this->primary_key = $primary_key;
        $this->conditions = isset($params['conditions']) ? $params['conditions'] : [];
        $this->cleanup_data_callback = isset($params['cleanup_data_callback']) ? $params['cleanup_data_callback'] : null;
        $this->after_sync_callback = isset($params['after_sync_callback']) ? $params['after_sync_callback'] : null;
    }

    abstract public function sync($source_product_id, array $destination_product_ids, array $conditions = []);

    public function addExcludedFields(array $excluded_fields)
    {
        foreach ($excluded_fields as $field) {
            $this->excluded_fields[] = $field;
        }
    }

    protected function prepareTableConditions($source_product_id, array $destination_product_ids)
    {
        if (is_callable($this->conditions)) {
            $conditions = call_user_func($this->conditions, $source_product_id, $destination_product_ids);
        } else {
            $conditions = $this->conditions;
        }

        return (array) $conditions;
    }

    protected function createQuery($table_id, array $conditions = [], array $fields = [], $table_alias = null)
    {
        return $this->query_factory->createQuery($table_id, $conditions, $fields, $table_alias);
    }

    protected function cleanUpData(array $data)
    {
        unset($data[$this->product_id_field]);

        if (!$this->hasCompositePrimaryKey()) {
            unset($data[reset($this->primary_key)]);
        }

        foreach ($this->excluded_fields as $field) {
            unset($data[$field]);
        }

        $data = $this->executeCleanUpDataCallback($data);

        return $data;
    }

    protected function validateConditions(array $conditions = [])
    {
        if (empty($conditions)) {
            return;
        }

        $primary_key = $this->primary_key;

        if ($this->hasProductFieldInPrimaryKey()) {
            $primary_key = array_diff($primary_key, [$this->product_id_field]);
        }

        if (array_diff_key($conditions, array_fill_keys($primary_key, 1))
            || array_diff_key(array_fill_keys($primary_key, 1), $conditions)
        ) {
            DeveloperException::throwException('The conditions must use only and all primary key fields.');
        }
    }

    protected function convertPrimaryKeyValuesToHashList(array $primary_key_value_list, array $primary_key = null)
    {
        $result = [];

        foreach ($primary_key_value_list as $item) {
            $result[] = $this->convertPrimaryKeyValueToHash($item, $primary_key);
        }

        return array_unique($result);
    }

    protected function convertHashListToPrimaryKeyValues($hash_list, array $primary_key = null)
    {
        $result = [];

        foreach ($hash_list as $hash) {
            $result[] = $this->convertHashToPrimaryKeyValue($hash, $primary_key);
        }

        return $result;
    }

    protected function convertPrimaryKeyValueToHash(array $data, array $primary_key = null)
    {
        $result = [];

        if ($primary_key === null) {
            $primary_key = $this->primary_key;
        }

        foreach ($primary_key as $field) {
            $result[] = $data[$field];
        }

        return implode(self::PRIMARY_KEY_DELIMITER, $result);
    }

    protected function convertHashToPrimaryKeyValue($hash, array $primary_key = null)
    {
        $values = explode(self::PRIMARY_KEY_DELIMITER, $hash);
        $result = [];

        if ($primary_key === null) {
            $primary_key = $this->primary_key;
        }

        foreach ($primary_key as $field) {
            $result[$field] = array_shift($values);
        }

        return $result;
    }

    protected function convertPrimaryKeyConditionsToPrimaryKeyValues(array $conditions)
    {
        $result = [];

        while ($conditions) {
            reset($conditions);
            $condition_field = key($conditions);
            $condition_values = (array) array_shift($conditions);

            if ($result) {
                $tmp_result = [];

                foreach ($condition_values as $value) {
                    foreach ($result as $item) {
                        $tmp_result[] = array_merge($item, [$condition_field => $value]);
                    }
                }

                $result = $tmp_result;
            } else {
                foreach ($condition_values as $value) {
                    $result[][$condition_field] = $value;
                }
            }
        }

        return $result;
    }

    protected function convertPrimaryKeyValuesToPrimaryKeyConditions(array $primary_key_value_list)
    {
        $primary_key_conditions = [];

        foreach ($primary_key_value_list as $item) {
            foreach ($item as $field => $value) {
                $primary_key_conditions[$field][$value] = $value;
            }
        }

        return $primary_key_conditions;
    }

    protected function diff(array $source_product_data_list, $products_data_map, array $destination_product_ids)
    {
        $exists_keys = $update_list = $insert_list = $delete_list = [];

        foreach ($source_product_data_list as $key => $data) {
            if (isset($products_data_map[$key])) {
                $product_ids = array_intersect($products_data_map[$key], $destination_product_ids);

                $update_list[$key] = $product_ids;
                $insert_list[$key] = array_diff($destination_product_ids, $product_ids);
                $delete_list[$key] = array_diff($product_ids, $destination_product_ids);
                $exists_keys = array_merge($exists_keys, array_keys($product_ids));
            } else {
                $insert_list[$key] = $destination_product_ids;
            }
            unset($products_data_map[$key]);
        }

        foreach ($products_data_map as $key => $product_ids) {
            $product_ids = array_intersect($product_ids, $destination_product_ids);
            $delete_list[$key] = $product_ids;
        }

        $update_list = array_filter($update_list);
        $insert_list = array_filter($insert_list);
        $delete_list = array_filter($delete_list);
        $exists_keys = array_filter($exists_keys);

        return [$update_list, $insert_list, $delete_list, $exists_keys];
    }

    protected function hasProductFieldInPrimaryKey()
    {
        return is_string($this->product_id_field) && in_array($this->product_id_field, $this->primary_key, true);
    }

    protected function hasCompositePrimaryKey()
    {
        return count($this->primary_key) > 1;
    }

    protected function executeCleanUpDataCallback($data)
    {
        if (is_callable($this->cleanup_data_callback)) {
            $data = call_user_func($this->cleanup_data_callback, $data);
        }

        return $data;
    }

    protected function executeAfterSyncCallback($source_product_id, $destination_product_ids, $source_data_list, $update_list = null, $insert_list = null, $delete_list = null)
    {
        if (is_callable($this->after_sync_callback)) {
            $update_pk_list = [];
            $insert_pk_list = [];
            $delete_pk_list = [];

            if ($update_list) {
                $update_pk_list = $this->convertHashListToPrimaryKeyValues(array_keys($update_list));
            }

            if ($insert_list) {
                $insert_pk_list = $this->convertHashListToPrimaryKeyValues(array_keys($insert_list));
            }

            if ($delete_list) {
                $delete_pk_list = $this->convertHashListToPrimaryKeyValues(array_keys($delete_list));
            }

            call_user_func($this->after_sync_callback, $source_product_id, $destination_product_ids, $source_data_list, $update_pk_list, $insert_pk_list, $delete_pk_list);
        }
    }
}