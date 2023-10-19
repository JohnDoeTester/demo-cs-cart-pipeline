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

use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;
use Tygh\Addons\ProductVariations\Product\Group\GroupProduct;
use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Addons\ProductVariations\Tools\QueryFactory;
use Tygh\Addons\ProductVariations\Product\Group\Repository as GroupRepository;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProductFeatures;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Registry;

/**
 * This class implements the methods for selecting products from the database
 *
 * @package Tygh\Addons\ProductVariations\Product
 */
class Repository
{
    /** @var string  */
    const TABLE_PRODUCTS = 'products';

    /** @var string  */
    const TABLE_PRODUCT_DESCRIPTIONS = 'product_descriptions';

    /** @var string  */
    const TABLE_PRODUCT_ULT_DESCRIPTIONS = 'ult_product_descriptions';

    /** @var string  */
    const TABLE_PRODUCT_FEATURE_VARIANTS = 'product_feature_variants';

    /** @var string  */
    const TABLE_PRODUCT_FEATURE_VARIANT_DESCRIPTIONS = 'product_feature_variant_descriptions';

    /** @var string  */
    const TABLE_PRODUCT_FEATURES = 'product_features';

    /** @var string  */
    const TABLE_PRODUCT_FEATURE_DESCRIPTIONS = 'product_features_descriptions';

    /** @var string  */
    const TABLE_PRODUCT_FEATURE_VALUES = 'product_features_values';

    /** @var string  */
    const TABLE_PRODUCT_GLOBAL_OPTION_LINKS = 'product_global_option_links';

    /** @var string */
    const TABLE_PRODUCTS_CATEGORIES = 'products_categories';

    /** @var string  */
    const TABLE_PRODUCT_FILTERS = 'product_filters';

    /** @var string  */
    const TABLE_PRODUCT_POPULARITY = 'product_popularity';

    /** @var string  */
    const TABLE_IMAGE_LINKS = 'images_links';

    /** @var \Tygh\Addons\ProductVariations\Product\Group\Repository  */
    protected $group_repository;

    /** @var \Tygh\Addons\ProductVariations\Tools\QueryFactory */
    protected $query_factory;

    /** @var array */
    protected $lang_codes = [];

    /** @var string */
    protected $lang_code;

    /** @var string */
    protected $current_area;

    /**
     * Repository constructor.
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\Repository $group_repository
     * @param \Tygh\Addons\ProductVariations\Tools\QueryFactory       $query_factory
     * @param string                                                  $current_area
     * @param string                                                  $lang_code
     * @param array                                                   $lang_codes
     */
    public function __construct(
        GroupRepository $group_repository,
        QueryFactory $query_factory,
        $current_area,
        $lang_code,
        array $lang_codes = []
    ) {
        $this->group_repository = $group_repository;
        $this->query_factory = $query_factory;
        $this->current_area = $current_area;
        $this->lang_code = $lang_code;
        $this->lang_codes = $lang_codes;
    }

    /**
     * Finds products by product identifiers
     *
     * @param array       $product_ids
     * @param array       $extends
     * @param string|null $area
     *
     * @return array Indexed by product_id
     */
    public function findProducts(array $product_ids, array $extends = ['product_name'], $area = 'A')
    {
        $result = [];

        if (empty($product_ids)) {
            return $result;
        }

        if ($area === SiteArea::ADMIN_PANEL) {
            $runtime_company_id = Registry::get('runtime.company_id');
            Registry::set('runtime.company_id', 0);
        }

        list($products) = fn_get_products([
            'area'                   => $area,
            'extend'                 => $extends,
            'pid'                    => $product_ids,
            'group_child_variations' => false,
            'skip_rating'            => true,
            'sort_by'                => 'null'
        ], 0, $this->getLangCode());

        if ($area === SiteArea::ADMIN_PANEL) {
            /** @psalm-suppress PossiblyUndefinedVariable */
            Registry::set('runtime.company_id', $runtime_company_id);
        }

        foreach ($product_ids as $product_id) {
            if (!isset($products[$product_id])) {
                continue;
            }
            $result[$product_id] = $products[$product_id];
        }

        return $result;
    }

    /**
     * Finds products by params (@see fn_get_products)
     *
     * @param array $params
     *
     * @return array
     */
    public function findProductsByParams(array $params)
    {
        list($products) = fn_get_products($params, 0, $this->getLangCode());

        return $products;
    }

