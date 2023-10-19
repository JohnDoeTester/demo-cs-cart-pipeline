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


namespace Tygh\Addons\ProductVariations\Product\Sync;


use Tygh\Addons\ProductVariations\Tools\QueryFactory;

/**
 * Class ProductDataIdentityMapRepository
 *
 * @package Tygh\Addons\ProductVariations\Product\Sync
 */
class ProductDataIdentityMapRepository
{
    const TABLE_ID = 'product_variation_data_identity_map';

    /** @var \Tygh\Addons\ProductVariations\Tools\QueryFactory */
    protected $query_factory;

    public function __construct(QueryFactory $query_factory)
    {
        $this->query_factory = $query_factory;
    }

    public function find($table_id, array $fields, array $conditions, $index_by = null)
    {
        $query = $this->createQuery($table_id);
        $query->addConditions($conditions);
        $query->setFields($fields);

        return $query->select($index_by);
    }

    public function insert(array $data)
    {
        $query = $this->createQuery();
        return $query->insert($data);
    }

    public function multipleInsert(array $data_list)
    {
        $query = $this->createQuery();
        $query->multipleInsert($data_list);
    }

    public function deleteById($table_id, array $ids)
    {
        $query = $this->createQuery($table_id);
        $query->addConditions(['id' => $ids]);

        $query->delete();
    }

    public function deleteByProductId($product_id)
    {
        $query = $this->createQuery();
        $query->addConditions(['product_id' => $product_id]);

        $query->delete();
    }

    public function deleteByProductIds(array $product_ids, array $exclude_ids = [])
    {
        $query = $this->createQuery();
        $query->addConditions(['product_id' => $product_ids]);

        if ($exclude_ids) {
            $query->addNotInCondition('id', $exclude_ids);
        }

        $query->delete();
    }

    public function cleanUpMap($table_id)
    {
        $query = $this->createQuery($table_id);

        $query->setFields(['parent_id']);
        $query->setGroupBy(['parent_id']);
        $query->setHaving('COUNT(id) = 1');

        $parent_ids = $query->column();

        if ($parent_ids) {
            $query = $this->createQuery($table_id);
            $query->addConditions(['parent_id' => $parent_ids]);
            $query->delete();
        }
    }

    public function changeParentProductId($parent_product_id, array $product_ids)
    {
        $query = $this->createQuery();
        $query->addConditions(['product_id' => $parent_product_id]);
        $query->addCondition('id != parent_id');
        $query->setFields(['id', 'parent_id', 'table_id']);

        foreach ($query->select() as $item) {
            $update_query = $this->createQuery($item['table_id']);
            $update_query->addConditions([
                'parent_id'  => $item['parent_id'],
                'product_id' => $product_ids
            ]);

            $update_query->update(['parent_id' => $item['id']]);
        }
    }

    public static function createMapRow($id, $parent_id, $product_id, $table_id)
    {
        return ['id' => $id, 'parent_id' => $parent_id, 'product_id' => $product_id, 'table_id' => $table_id];
    }

    protected function createQuery($table_id = null)
    {
        $query = $this->query_factory->createQuery(self::TABLE_ID);

        if ($table_id) {
            $query->addConditions(['table_id' => $table_id]);
        }

        return $query;
    }
}