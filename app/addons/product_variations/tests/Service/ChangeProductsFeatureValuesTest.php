<?php


namespace Tygh\Tests\Unit\Addons\ProductVariations\Service;


use Tygh\Addons\ProductVariations\Product\CombinationsGenerator;
use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Addons\ProductVariations\Product\Group\Group;
use Tygh\Addons\ProductVariations\Product\Group\GroupCodeGenerator;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeature;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;
use Tygh\Addons\ProductVariations\Product\Group\GroupProductCollection;
use Tygh\Addons\ProductVariations\Product\Group\Repository as GroupRepository;
use Tygh\Addons\ProductVariations\Product\ProductIdMap;
use Tygh\Addons\ProductVariations\Product\Repository as ProductRepository;
use Tygh\Addons\ProductVariations\Product\Sync\ProductDataIdentityMapRepository;
use Tygh\Addons\ProductVariations\Service;
use Tygh\Addons\ProductVariations\SyncService;
use Tygh\Tests\Unit\ATestCase;

class ChangeProductsFeatureValuesTest extends ATestCase
{
    /** @var Service */
    protected $service;

    /** @var \Tygh\Addons\ProductVariations\Product\Group\Repository|\PHPUnit\Framework\MockObject\MockObject */
    protected $group_repository;

    /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupCodeGenerator|\PHPUnit\Framework\MockObject\MockObject */
    protected $group_code_generator;

    /** @var \Tygh\Addons\ProductVariations\Product\Repository|\PHPUnit\Framework\MockObject\MockObject */
    protected $product_repository;

    /** @var \Tygh\Addons\ProductVariations\Product\Sync\ProductDataIdentityMapRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $identity_map_repository;

    /** @var \Tygh\Addons\ProductVariations\SyncService|\PHPUnit\Framework\MockObject\MockObject */
    protected $sync_service;

    /** @var \Tygh\Addons\ProductVariations\Product\ProductIdMap|\PHPUnit\Framework\MockObject\MockObject */
    protected $product_id_map;

