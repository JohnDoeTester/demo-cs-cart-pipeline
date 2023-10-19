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

use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Exceptions\InputException;

/**
 * Class GroupProduct
 *
 * @package Tygh\Addons\ProductVariations\ProductGroup
 */
class GroupProduct
{
    /** @var int */
    protected $product_id;

    /** @var int */
    protected $parent_product_id;

    /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue[] */
    protected $feature_values = [];

    /** @var int */
    protected $company_id;

    /** @var string */
    protected $combination_id;

    /** @var string */
    protected $group_combination_id;

    /**
     * GroupProduct constructor.
     *
     * @param int    $product_id
     * @param int    $parent_product_id
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue[]  $feature_values
     */
    protected function __construct($product_id, $parent_product_id, $company_id, array $feature_values)
    {
        $this->product_id = (int) $product_id;
        $this->parent_product_id = (int) $parent_product_id;
        $this->company_id = (int) $company_id;
        $this->setFeatureValues($feature_values);
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * @return int
     */
    public function getParentProductId()
    {
        return $this->parent_product_id;
    }

    /**
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * @return bool
     */
    public function hasParentProductId()
    {
        return $this->parent_product_id !== 0;
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue[]
     */
    public function getFeatureValues()
    {
        return $this->feature_values;
    }

    /**
     * @param int[] $feature_ids
     *
     * @return bool
     */
    public function hasFeatures($feature_ids)
    {
        return count($feature_ids) === count($this->feature_values)
            && array_intersect($feature_ids, array_keys($this->feature_values)) === $feature_ids;
    }

    /**
     * @param string $feature_purpose
     *
     * @return bool
     */
    public function hasFeaturePurpose($feature_purpose)
    {
        foreach ($this->feature_values as $feature_value) {
            if ($feature_value->getPurpose() == $feature_purpose) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function hasCreateVariationOfCatalogItemFeature()
    {
        return $this->hasFeaturePurpose(FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM);
    }

    /**
     * @param int $parent_product_id
     *
     * @return bool
     */
    public function hasSameParentProductId($parent_product_id)
    {
        return $this->parent_product_id === (int) $parent_product_id;
    }

    /**
     * @param int $company_id
     *
     * @return bool
     */
    public function hasSameCompanyId($company_id)
    {
        return $this->company_id === (int) $company_id;
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue[] $feature_values
     *
     * @return bool
     */
    public function hasSameFeatureValues(array $feature_values)
    {
        foreach ($feature_values as $feature_value) {
            if (!isset($this->feature_values[$feature_value->getFeatureId()])) {
                return false;
            }

            if ($this->feature_values[$feature_value->getFeatureId()]->getVariantId() !== $feature_value->getVariantId()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $group_combination_id
     *
     * @return bool
     */
    public function hasSameGroupCombinationId($group_combination_id)
    {
        return $this->group_combination_id === $group_combination_id;
    }

    /**
     * @return string
     */
    public function getCombinationId()
    {
        return $this->combination_id;
    }

    /**
     * @return string
     */
    public function getGroupCombinationId()
    {
        return $this->group_combination_id;
    }

    /**
     * @param int $parent_product_id
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct
     */
    public function changeParentProductId($parent_product_id)
    {
        return self::create($this->product_id, $parent_product_id, $this->company_id, $this->feature_values);
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue[] $feature_values
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct
     */
    public function changeFeatureValues(array $feature_values)
    {
        return self::create($this->product_id, $this->parent_product_id, $this->company_id, $feature_values);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'product_id'        => $this->getProductId(),
            'parent_product_id' => $this->getParentProductId(),
            'company_id'        => $this->getCompanyId(),
            'feature_values'    => array_map(function (GroupFeatureValue $group_feature_value) {
                return [
                    'feature_id' => $group_feature_value->getFeatureId(),
                    'variant_id' => $group_feature_value->getVariantId()
                ];
            }, $this->getFeatureValues())
        ];
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue[] $feature_values
     */
    protected function setFeatureValues(array $feature_values)
    {
        $group_variant_ids = $variant_ids = [];

        foreach ($feature_values as $feature_value) {
            $this->addFeatureValues($feature_value);

            $variant_ids[$feature_value->getVariantId()] = $feature_value->getVariantId();

            if ($feature_value->isPurposeCreateCatalogItem()) {
                $group_variant_ids[$feature_value->getVariantId()] = $feature_value->getVariantId();
            }
        }

        $this->combination_id = self::generateCombinationId($variant_ids);
        $this->group_combination_id = self::generateCombinationId($group_variant_ids);
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue $feature_value
     */
    protected function addFeatureValues(GroupFeatureValue $feature_value)
    {
        $this->feature_values[$feature_value->getFeatureId()] = $feature_value;
    }

    /**
     * @param int                                                              $product_id
     * @param int                                                              $parent_product_id
     * @param int                                                              $company_id
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue[] $feature_values
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct
     */
    public static function create($product_id, $parent_product_id, $company_id, array $feature_values)
    {
        return new self($product_id, $parent_product_id, $company_id, $feature_values);
    }

    /**
     * @param array                                                               $product
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $group_features
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct
     * @throws \Tygh\Exceptions\InputException
     */
    public static function createFromProduct(array $product, GroupFeatureCollection $group_features = null)
    {
        if (!isset($product['product_id'], $product['parent_product_id'], $product['variation_features'])) {
            throw new InputException();
        }

        return self::create(
            $product['product_id'],
            $product['parent_product_id'],
            $product['company_id'],
            GroupFeatureValue::createListFromProduct($product, $group_features)
        );
    }

    /**
     * @param array                                                               $product
     * @param array                                                               $feature_values
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $features
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct
     * @throws \Tygh\Exceptions\InputException
     */
    public static function createFromStorageData(array $product, array $feature_values, GroupFeatureCollection $features)
    {
        if (!isset($product['product_id'], $product['parent_product_id'])) {
            throw new InputException();
        }

        return self::create(
            $product['product_id'],
            $product['parent_product_id'],
            $product['company_id'],
            GroupFeatureValue::createListFromStorageData($feature_values, $features)
        );
    }

    /**
     * Generates combination identifier by variant identifiers
     *
     * @param array<int> $variant_ids Product feature variant IDs
     *
     * @return string
     */
    public static function generateCombinationId(array $variant_ids)
    {
        sort($variant_ids);
        return implode('_', $variant_ids);
    }
}
