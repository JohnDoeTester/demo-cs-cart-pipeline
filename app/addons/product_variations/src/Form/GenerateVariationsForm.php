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

namespace Tygh\Addons\ProductVariations\Form;

use Tygh\Addons\ProductVariations\Product\CombinationsGenerator;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValueCollection;
use Tygh\Addons\ProductVariations\Product\Group\Repository as GroupRepository;
use Tygh\Addons\ProductVariations\Product\Repository as ProductRepository;
use Tygh\Addons\ProductVariations\Service;
use Tygh\Addons\ProductVariations\ServiceProvider;

class GenerateVariationsForm
{
    /** @var \Tygh\Addons\ProductVariations\Product\Repository */
    protected $product_repository;

    /** @var \Tygh\Addons\ProductVariations\Product\Group\Repository */
    protected $group_repository;

    /** @var \Tygh\Addons\ProductVariations\Product\CombinationsGenerator */
    protected $combinations_generator;

    /** @var \Tygh\Addons\ProductVariations\Service */
    protected $service;

    /** @var int */
    protected $product_id;

    /** @var array */
    protected $request;

    /** @var array<string, mixed> */
    protected $product_data;

    /** @var null|\Tygh\Addons\ProductVariations\Product\Group\Group */
    protected $group;

    /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection */
    protected $feature_collection;

    /** @var null|\Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValueCollection */
    protected $feature_value_collection;

    /** @var array<string, array<string, mixed>> */
    protected $combinations;

    /** @var array<int, array<string, mixed>> */
    protected $features;

    /**
     * GenerationForm constructor.
     *
     * @param int                                                          $product_id
     * @param array                                                        $request
     * @param \Tygh\Addons\ProductVariations\Product\Repository            $product_repository
     * @param \Tygh\Addons\ProductVariations\Product\Group\Repository      $group_repository
     * @param \Tygh\Addons\ProductVariations\Product\CombinationsGenerator $combinations_generator
     * @param \Tygh\Addons\ProductVariations\Service                       $service
     */
    public function __construct(
        $product_id,
        array $request,
        ProductRepository $product_repository,
        GroupRepository $group_repository,
        CombinationsGenerator $combinations_generator,
        Service $service
    ) {
        $this->product_id = (int) $product_id;
        $this->request = (array) $request;
        $this->product_repository = $product_repository;
        $this->group_repository = $group_repository;
        $this->combinations_generator = $combinations_generator;
        $this->service = $service;
    }

    /**
     * @return array
     */
    public function getProductData()
    {
        if ($this->product_data !== null) {
            return $this->product_data;
        }
        $auth = [];

        $this->product_data = (array) fn_get_product_data(
            $this->product_id,
            $auth,
            CART_LANGUAGE,
            '',
            false,
            false,
            false,
            false,
            false,
            false
        );

        return $this->product_data;
    }

    /**
     * @return \Tygh\Addons\ProductVariations\Product\Group\Group|null
     */
    public function getGroup()
    {
        if ($this->group !== null) {
            return $this->group;
        }

        return $this->group = $this->group_repository->findGroupByProductId($this->product_id);
    }

    /**
     * @param array $request
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValueCollection
     */
    public function getFeatureValueCollection()
    {
        if ($this->feature_value_collection !== null) {
            return $this->feature_value_collection;
        }

        $group = $this->getGroup();
        $this->feature_value_collection = new GroupFeatureValueCollection();
        $features_variants_map = array_filter((array) $this->get('features_variants_ids', []));

        if ($group) {
            $this->feature_value_collection = $group->getFeatureValues();
        } elseif (!$this->has('feature_ids')) {
            $features = $this->product_repository->findAvailableFeatures($this->product_id);

            foreach ($features as $feature) {
                $this->feature_value_collection->addFeatureValue(
                    GroupFeatureValue::create($feature['feature_id'], $feature['purpose'], $feature['variant_id'])
                );
            }
        }

        if ($features_variants_map) {
            $features = $this->product_repository->findFeatures(array_keys($features_variants_map));

            foreach ($features_variants_map as $feature_id => $variant_ids) {
                if (!isset($features[$feature_id])) {
                    continue;
                }
                $feature = $features[$feature_id];
                $group_feature = $this->feature_value_collection->getFeature($feature_id);
                $purpose = $group_feature ? $group_feature->getFeaturePurpose() : $feature['purpose'];

                foreach ($variant_ids as $variant_id) {
                    $this->feature_value_collection->addFeatureValue(
                        GroupFeatureValue::create($feature_id, $purpose, $variant_id)
                    );
                }
            }
        }

        return $this->feature_value_collection;
    }

