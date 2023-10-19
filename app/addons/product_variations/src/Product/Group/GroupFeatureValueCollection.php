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

/**
 * Class GroupFeatureValueCollection
 *
 * @package Tygh\Addons\ProductVariations\Product\Group
 */
class GroupFeatureValueCollection
{
    /**
     * @var \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue[]
     */
    protected $feature_values = [];

    /**
     * @var \Tygh\Addons\ProductVariations\Product\Group\GroupFeature[]
     */
    protected $features = [];

    /**
     * GroupFeatureValueCollection constructor.
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue[] $feature_values
     */
    public function __construct(array $feature_values = [])
    {
        $this->setFeatureValues($feature_values);
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue[] $feature_values
     */
    public function setFeatureValues(array $feature_values)
    {
        foreach ($feature_values as $feature_value) {
            $this->addFeatureValue($feature_value);
        }
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue $feature_value
     */
    public function addFeatureValue(GroupFeatureValue $feature_value)
    {
        $this->feature_values[$feature_value->getVariantId()] = $feature_value;

        if (!isset($this->features[$feature_value->getFeatureId()])) {
            $this->features[$feature_value->getFeatureId()] = new GroupFeature(
                $feature_value->getFeatureId(),
                $feature_value->getPurpose()
            );
        }
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue[]
     */
    public function getFeatureValues()
    {
        return $this->feature_values;
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeature[]
     */
    public function getFeatures()
    {
        return $this->features;
    }

    /**
     * @param int $feature_id
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeature|null
     */
    public function getFeature($feature_id)
    {
        return isset($this->features[$feature_id]) ? $this->features[$feature_id] : null;
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection
     */
    public function getFeatureCollection()
    {
        return new GroupFeatureCollection($this->features);
    }

    /**
     * @return int[]
     */
    public function getFeatureIds()
    {
        return array_keys($this->features);
    }

    /**
     * @return int[]
     */
    public function getFeatureVariantIds()
    {
        return array_keys($this->feature_values);
    }
}