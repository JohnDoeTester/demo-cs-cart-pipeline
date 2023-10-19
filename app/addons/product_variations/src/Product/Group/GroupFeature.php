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
 * Class GroupFeature
 *
 * @package Tygh\Addons\ProductVariations\Product\Group
 */
class GroupFeature
{
    /** @var int */
    protected $feature_id;

    /** @var string */
    protected $feature_purpose;

    /**
     * GroupFeature constructor.
     *
     * @param int    $feature_id
     * @param string $feature_purpose
     */
    public function __construct($feature_id, $feature_purpose)
    {
        $this->feature_id = (int) $feature_id;
        $this->feature_purpose = trim($feature_purpose);
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
    public function getFeaturePurpose()
    {
        return $this->feature_purpose;
    }

    /**
     * @return bool
     */
    public function isCreateCatalogItem()
    {
        return $this->feature_purpose === FeaturePurposes::CREATE_CATALOG_ITEM;
    }

    /**
     * @return bool
     */
    public function isCreateVariationOfCatalogItem()
    {
        return $this->feature_purpose === FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'feature_id'                                  => $this->getFeatureId(),
            'purpose'                                     => $this->getFeaturePurpose(),
            'is_purpose_create_catalog_item'              => $this->isCreateCatalogItem(),
            'is_purpose_create_variation_of_catalog_item' => $this->isCreateVariationOfCatalogItem(),
        ];
    }

    /**
     * @param int    $feature_id
     * @param string $feature_purpose
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeature
     */
    public static function create($feature_id, $feature_purpose)
    {
        return new self($feature_id, $feature_purpose);
    }

    /**
     * @param array $data
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeature
     * @throws \Tygh\Exceptions\InputException
     */
    public static function createFromArray(array $data)
    {
        if (!isset($data['feature_id'], $data['purpose'])) {
            throw new InputException();
        }

        return new self($data['feature_id'], $data['purpose']);
    }

    /**
     * @param array $data_list
     *
     * @return array
     * @throws \Tygh\Exceptions\InputException
     */
    public static function createListFromArray(array $data_list)
    {
        $result = [];

        foreach ($data_list as $data) {
            $self = self::createFromArray($data);
            $result[$self->getFeatureId()] = $self;
        }

        return $result;
    }
}