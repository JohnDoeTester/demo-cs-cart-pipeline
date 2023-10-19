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


namespace Tygh\Addons\ProductVariations\Product\Group;

use Tygh\Addons\ProductVariations\Product\Group\Events\ProductAddedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductRemovedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductUpdatedEvent;
use Tygh\Addons\ProductVariations\Product\Type\Type;
use Tygh\Addons\ProductVariations\Tools\QueryFactory;
use Tygh\Enum\ProductFeatures;
use Tygh\Exceptions\InputException;

/**
 * Provides methods to access storage of the product variations groups.
 *
 * @package Tygh\Addons\ProductVariations\Product\Group
 */
class Repository
{
    /** @var string  */
    const TABLE_GROUPS = 'product_variation_groups';

    /** @var string  */
    const TABLE_GROUP_PRODUCTS = 'product_variation_group_products';

    /** @var string  */
    const TABLE_GROUP_FEATURES = 'product_variation_group_features';

    /** @var string  */
    const TABLE_PRODUCTS = 'products';

    /** @var string  */
    const TABLE_PRODUCT_FEATURES = 'product_features';

    /** @var string  */
    const TABLE_PRODUCT_FEATURE_VARIANT_DESCRIPTIONS = 'product_feature_variant_descriptions';

    /** @var string  */
    const TABLE_PRODUCT_FEATURE_VARIANTS = 'product_feature_variants';

    /** @var string  */
    const TABLE_PRODUCT_FEATURE_VALUES = 'product_features_values';

    /** @var \Tygh\Addons\ProductVariations\Tools\QueryFactory */
    protected $query_factory;

    /** @var string */
    protected $lang_code;

    /**
     * Repository constructor.
     *
     * @param \Tygh\Addons\ProductVariations\Tools\QueryFactory $query_factory
     * @param string                                            $lang_code
     */
    public function __construct(QueryFactory $query_factory, $lang_code)
    {
        $this->query_factory = $query_factory;
        $this->lang_code = $lang_code;
    }

    /**
     * Adds variation group
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\Group $group
     *
     * @return bool
     */
    public function add(Group $group)
    {
        if ($group->getId()) {
            return false;
        }

        $id = $this->createQuery(self::TABLE_GROUPS)->insert($group->toArray());
        $group->setId($id);

        $this->saveFeatures($group);
        $this->processEvents($group);

        return true;
    }

    /**
     * Removes variation group
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\Group $group
     *
     * @return bool
     */
    public function remove(Group $group)
    {
        if (!$group->getId()) {
            return false;
        }

        $group->detachAllProducts();
        $this->processEvents($group);

        $this->createQuery(self::TABLE_GROUPS, ['id' => $group->getId()])->delete();
        $this->createQuery(self::TABLE_GROUP_PRODUCTS, ['group_id' => $group->getId()])->delete();
        $this->createQuery(self::TABLE_GROUP_FEATURES, ['group_id' => $group->getId()])->delete();

        return true;
    }

    /**
     * Updates variation group
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\Group $group
     *
     * @return bool
     */
    public function update(Group $group)
    {
        if (!$group->getId()) {
            return false;
        }

        $this->processEvents($group);

        return true;
    }

    /**
     * Updates variation group code
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\Group $group
     */
    public function updateCode(Group $group)
    {
        $this->createQuery(self::TABLE_GROUPS, ['id' => $group->getId()])->update(['code' => $group->getCode()]);
    }

    /**
     * Saves variation group
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\Group $group
     *
     * @return bool
     */
    public function save(Group $group)
    {
        if ($group->getId()) {
            return $this->update($group);
        } else {
            return $this->add($group);
        }
    }

    /**
     * Finds product group by identifier
     *
     * @param int $group_id
     *
     * @return null|\Tygh\Addons\ProductVariations\Product\Group\Group
     */
    public function findGroupById($group_id)
    {
        $result = $this->findGroupsByIds([$group_id]);

        return $result ? reset($result) : null;
    }

