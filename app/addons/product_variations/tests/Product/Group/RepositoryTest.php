<?php


namespace Tygh\Tests\Unit\Addons\ProductVariations\Product\Group;


use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Addons\ProductVariations\Product\Group\Group;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeature;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;
use Tygh\Addons\ProductVariations\Product\Group\GroupProduct;
use Tygh\Addons\ProductVariations\Product\Group\GroupProductCollection;
use Tygh\Addons\ProductVariations\Product\Group\Repository;
use Tygh\Addons\ProductVariations\Product\Type\Type;
use Tygh\Addons\ProductVariations\Tools\QueryFactory;
use Tygh\Tests\Unit\ATestCase;

class RepositoryTest extends ATestCase
{
    /** @var Repository */
    protected $repository;

    /** @var \Tygh\Addons\ProductVariations\Tools\QueryFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $query_factory;

    /** @var \Tygh\Database\Connection|\PHPUnit\Framework\MockObject\MockObject */
    protected $db_connection;

    /** @var array  */
    protected $queries = [];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->db_connection = $this->getMockBuilder(\Tygh\Database\Connection::class)
            ->setMethods(['error', 'getRow', 'query', 'getArray', 'hasError', 'getColumn', 'getSingleHash', 'getHash', 'getMultiHash'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->query_factory = new QueryFactory($this->db_connection);
        $this->repository = new Repository($this->query_factory, 'en');
    }

    public function testSaveNewGroup()
    {
        $feature_list = GroupFeatureCollection::createFromFeatureList([
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

        $group = Group::createNewGroup($feature_list);
        $group->attachProducts($products);

        $this->db_connection->expects($this->at(0))->method('query')->with('INSERT INTO ?:product_variation_groups ?e', $this->anything())->willReturn(10);
        $this->db_connection->expects($this->at(1))->method('query')->with('INSERT INTO ?:product_variation_group_features ?m', [
            [
                'group_id'   => 10,
                'feature_id' => 100,
                'purpose'    => FeaturePurposes::CREATE_CATALOG_ITEM
            ],
            [
                'group_id'   => 10,
                'feature_id' => 200,
                'purpose'    => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
            ],
        ]);
        $this->db_connection->expects($this->at(2))->method('query')->with('INSERT INTO ?:product_variation_group_products ?m', [
            [
                'group_id'          => 10,
                'product_id'        => 1000,
                'parent_product_id' => 0
            ],
            [
                'group_id'          => 10,
                'product_id'        => 2000,
                'parent_product_id' => 0
            ],
            [
                'group_id'          => 10,
                'product_id'        => 3000,
                'parent_product_id' => 2000
            ],
        ]);

        $this->db_connection->method('getArray')
            ->with($this->stringContains('FROM ?:product_feature_variant_descriptions'))
            ->willReturn([
                [
                    'feature_id' => 100,
                    'variant_id' => 11,
                    'lang_code'  => 'en',
                    'value_int'  => null
                ],
                [
                    'feature_id' => 200,
                    'variant_id' => 12,
                    'lang_code'  => 'en',
                    'value_int'  => null
                ]
            ]);

        $this->repository->save($group);
    }

    public function testSaveExistsGroup()
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
            [
                'product_id'         => 6000,
                'parent_product_id'  => 2000,
                'company_id'         => 1,
                'variation_features' => [
                    100 => ['variant_id' => 11, 'feature_id' => 100, 'purpose' => FeaturePurposes::CREATE_CATALOG_ITEM],
                    200 => [
                        'variant_id' => 27,
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

        $group = Group::createFromArray([
            'id' => 10,
            'code' => 'PV-1',
            'features' => $features,
            'products' => $products
        ]);

        $group->attachProducts($new_products);
        $group->detachProductById(6000);

        $this->db_connection->expects($this->at(0))->method('query')->with('DELETE FROM ?:product_variation_group_products WHERE product_id IN (?n) AND group_id = ?i', [6000], 10);

        $this->db_connection->expects($this->at(1))->method('query')->with('INSERT INTO ?:product_variation_group_products ?m', [
            [
                'group_id'          => 10,
                'product_id'        => 5000,
                'parent_product_id' => 3000
            ]
        ]);

        $this->db_connection->expects($this->at(2))->method('query')->with(
            'UPDATE ?:product_variation_group_products SET ?u WHERE product_id = ?i AND group_id = ?i',
            ['parent_product_id' => 0],
            3000, 10
        );

        $this->db_connection->expects($this->at(3))->method('query')->with(
            'UPDATE ?:product_variation_group_products SET ?u WHERE product_id = ?i AND group_id = ?i',
            ['parent_product_id' => 3000],
            4000, 10
        );

        $this->db_connection->expects($this->at(4))->method('query')->with(
            'UPDATE ?:products SET ?u WHERE product_id IN (?n)',
            ['parent_product_id' => 0, 'product_type' => Type::PRODUCT_TYPE_SIMPLE],
            [6000, 3000]
        );

        $this->db_connection->expects($this->at(5))->method('query')->with(
            'UPDATE ?:products SET ?u WHERE product_id IN (?n)',
            ['parent_product_id' => 3000, 'product_type' => Type::PRODUCT_TYPE_VARIATION],
            [5000, 4000]
        );

        $this->db_connection->expects($this->at(6))->method('query')->with(
            'DELETE FROM ?:product_features_values WHERE product_id = ?i AND feature_id IN (?n)',
            2000, [100, 200]
        );

        $this->db_connection->expects($this->exactly(3))
            ->method('getArray')
            ->withConsecutive(
                [$this->stringContains('FROM ?:product_feature_variant_descriptions'), [100, 200], [12, 20]],
                [$this->stringContains('FROM ?:product_feature_variant_descriptions'), [100, 200], [11, 25]],
                []
            )
            ->willReturnOnConsecutiveCalls(
                [
                    [
                        'feature_id' => 100,
                        'variant_id' => 12,
                        'lang_code'  => 'en',
                        'value_int'  => null
                    ],
                    [
                        'feature_id' => 200,
                        'variant_id' => 20,
                        'lang_code'  => 'en',
                        'value_int'  => null
                    ]
                ],
                [
                    [
                        'feature_id' => 100,
                        'variant_id' => 11,
                        'lang_code'  => 'en',
                        'value_int'  => null
                    ],
                    [
                        'feature_id' => 200,
                        'variant_id' => 25,
                        'lang_code'  => 'en',
                        'value_int'  => null
                    ]
                ],
                []
            );

        $this->db_connection->expects($this->at(8))->method('query')->with('INSERT INTO ?:product_features_values ?m', [
            [
                'product_id' => 2000,
                'feature_id' => 100,
                'variant_id' => 12,
                'lang_code'  => 'en',
                'value_int'  => null
            ],
            [
                'product_id' => 2000,
                'feature_id' => 200,
                'variant_id' => 20,
                'lang_code'  => 'en',
                'value_int'  => null
            ]
        ]);

        $this->db_connection->expects($this->at(9))->method('query')->with(
            'DELETE FROM ?:product_features_values WHERE product_id = ?i AND feature_id IN (?n)',
            3000, [100, 200]
        );

        $this->db_connection->expects($this->at(11))->method('query')->with('INSERT INTO ?:product_features_values ?m', [
            [
                'product_id' => 3000,
                'feature_id' => 100,
                'variant_id' => 11,
                'lang_code'  => 'en',
                'value_int'  => null
            ],
            [
                'product_id' => 3000,
                'feature_id' => 200,
                'variant_id' => 25,
                'lang_code'  => 'en',
                'value_int'  => null
            ]
        ]);

        $this->repository->save($group);
    }
}