    /** @var \Tygh\Addons\ProductVariations\Product\CombinationsGenerator|\PHPUnit\Framework\MockObject\MockObject */
    protected $combination_generator;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->group_repository = $this->getMockBuilder(GroupRepository::class)
            ->setMethods(['save', 'delete', 'findGroupById', 'findGroupByProductId', 'remove'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->group_code_generator = $this->getMockBuilder(GroupCodeGenerator::class)
            ->setMethods(['next'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->product_repository = $this->getMockBuilder(ProductRepository::class)
            ->setMethods([
                'findProduct',
                'changeProductTypeToSimple',
                'changeProductTypeToChild',
                'findAvailableFeatures',
                'findProducts',
                'loadProductsFeatures',
                'updateProductFeaturesValues',
                'loadProductFeatures'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->identity_map_repository = $this->getMockBuilder(ProductDataIdentityMapRepository::class)
            ->setMethods(['deleteByProductId', 'changeParentProductId', 'deleteByProductIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sync_service = $this->getMockBuilder(SyncService::class)
            ->setMethods(['syncAll'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->product_id_map = $this->getMockBuilder(ProductIdMap::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->combination_generator = $this->getMockBuilder(CombinationsGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new Service(
            $this->group_repository,
            $this->group_code_generator,
            $this->product_repository,
            $this->identity_map_repository,
            $this->sync_service,
            $this->product_id_map,
            $this->combination_generator,
            false, false, false
        );

        $this->requireMockFunction('fn_set_hook');
        $this->requireMockFunction('__');
    }

    public function testChangeProductsFeatureValuesWithUpdateChildren()
    {
        $products = [
            12 => [
                'product_id'        => 12,
                'product'           => 'Product 12',
                'parent_product_id' => 0,
                'company_id'        => 1,
            ],
            13 => [
                'product_id'        => 13,
                'product'           => 'Product 13',
                'parent_product_id' => 12,
                'company_id'        => 1,
            ],
            14 => [
                'product_id'        => 14,
                'product'           => 'Product 14',
                'parent_product_id' => 0,
                'company_id'        => 1,
            ],
            15 => [
                'product_id'        => 15,
                'product'           => 'Product 15',
                'parent_product_id' => 14,
                'company_id'        => 1,
            ],
            16 => [
                'product_id'        => 16,
                'product'           => 'Product 16',
                'parent_product_id' => 14,
                'company_id'        => 1,
            ]
        ];

        $products_with_features = [
            12 => [
                'product_id'            => 12,
                'product'               => 'Product 12',
                'parent_product_id'     => 0,
                'company_id'            => 1,
                'variation_feature_ids' => [1, 2],
                'variation_features'    => [
                    1 => [
                        'feature_id'       => 1,
                        'description'      => 'Color',
                        'purpose'          => FeaturePurposes::CREATE_CATALOG_ITEM,
                        'position'         => 0,
                        'variant_id'       => 1,
                        'variant'          => 'White',
                        'variant_position' => 0
                    ],
                    2 => [
                        'feature_id'       => 2,
                        'description'      => 'Size',
                        'purpose'          => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'position'         => 10,
                        'variant_id'       => 1,
                        'variant'          => 'Small',
                        'variant_position' => 10
                    ]
                ]
            ],
            13 => [
                'product_id'            => 13,
                'product'               => 'Product 13',
                'parent_product_id'     => 12,
                'company_id'            => 1,
                'variation_feature_ids' => [1, 2],
                'variation_features'    => [
                    1 => [
                        'feature_id'       => 1,
                        'description'      => 'Color',
                        'purpose'          => FeaturePurposes::CREATE_CATALOG_ITEM,
                        'position'         => 0,
                        'variant_id'       => 1,
                        'variant'          => 'White',
                        'variant_position' => 0
                    ],
                    2 => [
                        'feature_id'       => 2,
                        'description'      => 'Size',
                        'purpose'          => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'position'         => 10,
                        'variant_id'       => 2,
                        'variant'          => 'Large',
                        'variant_position' => 20
                    ]
                ]
            ],
            14 => [
                'product_id'            => 14,
                'product'               => 'Product 14',
                'parent_product_id'     => 0,
                'company_id'            => 1,
                'variation_feature_ids' => [1, 2],
                'variation_features'    => [
                    1 => [
                        'feature_id'       => 1,
                        'description'      => 'Color',
                        'purpose'          => FeaturePurposes::CREATE_CATALOG_ITEM,
                        'position'         => 0,
                        'variant_id'       => 3,
                        'variant'          => 'Black',
                        'variant_position' => 0
                    ],
                    2 => [
                        'feature_id'       => 2,
                        'description'      => 'Size',
                        'purpose'          => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'position'         => 10,
                        'variant_id'       => 1,
                        'variant'          => 'Small',
                        'variant_position' => 10
                    ]
                ]
            ],
            15 => [
                'product_id'            => 15,
                'product'               => 'Product 15',
                'parent_product_id'     => 14,
                'company_id'            => 1,
                'variation_feature_ids' => [1, 2],
                'variation_features'    => [
                    1 => [
                        'feature_id'       => 1,
                        'description'      => 'Color',
                        'purpose'          => FeaturePurposes::CREATE_CATALOG_ITEM,
                        'position'         => 0,
                        'variant_id'       => 3,
                        'variant'          => 'Black',
                        'variant_position' => 0
                    ],
                    2 => [
                        'feature_id'       => 2,
                        'description'      => 'Size',
                        'purpose'          => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'position'         => 10,
                        'variant_id'       => 2,
                        'variant'          => 'Large',
                        'variant_position' => 20
                    ]
                ]
            ],
            16 => [
                'product_id'            => 16,
                'product'               => 'Product 16',
                'parent_product_id'     => 14,
                'company_id'            => 1,
                'variation_feature_ids' => [1, 2],
                'variation_features'    => [
                    1 => [
                        'feature_id'       => 1,
                        'description'      => 'Color',
                        'purpose'          => FeaturePurposes::CREATE_CATALOG_ITEM,
                        'position'         => 0,
                        'variant_id'       => 3,
                        'variant'          => 'Black',
                        'variant_position' => 0
                    ],
                    2 => [
                        'feature_id'       => 2,
                        'description'      => 'Size',
                        'purpose'          => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'position'         => 10,
                        'variant_id'       => 5,
                        'variant'          => 'X Large',
                        'variant_position' => 30
                    ]
                ]
            ]
        ];

        $group = Group::createFromArray([
            'id' => 10,
            'features' => GroupFeatureCollection::createFromFeatureList([
                1 => [
                    'feature_id'  => 1,
                    'description' => 'Color',
                    'purpose'     => FeaturePurposes::CREATE_CATALOG_ITEM,
                    'position'    => 0,
                ],
                2 => [
                    'feature_id'  => 2,
                    'description' => 'Size',
                    'purpose'     => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                    'position'    => 10,
                ]
            ]),
            'products' => GroupProductCollection::createFromProducts($products_with_features)
        ]);

        $this->group_repository->expects($this->once())->method('findGroupById')->with(10)->willReturn($group);

        $this->product_repository->expects($this->once())->method('findProducts')->with([14])->willReturn([14 => $products[14]]);
        $this->product_repository->expects($this->once())->method('loadProductsFeatures')->with([14 => $products[14]])->willReturn([14 => $products_with_features[14]]);

        $this->sync_service->expects($this->once())->method('syncAll')->with(15, [16 => 16]);

        $this->service->changeProductsFeatureValues(10, [14 => [1 => 5, 2 => 5]]);

        $this->assertEquals(new GroupFeatureCollection([
            1 => GroupFeature::create(1, FeaturePurposes::CREATE_CATALOG_ITEM),
            2 => GroupFeature::create(2, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM),
        ]), $group->getFeatures());

        $this->assertNotEmpty($group->getProduct(12));
        $this->assertEquals(0, $group->getProduct(12)->getParentProductId());

        $this->assertNotEmpty($group->getProduct(13));
        $this->assertEquals(12, $group->getProduct(13)->getParentProductId());

        $this->assertNotEmpty($group->getProduct(14));
        $this->assertEquals(0, $group->getProduct(14)->getParentProductId());

        $this->assertNotEmpty($group->getProduct(15));
        $this->assertEquals(0, $group->getProduct(15)->getParentProductId());

        $this->assertNotEmpty($group->getProduct(16));
        $this->assertEquals(15, $group->getProduct(16)->getParentProductId());

        $this->assertCount(5 ,$group->getProducts());
    }

    public function testChangeProductsFeatureValuesWithoutUpdateChildren()
    {
        $products = [
            12 => [
                'product_id'        => 12,
                'product'           => 'Product 12',
                'parent_product_id' => 0,
                'company_id'        => 1,
            ],
            13 => [
                'product_id'        => 13,
                'product'           => 'Product 13',
                'parent_product_id' => 12,
                'company_id'        => 1,
            ],
            14 => [
                'product_id'        => 14,
                'product'           => 'Product 14',
                'parent_product_id' => 0,
                'company_id'        => 1,
            ],
            15 => [
                'product_id'        => 15,
                'product'           => 'Product 15',
                'parent_product_id' => 14,
                'company_id'        => 1,
            ],
            16 => [
                'product_id'        => 16,
                'product'           => 'Product 16',
                'parent_product_id' => 14,
                'company_id'        => 1,
            ]
        ];

        $products_with_features = [
            12 => [
                'product_id'            => 12,
                'product'               => 'Product 12',
                'parent_product_id'     => 0,
                'company_id'            => 1,
                'variation_feature_ids' => [1, 2],
                'variation_features'    => [
                    1 => [
                        'feature_id'       => 1,
                        'description'      => 'Color',
                        'purpose'          => FeaturePurposes::CREATE_CATALOG_ITEM,
                        'position'         => 0,
                        'variant_id'       => 1,
                        'variant'          => 'White',
                        'variant_position' => 0
                    ],
                    2 => [
                        'feature_id'       => 2,
                        'description'      => 'Size',
                        'purpose'          => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'position'         => 10,
                        'variant_id'       => 1,
                        'variant'          => 'Small',
                        'variant_position' => 10
                    ]
                ]
            ],
            13 => [
                'product_id'            => 13,
                'product'               => 'Product 13',
                'parent_product_id'     => 12,
                'company_id'            => 1,
                'variation_feature_ids' => [1, 2],
                'variation_features'    => [
                    1 => [
                        'feature_id'       => 1,
                        'description'      => 'Color',
                        'purpose'          => FeaturePurposes::CREATE_CATALOG_ITEM,
                        'position'         => 0,
                        'variant_id'       => 1,
                        'variant'          => 'White',
                        'variant_position' => 0
                    ],
                    2 => [
                        'feature_id'       => 2,
                        'description'      => 'Size',
                        'purpose'          => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'position'         => 10,
                        'variant_id'       => 2,
                        'variant'          => 'Large',
                        'variant_position' => 20
                    ]
                ]
            ],
            14 => [
                'product_id'            => 14,
                'product'               => 'Product 14',
                'parent_product_id'     => 0,
                'company_id'            => 1,
                'variation_feature_ids' => [1, 2],
                'variation_features'    => [
                    1 => [
                        'feature_id'       => 1,
                        'description'      => 'Color',
                        'purpose'          => FeaturePurposes::CREATE_CATALOG_ITEM,
                        'position'         => 0,
                        'variant_id'       => 3,
                        'variant'          => 'Black',
                        'variant_position' => 0
                    ],
                    2 => [
                        'feature_id'       => 2,
                        'description'      => 'Size',
                        'purpose'          => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'position'         => 10,
                        'variant_id'       => 1,
                        'variant'          => 'Small',
                        'variant_position' => 10
                    ]
                ]
            ],
            15 => [
                'product_id'            => 15,
                'product'               => 'Product 15',
                'parent_product_id'     => 14,
                'company_id'            => 1,
                'variation_feature_ids' => [1, 2],
                'variation_features'    => [
                    1 => [
                        'feature_id'       => 1,
                        'description'      => 'Color',
                        'purpose'          => FeaturePurposes::CREATE_CATALOG_ITEM,
                        'position'         => 0,
                        'variant_id'       => 3,
                        'variant'          => 'Black',
                        'variant_position' => 0
                    ],
                    2 => [
                        'feature_id'       => 2,
                        'description'      => 'Size',
                        'purpose'          => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'position'         => 10,
                        'variant_id'       => 2,
                        'variant'          => 'Large',
                        'variant_position' => 20
                    ]
                ]
            ],
            16 => [
                'product_id'            => 16,
                'product'               => 'Product 16',
                'parent_product_id'     => 14,
                'company_id'            => 1,
                'variation_feature_ids' => [1, 2],
                'variation_features'    => [
                    1 => [
                        'feature_id'       => 1,
                        'description'      => 'Color',
                        'purpose'          => FeaturePurposes::CREATE_CATALOG_ITEM,
                        'position'         => 0,
                        'variant_id'       => 3,
                        'variant'          => 'Black',
                        'variant_position' => 0
                    ],
                    2 => [
                        'feature_id'       => 2,
                        'description'      => 'Size',
                        'purpose'          => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'position'         => 10,
                        'variant_id'       => 5,
                        'variant'          => 'X Large',
                        'variant_position' => 30
                    ]
                ]
            ]
        ];

        $group = Group::createFromArray([
            'id' => 10,
            'features' => GroupFeatureCollection::createFromFeatureList([
                1 => [
                    'feature_id'  => 1,
                    'description' => 'Color',
                    'purpose'     => FeaturePurposes::CREATE_CATALOG_ITEM,
                    'position'    => 0,
                ],
                2 => [
                    'feature_id'  => 2,
                    'description' => 'Size',
                    'purpose'     => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                    'position'    => 10,
                ]
            ]),
            'products' => GroupProductCollection::createFromProducts($products_with_features)
        ]);

        $this->group_repository->expects($this->once())->method('findGroupById')->with(10)->willReturn($group);

        $this->product_repository->expects($this->once())->method('findProducts')->with([14])->willReturn([14 => $products[14]]);
        $this->product_repository->expects($this->once())->method('loadProductsFeatures')->with([14 => $products[14]])->willReturn([14 => $products_with_features[14]]);

        $this->service->changeProductsFeatureValues(10, [14 => [2 => 6]]);

        $this->assertEquals(new GroupFeatureCollection([
            1 => GroupFeature::create(1, FeaturePurposes::CREATE_CATALOG_ITEM),
            2 => GroupFeature::create(2, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM),
        ]), $group->getFeatures());

        $this->assertNotEmpty($group->getProduct(12));
        $this->assertEquals(0, $group->getProduct(12)->getParentProductId());

        $this->assertNotEmpty($group->getProduct(13));
        $this->assertEquals(12, $group->getProduct(13)->getParentProductId());

        $this->assertNotEmpty($group->getProduct(14));
        $this->assertEquals(0, $group->getProduct(14)->getParentProductId());

        $this->assertNotEmpty($group->getProduct(15));
        $this->assertEquals(14, $group->getProduct(15)->getParentProductId());

        $this->assertNotEmpty($group->getProduct(16));
        $this->assertEquals(14, $group->getProduct(16)->getParentProductId());

        $this->assertCount(5 ,$group->getProducts());
    }

}