    /**
     * Finds variation products by products collections
     *
     * @param \Tygh\Addons\ProductVariations\Product\ProductCollection $product_collection
     *
     * @return array
     */
    public function findVariationProductsByProductCollection(ProductCollection $product_collection)
    {
        if (!$product_collection->hasGroupIds()) {
            return [];
        }

        $group_ids = $product_collection->getGroupIds();

        $products = $this->findProductsByParams([
            'area'                       => $product_collection->hasPreviewMarks() ? SiteArea::ADMIN_PANEL : $this->current_area,
            'custom_extend'              => ['product_name', 'categories'],
            'group_child_variations'     => false,
            'include_child_variations'   => true,
            'skip_rating'                => true,
            'load_products_extra_data'   => false,
            'sort_by'                    => 'null',
            'variation_group_id'         => $group_ids,
            'get_amount'                 => true,
        ]);

        if (YesNo::toBool(Registry::get('addons.product_variations.variations_show_all_possible_feature_variants'))) {
            $product_ids = ServiceProvider::getGroupRepository()->findGroupProductIdsByGroupIds($group_ids);
            $all_products = [];

            foreach ($product_ids as $product_id) {
                if (count($group_ids) > 1) {
                    $group_id = ServiceProvider::getGroupRepository()->findGroupIdByProductId($product_id);
                } else {
                    $group_id = reset($group_ids);
                }
                $all_products[$product_id] = [
                    'product_id'         => $product_id,
                    'variation_group_id' => $group_id,
                    'amount'             => 0
                ];

                if (!in_array($product_id, array_keys($products))) {
                    $all_products[$product_id]['disabled'] = true;
                }
            }
            $products = array_replace($all_products, $products);
        }

        if (empty($products)) {
            return $products;
        }

        $products_features_values = $this->group_repository->findGroupProductsFeaturesValues($group_ids);

        if (empty($products_features_values)) {
            return $products;
        }

        $variant_ids = [];

        foreach ($products_features_values as $product_id => $features_values) {
            if (!isset($products[$product_id])) {
                unset($products_features_values[$product_id]);
                continue;
            }

            foreach ($features_values as $variant_id) {
                $variant_ids[$variant_id] = $variant_id;
            }
        }

        if (empty($variant_ids)) {
            return $products;
        }

        $feature_variants = $this->findFeaturesVariants($variant_ids);

        foreach ($products as &$product) {
            $product_id = $product['product_id'];
            $group_id = (int) $product['variation_group_id'];

            $product['variation_features'] = [];

            if (!isset($products_features_values[$product_id])) {
                continue;
            }

            foreach ($product_collection->getVariationGroupFeatures($group_id) as $feature_id => $variation_feature) {
                if (!isset($products_features_values[$product_id][$feature_id])) {
                    break;
                }

                $variant_id = $products_features_values[$product_id][$feature_id];

                if (!isset($feature_variants[$variant_id])) {
                    break;
                }

                $variation_feature['variant_id'] = $variant_id;
                $variation_feature['variant'] = $feature_variants[$variant_id]['variant'];
                $variation_feature['variant_position'] = $feature_variants[$variant_id]['position'];

                $product['variation_features'][$feature_id] = $variation_feature;
            }
        }
        unset($product);

        return $products;
    }

    /**
     * Finds product by product identifier
     *
     * @param int   $product_id
     * @param array $extends
     *
     * @return array
     */
    public function findProduct($product_id, array $extends = ['product_name'])
    {
        $result = $this->findProducts([$product_id], $extends);

        return reset($result);
    }

    /**
     * Finds features by feature identifiers, indexed by feature_id
     *
     * @param array $feature_ids
     *
     * @return array
     */
    public function findFeatures(array $feature_ids)
    {
        $query = $this->createQuery(
            self::TABLE_PRODUCT_FEATURES,
            ['feature_id' => $feature_ids],
            [
                'pf.feature_id', 'pf.feature_style', 'pf.position', 'pf.purpose', 'pf.display_on_catalog',
                'pfd.description', 'pfd.internal_name', 'pfd.prefix', 'pfd.suffix'
            ],
            'pf'
        );

        $query
            ->addField(sprintf("(CASE WHEN pf.purpose = '%s' THEN 0 ELSE 1 END) AS purpose_position", FeaturePurposes::CREATE_CATALOG_ITEM))
            ->addInnerJoin(
                'pfd',
                self::TABLE_PRODUCT_FEATURE_DESCRIPTIONS,
                ['feature_id' => 'feature_id'],
                ['lang_code' => $this->lang_code]
            )
            ->setOrderBy(['purpose_position ASC', 'pf.position ASC', 'pf.feature_id ASC']);

        return $query->executeWithoutSharing(function () use ($query) {
            return $query->select('feature_id');
        });
    }

