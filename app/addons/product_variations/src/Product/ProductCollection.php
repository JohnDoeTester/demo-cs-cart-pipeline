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


namespace Tygh\Addons\ProductVariations\Product;

/**
 * Class ProductCollection
 *
 * @package Product
 */
class ProductCollection
{
    /** @var array  */
    protected $products = [];

    /** @var int[] */
    protected $product_ids = [];

    /** @var int[] */
    protected $group_ids;

    /** @var bool */
    protected $is_priview = false;

    /** @var array */
    protected $variation_groups_info = [];

    /** @var array  */
    protected $variation_groups_features = [];

    /**
     * ProductCollection constructor.
     *
     * @param array $products
     */
    public function __construct(array $products)
    {
        foreach ($products as $product) {
            $this->addProduct($product);
        }
    }

    /**
     * Adds product to collection
     *
     * @param array $product
     */
    public function addProduct(array $product)
    {
        if (!isset($product['product_id'])) {
            return;
        }

        $product_id = (int) $product['product_id'];

        $this->products[$product_id] = $product;
        $this->product_ids[$product_id] = $product_id;

        if (!empty($product['detailed_params']['is_preview'])) {
            $this->is_priview = true;
        }

        if (isset($product['variation_group_id'])) {
            $group_id = (int) $product['variation_group_id'];

            $this->group_ids[$group_id] = $group_id;

            if (!isset($this->variation_groups_info[$group_id])) {
                $this->variation_groups_info[$group_id] = [
                    'variation_feature_ids'        => $product['variation_feature_ids'],
                    'variation_feature_collection' => $product['variation_feature_collection'],
                    'variation_group_id'           => $product['variation_group_id'],
                    'variation_group_code'         => $product['variation_group_code']
                ];
            }

            if (isset($product['variation_features'])) {
                foreach ($product['variation_features'] as $feature_id => $feature) {
                    if (isset($this->variation_groups_features[$group_id][$feature_id])) {
                        continue;
                    }

                    $this->variation_groups_features[$group_id][$feature_id] = $feature;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @return int[]
     */
    public function getProductIds()
    {
        return $this->product_ids;
    }

    /**
     * @return int[]
     */
    public function getGroupIds()
    {
        return $this->group_ids;
    }

    /**
     * @return bool
     */
    public function hasGroupIds()
    {
        return !empty($this->group_ids);
    }

    /**
     * @return bool
     */
    public function hasPreviewMarks()
    {
        return $this->is_priview;
    }

    /**
     * @return array
     */
    public function getVariationGroupsInfo()
    {
        return $this->variation_groups_info;
    }

    /**
     * @return array
     */
    public function getVariationGroupsFeatures()
    {
        return $this->variation_groups_features;
    }

    /**
     * @param int $group_id
     *
     * @return bool
     */
    public function hasVariationGroupFeatures($group_id)
    {
        return isset($this->variation_groups_features[$group_id]);
    }

    /**
     * @param int $group_id
     * @param int $feature_id
     *
     * @return bool
     */
    public function hasVariationGroupFeature($group_id, $feature_id)
    {
        return isset($this->variation_groups_features[$group_id][$feature_id]);
    }

    /**
     * @param int $group_id
     * @param int $feature_id
     *
     * @return array
     */
    public function getVariationGroupFeature($group_id, $feature_id)
    {
        return $this->hasVariationGroupFeature($group_id, $feature_id)
            ? $this->variation_groups_features[$group_id][$feature_id]
            : [];
    }

    /**
     * @param int $group_id
     * @param int $feature_id
     *
     * @return array
     */
    public function getVariationGroupFeatures($group_id)
    {
        return $this->hasVariationGroupFeatures($group_id)
            ? $this->variation_groups_features[$group_id]
            : [];
    }
}