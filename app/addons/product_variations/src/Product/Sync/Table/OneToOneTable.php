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

/**
 * Class OneToOneTable
 *
 * @package Tygh\Addons\ProductVariations\Product\Sync\Table
 * @example product_popularity
 */
class OneToOneTable extends ATable
{
    public function __construct(
        QueryFactory $query_factory,
        $table_id,
        $product_id_field,
        array $excluded_fields = [],
        array $params = []
    ) {
        parent::__construct($query_factory, $table_id, [$product_id_field], $product_id_field, $excluded_fields, $params);
    }

    public static function create($table_id, $product_id_field, array $excluded_fields = [], array $params = [])
    {
        return new self(ServiceProvider::getQueryFactory(), $table_id, $product_id_field, $excluded_fields, $params);
    }

    public function sync($source_product_id, array $destination_product_ids, array $conditions = [])
    {
        $query = $this->createQuery($this->table_id, [$this->product_id_field => $source_product_id], ['*']);
        $source_product_data_list = $query->select($this->product_id_field);

        $query = $this->createQuery(
            $this->table_id,
            [$this->product_id_field => $destination_product_ids],
            [$this->product_id_field]
        );

        $products_data_map = [$source_product_id => $query->column()];


        list($update_list, $insert_list, $delete_list) = $this->diff($source_product_data_list, $products_data_map, $destination_product_ids);

        if ($update_list) {
            $update_product_ids = reset($update_list);
            $data = reset($source_product_data_list);

            $query = $this->createQuery($this->table_id, [$this->product_id_field => $update_product_ids]);
            $query->update($this->cleanUpData($data));
        }

        if ($insert_list) {
            $insert_data_list = [];
            $insert_product_ids = reset($insert_list);
            $data = reset($source_product_data_list);
            $data = $this->cleanUpData($data);

            foreach ($insert_product_ids as $product_id) {
                $data[$this->product_id_field] = $product_id;
                $insert_data_list[] = $data;
            }

            $query = $this->createQuery($this->table_id);
            $query->multipleInsert($insert_data_list);
        }

        if ($delete_list) {
            $delete_product_ids = reset($delete_list);

            $query = $this->createQuery($this->table_id, [$this->product_id_field => $delete_product_ids]);
            $query->delete();
        }

        $this->executeAfterSyncCallback($source_product_id, $destination_product_ids, $source_product_data_list, $update_list, $insert_list, $delete_list);
    }
}