    /**
     * Finds features variants by variant IDs
     *
     * @param array $variant_ids Feature variants IDs
     *
     * @return array
     */
    public function findFeaturesVariants(array $variant_ids)
    {
        $query = $this->createQuery(
            [self::TABLE_PRODUCT_FEATURE_VARIANTS => 'pfv'],
            ['variant_id' => $variant_ids],
            ['pfv.variant_id', 'pfv.position', 'pfvd.variant']
        );

        $query->addInnerJoin('pfvd', self::TABLE_PRODUCT_FEATURE_VARIANT_DESCRIPTIONS,
            ['variant_id' => 'variant_id'],
            ['lang_code' => $this->lang_code]
        );

        return $query->select('variant_id');
    }

    /**
     * Finds features by group feature list, indexed by feature_id
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $group_features
     *
     * @return array
     */
    public function findFeaturesByFeatureCollection(GroupFeatureCollection $group_features)
    {
        $features = $this->findFeatures($group_features->getFeatureIds());

        foreach ($features as $feature_id => &$feature) {
            $feature['product_feature_purpose'] = $feature['purpose'];
            $feature['purpose'] = $group_features[$feature_id]->getFeaturePurpose();
        }
        unset($feature);

        return $this->sortFeaturesByPurposeAndPosition($features);
    }

    /**
     * Find usable to create variations group features
     *
     * @param int $product_id
     *
     * @return array
     */
    public function findAvailableFeatures($product_id)
    {
        list($features_list) = fn_get_product_features([
            'product_id'    => $product_id,
            'existent_only' => true,
            'exclude_group' => true,
            'purpose'       => FeaturePurposes::getAll(),
            'feature_types' => [
                ProductFeatures::TEXT_SELECTBOX,
                ProductFeatures::NUMBER_SELECTBOX
            ],
            'statuses'      => [
                ObjectStatuses::ACTIVE,
                ObjectStatuses::HIDDEN
            ]
        ]);

        return $features_list;
    }

    /**
     * Loads variants for features
     *
     * @param array $features
     *
     * @return array
     */
    public function loadFeaturesVariants(array $features)
    {
        $feature_ids = array_column($features, 'feature_id');

        $query = $this->createQuery(
            self::TABLE_PRODUCT_FEATURE_VARIANTS,
            ['feature_id' => $feature_ids],
            ['fv.feature_id', 'fv.variant_id', 'fv.position', 'fvd.variant'],
            'fv'
        );

        $query
            ->addInnerJoin(
                'fvd',
                self::TABLE_PRODUCT_FEATURE_VARIANT_DESCRIPTIONS,
                ['variant_id' => 'variant_id'],
                ['lang_code' => $this->lang_code]
            )
            ->setOrderBy(['fv.position ASC', 'fvd.variant ASC', 'fv.variant_id ASC']);

        $features_variants = $query->select(['feature_id', 'variant_id']);

        foreach ($features as &$feature) {
            $feature['variants'] = $features_variants[$feature['feature_id']];
        }
        unset($feature);

        return $features;
    }

    /**
     * Loads variations features for products
     *
     * @param array $products
     * @param bool  $load_features
     *
     * @return array
     */
    public function loadProductsGroupInfo(array $products, $load_features = true)
    {
        $product_ids = array_column($products, 'product_id');

        $product_to_group_info = $this->group_repository->findGroupInfoByProductIds($product_ids);

        foreach ($products as &$product) {
            if (!isset($product_to_group_info[$product['product_id']])) {
                continue;
            }

            $group_info = $product_to_group_info[$product['product_id']];

            $product['variation_feature_ids'] = $group_info['feature_ids'];
            $product['variation_feature_collection'] = $group_info['feature_collection'];
            $product['variation_group_id'] = (int) $group_info['id'];
            $product['variation_group_code'] = $group_info['code'];
            $product['variation_parent_product_id'] = (int) $group_info['parent_product_id'];
            $product['variation_sub_group_id'] = sprintf('%s_%s',
                $group_info['id'],
                empty($group_info['parent_product_id']) ? $product['product_id'] : $group_info['parent_product_id']
            );
        }
        unset($product);

        if ($load_features) {
            return $this->loadProductsFeatures($products);
        }

        return $products;
    }

    /**
     * Loads variations features for product
     *
     * @param array $product
     *
     * @return array
     */
    public function loadProductGroupInfo(array $product)
    {
        $result = $this->loadProductsGroupInfo([$product]);

        return reset($result);
    }

