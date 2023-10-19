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

use DateTime;
use DateTimeZone;
use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Addons\ProductVariations\Product\Group\Events\AEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ParentProductChangedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductAddedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductRemovedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductUpdatedEvent;

/**
 * Represents model of product variation group
 *
 * @package Tygh\Addons\ProductVariations\Product\Group
 */
class Group
{
    /** @var int Result of product add: nothing to do */
    const RESULT_NOTHING = 0;

    /** @var int Result of product add: product added */
    const RESULT_ADDED = 1;

    /** @var int Result of product add: product updated */
    const RESULT_UPDATED = 2;

    /** @var int Undefined error */
    const RESULT_ERROR_UNDEFINED = 200;

    /** @var int Result of product add: company of product does not match to group company */
    const RESULT_ERROR_PRODUCT_COMPANY_DOES_NOT_MATCH_TO_GROUP_COMPANY = 253;

    /** @var int Result of product add: combination of product already exists in group */
    const RESULT_ERROR_PRODUCT_COMBINATION_ALREADY_EXISTS = 254;

    /** @var int Result of product add: product has invalid feature values */
    const RESULT_ERROR_PRODUCT_INVALID_FEATURE_VALUES = 255;

    /** @var int */
    protected $id;

    /** @var string|null */
    protected $code;

    /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection */
    protected $features;

    /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupProductCollection */
    protected $products;

    /** @var DateTime */
    protected $created_at;

    /** @var DateTime */
    protected $updated_at;

    /** @var array */
    protected $events = [];