    /**
     * Finds product groups by identifiers
     *
     * @param int[] $group_ids
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\Group[]
     */
    public function findGroupsByIds(array $group_ids)
    {
        $group_ids = array_filter($group_ids);

        if (!$group_ids) {
            return [];
        }

        $groups = [];

        $items = $this->createQuery(self::TABLE_GROUPS, ['id' => $group_ids], ['*'])->select('id');

        if ($items) {
            $products = $this->createQuery(self::TABLE_GROUP_PRODUCTS, ['group_id' => $group_ids], ['gp.*', 'p.company_id'], 'gp')
                ->addLeftJoin('p', self::TABLE_PRODUCTS, ['product_id' => 'product_id'])
                ->select(['group_id', 'product_id']);

            $features = $this->createQuery(self::TABLE_GROUP_FEATURES, ['group_id' => $group_ids], ['*'])->select(['group_id', 'feature_id']);

            foreach ($items as $item) {
                $group_id = $item['id'];

                $item_features = isset($features[$group_id]) ? $features[$group_id] : [];
                $item_products = isset($products[$group_id]) ? $products[$group_id] : [];
                $item_products_feature_values = [];

                if ($item_features && $item_products) {
                    $query = $this->createQuery(
                        self::TABLE_PRODUCT_FEATURE_VALUES,
                        ['product_id' => array_keys($item_products), 'feature_id' => array_keys($item_features), 'lang_code' => $this->lang_code],
                        ['product_id', 'feature_id', 'variant_id']
                    );

                    $item_products_feature_values = $query->select(['product_id', 'feature_id']);
                }

                $groups[] = $this->createGroup($item, $item_features, $item_products, $item_products_feature_values);
            }
        }

        return $groups;
    }

    /**
     * Finds variations group id by product identifier
     *
     * @param int $product_id
     *
     * @return null|int
     */
    public function findGroupIdByProductId($product_id)
    {
        $product_id = (int) $product_id;
        $group_id = $this->createQuery(self::TABLE_GROUP_PRODUCTS, ['product_id' => $product_id], ['group_id'])->scalar();

        if (!$group_id) {
            return null;
        }

        return (int) $group_id;
    }

    /**
     * Finds variations group id by product identifiers
     *
     * @param int[] $product_ids Product identifiers
     *
     * @return int[] [product_id => group_id]
     */
    public function findGroupIdsByProductIds(array $product_ids)
    {
        return $this->createQuery(self::TABLE_GROUP_PRODUCTS, ['product_id' => $product_ids], ['group_id', 'product_id'])
            ->column(['product_id', 'group_id']);
    }

    /**
     * Finds variations group id by feature identifiers
     *
     * @param int[] $feature_ids
     *
     * @return int[] group_ids
     */
    public function findGroupIdsByFeaturesIds(array $feature_ids)
    {
        return $this->createQuery(self::TABLE_GROUP_FEATURES, ['feature_id' => $feature_ids], ['group_id'])
            ->column();
    }

    /**
     * Finds variations group id by group code
     *
     * @param string $code
     *
     * @return null|int
     */
    public function findGroupIdByCode($code)
    {
        $code = trim($code);
        $group_id = $this->createQuery(self::TABLE_GROUPS)
            ->addCondition('code = ?s', [$code])
            ->addField('id')
            ->scalar();

        if (!$group_id) {
            return null;
        }

        return $group_id;
    }

    /**
     * Finds variations group by product identifier
     *
     * @param int $product_id
     *
     * @return null|\Tygh\Addons\ProductVariations\Product\Group\Group
     */
    public function findGroupByProductId($product_id)
    {
        $group_id = $this->findGroupIdByProductId($product_id);

        if (!$group_id) {
            return null;
        }

        return $this->findGroupById($group_id);
    }