    public function getFeaturesVariantsMap()
    {
        $map = [];

        foreach ($this->getFeatureValueCollection()->getFeatureValues() as $feature_value) {
            $map[$feature_value->getFeatureId()][$feature_value->getVariantId()] = $feature_value->getVariantId();
        }

        return $map;
    }

    public function getExistsFeaturesVariantsMap()
    {
        $map = [];

        if (!$this->getGroup()) {
            return $map;
        }

        foreach ($this->getGroup()->getFeatureValues()->getFeatureValues() as $feature_value) {
            $map[$feature_value->getFeatureId()][$feature_value->getVariantId()] = $feature_value->getVariantId();
        }

        return $map;
    }

    public function getCombinations()
    {
        if ($this->combinations !== null) {
            return $this->combinations;
        }

        $this->combinations_generator->setDefaulIsActive(!empty($this->get('features_variants_ids')));

        return $this->combinations = $this->combinations_generator->generateByFeatureVariant(
            $this->getFeatureValueCollection(),
            $this->getProductIds(),
            $this->getCombinationsData()
        );
    }

    public function getNewCombinationsCount()
    {
        return array_reduce($this->getCombinations(), function ($carry, $combination) {
            if ($combination['active'] && !$combination['linked']) {
                $carry++;
            }

            return $carry;
        }, 0);
    }

    public function getFeatureCollection()
    {
        if ($this->feature_collection !== null) {
            return $this->feature_collection;
        }

        $this->feature_collection = GroupFeatureCollection::createFromFeatureList($this->getFeatures());

        return $this->feature_collection;
    }

    public function getFeatures()
    {
        if ($this->features !== null) {
            return $this->features;
        }

        $group = $this->getGroup();

        if ($group) {
            $this->features = $this->product_repository->findFeaturesByFeatureCollection(
                $group->getFeatures()
            );
        } elseif ($this->has('feature_ids')) {
            $this->features = $this->product_repository->findFeatures(
                $this->get('feature_ids', [])
            );
        } else {
            $this->features = $this->product_repository->findAvailableFeatures($this->product_id);
        }

        return $this->features;
    }

    public function getCombinationsData()
    {
        return (array) $this->get('combinations_data', []);
    }

    /**
     * Checks if all combinations are active, or not
     *
     * @return bool
     */
    public function isAllCombinationsActive()
    {
        if ($this->combinations === null) {
            return false;
        }

        foreach ($this->combinations as $combination) {
            if ($combination['active'] === false) {
                return false;
            }
        }

        return true;
    }

    protected function getProductIds()
    {
        $group = $this->getGroup();
        return $group ? $group->getProductIds() : [$this->product_id];
    }

    protected function get($key, $default = null)
    {
        return isset($this->request[$key]) ? $this->request[$key] : $default;
    }

    protected function has($key)
    {
        return isset($this->request[$key]);
    }

    public static function create($product_id, array $request)
    {
        return new self(
            $product_id,
            $request,
            ServiceProvider::getProductRepository(),
            ServiceProvider::getGroupRepository(),
            ServiceProvider::getCombinationsGenerator(),
            ServiceProvider::getService()
        );
    }
}
