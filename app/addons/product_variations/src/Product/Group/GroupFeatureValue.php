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

/**
 * Class GroupFeatureValue
 *
 * @package Tygh\Addons\ProductVariations\Product\Group
 */
class GroupFeatureValue
{
    /** @var int */
    protected $feature_id;

    /** @var string */
    protected $purpose;

    /** @var int */
    protected $variant_id;

    /**
     * GroupFeatureValue constructor.
     *
     * @param int    $feature_id
     * @param string $purpose
     * @param int    $variant_id
     */
    public function __construct($feature_id, $purpose, $variant_id)
    {
        $this->feature_id = (int) $feature_id;
        $this->purpose = (string) $purpose;
        $this->variant_id = (string) $variant_id;
    }

    /**
     * @return int
     */
    public function getFeatureId()
    {
        return $this->feature_id;
    }

    /**
     * @return string
     */
    public function getPurpose()
    {
        return $this->purpose;
    }

    /**
     * @return int
     */
    public function getVariantId()
    {
        return $this->variant_id;
    }

    /**
     * @param int $purpose
     *
     * @return bool
     */
    public function isPurpose($purpose)
    {
        return $this->purpose === $purpose;
    }

    /**
     * @return bool
     */
    public function isPurposeCreateCatalogItem()
    {
        return $this->isPurpose(FeaturePurposes::CREATE_CATALOG_ITEM);
    }

    /**
     * @return bool
     */
    public function isPurposeCreateVariationOfCatalogItem()
    {
        return $this->isPurpose(FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM);
    }

    /**
     * Chages variant
     *
     * @param int $variant_id Product feature variant ID
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue
     */
    public function changeVariantId($variant_id)
    {
        return self::create($this->feature_id, $this->purpose, $variant_id);
    }

    /**
     * @param int    $feature_id
     * @param string $purpose
     * @param int    $variant_id
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue
     */
    public static function create($feature_id, $purpose, $variant_id)
    {
        return new self($feature_id, $purpose, $variant_id);
    }

    /**
     * @param array                                                               $product
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $features
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue[]
     */
    public static function createListFromProduct(array $product, GroupFeatureCollection $features = null)
    {
        $result = [];

        if (empty($product['variation_features'])) {
            return $result;
        }

        foreach ($product['variation_features'] as $feature) {
            $purpose = null;

            if ($features) {
                $purpose = $features->getFeaturePurpose($feature['feature_id']);
            }

            if (!$purpose) {
                $purpose = $feature['purpose'];
            }

            $result[] = self::create($feature['feature_id'], $purpose, $feature['variant_id']);
        }

        return $result;
    }

    /**
     * @param array                                                               $product_feature_values
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $features
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue[]
     */
    public static function createListFromStorageData(array $product_feature_values, GroupFeatureCollection $features)
    {
        $result = [];

        foreach ($product_feature_values as $item) {
            if (empty($item['feature_id']) || empty($item['variant_id'])) {
                continue;
            }

            $feature_id = $item['feature_id'];
            $variant_id = $item['variant_id'];

            if (!$features->hasFeature($feature_id)) {
                continue;
            }

            $result[] = self::create($feature_id, $features->getFeature($feature_id)->getFeaturePurpose(), $variant_id);
        }

        return $result;
    }
}