    /**
     * Finds product groups by product feature identifier
     *
     * @param int $feature_id
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\Group[]
     */
    public function findGroupsByFeatureId($feature_id)
    {
        $feature_id = (int) $feature_id;
        $group_ids = $this->createQuery(self::TABLE_GROUP_FEATURES, ['feature_id' => $feature_id], ['group_id'])->column();

        return $this->findGroupsByIds($group_ids);
    }

    /**
     * Finds product variations group info by product identifiers
     *
     * @param int[] $product_ids
     *
     * @return array [product_id => [id => (int) group_id, code => (string) group_code, feature_ids => (array) []]]
     */
    public function findGroupInfoByProductIds(array $product_ids)
    {
        $product_ids = array_filter($product_ids);

        if (empty($product_ids)) {
            return [];
        }

        $query = $this->createQuery(
            self::TABLE_GROUP_FEATURES,
            [],
            ['f.feature_id', 'f.purpose', 'p.product_id', 'p.parent_product_id', 'g.id', 'g.code'],
            'f'
        );

        $query
            ->addInnerJoin('g', self::TABLE_GROUPS, ['group_id' => 'id'])
            ->addInnerJoin('p', self::TABLE_GROUP_PRODUCTS, ['group_id' => 'group_id'])
            ->addInCondition('product_id', $product_ids, 'p');

        $data = $query->select();
        $result = [];

        foreach ($data as $item) {
            if (!isset($result[$item['product_id']])) {
                $result[$item['product_id']] = [
                    'id'                 => (int)$item['id'],
                    'code'               => $item['code'],
                    'parent_product_id'  => (int)$item['parent_product_id'],
                    'feature_ids'        => [],
                    'feature_collection' => [],
                ];
            }

            $result[$item['product_id']]['feature_ids'][$item['feature_id']] = $item['feature_id'];
            $result[$item['product_id']]['feature_collection'][$item['feature_id']] = [
                'feature_id' => $item['feature_id'],
                'purpose'    => $item['purpose'],
            ];
        }

        return $result;
    }

    /**
     * Finds all products features values by variation group IDs
     *
     * @param array $group_ids Variation group IDs
     *
     * @return array
     */
    public function findGroupProductsFeaturesValues(array $group_ids)
    {
        $query = $this->createQuery(
            [self::TABLE_PRODUCT_FEATURE_VALUES => 'pfv'],
            ['lang_code' => $this->lang_code],
            ['pfv.feature_id', 'pfv.product_id', 'pfv.variant_id', 'gp.group_id']
        );

        $query
            ->addInnerJoin('gp', self::TABLE_GROUP_PRODUCTS, ['pfv.product_id' => 'gp.product_id'])
            ->addInnerJoin('gpf', self::TABLE_GROUP_FEATURES, ['gpf.group_id' => 'gp.group_id', 'gpf.feature_id' => 'pfv.feature_id'])
            ->addConditions(['group_id' => $group_ids], 'gp');

        return $query->select(['product_id', 'feature_id', 'variant_id']);
    }

    /**
     * Finds product variations group info by product identifier
     *
     * @param int $product_id
     *
     * @return array [id => (int) group_id, code => (string) group_code, feature_ids => (array) []]
     */
    public function findGroupInfoByProductId($product_id)
    {
        $result = $this->findGroupInfoByProductIds([$product_id]);

        return isset($result[$product_id]) ? $result[$product_id] : [];
    }

    /**
     * Find variations group feature identifiers by product identifiers
     *
     * @param array $product_ids
     *
     * @return array
     */
    public function findGroupFeatureIdsByProductIds(array $product_ids)
    {
        $product_ids = array_filter($product_ids);

        if (empty($product_ids)) {
            return [];
        }

        $query = $this->createQuery(self::TABLE_GROUP_FEATURES, [], ['f.feature_id', 'p.product_id'], 'f');

        $query
            ->addInnerJoin('g', self::TABLE_GROUPS, ['group_id' => 'id'])
            ->addInnerJoin('p', self::TABLE_GROUP_PRODUCTS, ['group_id' => 'group_id'])
            ->addInCondition('product_id', $product_ids, 'p');

        return $query->select(['product_id', 'feature_id', 'feature_id']);
    }

