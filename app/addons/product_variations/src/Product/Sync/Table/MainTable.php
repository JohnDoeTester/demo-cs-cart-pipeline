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

use Tygh\Addons\ProductVariations\Tools\QueryFactory;
use Tygh\Addons\ProductVariations\ServiceProvider;

/**
 * Class MainTable
 *
 * @package Tygh\Addons\ProductVariations\Product\Sync\Table
 * @example products
 */
class MainTable extends ATable
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
        $data = $query->select();
        $data = $this->cleanUpData(reset($data));

        $update_query = $this->createQuery($this->table_id, [$this->product_id_field => $destination_product_ids]);
        $update_query->update($data);

        $this->executeAfterSyncCallback($source_product_id, $destination_product_ids, $data);
    }
}