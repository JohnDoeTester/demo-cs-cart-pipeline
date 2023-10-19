<?php


namespace Tygh\Tests\Unit\Addons\ProductVariations\Product\Group;


use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Addons\ProductVariations\Product\Group\Events\ParentProductChangedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductAddedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductRemovedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductUpdatedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Group;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeature;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;
use Tygh\Addons\ProductVariations\Product\Group\GroupProduct;
use Tygh\Addons\ProductVariations\Product\Group\GroupProductCollection;
use Tygh\Tests\Unit\ATestCase;

class GroupTest extends ATestCase
{
    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\Group $group
     * @param GroupProductCollection                             $products
     * @param GroupProductCollection                             $expected_group_products
     * @param array                                              $expected_events
     * @param array                                              $expected_results
     *
     * @dataProvider dpAddProducts
     */
    public function testAttachProducts(Group $group, GroupProductCollection $products, GroupProductCollection $expected_group_products, array $expected_events, array $expected_results)
    {
        $result = $group->attachProducts($products);

        $this->assertEquals($expected_results, $result);
        $this->assertEquals($expected_group_products->getProducts(), $group->getProducts()->getProducts());
        $this->assertEquals($expected_events, $group->getEvents());
    }

    public function testGetChildProductIds()
    {
        $features = GroupFeature::createListFromArray([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);

        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 4000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 23,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $group = Group::createFromArray(['features' => $features, 'products' => $products]);

        $this->assertEquals([], $group->getChildProductIds(1));
        $this->assertEquals([], $group->getChildProductIds(1000));
        $this->assertEquals([3000, 4000], $group->getChildProductIds(2000));
        $this->assertEquals([], $group->getChildProductIds(3000));
    }

    public function testDetachProductById()
    {
        $features = GroupFeature::createListFromArray([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);

        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 4000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 23,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $group = Group::createFromArray(['features' => $features, 'products' => clone $products]);

        $group->detachProductById(2000);

        $this->assertEquals([1000, 3000, 4000], $group->getProductIds());

        $this->assertEquals([
            ParentProductChangedEvent::create($products[2000], $products[3000]->changeParentProductId(0)),
            ProductUpdatedEvent::create($products[3000], $products[3000]->changeParentProductId(0)),
            ProductUpdatedEvent::create($products[4000], $products[4000]->changeParentProductId(3000)),
            ProductRemovedEvent::create($products[2000])
        ], $group->getEvents());
    }

    public function testSetDefaultProduct()
    {
        $features = GroupFeature::createListFromArray([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);

        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 4000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 23,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $group = Group::createFromArray(['features' => $features, 'products' => clone $products]);

        $group->setDefaultProduct(4000);

        $this->assertEquals([
            ProductUpdatedEvent::create($products[3000], $products[3000]->changeParentProductId(4000)),
            ParentProductChangedEvent::create($products[2000], $products[4000]->changeParentProductId(0)),
            ProductUpdatedEvent::create($products[4000], $products[4000]->changeParentProductId(0)),
            ProductUpdatedEvent::create($products[2000], $products[2000]->changeParentProductId(4000)),
        ], $group->getEvents());
    }

    public function dpAddProducts()
    {
        return [
            self::testAddProductsData0(),
            self::testAddProductsData1(),
            self::testAddProductsData2(),
            self::testAddProductsData3(),
            self::testAddProductsData4(),
            self::testAddProductsData5(),
            self::testAddProductsData6(),
            self::testAddProductsData7(),
            self::testAddProductsData8(),
            self::testAddProductsData9(),
            self::testAddProductsData10(),
        ];
    }

    /**
     * Define:
     *  New variations group
     *  New products
     *
     * Expected:
     *  All products added to group
     *
     * @return array
     * @throws \Tygh\Exceptions\InputException
     *
     * @doesNotPerformAssertions
     */
    public static function testAddProductsData0()
    {
        $features = GroupFeatureCollection::createFromFeatureList([['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM]]);
        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 10,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_CATALOG_ITEM
                    ]
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 11,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_CATALOG_ITEM
                    ]
                ]
            ],
        ]);

        return [
            Group::createNewGroup($features),
            $products,
            $products,
            [
                ProductAddedEvent::create($products[1000]),
                ProductAddedEvent::create($products[2000]),
            ],
            [
                1000 => Group::RESULT_ADDED,
                2000 => Group::RESULT_ADDED,
            ]
        ];
    }