    /**
     * @param int $product_id
     *
     * @return array
     */
    public function findGroupFeatureIdsByProductId($product_id)
    {
        if (empty($product_id)) {
            return [];
        }

        $result = $this->findGroupFeatureIdsByProductIds([$product_id]);

        return reset($result);
    }

    /**
     * Find variations group feature identifiers by group identifier
     *
     * @param int $group_id
     *
     * @return array
     */
    public function findGroupFeatureIdsByGroupId($group_id)
    {
        $group_id = (int) $group_id;

        if (empty($group_id)) {
            return [];
        }

        $query = $this->createQuery(self::TABLE_GROUP_FEATURES, [], ['f.feature_id'], 'f');

        $query
            ->addInnerJoin('g', self::TABLE_GROUPS, ['group_id' => 'id'])
            ->addConditions(['id' => $group_id], 'g');

        return $query->column();
    }

    /**
     * @param int[] $group_ids
     *
     * @return int[]
     */
    public function findGroupProductIdsByGroupIds(array $group_ids)
    {
        $group_ids = array_filter($group_ids);

        if (empty($group_ids)) {
            return [];
        }

        $query = $this->createQuery(self::TABLE_GROUP_PRODUCTS, ['group_id' => $group_ids], ['product_id']);

        return $query->column('product_id');
    }

    /**
     * @param array $feature_ids
     *
     * @return array
     */
    public function findGroupCodesByFeatureIds(array $feature_ids)
    {
        $query = $this->createQuery(
            self::TABLE_GROUP_FEATURES,
            [],
            ['g.id', 'g.code'],
            'gf'
        );

        sort($feature_ids);

        $query
            ->addInnerJoin('g', self::TABLE_GROUPS, ['group_id' => 'id'])
            ->setGroupBy(['g.id', 'g.code'])
            ->setHaving(sprintf("COUNT(gf.feature_id) = %d AND GROUP_CONCAT(gf.feature_id ORDER BY gf.feature_id ASC SEPARATOR '_') = '%s'", count($feature_ids), implode('_', $feature_ids)));

        return $query->column(['id', 'code']);
    }

    /**
     * @param array $feature_ids
     *
     * @return array [feature_id => [group_id => [id, code]]]
     */
    public function findGroupsInfoByFeatureIds(array $feature_ids)
    {
        $query = $this->createQuery(
            self::TABLE_GROUP_FEATURES,
            [],
            ['gf.feature_id', 'g.id', 'g.code'],
            'gf'
        );

        $query
            ->addInnerJoin('g', self::TABLE_GROUPS, ['group_id' => 'id'])
            ->addConditions(['feature_id' => $feature_ids]);

        return $query->select(['feature_id', 'id']);
    }

    /**
     * @param string $code
     * @param int    $group_id
     *
     * @return bool
     */
    public function exists($code, $group_id = null)
    {
        $query = $this->createQuery(self::TABLE_GROUPS)
            ->addCondition('code = ?s', [$code])
            ->addField('id');

        if ($group_id) {
            $query->addCondition('id != ?i', [$group_id]);
        }

        return (bool) $query->scalar();
    }

    /**
     * @param array $product_ids
     *
     * @return array [child_id => parent_id]
     */
    public function getParentProductIdMap(array $product_ids)
    {
        if (empty($product_ids)) {
            return [];
        }

        $query = $this->createQuery(self::TABLE_GROUP_PRODUCTS, [], ['product_id', 'parent_product_id']);
        $query->addInCondition('product_id', $product_ids);

        return $query->column(['product_id', 'parent_product_id']);
    }