    /**
     * Loads variations features for products
     *
     * @param array                                                               $products
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $group_features
     *
     * @return array
     */
    public function loadProductsFeatures(array $products, GroupFeatureCollection $group_features = null)
    {
        if (empty($products)) {
            return $products;
        }

        $lang_code = $this->lang_code;
        $sorted_features = $feature_collections = [];
        $feature_ids = $product_ids = [];

        if ($group_features) {
            $feature_collections[0] = $group_features;
            $feature_ids = $group_features->getFeatureIds();

            foreach ($products as &$product) {
                $product['variation_features'] = [];
                $product['variation_feature_ids'] = $group_features->getFeatureIds();
                $product['variation_feature_collection'] = $group_features->toArray();

                if (!isset($product['variation_group_id'])) {
                    $product['variation_group_id'] = 0;
                }

                $product_ids[] = $product['product_id'];
            }
            unset($product);
        } else {
            foreach ($products as &$product) {
                $product['variation_features'] = [];

                if (!isset($product['variation_feature_collection'], $product['variation_group_id'])) {
                    continue;
                }

                if (!isset($feature_collections[$product['variation_group_id']])) {
                    $feature_collection = GroupFeatureCollection::createFromFeatureList($product['variation_feature_collection']);
                    $feature_collections[$product['variation_group_id']] = $feature_collection;

                    $feature_ids = array_merge($feature_ids, $feature_collection->getFeatureIds());
                }

                $product_ids[] = $product['product_id'];
            }
            unset($product);

            $feature_ids = array_unique($feature_ids);
        }

        if (empty($feature_ids)) {
            return $products;
        }

        $features = $this->findFeatures($feature_ids);

        if (empty($features)) {
            return $products;
        }

        /**
         * @var int                    $features_group_id
         * @var GroupFeatureCollection $feature_collection
         */
        foreach ($feature_collections as $features_group_id => $feature_collection) {
            $sorted_features[$features_group_id] = [];

            foreach ($feature_collection as $group_feature) {
                if (!isset($features[$group_feature->getFeatureId()])) {
                    continue;
                }

                $feature = $features[$group_feature->getFeatureId()];
                $feature['product_feature_purpose'] = $feature['purpose'];
                $feature['purpose'] = $group_feature->getFeaturePurpose();

                $sorted_features[$features_group_id][$feature['feature_id']] = $feature;
            }

            $sorted_features[$features_group_id] = $this->sortFeaturesByPurposeAndPosition($sorted_features[$features_group_id]);
        }

        $query = $this->createQuery(
            self::TABLE_PRODUCT_FEATURE_VALUES,
            ['feature_id' => $feature_ids, 'product_id' => $product_ids, 'lang_code' => $lang_code],
            ['pfv.feature_id', 'pfv.product_id', 'pfv.variant_id', 'fv.position', 'fvd.variant'],
            'pfv'
        );

        $query
            ->addInnerJoin('fv', self::TABLE_PRODUCT_FEATURE_VARIANTS, ['feature_id' => 'feature_id', 'variant_id' => 'variant_id'])
            ->addInnerJoin('fvd', self::TABLE_PRODUCT_FEATURE_VARIANT_DESCRIPTIONS, ['variant_id' => 'variant_id'], ['lang_code' => $lang_code]);

        $product_variants = $query->select(['product_id', 'feature_id']);

        foreach ($products as &$product) {
            if (!isset($product_variants[$product['product_id']], $product['variation_group_id'])) {
                continue;
            }

            $features_group_id = $group_features ? 0 : $product['variation_group_id'];

            $product['variation_features'] = [];

            foreach ($sorted_features[$features_group_id] as $feature_id => $feature) {
                if (!isset($product_variants[$product['product_id']][$feature_id])) {
                    continue;
                }

                $variant = $product_variants[$product['product_id']][$feature_id];

                $feature['variant'] = $variant['variant'];
                $feature['variant_id'] = $variant['variant_id'];
                $feature['variant_position'] = $variant['position'];

                $product['variation_features'][$feature_id] = $feature;
            }
        }
        unset($product);

        return $products;
    }

    /**
     * Loads variations features for product
     *
     * @param array                                                               $product
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $features
     *
     * @return array
     */
    public function loadProductFeatures(array $product, GroupFeatureCollection $features = null)
    {
        $result = $this->loadProductsFeatures([$product], $features);

        return reset($result);
    }

