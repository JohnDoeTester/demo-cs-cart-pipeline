<?php


namespace Tygh\Tests\Unit\Addons\ProductVariations\Service;


use Tygh\Addons\ProductVariations\Product\CombinationsGenerator;
use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Addons\ProductVariations\Product\Group\Group;
use Tygh\Addons\ProductVariations\Product\Group\GroupCodeGenerator;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;
use Tygh\Addons\ProductVariations\Product\Group\GroupProductCollection;
use Tygh\Addons\ProductVariations\Product\Group\Repository as GroupRepository;
use Tygh\Addons\ProductVariations\Product\ProductIdMap;
use Tygh\Addons\ProductVariations\Product\Repository as ProductRepository;
use Tygh\Addons\ProductVariations\Product\Sync\ProductDataIdentityMapRepository;
use Tygh\Addons\ProductVariations\Service;
use Tygh\Addons\ProductVariations\SyncService;
use Tygh\Tests\Unit\ATestCase;

class SetDefaultProductTest extends ATestCase
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

    public function testGeneral()
    {
        $features = $this->getFeatures();

        $group_10 = Group::createFromArray([
            'features' => $features,
            'products' => $this->getProducts(10)
        ]);

        $this->group_repository->expects($this->once())
            ->method('findGroupById')
            ->with(10)
            ->willReturn($group_10);

        $this->service->setDefaultProduct(10, 3000);

        $this->assertEquals(0, $group_10->getProduct(3000)->getParentProductId());
        $this->assertEquals(0, $group_10->getProduct(1000)->getParentProductId());
        $this->assertEquals(3000, $group_10->getProduct(2000)->getParentProductId());
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
            2000 => [
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
            3000 => [
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