    /**
     * @param int $product_id
     *
     * @return null|int
     */
    public function getParentProductId($product_id)
    {
        $product_id = (int) $product_id;

        if (empty($product_id)) {
            return null;
        }

        $query = $this->createQuery(self::TABLE_GROUP_PRODUCTS, ['product_id' => $product_id], ['parent_product_id']);
        $result = $query->scalar();

        if ($result === '') {
            return null;
        }

        return (int) $result;
    }

    /**
     * @param int $product_id
     *
     * @return array
     */
    public function getProductChildrenIds($product_id)
    {
        if (empty($product_id)) {
            return [];
        }

        $query = $this->createQuery(self::TABLE_GROUP_PRODUCTS, [], ['product_id', 'parent_product_id']);
        $query->addCondition('parent_product_id = ?i', [$product_id]);

        return $query->column('product_id');
    }


    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\Group $group
     */
    protected function processEvents(Group $group)
    {
        /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupProduct[] $on_insert_list */
        $on_insert_list = [];
        /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupProduct[] $on_update_list */
        $on_update_list = [];
        /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupProduct[] $on_remove_list */
        $on_remove_list = [];
        /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupProduct[] $on_update_feature_values_list */
        $on_update_feature_values_list = [];
        /** @var array $on_parent_update_list */
        $on_parent_update_list = [];

        foreach ($group->getEvents() as $event) {
            if ($event instanceof ProductAddedEvent) {
                $on_insert_list[$event->getProduct()->getProductId()] = $event->getProduct();
                $on_update_feature_values_list[$event->getProduct()->getProductId()] = $event->getProduct();
            } elseif ($event instanceof ProductUpdatedEvent) {
                if (!$event->getFrom()->hasSameParentProductId($event->getTo()->getParentProductId())) {
                    $on_update_list[$event->getTo()->getProductId()] = $event->getTo();
                }
                if (!$event->getFrom()->hasSameFeatureValues($event->getTo()->getFeatureValues())) {
                    $on_update_feature_values_list[$event->getTo()->getProductId()] = $event->getTo();
                }
            } elseif ($event instanceof ProductRemovedEvent) {
                $on_remove_list[$event->getProduct()->getProductId()] = $event->getProduct();
            }
        }

        if ($on_remove_list) {
            $product_ids = [];

            foreach ($on_remove_list as $product) {
                unset($on_update_feature_values_list[$product->getProductId()]);
                unset($on_update_list[$product->getProductId()]);
                unset($on_insert_list[$product->getProductId()]);

                $product_ids[] = $product->getProductId();
                $on_parent_update_list[0][$product->getProductId()] = $product->getProductId();
            }

            $this->createQuery(self::TABLE_GROUP_PRODUCTS, ['product_id' => $product_ids, 'group_id' => $group->getId()])->delete();
        }

        if ($on_insert_list) {
            $data = [];

            foreach ($on_insert_list as $product) {
                $data[] = [
                    'group_id'          => $group->getId(),
                    'product_id'        => $product->getProductId(),
                    'parent_product_id' => $product->getParentProductId(),
                ];

                $on_parent_update_list[$product->getParentProductId()][$product->getProductId()] = $product->getProductId();
            }

            $this->createQuery(self::TABLE_GROUP_PRODUCTS)->multipleInsert($data);
        }

        if ($on_update_list) {
            foreach ($on_update_list as $product) {
                $on_parent_update_list[$product->getParentProductId()][$product->getProductId()] = $product->getProductId();

                $this->createQuery(self::TABLE_GROUP_PRODUCTS, ['product_id' => $product->getProductId(), 'group_id' => $group->getId()])->update([
                    'parent_product_id' => $product->getParentProductId()
                ]);
            }
        }

        foreach ($on_parent_update_list as $parent_product_id => $product_ids) {
            $this->createQuery(self::TABLE_PRODUCTS, ['product_id' => $product_ids])->update([
                'product_type'      => $parent_product_id ? Type::PRODUCT_TYPE_VARIATION : Type::PRODUCT_TYPE_SIMPLE,
                'parent_product_id' => $parent_product_id
            ]);
        }

        foreach ($on_update_feature_values_list as $product) {
            $values = [];
            foreach ($product->getFeatureValues() as $feature_value) {
                $values[$feature_value->getFeatureId()] = $feature_value->getVariantId();
            }
            $this->updateFeatureValues($product->getProductId(), $values);
        }

        $group->clearEvents();
    }

