<?php


namespace Tygh\Tests\Unit\Addons\ProductVariations\Service;


use Tygh\Addons\ProductVariations\Product\CombinationsGenerator;
use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Addons\ProductVariations\Product\Group\Group;
use Tygh\Addons\ProductVariations\Product\Group\GroupCodeGenerator;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue;
use Tygh\Addons\ProductVariations\Product\Group\GroupProductCollection;
use Tygh\Addons\ProductVariations\Product\Group\Repository as GroupRepository;
use Tygh\Addons\ProductVariations\Product\ProductIdMap;
use Tygh\Addons\ProductVariations\Product\Repository as ProductRepository;
use Tygh\Addons\ProductVariations\Product\Sync\ProductDataIdentityMapRepository;
use Tygh\Addons\ProductVariations\Service;
use Tygh\Addons\ProductVariations\SyncService;
use Tygh\Tests\Unit\ATestCase;

class MoveProductsToNewGroupTest extends ATestCase
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
            ->setMethods(['save', 'delete', 'findGroupById', 'findGroupByProductId', 'remove', 'exists'])
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
                'loadProductsGroupInfo',
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
        $this->requireMockFunction('fn_get_product_feature_purposes_by_feature_ids');
    }

    public function testGeneral()
    {
        $features = $this->getFeatures();

        $group_10 = Group::createFromArray([
            'features' => $features,
            'products' => $this->getProducts(10)
        ]);

        $group_11 = Group::createFromArray([
            'features' => $features,
            'products' => $this->getProducts(11)
        ]);

        $group_12 = Group::createFromArray([
            'features' => $features,
            'products' => $this->getProducts(12)
        ]);

        $this->group_repository->expects($this->exactly(3))
            ->method('findGroupById')
            ->withConsecutive([11], [10], [12])
            ->willReturnOnConsecutiveCalls(
                $group_11, $group_10, $group_12
            );

        $products = $this->getProductDataSlice([5000, 6000, 1000, 2000, 8002]);

        $this->product_repository
            ->method('findProducts')
            ->with([5000, 6000, 1000, 2000, 8002])
            ->willReturn($products);

        $this->product_repository
            ->method('loadProductsGroupInfo')
            ->with($products)
            ->willReturn($products);

        $this->product_repository
            ->method('loadProductsFeatures')
            ->with($products)
            ->willReturn($products);

        $this->product_repository
            ->method('findAvailableFeatures')
            ->with(5000)
            ->willReturn([
                ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
            ]);

        $result = $this->service->moveProductsToNewGroup('NEW_GROUP', [5000, 6000, 1000, 2000, 8002], [
            5000 => [
                100 => 12,
                200 => 22
            ],
            6000 => [
                100 => 12,
                200 => 22
            ],
            2000 => [
                100 => 12,
                200 => 23
            ],
            8002 => [
                100 => 13,
                200 => 23
            ],
        ]);

        /** @var Group $group */
        $group = $result->getData('group');

        $this->assertNotNull($group->getProduct(5000));
        $this->assertEquals(0, $group->getProduct(5000)->getParentProductId());
        $this->assertEquals(
            [
                100 => GroupFeatureValue::create(100, FeaturePurposes::CREATE_CATALOG_ITEM, 12),
                200 => GroupFeatureValue::create(200, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM, 22),
            ],
            $group->getProduct(5000)->getFeatureValues()
        );
        $this->assertNull($group_11->getProduct(5000));

        $this->assertNull($group->getProduct(6000));
        $this->assertNotNull($group_11->getProduct(6000));
        $this->assertEquals(0, $group_11->getProduct(6000)->getParentProductId());

        $this->assertNotNull($group->getProduct(1000));
        $this->assertEquals(0, $group->getProduct(1000)->getParentProductId());
        $this->assertEquals(
            [
                100 => GroupFeatureValue::create(100, FeaturePurposes::CREATE_CATALOG_ITEM, 10),
                200 => GroupFeatureValue::create(200, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM, 20),
            ],
            $group->getProduct(1000)->getFeatureValues()
        );
        $this->assertNull($group_10->getProduct(1000));

        $this->assertNotNull($group->getProduct(2000));
        $this->assertEquals(5000, $group->getProduct(2000)->getParentProductId());
        $this->assertEquals(
            [
                100 => GroupFeatureValue::create(100, FeaturePurposes::CREATE_CATALOG_ITEM, 12),
                200 => GroupFeatureValue::create(200, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM, 23),
            ],
            $group->getProduct(2000)->getFeatureValues()
        );
        $this->assertNull($group_10->getProduct(2000));

        $this->assertNull($group_12->getProduct(8002));
        $this->assertNotNull($group->getProduct(8002));
        $this->assertEquals(0, $group->getProduct(8002)->getParentProductId());
        $this->assertEquals(
            [
                100 => GroupFeatureValue::create(100, FeaturePurposes::CREATE_CATALOG_ITEM, 13),
                200 => GroupFeatureValue::create(200, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM, 23),
            ],
            $group->getProduct(8002)->getFeatureValues()
        );
    }


    protected function getFeatures()
    {
        return GroupFeatureCollection::createFromFeatureList([
            ['feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
            ['feature_id' => 200, 'purpose' => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM],
        ]);
    }

    protected function getProducts($group_id)
    {
        if ($group_id === 10) {
            return GroupProductCollection::createFromProducts($this->getProductDataSlice([1000, 2000, 3000]));
        }

        if ($group_id === 11) {
            return GroupProductCollection::createFromProducts($this->getProductDataSlice([5000, 6000]));
        }

        if ($group_id === 12) {
            return GroupProductCollection::createFromProducts($this->getProductDataSlice([8000, 8001, 8002]));
        }
    }

    protected function getProductDataSlice(array $product_ids)
    {
        $result = [];
        $data = [
            1000 => [
                'product_id'         => 1000,
                'product'            => 'Product 1000',
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_group_id' => 10,
                'variation_features' => [
                    100 => ['variant_id' => 10, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 20,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            2000 => [
                'product_id'         => 2000,
                'product'            => 'Product 2000',
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_group_id' => 10,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            3000 => [
                'product_id'         => 3000,
                'product'            => 'Product 3000',
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_group_id' => 10,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            5000 => [
                'product_id'         => 5000,
                'product'            => 'Product 5000',
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_group_id' => 11,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 22,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            6000 => [
                'product_id'         => 6000,
                'product'            => 'Product 6000',
                'parent_product_id'  => 5000,
                'company_id'         => 1,
                'variation_group_id' => 11,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 21,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            7000 => [
                'product_id'         => 7000,
                'product'            => 'Product 7000',
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_group_id' => 0,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 23,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            8000 => [
                'product_id'         => 8000,
                'product'            => 'Product 8000',
                'parent_product_id'  => 0,
                'company_id'         => 1,
                'variation_group_id' => 12,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 23,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            8001 => [
                'product_id'         => 8001,
                'product'            => 'Product 8001',
                'parent_product_id'  => 8000,
                'company_id'         => 1,
                'variation_group_id' => 12,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 24,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
            8002 => [
                'product_id'         => 8002,
                'product'            => 'Product 8002',
                'parent_product_id'  => 8000,
                'company_id'         => 1,
                'variation_group_id' => 12,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 25,
                        'feature_id' => 200,
                        'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
                    ],
                ]
            ],
        ];

        foreach ($product_ids as $product_id) {
            $result[$product_id] = $data[$product_id];
        }

        return $result;
    }
}