    /**
     * Loads features combinations variants for products
     *
     * @param array $products
     *
     * @return array
     */
    public function loadProductsFeaturesVariants(array $products)
    {
        if (empty($products)) {
            return $products;
        }

        $product_collection = new ProductCollection($products);

        if (!$product_collection->hasGroupIds()) {
            return $products;
        }

        $variation_products = $this->findVariationProductsByProductCollection($product_collection);

        if (empty($variation_products)) {
            return $products;
        }

        $group_combinations = $this->getGroupCombinations($variation_products);
        $group_features_variants = $this->getGroupFeaturesVariants($variation_products);
        $combinations_products = [];

        foreach ($products as &$product) {
            if (empty($product['variation_group_id']) || empty($product['variation_features'])) {
                continue;
            }

            $product['variation_features_variants'] = [];

            $group_id = $product['variation_group_id'];
            $selected_variant_ids = array_column($product['variation_features'], 'variant_id', 'feature_id');
            $variant_ids = [];

            foreach ($product['variation_features'] as $feature_id => $feature) {
                $feature['variants'] = [];

                if (!isset($group_features_variants[$group_id][$feature_id])) {
                    continue;
                }

                $group_feature_variants = $group_features_variants[$group_id][$feature_id];

                foreach ($group_feature_variants as $variation_variant_id => $variation_variant) {
                    $variant_ids[$feature_id] = $variation_variant_id;
                    $variation_variant_ids = array_replace($selected_variant_ids, [
                        $feature_id => $variation_variant_id
                    ]);

                    $combination_id = $this->generateCombinationId($variation_variant_ids);

                    if (!isset($group_combinations[$group_id][$combination_id])) {
                        $combination_id = $this->generateCombinationId($variant_ids);
                    }

                    $feature['variants'][$variation_variant_id] = $variation_variant;

                    if (isset($group_combinations[$group_id][$combination_id])) {
                        $product_id = $group_combinations[$group_id][$combination_id]['product_id'];
                        $showed_product_id = 0;
                        $amount = $variation_products[$product_id]['amount'];
                        if (empty($amount)) {
                            $product_id_map = ServiceProvider::getProductIdMap();
                            if (
                                $feature['purpose'] === FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                                && !$product_id_map->isParentProduct($product_id)
                            ) {
                                $variation_subgroup_product_ids = [];
                            } else {
                                $variation_subgroup_product_ids = $product_id_map->getVariationSubGroupProductIds($product_id);
                            }
                            if (!empty($variation_subgroup_product_ids)) {
                                foreach ($variation_subgroup_product_ids as $id) {
                                    if (empty($variation_products[$id]['amount'])) {
                                        continue;
                                    }
                                    $showed_product_id = $id;
                                    $amount = $variation_products[$showed_product_id]['amount'];
                                    break;
                                }
                            }
                        }

                        $combinations_products[$product_id] = $variation_products[$product_id];

                        $feature['variants'][$variation_variant_id] = array_merge($variation_variant, [
                            'product_id'        => $product_id,
                            'amount'            => $amount,
                            'showed_product_id' => $showed_product_id,
                        ]);
                    }
                }

                $variant_ids[$feature_id] = $feature['variant_id'];

                $feature['variants'] = $this->sortFeatureVariantsByPosition($feature['variants']);
                $product['variation_features_variants'][$feature_id] = $feature;
                $product['has_child_variations'] = FeaturePurposes::isCreateVariationOfCatalogItem($feature['purpose'])
                    && !empty($feature['variants']);
            }
        }
        unset($product);

        if ($combinations_products) {
            $combinations_products = $this->loadProductsMainImage($combinations_products);

            foreach ($products as &$product) {
                if (empty($product['variation_features_variants'])) {
                    continue;
                }

                foreach ($product['variation_features_variants'] as &$feature) {
                    foreach ($feature['variants'] as &$variant) {
                        if (empty($variant['product_id'])) {
                            continue;
                        }

                        $product_id = $variant['product_id'];
                        $variant['product'] = $combinations_products[$product_id];
                    }
                    unset($variant);
                }
                unset($feature);
            }
            unset($product);
        }

        return $products;
    }

    /**
     * Loads features combinations variants for product
     *
     * @param array $product
     *
     * @return array
     */
    public function loadProductFeaturesVariants(array $product)
    {
        $result = $this->loadProductsFeaturesVariants([$product]);

        return reset($result);
    }

