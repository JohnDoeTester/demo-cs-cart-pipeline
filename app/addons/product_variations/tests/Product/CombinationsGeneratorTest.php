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

namespace Tygh\Tests\Unit\Addons\ProductVariations\Product;

use Tygh\Addons\ProductVariations\Product\CombinationsGenerator;
use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValueCollection;
use Tygh\Addons\ProductVariations\Product\Repository as ProductRepository;
use Tygh\Tests\Unit\ATestCase;

class CombinationsGeneratorTest extends ATestCase
{
    /**
     * @var \Tygh\Addons\ProductVariations\Product\CombinationsGenerator
     */
    protected $combinations_generator;

    /** @var \Tygh\Addons\ProductVariations\Product\Repository|\PHPUnit\Framework\MockObject\MockObject */
    protected $product_repository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->product_repository = $this->getMockBuilder(ProductRepository::class)
            ->setMethods([
                'findProducts',
                'findFeaturesByFeatureCollection',
                'loadFeaturesVariants',
                'loadProductsFeatures'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->combinations_generator = new CombinationsGenerator(
            $this->product_repository
        );
    }

    public function testGeneral()
    {
        $this->product_repository->method('findProducts')
            ->with([280, 281, 282])
            ->willReturn($this->getVariationsProducts());

        $this->product_repository->method('loadProductsFeatures')
            ->willReturn($this->getVariationsProducts());

        $this->product_repository->method('findFeaturesByFeatureCollection')
            ->willReturn($this->getFeaturesByFeatureCollection());

        $this->product_repository->method('loadFeaturesVariants')
            ->willReturn($this->getLoadedFeaturesVariants());


        $combinations = $this->combinations_generator->generateByFeatureVariant(
            new GroupFeatureValueCollection([
                new GroupFeatureValue(549, FeaturePurposes::CREATE_CATALOG_ITEM, 1198),
                new GroupFeatureValue(549, FeaturePurposes::CREATE_CATALOG_ITEM, 1199),
                new GroupFeatureValue(549, FeaturePurposes::CREATE_CATALOG_ITEM, 1200),
                new GroupFeatureValue(548, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM, 1194),
                new GroupFeatureValue(548, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM, 1195),
                new GroupFeatureValue(548, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM, 1196),
                new GroupFeatureValue(548, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM, 1197),
            ]),
            [280, 281, 282],
            [
                '1196_1198' => [
                    'set_as_default' => true,
                    'product_name'   => 'Большая зеленая футболка',
                    'product_code'   => 'CODE-1196_1198',
                    'product_price'  => 150,
                    'product_amount' => 30
                ],
                '1194_1198' => [
                    'product_code'   => 'CODE-1194_1198',
                ],
                '1195_1198' => [
                    'product_code'   => 'CODE-1195_1198',
                ],
                '1197_1198' => [
                    'product_code'   => 'CODE-1197_1198',
                ],
                '1195_1199' => [
                    'active'         => false,
                    'product_code'   => 'CODE-1195_1199',
                    'product_name'   => 'Голубая футболка среднего размера',
                ],
                '1194_1199' => [
                    'active'       => false,
                    'product_code' => 'CODE-1194_1199',
                ],
                '1197_1199' => [
                    'set_as_default' => true,
                    'product_code'   => 'CODE-1197_1199',
                ],
                '1196_1199' => [
                    'product_code'   => 'CODE-1196_1199',
                ],
                '1195_1200' => [
                    'product_code'   => 'CODE-1195_1200',
                ],
                '1194_1200' => [
                    'product_code'   => 'CODE-1194_1200',
                ],
                '1197_1200' => [
                    'product_code'   => 'CODE-1197_1200',
                ],
                '1196_1200' => [
                    'product_code'   => 'CODE-1196_1200',
                ]
            ]
        );

        $this->assertEquals(
            [
                '1196_1198' => [
                    'active'                    => true,
                    'updated'                   => true,
                    'combination_id'            => '1196_1198',
                    'selected_variants'         => [
                        549 => '1198',
                        548 => '1196',
                    ],
                    'variant_names'             => [
                        1198 => 'Green',
                        1196 => 'XX Large',
                    ],
                    'variants_position'         => '1_1198_6_1196',
                    'name'                      => 'Color: Green, Size: XX Large',
                    'group_name'                => 'Color: Green',
                    'exists'                    => false,
                    'linked'                    => false,
                    'product_id'                => 0,
                    'product_name'              => 'Большая зеленая футболка',
                    'product_code'              => 'CODE-1196_1198',
                    'product_price'             => 150,
                    'product_amount'            => 30,
                    'base_product_id'           => '280',
                    'parent_product_id'         => 0,
                    'parent_combination_id'     => '0',
                    'group_combination_id'      => '1198',
                    'group_position'            => '1_1198',
                    'has_children'              => true,
                    'parent_combination_exists' => false
                ],
                '1194_1198' => [
                    'active'                    => true,
                    'updated'                   => true,
                    'combination_id'            => '1194_1198',
                    'selected_variants'         => [
                        549 => '1198',
                        548 => '1194',
                    ],
                    'variant_names'             => [
                        1198 => 'Green',
                        1194 => 'Large',
                    ],
                    'variants_position'         => '1_1198_2_1194',
                    'name'                      => 'Color: Green, Size: Large',
                    'group_name'                => 'Color: Green',
                    'exists'                    => false,
                    'linked'                    => false,
                    'product_id'                => 0,
                    'product_name'              => 'T-shirt, Color: Black',
                    'product_code'              => 'CODE-1194_1198',
                    'product_price'             => '36.000000',
                    'product_amount'            => '90',
                    'base_product_id'           => '280',
                    'parent_product_id'         => 0,
                    'parent_combination_id'     => '1196_1198',
                    'group_combination_id'      => '1198',
                    'group_position'            => '1_1198',
                    'has_children'              => false,
                    'parent_combination_exists' => false
                ],
                '1195_1198' => [
                    'active'                    => true,
                    'updated'                   => true,
                    'combination_id'            => '1195_1198',
                    'selected_variants'         => [
                        549 => '1198',
                        548 => '1195',
                    ],
                    'variant_names'             => [
                        1198 => 'Green',
                        1195 => 'Medium',
                    ],
                    'variants_position'         => '1_1198_3_1195',
                    'name'                      => 'Color: Green, Size: Medium',
                    'group_name'                => 'Color: Green',
                    'exists'                    => false,
                    'linked'                    => false,
                    'product_id'                => 0,
                    'product_name'              => 'T-shirt, Color: Black',
                    'product_code'              => 'CODE-1195_1198',
                    'product_price'             => '36.000000',
                    'product_amount'            => '90',
                    'base_product_id'           => '280',
                    'parent_product_id'         => 0,
                    'parent_combination_id'     => '1196_1198',
                    'group_combination_id'      => '1198',
                    'group_position'            => '1_1198',
                    'has_children'              => false,
                    'parent_combination_exists' => false
                ],
                '1197_1198' => [
                    'active'                    => true,
                    'updated'                   => true,
                    'combination_id'            => '1197_1198',
                    'selected_variants'         => [
                        549 => '1198',
                        548 => '1197',
                    ],
                    'variant_names'             => [
                        1198 => 'Green',
                        1197 => 'X Large',
                    ],
                    'variants_position'         => '1_1198_5_1197',
                    'name'                      => 'Color: Green, Size: X Large',
                    'group_name'                => 'Color: Green',
                    'exists'                    => false,
                    'linked'                    => false,
                    'product_id'                => 0,
                    'product_name'              => 'T-shirt, Color: Black',
                    'product_code'              => 'CODE-1197_1198',
                    'product_price'             => '36.000000',
                    'product_amount'            => '90',
                    'base_product_id'           => '280',
                    'parent_product_id'         => 0,
                    'parent_combination_id'     => '1196_1198',
                    'group_combination_id'      => '1198',
                    'group_position'            => '1_1198',
                    'has_children'              => false,
                    'parent_combination_exists' => false
                ],
                '1195_1199' => [
                    'active'                    => true,
                    'updated'                   => true,
                    'combination_id'            => '1195_1199',
                    'selected_variants'         => [
                        549 => '1199',
                        548 => '1195',
                    ],
                    'variant_names'             => [
                        1199 => 'Blue',
                        1195 => 'Medium',
                    ],
                    'variants_position'         => '2_1199_3_1195',
                    'name'                      => 'Color: Blue, Size: Medium',
                    'group_name'                => 'Color: Blue',
                    'exists'                    => true,
                    'linked'                    => true,
                    'product_id'                => '282',
                    'product_name'              => 'Голубая футболка среднего размера',
                    'product_code'              => 'CODE-1195_1199',
                    'product_price'             => '27.990000',
                    'product_amount'            => '90',
                    'base_product_id'           => '282',
                    'parent_product_id'         => '0',
                    'parent_combination_id'     => '0',
                    'group_combination_id'      => '1199',
                    'group_position'            => '2_1199',
                    'has_children'              => true,
                    'parent_combination_exists' => false
                ],
                '1194_1199' => [
                    'active'                    => false,
                    'updated'                   => true,
                    'combination_id'            => '1194_1199',
                    'selected_variants'         => [
                        549 => '1199',
                        548 => '1194',
                    ],
                    'variant_names'             => [
                        1199 => 'Blue',
                        1194 => 'Large',
                    ],
                    'variants_position'         => '2_1199_2_1194',
                    'name'                      => 'Color: Blue, Size: Large',
                    'group_name'                => 'Color: Blue',
                    'exists'                    => false,
                    'linked'                    => false,
                    'product_id'                => 0,
                    'product_name'              => 'T-shirt, Color: Blue',
                    'product_code'              => 'CODE-1194_1199',
                    'product_price'             => '27.990000',
                    'product_amount'            => '90',
                    'base_product_id'           => '282',
                    'parent_product_id'         => '282',
                    'parent_combination_id'     => '1195_1199',
                    'group_combination_id'      => '1199',
                    'group_position'            => '2_1199',
                    'has_children'              => false,
                    'parent_combination_exists' => true
                ],
                '1197_1199' => [
                    'active'                    => true,
                    'updated'                   => true,
                    'combination_id'            => '1197_1199',
                    'selected_variants'         => [
                        549 => '1199',
                        548 => '1197',
                    ],
                    'variant_names'             => [
                        1199 => 'Blue',
                        1197 => 'X Large',
                    ],
                    'variants_position'         => '2_1199_5_1197',
                    'name'                      => 'Color: Blue, Size: X Large',
                    'group_name'                => 'Color: Blue',
                    'exists'                    => false,
                    'linked'                    => false,
                    'product_id'                => 0,
                    'product_name'              => 'T-shirt, Color: Blue',
                    'product_code'              => 'CODE-1197_1199',
                    'product_price'             => '27.990000',
                    'product_amount'            => '90',
                    'base_product_id'           => '282',
                    'parent_product_id'         => '282',
                    'parent_combination_id'     => '1195_1199',
                    'group_combination_id'      => '1199',
                    'group_position'            => '2_1199',
                    'has_children'              => false,
                    'parent_combination_exists' => true
                ],
                '1196_1199' => [
                    'active'                    => true,
                    'updated'                   => true,
                    'combination_id'            => '1196_1199',
                    'selected_variants'         => [
                        549 => '1199',
                        548 => '1196',
                    ],
                    'variant_names'             => [
                        1199 => 'Blue',
                        1196 => 'XX Large',
                    ],
                    'variants_position'         => '2_1199_6_1196',
                    'name'                      => 'Color: Blue, Size: XX Large',
                    'group_name'                => 'Color: Blue',
                    'exists'                    => false,
                    'linked'                    => false,
                    'product_id'                => 0,
                    'product_name'              => 'T-shirt, Color: Blue',
                    'product_code'              => 'CODE-1196_1199',
                    'product_price'             => '27.990000',
                    'product_amount'            => '90',
                    'base_product_id'           => '282',
                    'parent_product_id'         => '282',
                    'parent_combination_id'     => '1195_1199',
                    'group_combination_id'      => '1199',
                    'group_position'            => '2_1199',
                    'has_children'              => false,
                    'parent_combination_exists' => true
                ],
                '1195_1200' => [
                    'active'                    => true,
                    'updated'                   => true,
                    'combination_id'            => '1195_1200',
                    'selected_variants'         => [
                        549 => '1200',
                        548 => '1195',
                    ],
                    'variant_names'             => [
                        1200 => 'Black',
                        1195 => 'Medium',
                    ],
                    'variants_position'         => '3_1200_3_1195',
                    'name'                      => 'Color: Black, Size: Medium',
                    'group_name'                => 'Color: Black',
                    'exists'                    => true,
                    'linked'                    => true,
                    'product_id'                => '280',
                    'product_name'              => 'T-shirt, Color: Black',
                    'product_code'              => 'CODE-1195_1200',
                    'product_price'             => '36.000000',
                    'product_amount'            => '90',
                    'base_product_id'           => '280',
                    'parent_product_id'         => '0',
                    'parent_combination_id'     => '0',
                    'group_combination_id'      => '1200',
                    'group_position'            => '3_1200',
                    'has_children'              => true,
                    'parent_combination_exists' => false
                ],
                '1194_1200' => [
                    'active'                    => true,
                    'updated'                   => true,
                    'combination_id'            => '1194_1200',
                    'selected_variants'         => [
                        549 => '1200',
                        548 => '1194',
                    ],
                    'variant_names'             => [
                        1200 => 'Black',
                        1194 => 'Large',
                    ],
                    'variants_position'         => '3_1200_2_1194',
                    'name'                      => 'Color: Black, Size: Large',
                    'group_name'                => 'Color: Black',
                    'exists'                    => true,
                    'linked'                    => true,
                    'product_id'                => '281',
                    'product_name'              => 'T-shirt, Color: Black',
                    'product_code'              => 'CODE-1194_1200',
                    'product_price'             => '37.000000',
                    'product_amount'            => '0',
                    'base_product_id'           => '281',
                    'parent_product_id'         => '280',
                    'parent_combination_id'     => '1195_1200',
                    'group_combination_id'      => '1200',
                    'group_position'            => '3_1200',
                    'has_children'              => false,
                    'parent_combination_exists' => true
                ],
                '1197_1200' => [
                    'active'                    => true,
                    'updated'                   => true,
                    'combination_id'            => '1197_1200',
                    'selected_variants'         => [
                        549 => '1200',
                        548 => '1197',
                    ],
                    'variant_names'             => [
                        1200 => 'Black',
                        1197 => 'X Large',
                    ],
                    'variants_position'         => '3_1200_5_1197',
                    'name'                      => 'Color: Black, Size: X Large',
                    'group_name'                => 'Color: Black',
                    'exists'                    => false,
                    'linked'                    => false,
                    'product_id'                => 0,
                    'product_name'              => 'T-shirt, Color: Black',
                    'product_code'              => 'CODE-1197_1200',
                    'product_price'             => '36.000000',
                    'product_amount'            => '90',
                    'base_product_id'           => '280',
                    'parent_product_id'         => '280',
                    'parent_combination_id'     => '1195_1200',
                    'group_combination_id'      => '1200',
                    'group_position'            => '3_1200',
                    'has_children'              => false,
                    'parent_combination_exists' => true
                ],
                '1196_1200' => [
                    'active'                    => true,
                    'updated'                   => true,
                    'combination_id'            => '1196_1200',
                    'selected_variants'         => [
                        549 => '1200',
                        548 => '1196',
                    ],
                    'variant_names'             => [
                        1200 => 'Black',
                        1196 => 'XX Large',
                    ],
                    'variants_position'         => '3_1200_6_1196',
                    'name'                      => 'Color: Black, Size: XX Large',
                    'group_name'                => 'Color: Black',
                    'exists'                    => false,
                    'linked'                    => false,
                    'product_id'                => 0,
                    'product_name'              => 'T-shirt, Color: Black',
                    'product_code'              => 'CODE-1196_1200',
                    'product_price'             => '36.000000',
                    'product_amount'            => '90',
                    'base_product_id'           => '280',
                    'parent_product_id'         => '280',
                    'parent_combination_id'     => '1195_1200',
                    'group_combination_id'      => '1200',
                    'group_position'            => '3_1200',
                    'has_children'              => false,
                    'parent_combination_exists' => true
                ],
            ],
            $combinations
        );
    }

    public function testNewGroup()
    {
        $this->product_repository->method('findProducts')
            ->with([280])
            ->willReturn($this->getSimpleProducts([280]));

        $this->product_repository->method('loadProductsFeatures')
            ->willReturn($this->getSimpleProducts([280]));

        $this->product_repository->method('findFeaturesByFeatureCollection')
            ->willReturn($this->getFeaturesByFeatureCollection());

        $this->product_repository->method('loadFeaturesVariants')
            ->willReturn($this->getLoadedFeaturesVariants());

        $combinations = $this->combinations_generator->generateByFeatureVariant(
            new GroupFeatureValueCollection([
                new GroupFeatureValue(549, FeaturePurposes::CREATE_CATALOG_ITEM, 1198),
                new GroupFeatureValue(549, FeaturePurposes::CREATE_CATALOG_ITEM, 1199),
                new GroupFeatureValue(549, FeaturePurposes::CREATE_CATALOG_ITEM, 1200),
                new GroupFeatureValue(548, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM, 1194),
                new GroupFeatureValue(548, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM, 1195),
                new GroupFeatureValue(548, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM, 1196),
                new GroupFeatureValue(548, FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM, 1197),
            ]),
            [280],
            [
                '1196_1198' => [
                    'set_as_default' => true,
                    'product_name'   => 'Большая зеленая футболка',
                    'product_code'   => 'CODE-1196_1198',
                    'product_price'  => 150,
                    'product_amount' => 30
                ],
                '1194_1198' => [
                    'product_code'   => 'CODE-1194_1198',
                ],
                '1195_1198' => [
                    'product_code'   => 'CODE-1195_1198',
                ],
                '1197_1198' => [
                    'product_code'   => 'CODE-1197_1198',
                ],
                '1195_1199' => [
                    'active'         => false,
                    'product_code'   => 'CODE-1195_1199',
                    'product_name'   => 'Голубая футболка среднего размера',
                ],
                '1197_1199' => [
                    'set_as_default' => true,
                    'product_code'   => 'CODE-1197_1199',
                ],
                '1194_1199' => [
                    'active'       => false,
                    'product_code' => 'CODE-1194_1199',
                ],
                '1196_1199' => [
                    'product_code'   => 'CODE-1196_1199',
                ],
                '1195_1200' => [
                    'set_as_default' => true,
                    'product_code'   => 'CODE-1195_1200',
                ],
                '1194_1200' => [
                    'product_code'   => 'CODE-1194_1200',
                ],
                '1197_1200' => [
                    'product_code'   => 'CODE-1197_1200',
                ],
                '1196_1200' => [
                    'product_code'   => 'CODE-1196_1200',
                ]
            ]
        );

        $this->assertEquals([
            '1196_1198' => [
                'active'                    => true,
                'updated'                   => true,
                'combination_id'            => '1196_1198',
                'selected_variants'         => [
                    549 => '1198',
                    548 => '1196',
                ],
                'variants_position'         => '1_1198_6_1196',
                'variant_names'             => [
                    1198 => 'Green',
                    1196 => 'XX Large',
                ],
                'name'                      => 'Color: Green, Size: XX Large',
                'group_name'                => 'Color: Green',
                'exists'                    => true,
                'linked'                    => false,
                'has_children'              => true,
                'product_id'                => '280',
                'product_name'              => 'Большая зеленая футболка',
                'product_code'              => 'CODE-1196_1198',
                'product_price'             => 150,
                'product_amount'            => 30,
                'parent_product_id'         => 0,
                'base_product_id'           => '280',
                'parent_combination_id'     => '0',
                'parent_combination_exists' => false,
                'group_combination_id'      => '1198',
                'group_position'            => '1_1198',
            ],
            '1194_1198' => [
                'active'                    => true,
                'updated'                   => true,
                'combination_id'            => '1194_1198',
                'selected_variants'         => [
                    549 => '1198',
                    548 => '1194',
                ],
                'variants_position'         => '1_1198_2_1194',
                'variant_names'             => [
                    1198 => 'Green',
                    1194 => 'Large',
                ],
                'name'                      => 'Color: Green, Size: Large',
                'group_name'                => 'Color: Green',
                'exists'                    => false,
                'linked'                    => false,
                'has_children'              => false,
                'product_id'                => 0,
                'product_name'              => 'T-shirt, Color: Black',
                'product_code'              => 'CODE-1194_1198',
                'product_price'             => '36.000000',
                'product_amount'            => '90',
                'parent_product_id'         => 0,
                'base_product_id'           => '280',
                'parent_combination_id'     => '1196_1198',
                'parent_combination_exists' => null,
                'group_combination_id'      => '1198',
                'group_position'            => '1_1198',
            ],
            '1195_1198' => [
                'active'                    => true,
                'updated'                   => true,
                'combination_id'            => '1195_1198',
                'selected_variants'         => [
                    549 => '1198',
                    548 => '1195',
                ],
                'variants_position'         => '1_1198_3_1195',
                'variant_names'             => [
                    1198 => 'Green',
                    1195 => 'Medium',
                ],
                'name'                      => 'Color: Green, Size: Medium',
                'group_name'                => 'Color: Green',
                'exists'                    => false,
                'linked'                    => false,
                'has_children'              => false,
                'product_id'                => 0,
                'product_name'              => 'T-shirt, Color: Black',
                'product_code'              => 'CODE-1195_1198',
                'product_price'             => '36.000000',
                'product_amount'            => '90',
                'parent_product_id'         => 0,
                'base_product_id'           => '280',
                'parent_combination_id'     => '1196_1198',
                'parent_combination_exists' => false,
                'group_combination_id'      => '1198',
                'group_position'            => '1_1198',
            ],
            '1197_1198' => [
                'active'                    => true,
                'updated'                   => true,
                'combination_id'            => '1197_1198',
                'selected_variants'         => [
                    549 => '1198',
                    548 => '1197',
                ],
                'variants_position'         => '1_1198_5_1197',
                'variant_names'             => [
                    1198 => 'Green',
                    1197 => 'X Large',
                ],
                'name'                      => 'Color: Green, Size: X Large',
                'group_name'                => 'Color: Green',
                'exists'                    => false,
                'linked'                    => false,
                'has_children'              => false,
                'product_id'                => 0,
                'product_name'              => 'T-shirt, Color: Black',
                'product_code'              => 'CODE-1197_1198',
                'product_price'             => '36.000000',
                'product_amount'            => '90',
                'parent_product_id'         => 0,
                'base_product_id'           => '280',
                'parent_combination_id'     => '1196_1198',
                'parent_combination_exists' => false,
                'group_combination_id'      => '1198',
                'group_position'            => '1_1198',
            ],
            '1197_1199' => [
                'active'                    => true,
                'updated'                   => true,
                'combination_id'            => '1197_1199',
                'selected_variants'         => [
                    549 => '1199',
                    548 => '1197',
                ],
                'variants_position'         => '2_1199_5_1197',
                'variant_names'             => [
                    1199 => 'Blue',
                    1197 => 'X Large',
                ],
                'name'                      => 'Color: Blue, Size: X Large',
                'group_name'                => 'Color: Blue',
                'exists'                    => false,
                'linked'                    => false,
                'has_children'              => true,
                'product_id'                => 0,
                'product_name'              => 'T-shirt, Color: Black',
                'product_code'              => 'CODE-1197_1199',
                'product_price'             => '36.000000',
                'product_amount'            => '90',
                'parent_product_id'         => 0,
                'base_product_id'           => '280',
                'parent_combination_id'     => '0',
                'parent_combination_exists' => false,
                'group_combination_id'      => '1199',
                'group_position'            => '2_1199',
            ],
            '1194_1199' => [
                'active'                    => false,
                'updated'                   => true,
                'combination_id'            => '1194_1199',
                'selected_variants'         => [
                    549 => '1199',
                    548 => '1194',
                ],
                'variants_position'         => '2_1199_2_1194',
                'variant_names'             => [
                    1199 => 'Blue',
                    1194 => 'Large',
                ],
                'name'                      => 'Color: Blue, Size: Large',
                'group_name'                => 'Color: Blue',
                'exists'                    => false,
                'linked'                    => false,
                'has_children'              => false,
                'product_id'                => 0,
                'product_name'              => 'T-shirt, Color: Black',
                'product_code'              => 'CODE-1194_1199',
                'product_price'             => '36.000000',
                'product_amount'            => '90',
                'parent_product_id'         => 0,
                'base_product_id'           => '280',
                'parent_combination_id'     => '1197_1199',
                'parent_combination_exists' => null,
                'group_combination_id'      => '1199',
                'group_position'            => '2_1199',
            ],
            '1195_1199' => [
                'active'                    => false,
                'updated'                   => true,
                'combination_id'            => '1195_1199',
                'selected_variants'         => [
                    549 => '1199',
                    548 => '1195',
                ],
                'variants_position'         => '2_1199_3_1195',
                'variant_names'             => [
                    1199 => 'Blue',
                    1195 => 'Medium',
                ],
                'name'                      => 'Color: Blue, Size: Medium',
                'group_name'                => 'Color: Blue',
                'exists'                    => false,
                'linked'                    => false,
                'has_children'              => false,
                'product_id'                => 0,
                'product_name'              => 'Голубая футболка среднего размера',
                'product_code'              => 'CODE-1195_1199',
                'product_price'             => '36.000000',
                'product_amount'            => '90',
                'parent_product_id'         => 0,
                'base_product_id'           => '280',
                'parent_combination_id'     => '1197_1199',
                'parent_combination_exists' => false,
                'group_combination_id'      => '1199',
                'group_position'            => '2_1199',
            ],
            '1196_1199' => [
                'active'                    => true,
                'updated'                   => true,
                'combination_id'            => '1196_1199',
                'selected_variants'         => [
                    549 => '1199',
                    548 => '1196',
                ],
                'variants_position'         => '2_1199_6_1196',
                'variant_names'             => [
                    1199 => 'Blue',
                    1196 => 'XX Large',
                ],
                'name'                      => 'Color: Blue, Size: XX Large',
                'group_name'                => 'Color: Blue',
                'exists'                    => false,
                'linked'                    => false,
                'has_children'              => false,
                'product_id'                => 0,
                'product_name'              => 'T-shirt, Color: Black',
                'product_code'              => 'CODE-1196_1199',
                'product_price'             => '36.000000',
                'product_amount'            => '90',
                'parent_product_id'         => 0,
                'base_product_id'           => '280',
                'parent_combination_id'     => '1197_1199',
                'parent_combination_exists' => false,
                'group_combination_id'      => '1199',
                'group_position'            => '2_1199',
            ],
            '1195_1200' => [
                'active'                    => true,
                'updated'                   => true,
                'combination_id'            => '1195_1200',
                'selected_variants'         => [
                    549 => '1200',
                    548 => '1195',
                ],
                'variants_position'         => '3_1200_3_1195',
                'variant_names'             => [
                    1200 => 'Black',
                    1195 => 'Medium',
                ],
                'name'                      => 'Color: Black, Size: Medium',
                'group_name'                => 'Color: Black',
                'exists'                    => false,
                'linked'                    => false,
                'has_children'              => true,
                'product_id'                => 0,
                'product_name'              => 'T-shirt, Color: Black',
                'product_code'              => 'CODE-1195_1200',
                'product_price'             => '36.000000',
                'product_amount'            => '90',
                'parent_product_id'         => 0,
                'base_product_id'           => '280',
                'parent_combination_id'     => '0',
                'parent_combination_exists' => false,
                'group_combination_id'      => '1200',
                'group_position'            => '3_1200',
            ],
            '1194_1200' => [
                'active'                    => true,
                'updated'                   => true,
                'combination_id'            => '1194_1200',
                'selected_variants'         => [
                    549 => '1200',
                    548 => '1194',
                ],
                'variants_position'         => '3_1200_2_1194',
                'variant_names'             => [
                    1200 => 'Black',
                    1194 => 'Large',
                ],
                'name'                      => 'Color: Black, Size: Large',
                'group_name'                => 'Color: Black',
                'exists'                    => false,
                'linked'                    => false,
                'has_children'              => false,
                'product_id'                => 0,
                'product_name'              => 'T-shirt, Color: Black',
                'product_code'              => 'CODE-1194_1200',
                'product_price'             => '36.000000',
                'product_amount'            => '90',
                'parent_product_id'         => 0,
                'base_product_id'           => '280',
                'parent_combination_id'     => '1195_1200',
                'parent_combination_exists' => null,
                'group_combination_id'      => '1200',
                'group_position'            => '3_1200',
            ],
            '1197_1200' => [
                'active'                    => true,
                'updated'                   => true,
                'combination_id'            => '1197_1200',
                'selected_variants'         => [
                    549 => '1200',
                    548 => '1197',
                ],
                'variants_position'         => '3_1200_5_1197',
                'variant_names'             => [
                    1200 => 'Black',
                    1197 => 'X Large',
                ],
                'name'                      => 'Color: Black, Size: X Large',
                'group_name'                => 'Color: Black',
                'exists'                    => false,
                'linked'                    => false,
                'has_children'              => false,
                'product_id'                => 0,
                'product_name'              => 'T-shirt, Color: Black',
                'product_code'              => 'CODE-1197_1200',
                'product_price'             => '36.000000',
                'product_amount'            => '90',
                'parent_product_id'         => 0,
                'base_product_id'           => '280',
                'parent_combination_id'     => '1195_1200',
                'parent_combination_exists' => false,
                'group_combination_id'      => '1200',
                'group_position'            => '3_1200',
            ],
            '1196_1200' => [
                'active'                    => true,
                'updated'                   => true,
                'combination_id'            => '1196_1200',
                'selected_variants'         => [
                    549 => '1200',
                    548 => '1196',
                ],
                'variants_position'         => '3_1200_6_1196',
                'variant_names'             => [
                    1200 => 'Black',
                    1196 => 'XX Large',
                ],
                'name'                      => 'Color: Black, Size: XX Large',
                'group_name'                => 'Color: Black',
                'exists'                    => false,
                'linked'                    => false,
                'has_children'              => false,
                'product_id'                => 0,
                'product_name'              => 'T-shirt, Color: Black',
                'product_code'              => 'CODE-1196_1200',
                'product_price'             => '36.000000',
                'product_amount'            => '90',
                'parent_product_id'         => 0,
                'base_product_id'           => '280',
                'parent_combination_id'     => '1195_1200',
                'parent_combination_exists' => false,
                'group_combination_id'      => '1200',
                'group_position'            => '3_1200',
            ],
        ], $combinations);
    }

    protected function getSimpleProducts(array $product_ids = null)
    {
        $products = [
            280 => [
                'product_id' => '280',
                'product'    => 'T-shirt, Color: Black',
                'product_type'                 => 'P',
                'parent_product_id'            => '0',
                'product_code'                 => 'TSHIRT3',
                'status'                       => 'A',
                'company_id'                   => '1',
                'list_price'                   => '50.00',
                'amount'                       => '90',
                'weight'                       => '0.000',
                'price'                        => '36.000000',
                'category_ids'                 => [
                    0 => 325,
                    1 => 224,
                ],
                'main_category'                => 224,
                'variation_features'           => [],
                'variation_feature_ids'        => [],
                'variation_group_id'           => 0,
                'variation_group_id'           => 0,
            ],
            281 => [
                'product_id'                   => '281',
                'product'                      => 'T-shirt, Color: Black',
                'product_type'                 => 'V',
                'parent_product_id'            => '280',
                'product_code'                 => 'TSHIRT4',
                'status'                       => 'A',
                'company_id'                   => '1',
                'list_price'                   => '75.00',
                'amount'                       => '0',
                'weight'                       => '0.000',
                'price'                        => '37.000000',
                'category_ids'                 => [
                    0 => 325,
                    1 => 224,
                ],
                'main_category'                => 224,
                'variation_features'           => [],
                'variation_feature_ids'        => [],
                'variation_group_id'           => 0,
            ],
            282 => [
                'product_id'                   => '282',
                'product'                      => 'T-shirt, Color: Blue',
                'product_type'                 => 'P',
                'parent_product_id'            => '0',
                'product_code'                 => 'TSHIRT5',
                'status'                       => 'A',
                'company_id'                   => '1',
                'list_price'                   => '75.00',
                'amount'                       => '90',
                'weight'                       => '0.000',
                'price'                        => '27.990000',
                'category_ids'                 => [
                    0 => 224,
                ],
                'main_category'                => 224,
                'variation_features'           => [],
                'variation_feature_ids'        => [],
                'variation_group_id'           => 0,
            ],
        ];

        if ($product_ids !== null) {
            return array_intersect_key($products, array_flip($product_ids));
        }

        return $products;
    }

    protected function getVariationsProducts(array $product_ids = null)
    {
        $products = [
            280 => [
                'product_id'                   => '280',
                'product'                      => 'T-shirt, Color: Black',
                'product_type'                 => 'P',
                'parent_product_id'            => '0',
                'product_code'                 => 'TSHIRT3',
                'status'                       => 'A',
                'company_id'                   => '1',
                'list_price'                   => '50.00',
                'amount'                       => '90',
                'weight'                       => '0.000',
                'price'                        => '36.000000',
                'category_ids'                 => [
                    0 => 325,
                    1 => 224,
                ],
                'main_category'                => 224,
                'variation_features'           => [
                    549 => [
                        'feature_id'         => '549',
                        'feature_style'      => 'dropdown',
                        'position'           => '10',
                        'purpose'            => FeaturePurposes::CREATE_CATALOG_ITEM,
                        'display_on_catalog' => 'Y',
                        'description'        => 'Color',
                        'prefix'             => '',
                        'suffix'             => '',
                        'purpose_position'   => '0',
                        'variant'            => 'Black',
                        'variant_id'         => '1200',
                        'variant_position'   => '3',
                    ],
                    548 => [
                        'feature_id'         => '548',
                        'feature_style'      => 'dropdown',
                        'position'           => '20',
                        'purpose'            => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'display_on_catalog' => 'Y',
                        'description'        => 'Size',
                        'prefix'             => '',
                        'suffix'             => '',
                        'purpose_position'   => '1',
                        'variant'            => 'Medium',
                        'variant_id'         => '1195',
                        'variant_position'   => '3',
                    ],
                ],
                'variation_feature_ids'        => [
                    0 => 549,
                    1 => 548,
                ],
                'variation_group_id'           => 0,
            ],
            281 => [
                'product_id'                   => '281',
                'product'                      => 'T-shirt, Color: Black',
                'product_type'                 => 'V',
                'parent_product_id'            => '280',
                'product_code'                 => 'TSHIRT4',
                'status'                       => 'A',
                'company_id'                   => '1',
                'list_price'                   => '75.00',
                'amount'                       => '0',
                'weight'                       => '0.000',
                'price'                        => '37.000000',
                'category_ids'                 => [
                    0 => 325,
                    1 => 224,
                ],
                'main_category'                => 224,
                'variation_features'           => [
                    549 => [
                        'feature_id'         => '549',
                        'feature_style'      => 'dropdown',
                        'position'           => '10',
                        'purpose'            => FeaturePurposes::CREATE_CATALOG_ITEM,
                        'display_on_catalog' => 'Y',
                        'description'        => 'Color',
                        'prefix'             => '',
                        'suffix'             => '',
                        'purpose_position'   => '0',
                        'variant'            => 'Black',
                        'variant_id'         => '1200',
                        'variant_position'   => '3',
                    ],
                    548 => [
                        'feature_id'         => '548',
                        'feature_style'      => 'dropdown',
                        'position'           => '20',
                        'purpose'            => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'display_on_catalog' => 'Y',
                        'description'        => 'Size',
                        'prefix'             => '',
                        'suffix'             => '',
                        'purpose_position'   => '1',
                        'variant'            => 'Large',
                        'variant_id'         => '1194',
                        'variant_position'   => '2',
                    ],
                ],
                'variation_feature_ids'        => [
                    0 => 549,
                    1 => 548,
                ],
                'variation_group_id'           => 0,
            ],
            282 => [
                'product_id'                   => '282',
                'product'                      => 'T-shirt, Color: Blue',
                'product_type'                 => 'P',
                'parent_product_id'            => '0',
                'product_code'                 => 'TSHIRT5',
                'status'                       => 'A',
                'company_id'                   => '1',
                'list_price'                   => '75.00',
                'amount'                       => '90',
                'weight'                       => '0.000',
                'price'                        => '27.990000',
                'category_ids'                 => [
                    0 => 224,
                ],
                'main_category'                => 224,
                'variation_features'           => [
                    549 => [
                        'feature_id'         => '549',
                        'feature_style'      => 'dropdown',
                        'position'           => '10',
                        'purpose'            => FeaturePurposes::CREATE_CATALOG_ITEM,
                        'display_on_catalog' => 'Y',
                        'description'        => 'Color',
                        'prefix'             => '',
                        'suffix'             => '',
                        'purpose_position'   => '0',
                        'variant'            => 'Blue',
                        'variant_id'         => '1199',
                        'variant_position'   => '2',
                    ],
                    548 => [
                        'feature_id'         => '548',
                        'feature_style'      => 'dropdown',
                        'position'           => '20',
                        'purpose'            => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                        'display_on_catalog' => 'Y',
                        'description'        => 'Size',
                        'prefix'             => '',
                        'suffix'             => '',
                        'purpose_position'   => '1',
                        'variant'            => 'Medium',
                        'variant_id'         => '1195',
                        'variant_position'   => '3',
                    ],
                ],
                'variation_feature_ids'        => [
                    0 => 549,
                    1 => 548,
                ],
                'variation_group_id'           => 0,
            ],
        ];

        if ($product_ids !== null) {
            return array_intersect_key($products, array_flip($product_ids));
        }

        return $products;
    }

    protected function getFeaturesByFeatureCollection()
    {
        return [
            549 => [
                'feature_id' => '549',
                'feature_style'      => 'dropdown',
                'position'           => '10',
                'purpose'            => FeaturePurposes::CREATE_CATALOG_ITEM,
                'display_on_catalog' => 'Y',
                'description'        => 'Color',
                'prefix'             => '',
                'suffix'             => '',
                'purpose_position'   => '0',
            ],
            548 => [
                'feature_id'         => '548',
                'feature_style'      => 'dropdown',
                'position'           => '20',
                'purpose'            => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                'display_on_catalog' => 'Y',
                'description'        => 'Size',
                'prefix'             => '',
                'suffix'             => '',
                'purpose_position'   => '1',
            ],
        ];
    }

    protected function getLoadedFeaturesVariants()
    {
        return [
            549 => [
                'feature_id'         => '549',
                'feature_style'      => 'dropdown',
                'position'           => '10',
                'purpose'            => FeaturePurposes::CREATE_CATALOG_ITEM,
                'display_on_catalog' => 'Y',
                'description'        => 'Color',
                'prefix'             => '',
                'suffix'             => '',
                'purpose_position'   => '0',
                'variants'           => [
                    1198 => [
                        'feature_id' => '549',
                        'variant_id' => '1198',
                        'position'   => '1',
                        'variant'    => 'Green',
                    ],
                    1199 => [
                        'feature_id' => '549',
                        'variant_id' => '1199',
                        'position'   => '2',
                        'variant'    => 'Blue',
                    ],
                    1200 => [
                        'feature_id' => '549',
                        'variant_id' => '1200',
                        'position'   => '3',
                        'variant'    => 'Black',
                    ],
                    1201 => [
                        'feature_id' => '549',
                        'variant_id' => '1201',
                        'position'   => '4',
                        'variant'    => 'White',
                    ],
                ],
            ],
            548 => [
                'feature_id'         => '548',
                'feature_style'      => 'dropdown',
                'position'           => '20',
                'purpose'            => FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM,
                'display_on_catalog' => 'Y',
                'description'        => 'Size',
                'prefix'             => '',
                'suffix'             => '',
                'purpose_position'   => '1',
                'variants'           => [
                    1193 => [
                        'feature_id' => '548',
                        'variant_id' => '1193',
                        'position'   => '1',
                        'variant'    => 'Small',
                    ],
                    1194 => [
                        'feature_id' => '548',
                        'variant_id' => '1194',
                        'position'   => '2',
                        'variant'    => 'Large',
                    ],
                    1195 => [
                        'feature_id' => '548',
                        'variant_id' => '1195',
                        'position'   => '3',
                        'variant'    => 'Medium',
                    ],
                    1197 => [
                        'feature_id' => '548',
                        'variant_id' => '1197',
                        'position'   => '5',
                        'variant'    => 'X Large',
                    ],
                    1196 => [
                        'feature_id' => '548',
                        'variant_id' => '1196',
                        'position'   => '6',
                        'variant'    => 'XX Large',
                    ],
                ],
            ],
        ];
    }
}