    /**
     * Define:
     *  New variations group
     *  New products with invalid feature values
     *
     * Expected:
     *  All products were not added to group
     *
     * @return array
     * @throws \Tygh\Exceptions\InputException
     *
     * @doesNotPerformAssertions
     */
    public static function testAddProductsData1()
    {
        $features = GroupFeatureCollection::createFromFeatureList([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);
        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 10,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_CATALOG_ITEM
                    ]
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 11,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_CATALOG_ITEM
                    ]
                ]
            ],
        ]);

        return [
            Group::createNewGroup($features),
            $products,
            new GroupProductCollection(),
            [],
            [
                1000 => Group::RESULT_ERROR_PRODUCT_INVALID_FEATURE_VALUES,
                2000 => Group::RESULT_ERROR_PRODUCT_INVALID_FEATURE_VALUES,
            ]
        ];
    }

    /**
     * Define:
     *  New variations group
     *  Features with purpose is create variation of catalog item
     *  New products
     *
     * Expected:
     *  All products added to group
     *  One of products became a child
     *
     * @return array
     * @throws \Tygh\Exceptions\InputException
     *
     * @doesNotPerformAssertions
     */
    public static function testAddProductsData2()
    {
        $features = GroupFeatureCollection::createFromFeatureList([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);
        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);
        $expected_products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        return [
            Group::createNewGroup($features),
            $products,
            $expected_products,
            [
                ProductAddedEvent::create($expected_products[1000]),
                ProductAddedEvent::create($expected_products[2000]),
                ProductAddedEvent::create($expected_products[3000]),
            ],
            [
                1000 => Group::RESULT_ADDED,
                2000 => Group::RESULT_ADDED,
                3000 => Group::RESULT_ADDED,
            ]
        ];
    }

    /**
     * Define:
     *  New variations group
     *  Features with purpose is create variation of catalog item
     *  New products
     *  Two product has same combination of feature values
     *
     * Expected:
     *  All but one products added to group
     *
     * @return array
     * @throws \Tygh\Exceptions\InputException
     *
     * @doesNotPerformAssertions
     */
    public static function testAddProductsData3()
    {
        $features = GroupFeatureCollection::createFromFeatureList([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);
        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 4000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);
        $expected_products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        return [
            Group::createNewGroup($features),
            $products,
            $expected_products,
            [
                ProductAddedEvent::create($expected_products[1000]),
                ProductAddedEvent::create($expected_products[2000]),
                ProductAddedEvent::create($expected_products[3000]),
            ],
            [
                1000 => Group::RESULT_ADDED,
                2000 => Group::RESULT_ADDED,
                3000 => Group::RESULT_ADDED,
                4000 => Group::RESULT_ERROR_PRODUCT_COMBINATION_ALREADY_EXISTS,
            ]
        ];
    }

    /**
     * Define:
     *  Exists variations group
     *  Features with purpose is create variation of catalog item
     *  Product has same combination of feature values than on group
     *
     * Expected:
     *  Product was not added
     *
     * @return array
     * @throws \Tygh\Exceptions\InputException
     *
     * @doesNotPerformAssertions
     */
    public static function testAddProductsData4()
    {
        $features = GroupFeature::createListFromArray([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);

        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        return [
            Group::createFromArray(['features' => $features, 'products' => $products]),
            GroupProductCollection::createFromProducts([
                [
                    'product_id'         => 4000,
                    'parent_product_id'  => 0,
                    'company_id'         => 1,
                    'variation_features' => [
                        100 => [
                            'variant_id' => 11,
                            'feature_id' => 100,
                            'purpose'    => FeaturePurposes::CREATE_CATALOG_ITEM
                        ],
                        200 => [
                            'variant_id' => 22,
                            'feature_id' => 200,
                            'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                        ]
                    ]
                ],
            ]),
            $products,
            [],
            [
                4000 => Group::RESULT_ERROR_PRODUCT_COMBINATION_ALREADY_EXISTS,
            ]
        ];
    }

    /**
     * Define:
     *  Exists variations group
     *  Features with purpose is create variation of catalog item
     *  One product has same combination of feature values than on group
     *  Second product updating this combination
     *
     * Expected:
     *  All product was not added/updated
     *
     * @return array
     * @throws \Tygh\Exceptions\InputException
     *
     * @doesNotPerformAssertions
     */
    public static function testAddProductsData5()
    {
        $features = GroupFeature::createListFromArray([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);

        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $new_products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 4000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 23,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $expected_products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 23,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 4000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        return [
            Group::createFromArray(['features' => $features, 'products' => $products]),
            $new_products,
            $expected_products,
            [
                ProductUpdatedEvent::create($products[3000], $expected_products[3000]),
                ProductAddedEvent::create($expected_products[4000]),
            ],
            [
                4000 => Group::RESULT_ADDED,
                3000 => Group::RESULT_UPDATED
            ]
        ];
    }

    /**
     * @return array
     * @throws \Tygh\Exceptions\InputException
     *
     * @doesNotPerformAssertions
     */
    public static function testAddProductsData6()
    {
        $features = GroupFeature::createListFromArray([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);

        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $new_products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 4000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 23,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $expected_products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 23,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 4000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        return [
            Group::createFromArray(['features' => $features, 'products' => $products]),
            $new_products,
            $expected_products,
            [
                ProductUpdatedEvent::create($products[1000], $expected_products[1000]),
                ProductUpdatedEvent::create($products[3000], $expected_products[3000]),
                ProductAddedEvent::create($expected_products[4000]),
            ],
            [
                1000 => Group::RESULT_UPDATED,
                3000 => Group::RESULT_UPDATED,
                4000 => Group::RESULT_ADDED,
            ]
        ];
    }

    /**
     * @return array
     * @throws \Tygh\Exceptions\InputException
     *
     * @doesNotPerformAssertions
     */
    public static function testAddProductsData7()
    {
        $features = GroupFeature::createListFromArray([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);

        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 4000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 23,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $new_products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 12, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 5000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 25,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $expected_products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 12, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 3000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 25,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 4000,
                'parent_product_id'  => 3000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 23,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 5000,
                'parent_product_id'  => 3000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        return [
            Group::createFromArray(['features' => $features, 'products' => $products]),
            $new_products,
            $expected_products,
            [
                ParentProductChangedEvent::create($products[2000], $products[3000]->changeParentProductId(0)),
                ProductUpdatedEvent::create($products[3000], $products[3000]->changeParentProductId(0)),
                ProductUpdatedEvent::create($products[4000], $expected_products[4000]),
                ProductUpdatedEvent::create($products[2000], $expected_products[2000]),
                ProductUpdatedEvent::create($products[3000]->changeParentProductId(0), $expected_products[3000]),
                ProductAddedEvent::create($expected_products[5000]),
            ],
            [
                2000 => Group::RESULT_UPDATED,
                3000 => Group::RESULT_UPDATED,
                5000 => Group::RESULT_ADDED,
            ]
        ];
    }

    /**
     * @return array
     * @throws \Tygh\Exceptions\InputException
     *
     * @doesNotPerformAssertions
     */
    public static function testAddProductsData8()
    {
        $features = GroupFeatureCollection::createFromFeatureList([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);
        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 10,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 11,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $expected_products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 10,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 1000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 11,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        return [
            Group::createNewGroup($features),
            $products,
            $expected_products,
            [
                ProductAddedEvent::create($expected_products[1000]),
                ProductAddedEvent::create($expected_products[2000]),
            ],
            [
                1000 => Group::RESULT_ADDED,
                2000 => Group::RESULT_ADDED,
            ]
        ];
    }

    /**
     * @return array
     * @throws \Tygh\Exceptions\InputException
     *
     * @doesNotPerformAssertions
     */
    public static function testAddProductsData9()
    {
        $features = GroupFeature::createListFromArray([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);
        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 10,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 1000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 11,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $new_products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 3000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 10,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 4000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 12,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $expected_products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 10,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 1000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 11,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 4000,
                'parent_product_id'  => 1000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 12,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        return [
            Group::createFromArray(['features' => $features, 'products' => $products]),
            $new_products,
            $expected_products,
            [
                ProductAddedEvent::create($expected_products[4000]),
            ],
            [
                3000 => Group::RESULT_ERROR_PRODUCT_COMBINATION_ALREADY_EXISTS,
                4000 => Group::RESULT_ADDED,
            ]
        ];
    }

    /**
     * @return array
     * @throws \Tygh\Exceptions\InputException
     *
     * @doesNotPerformAssertions
     */
    public static function testAddProductsData10()
    {
        $features = GroupFeature::createListFromArray([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);
        $products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 10,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 1000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 11,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $new_products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 3000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 10,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 4000,
                'parent_product_id'  => 0,
                'company_id'         => 2,
                'variation_features' => [
                    100 => [
                        'variant_id' => 12,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ]);

        $expected_products = GroupProductCollection::createFromProducts([
            [
                'product_id'         => 1000,
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 10,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            [
                'product_id'         => 2000,
                'parent_product_id'  => 1000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => [
                        'variant_id' => 11,
                        'feature_id' => 100,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ]
        ]);

        return [
            Group::createFromArray(['features' => $features, 'products' => $products]),
            $new_products,
            $expected_products,
            [],
            [
                3000 => Group::RESULT_ERROR_PRODUCT_COMBINATION_ALREADY_EXISTS,
                4000 => Group::RESULT_ERROR_PRODUCT_COMPANY_DOES_NOT_MATCH_TO_GROUP_COMPANY,
            ]
        ];
    }
}