    /**
     * Loads products main images
     *
     * @param array $products
     *
     * @return array
     */
    public function loadProductsMainImage(array $products)
    {
        $product_ids = array_column($products, 'product_id');

        $products_images = fn_get_image_pairs($product_ids, 'product', 'M', false, true, $this->lang_code);

        foreach ($products as &$product) {
            $product_id = $product['product_id'];

            if (empty($product['main_pair']) && !empty($products_images[$product_id])) {
                $product['main_pair'] = reset($products_images[$product_id]);
            }
        }
        unset($product);

        return $products;
    }

    /**
     * Loads the variation name for products
     *
     * @param array $products
     *
     * @return array
     */
    public function loadProductsVariationName(array $products)
    {
        foreach ($products as &$product) {
            if (empty($product['variation_features'])) {
                continue;
            }

            $variant_names = [];

            foreach ($product['variation_features'] as $feature) {
                if (FeaturePurposes::isCreateVariationOfCatalogItem($feature['purpose'])) {
                    $variant_names[] = $feature['variant'];
                }
            }

            if ($variant_names) {
                $product['variation_name'] = sprintf('%s (%s)', $product['product'], implode(', ', $variant_names));
            } else {
                $product['variation_name'] = $product['product'];
            }
        }
        unset($product);

        return $products;
    }

    /**
     * Loads the variation name for product
     *
     * @param array $product
     *
     * @return array
     */
    public function loadProductVariationName(array $product)
    {
        $result = $this->loadProductsVariationName([$product]);

        return reset($result);
    }

    /**
     * Generates combination identifier for products
     *
     * @param array $products
     *
     * @return array
     */
    public function generateProductsCombinationId(array $products)
    {
        foreach ($products as &$product) {
            $product['variation_combination_id'] = null;
            $product['parent_variation_combination_id'] = null;

            if (empty($product['variation_feature_ids'])) {
                continue;
            }

            $parent_variant_ids = $variant_ids = [];

            foreach ($product['variation_feature_ids'] as $feature_id) {
                if (!isset($product['variation_features'][$feature_id])) {
                    continue 2;
                }

                $feature = $product['variation_features'][$feature_id];
                $variant_ids[$feature['feature_id']] = $feature['variant_id'];

                if (FeaturePurposes::isCreateCatalogItem($feature['purpose'])) {
                    $parent_variant_ids[$feature['feature_id']] = $feature['variant_id'];
                }
            }

            $product['variation_combination_id'] = $this->generateCombinationId($variant_ids);
            $product['parent_variation_combination_id'] = $this->generateCombinationId($parent_variant_ids);
        }
        unset($product);

        return $products;
    }

    /**
     * Generates combination identifier for product
     *
     * @param array $product
     *
     * @return array
     */
    public function generateProductCombinationId(array $product)
    {
        $result = $this->generateProductsCombinationId([$product]);

        return reset($result);
    }

    /**
     * Creates product
     *
     * @param array $product_data
     *
     * @return int
     */
    public function createProduct(array $product_data)
    {
        $product_id = $this->createQuery(self::TABLE_PRODUCTS)
            ->insert($product_data);

        if (isset($product_data['price'])) {
            $this->updateProductPrice($product_id, $product_data['price']);
        }

        $descriptions = [];

        foreach ($this->lang_codes as $lang_code) {
            $descriptions[] = [
                'product_id'        => $product_id,
                'lang_code'         => $lang_code,
                'product'           => $product_data['product'],
                'shortname'         => isset($product_data['shortname']) ? $product_data['shortname'] : '',
                'short_description' => isset($product_data['short_description']) ? $product_data['short_description'] : '',
                'full_description'  => isset($product_data['full_description']) ? $product_data['full_description'] : '',
                'meta_keywords'     => isset($product_data['meta_keywords']) ? $product_data['meta_keywords'] : '',
                'meta_description'  => isset($product_data['meta_description']) ? $product_data['meta_description'] : '',
                'search_words'      => isset($product_data['search_words']) ? $product_data['search_words'] : '',
                'page_title'        => isset($product_data['page_title']) ? $product_data['page_title'] : '',
                'promo_text'        => isset($product_data['promo_text']) ? $product_data['promo_text'] : '',
            ];
        }

        $this->createQuery(self::TABLE_PRODUCT_DESCRIPTIONS)->multipleInsert($descriptions);

        return $product_id;
    }