    /**
     * Group constructor.
     *
     * @param int                                                                 $id
     * @param string|null                                                         $code
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $features
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProductCollection $products
     * @param \DateTime                                                           $created_at
     * @param \DateTime                                                           $updated_at
     */
    protected function __construct(
        $id = 0,
        $code = null,
        GroupFeatureCollection $features = null,
        GroupProductCollection $products = null,
        $created_at = null,
        $updated_at = null
    ) {
        if ($features === null) {
            $features = new GroupFeatureCollection();
        }

        if ($products === null) {
            $products = new GroupProductCollection();
        }

        $this->setId($id);
        $this->setCode($code);
        $this->setFeatures($features);
        $this->setProducts($products);
        $this->setCreatedAt($created_at === null ? 'now' : $created_at);
        $this->setUpdatedAt($updated_at === null ? 'now' : $updated_at);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @return null|string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int $feature_id
     *
     * @return string|null
     */
    public function getFeaturePurpose($feature_id)
    {
        return $this->features->getFeaturePurpose($feature_id);
    }

    /**
     * @param string $feature_purpose
     *
     * @return bool
     */
    public function hasFeaturePurpose($feature_purpose)
    {
        return $this->features->hasFeaturePurpose($feature_purpose);
    }

    /**
     * @return bool
     */
    public function hasCreateVariationOfCatalogItemFeature()
    {
        return $this->hasFeaturePurpose(FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM);
    }

    /**
     * @param int    $feature_id
     * @param string $feature_purpose
     *
     * @return bool
     */
    public function hasFeature($feature_id, $feature_purpose)
    {
        return $this->features->hasFeature($feature_id, $feature_purpose);
    }

    /**
     * @param int $feature_id
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeature|null
     */
    public function getFeature($feature_id)
    {
        return $this->features->getFeature($feature_id);
    }

    /**
     * @return array
     */
    public function getFeatureIds()
    {
        return $this->features->getFeatureIds();
    }

    /**
     * @return array
     */
    public function getFeaturesVariantIds()
    {
        return $this->products->getFeaturesVariantIds();
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValueCollection
     */
    public function getFeatureValues()
    {
        $collection = new GroupFeatureValueCollection();

        foreach ($this->products as $group_product) {
            foreach ($group_product->getFeatureValues() as $feature_value) {
                $collection->addFeatureValue($feature_value);
            }
        }

        return $collection;
    }

    /**
     * @param string $purpose
     *
     * @return array
     */
    public function getFeatureIdsByPurpose($purpose)
    {
        return $this->features->getFeatureIdsByPurpose($purpose);
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection
     */
    public function getFeatures()
    {
        return $this->features;
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProductCollection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param int $product_id
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct|null
     */
    public function getProduct($product_id)
    {
        return $this->products->getProduct($product_id);
    }

    /**
     * @param int $product_id
     *
     * @return bool
     */
    public function hasProduct($product_id)
    {
        return $this->products->hasProduct($product_id);
    }

    /**
     * @param int $product_id
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct[]
     */
    public function getProductChildren($product_id)
    {
        return $this->products->getProductChildren($product_id);
    }

    /**
     * @param int $product_id
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct|null
     */
    public function getFirstProductChildren($product_id)
    {
        return $this->products->getFirstProductChildren($product_id);
    }

    /**
     * @return int[]
     */
    public function getProductIds()
    {
        return $this->products->getProductIds();
    }

    /**
     * @param int $product_id
     *
     * @return int[]
     */
    public function getChildProductIds($product_id)
    {
        $children = $this->getProductChildren($product_id);

        return array_map(function (GroupProduct $product) {
            return $product->getProductId();
        }, $children);
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int) $id;
    }

    /**
     * @param null|string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @param GroupProduct $product
     *
     * @return int
     */
    public function attachProduct(GroupProduct $product)
    {
        if ($this->isCombinationBelongsToProduct($product)) {
            return self::RESULT_NOTHING;
        }

        if (!$product->hasFeatures($this->getFeatureIds())) {
            return self::RESULT_ERROR_PRODUCT_INVALID_FEATURE_VALUES;
        }

        if ($this->hasCombination($product)) {
            return self::RESULT_ERROR_PRODUCT_COMBINATION_ALREADY_EXISTS;
        }

        if (!$this->hasProductsWithSameCompany($product)) {
            return self::RESULT_ERROR_PRODUCT_COMPANY_DOES_NOT_MATCH_TO_GROUP_COMPANY;
        }

        $parent_product_id = $this->getParentProductIdViaCombination($product);

        if ($this->hasProduct($product->getProductId())) {
            $exist_product = $this->getProduct($product->getProductId());

            if (!$exist_product->hasSameGroupCombinationId($product->getGroupCombinationId())) {
                $child = $this->getFirstProductChildren($product->getProductId());

                if ($child) {
                    $this->changeParentProductId($child->getParentProductId(), $child->getProductId());
                }
            }

            if ($exist_product->hasSameParentProductId($parent_product_id)) {
                $product = $exist_product->changeFeatureValues($product->getFeatureValues());

                $this->unsetProduct($exist_product);
                $this->addProduct($product);
                $this->raiseEvent(ProductUpdatedEvent::create($exist_product, $product));

                return self::RESULT_UPDATED;
            } else {
                $product = $product->changeParentProductId($parent_product_id);

                $this->unsetProduct($exist_product);
                $this->addProduct($product);
                $this->raiseEvent(ProductUpdatedEvent::create($exist_product, $product));

                return self::RESULT_UPDATED;
            }
        } else {
            $product = $product->changeParentProductId($parent_product_id);

            $this->addProduct($product);
            $this->raiseEvent(ProductAddedEvent::create($product));

            return self::RESULT_ADDED;
        }
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProductCollection $products
     *
     * @return int[]
     */
    public function attachProducts(GroupProductCollection $products)
    {
        $result = [];
        $products = $products->getProducts();

        do {
            $updated = 0;

            foreach ($products as $key => $product) {
                $add_result = $this->attachProduct($product);

                $result[$product->getProductId()] = $add_result;

                if ($add_result === self::RESULT_UPDATED) {
                    $updated++;
                }

                if (!self::isResultError($add_result)) {
                    unset($products[$key]);
                }
            }
        } while ($updated);

        return $result;
    }

    /**
     * @param int $product_id
     *
     * @return bool
     */
    public function detachProductById($product_id)
    {
        $product = $this->getProduct($product_id);

        if (!$product) {
            return true;
        }

        $child = $this->getFirstProductChildren($product->getProductId());

        if ($child) {
            $this->changeParentProductId($child->getParentProductId(), $child->getProductId());
        }

        $this->unsetProduct($product);
        $this->raiseEvent(ProductRemovedEvent::create($product));

        return true;
    }

    /**
     * Detaches all products from group
     */
    public function detachAllProducts()
    {
        foreach ($this->products->getProducts() as $product) {
            $this->unsetProduct($product);
            $this->raiseEvent(ProductRemovedEvent::create($product));
        }
    }

    /**
     * Changes main product
     *
     * @param int $main_product_id
     *
     * @return bool
     */
    public function setDefaultProduct($main_product_id)
    {
        if (!$this->hasProduct($main_product_id)) {
            return false;
        }

        $product = $this->getProduct($main_product_id);

        if (!$product->getParentProductId()) {
            return false;
        }

        if (!$this->hasProduct($product->getParentProductId())) {
            return false;
        }

        $current_main_product = $this->getProduct($product->getParentProductId());

        $this->changeParentProductId($current_main_product->getProductId(), $product->getProductId());

        $product = $current_main_product->changeParentProductId($product->getProductId());

        $this->unsetProduct($product);
        $this->addProduct($product);

        $this->raiseEvent(ProductUpdatedEvent::create($current_main_product, $product));

        return true;
    }

    /**
     * @param int $from_id
     * @param int $to_id
     *
     * @return bool
     */
    protected function changeParentProductId($from_id, $to_id)
    {
        $parent = $this->getProduct($from_id);
        $child = $this->getProduct($to_id);

        if (!$child || !$child->hasSameParentProductId($parent->getProductId())) {
            return false;
        }

        $children = $this->getProductChildren($from_id);

        foreach ($children as $item) {
            $this->unsetProduct($item);

            if ($item === $child) {
                $product = $item->changeParentProductId(0);
                $this->raiseEvent(ParentProductChangedEvent::create($parent, $product));
            } else {
                $product = $item->changeParentProductId($child->getProductId());
            }

            $this->addProduct($product);
            $this->raiseEvent(ProductUpdatedEvent::create($item, $product));
        }

        return true;
    }

    /**
     * @param \DateTime|int|string $created_at
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $this->convertToDateTime($created_at);
    }

    /**
     * @param \DateTime|int|string $updated_at
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $this->convertToDateTime($updated_at);
    }

    /**
     * Converts instance to array
     *
     * @param bool $deep
     *
     * @return array
     */
    public function toArray($deep = false)
    {
        return [
            'id'          => $this->getId(),
            'code'        => $this->getCode(),
            'features'    => $this->getFeatures()->toArray($deep),
            'products'    => $this->getProducts()->toArray($deep),
            'created_at'  => $this->getCreatedAt()->getTimestamp(),
            'updated_at'  => $this->getUpdatedAt()->getTimestamp()
        ];
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->products->isEmpty();
    }

    /**
     * @return AEvent[]
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Clear events
     */
    public function clearEvents()
    {
        $this->events = [];
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue $feature_value
     *
     * @return bool
     */
    public function hasFeatureValue(GroupFeatureValue $feature_value)
    {
        return $this->products->hasFeatureValue($feature_value);
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $group_feature_list
     */
    protected function setFeatures(GroupFeatureCollection $group_feature_list)
    {
        $this->features = $group_feature_list;
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeature $group_feature
     */
    protected function setFeature(GroupFeature $group_feature)
    {
        $this->features[$group_feature->getFeatureId()] = $group_feature;
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct $product
     */
    protected function unsetProduct(GroupProduct $product)
    {
        $this->products->unsetProduct($product);
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct $product
     */
    protected function addProduct(GroupProduct $product)
    {
        $this->products->addProduct($product);
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProductCollection $products
     */
    protected function setProducts(GroupProductCollection $products)
    {
        $this->products = $products;
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\Events\AEvent $event
     */
    protected function raiseEvent(AEvent $event)
    {
        $this->events[] = $event;
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct $product
     *
     * @return bool
     */
    protected function hasCombination(GroupProduct $product)
    {
        return $this->products->hasCombination($product);
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct $product
     *
     * @return bool
     */
    protected function hasProductsWithSameCompany(GroupProduct $product)
    {
        if ($this->products->isEmpty()) {
            return true;
        }

        $this->products->rewind();
        $first_product = $this->products->current();

        return $first_product->hasSameCompanyId($product->getCompanyId());
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct $product
     *
     * @return bool
     */
    protected function isCombinationBelongsToProduct(GroupProduct $product)
    {
        return $this->products->isCombinationBelongsToProduct($product);
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct $product
     *
     * @return int
     */
    protected function getParentProductIdViaCombination(GroupProduct $product)
    {
        return $this->products->getParentProductIdViaCombination($product);
    }

    /**
     * @param DateTime|int|string $time
     *
     * @return \DateTime
     */
    protected static function convertToDateTime($time)
    {
        if ($time instanceof DateTime) {
            return $time;
        } elseif (is_numeric($time)) {
            $time = DateTime::createFromFormat('U', $time);
            $time->setTimezone(new DateTimeZone(date_default_timezone_get()));

            return $time;
        } else {
            return date_create($time);
        }
    }

    /**
     * @param int $result
     *
     * @return bool
     */
    public static function isResultError($result)
    {
        return in_array($result, [
            self::RESULT_ERROR_PRODUCT_COMBINATION_ALREADY_EXISTS,
            self::RESULT_ERROR_PRODUCT_INVALID_FEATURE_VALUES,
            self::RESULT_ERROR_PRODUCT_COMPANY_DOES_NOT_MATCH_TO_GROUP_COMPANY,
            self::RESULT_ERROR_UNDEFINED,
        ], true);
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $features
     * @param string|null                                                         $code
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\Group
     */
    public static function createNewGroup(GroupFeatureCollection $features, $code = null)
    {
        $self = new self();
        $self->setFeatures($features);

        if ($code !== null) {
            $self->setCode($code);
        }

        return $self;
    }

    /**
     * Creates instance by array
     *
     * @param array $data
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\Group
     */
    public static function createFromArray(array $data)
    {
        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = new GroupFeatureCollection($data['features']);
        }

        if (isset($data['products']) && is_array($data['products'])) {
            $data['products'] = new GroupProductCollection($data['products']);
        }

        return new self(
            array_key_exists('id', $data) ? $data['id'] : 0,
            array_key_exists('code', $data) ? $data['code'] : null,
            array_key_exists('features', $data) ? $data['features'] : null,
            array_key_exists('products', $data) ? $data['products'] : null,
            array_key_exists('created_at', $data) ? self::convertToDateTime($data['created_at']) : self::convertToDateTime('now'),
            array_key_exists('updated_at', $data) ? self::convertToDateTime($data['updated_at']) : self::convertToDateTime('now')
        );
    }
}