    /**
     * Saves product group feature identifiers
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\Group $group
     */
    protected function saveFeatures(Group $group)
    {
        $data = [];

        foreach ($group->getFeatures()->getFeatures() as $feature) {
            $data[] = [
                'group_id'   => $group->getId(),
                'feature_id' => $feature->getFeatureId(),
                'purpose'    => $feature->getFeaturePurpose()
            ];
        }

        if ($data) {
            $this->createQuery(self::TABLE_GROUP_FEATURES)->multipleInsert($data);
        }
    }

    /**
     * Creates instance of product group model
     *
     * @param array $data
     * @param array $features
     * @param array $products
     * @param array $products_feature_values
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\Group
     */
    protected function createGroup(array $data, array $features, array $products, $products_feature_values)
    {
        try {
            $data['features'] = GroupFeatureCollection::createFromFeatureList($features);
        } catch (InputException $exception) {
            $data['features'] = new GroupFeatureCollection();
        }

        try {
            $data['products'] = GroupProductCollection::createFromStorageDataList($products, $products_feature_values, $data['features']);
        } catch (InputException $exception) {
            $data['products'] = new GroupProductCollection();
        }

        return Group::createFromArray($data);
    }

    /**
     * @param string|array $table_id
     * @param array        $conditions
     * @param array        $fields
     * @param string       $table_alias
     *
     * @return \Tygh\Addons\ProductVariations\Tools\Query
     */
    protected function createQuery($table_id, array $conditions = [], array $fields = [], $table_alias = null)
    {
        return $this->query_factory->createQuery($table_id, $conditions, $fields, $table_alias);
    }

    /**
     * Updates features values used for generate variations
     *
     * @param int   $product_id Product identifier
     * @param array $values     Contains feature identifier and variants identifiers according to them
     */
    public function updateFeatureValues($product_id, array $values)
    {
        $this->createQuery(self::TABLE_PRODUCT_FEATURE_VALUES, [
            'product_id' => $product_id,
            'feature_id' => array_keys($values)
        ])->delete();

        $feature_values = $this->createQuery(self::TABLE_PRODUCT_FEATURE_VARIANT_DESCRIPTIONS,
                ['variant_id' => $values],
                [
                    'pf.feature_id', 'pfvd.variant_id', 'pfvd.lang_code',
                    "CASE WHEN(pf.feature_type='" . ProductFeatures::NUMBER_SELECTBOX . "') THEN pfvd.variant ELSE NULL END as value_int"
                ],
                'pfvd'
            )
            ->addInnerJoin('pfv', self::TABLE_PRODUCT_FEATURE_VARIANTS, ['variant_id' => 'variant_id'])
            ->addInnerJoin('pf', self::TABLE_PRODUCT_FEATURES, ['pfv.feature_id' => 'pf.feature_id'], ['feature_id' => array_keys($values)])
            ->setGroupBy(['variant_id', 'lang_code'])
            ->select();

        $insert_list = [];

        foreach ($feature_values as $value) {
            $insert_list[] = array_merge(['product_id' => $product_id], $value);
        }

        $this->createQuery(self::TABLE_PRODUCT_FEATURE_VALUES)->multipleInsert($insert_list);
    }

    /**
     * Finds all group ids if variation groups exist
     *
     * return array Group identifiers
     */
    public function findAllGroupIds()
    {
        $query = $this->createQuery(self::TABLE_GROUPS, [], ['id']);

        return $query->column();
    }
}