    /**
     * Updates product
     *
     * @param int   $product_id
     * @param array $product_data
     */
    public function updateProduct($product_id, array $product_data)
    {
        $base_data = array_intersect_key(
            $product_data,
            array_flip([
                'product_code', 'updated_timestamp', 'amount'
            ])
        );

        $description_data = array_intersect_key(
            $product_data,
            array_flip(['product'])
        );

        if ($base_data) {
            $this->createQuery(self::TABLE_PRODUCTS)
                ->addConditions(['product_id' => $product_id])
                ->update($base_data);
        }

        if ($description_data) {
            $this->createQuery(self::TABLE_PRODUCT_DESCRIPTIONS)
                ->addConditions(['product_id' => $product_id])
                ->update($description_data);

            if (fn_allowed_for('ULTIMATE')) {
                $this->createQuery(self::TABLE_PRODUCT_ULT_DESCRIPTIONS)
                    ->addConditions(['product_id' => $product_id])
                    ->update($description_data);
            }
        }

        if (isset($product_data['price'])) {
            $this->updateProductPrice($product_id, $product_data['price']);
        }
    }

    /**
     * Updates product price
     *
     * @param int   $product_id
     * @param float $price
     * @param array $prices
     */
    public function updateProductPrice($product_id, $price, array $prices = [])
    {
        fn_update_product_prices($product_id, [
            'price'  => $price,
            'prices' => $prices,
        ]);
    }

    /**
     * Updates product features values
     *
     * @param int   $product_id
     * @param array $values
     */
    public function updateProductFeaturesValues($product_id, array $values)
    {
        if (!$product_id || !$values) {
            return;
        }

        $this->group_repository->updateFeatureValues($product_id, $values);
    }

    /**
     * Generates combination identifier by variant identifiers
     *
     * @param array<int> $variant_ids Product feature variant IDs
     *
     * @return string
     */
    public function generateCombinationId(array $variant_ids)
    {
        return GroupProduct::generateCombinationId($variant_ids);
    }

    /**
     * Retrieves variant IDs from combination ID generated by  \Tygh\Addons\ProductVariations\Product\Repository::generateCombinationId
     *
     * @param string $combination_id
     *
     * @return array
     * @see \Tygh\Addons\ProductVariations\Product\Repository::generateCombinationId
     */
    public function getVariantIdsFromCombinationId($combination_id)
    {
        return array_filter(explode('_', $combination_id));
    }

    /**
     * @return string
     */
    public function getLangCode()
    {
        return $this->lang_code;
    }

    /**
     * @param string $lang_code
     */
    public function setLangCode($lang_code)
    {
        $this->lang_code = $lang_code;
    }

    /**
     * Finds parent products that have their quantity at 0
     *
     * @param array<int> $product_ids Product IDs
     *
     * @return array<array-key, int>
     */
    public function findParentProductIdsWithZeroQuantity(array $product_ids)
    {
        $query = $this->createQuery(self::TABLE_PRODUCTS, [
            'product_id' => $product_ids
        ]);
        $query->addConditions([
            'parent_product_id' => 0,
            'amount'            => 0,
        ]);
        $query->addField('product_id');

        return $query->column();
    }

    /**
     * Finds the most popular active product
     *
     * @param array<int> $product_ids Product Ids
     * @param bool       $on_stock    If true finds only in stock products
     *
     * @return int
     */
    public function findActiveAndMorePopularProductId(array $product_ids, $on_stock = true)
    {
        $query = $this->createQuery([self::TABLE_PRODUCTS => 'product'], [
            'product_id' => $product_ids,
            'status'     => ObjectStatuses::ACTIVE,
        ]);

        if ($on_stock) {
            $query->addCondition('product.amount > 0');
        }

        $query
            ->addField('product.product_id')
            ->addLeftJoin('popularity', self::TABLE_PRODUCT_POPULARITY, ['product_id' => 'product_id'])
            ->setOrderBy(['popularity.total DESC', 'product.product_id ASC'])
            ->setLimit(1);

        /**
         * Allows you to change query for selecting active and more popular product ids.
         *
         * @param Repository                                 $this        Current state of object.
         * @param array<int>                                 $product_ids Product ids.
         * @param bool                                       $on_stock    If true finds only in stock products.
         * @param \Tygh\Addons\ProductVariations\Tools\Query $query       Request query.
         */
        fn_set_hook('product_variations_product_repository_find_active_and_more_popular_product_id', $this, $product_ids, $on_stock, $query);

        return (int) $query->scalar();
    }

    /**
     * Gets product amount
     *
     * @param int $product_id Product identificator
     *
     * @return int
     */
    public function getProductAmount($product_id)
    {
        $query = $this->createQuery(
            [self::TABLE_PRODUCTS => 'product'],
            ['product_id' => $product_id,],
            ['amount']
        );

        /**
         * Executes before query, allows to change params of query for getting product amount.
         *
         * @param Repository                                 $this       Current state of object
         * @param int                                        $product_id Product identificator
         * @param \Tygh\Addons\ProductVariations\Tools\Query $query      Request query
         */
        fn_set_hook('product_variations_product_repository_get_product_amount', $this, $product_id, $query);

        return (int) $query->scalar();
    }

    /**
     * Creates query instance
     *
     * @param string|array $table_id
     * @param array        $conditions
     * @param array        $fields
     * @param string       $table_alias
     *
     * @return \Tygh\Addons\ProductVariations\Tools\Query
     */
    public function createQuery($table_id, array $conditions = [], array $fields = [], $table_alias = null)
    {
        return $this->query_factory->createQuery($table_id, $conditions, $fields, $table_alias);
    }

    /**
     * Sorts features by purpose and position
     *
     * @param array<array-key, string|array<array-key, string>> $features      List of features
     * @param string                                            $purpose_field Name of the purpose field
     *
     * @return array<array-key, string|array<array-key, string>>
     */
    protected function sortFeaturesByPurposeAndPosition(array $features, $purpose_field = 'product_feature_purpose')
    {
        uasort($features, static function ($feature_a, $feature_b) use ($purpose_field) {
            if (!isset($feature_a[$purpose_field], $feature_b[$purpose_field])) {
                $purpose_field = 'purpose';
            }

            if ($feature_a[$purpose_field] === $feature_b[$purpose_field]) {
                if ($feature_a['position'] < $feature_b['position']) {
                    return -1;
                } elseif ($feature_a['position'] > $feature_b['position']) {
                    return 1;
                } else {
                    return $feature_a['feature_id'] < $feature_b['feature_id'] ? -1 : 1;
                }
            } elseif (FeaturePurposes::isCreateCatalogItem($feature_a[$purpose_field])) {
                return -1;
            } else {
                return 1;
            }
        });

        return $features;
    }

    /**
     * Sorts feature variants by position and name
     *
     * @param array $variants
     *
     * @return array
     */
    protected function sortFeatureVariantsByPosition(array $variants)
    {
        uasort($variants, function ($a, $b) {
            if ($a['variant_position'] > $b['variant_position']) {
                return 1;
            } elseif ($a['variant_position'] < $b['variant_position']) {
                return -1;
            } else {
                return strnatcmp($a['variant'], $b['variant']);
            }
        });

        return $variants;
    }

    /**
     * Gets feature variants grouped by group ID and feature ID
     *
     * @param array $products
     *
     * @return array
     */
    protected function getGroupFeaturesVariants(array $products)
    {
        $group_features_variants = [];

        foreach ($products as $product) {
            if (empty($product['variation_features'])) {
                continue;
            }

            $group_id = $product['variation_group_id'];

            foreach ($product['variation_features'] as $feature_id => $feature) {
                $variant_id = $feature['variant_id'];

                if (isset($group_features_variants[$group_id][$feature_id][$variant_id])) {
                    continue;
                }

                $group_features_variants[$group_id][$feature_id][$variant_id] = [
                    'variant_id'       => $feature['variant_id'],
                    'variant_position' => $feature['variant_position'],
                    'variant'          => $feature['variant'],
                ];
            }
        }

        return $group_features_variants;
    }

    /**
     * Gets products grouped by group ID and combination ID
     *
     * @param array $products
     *
     * @return array
     */
    protected function getGroupCombinations(array $products)
    {
        $group_combinations = [];

        foreach ($products as $product) {
            if (empty($product['variation_features'])) {
                continue;
            }

            $group_id = $product['variation_group_id'];
            $product_id = $product['product_id'];
            $variant_ids = [];
            $variant_positions = [];

            foreach ($product['variation_features'] as $feature_id => $feature) {
                $variant_id = $feature['variant_id'];
                $variant_positions[$variant_id] = $feature['variant_position'];

                $combination_position = FeaturePurposes::isCreateCatalogItem($feature['purpose'])
                    ? (int) !empty($product['parent_product_id'])
                    : 1;

                $combination_position .= implode('', array_diff_key($variant_positions, $variant_ids));

                $variant_ids[$feature_id] = $variant_id;
                $combination_id = $this->generateCombinationId($variant_ids);

                if (
                    !isset($group_combinations[$group_id][$combination_id])
                    || $combination_position < $group_combinations[$group_id][$combination_id]['position']
                ) {
                    $group_combinations[$group_id][$combination_id] = [
                        'product_id' => $product_id,
                        'position'   => $combination_position
                    ];
                }
            }
        }

        return $group_combinations;
    }
}
