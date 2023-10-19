<?php


namespace Tygh\Tests\Unit\Addons\ProductVariations\Product;


use Tygh\Addons\ProductVariations\Product\Group\Repository;
use Tygh\Addons\ProductVariations\Product\ProductIdMap;
use Tygh\Addons\ProductVariations\Product\Type\Type;
use Tygh\Tests\Unit\ATestCase;

class ProductIdMapTest extends ATestCase
{
    /** @var \Tygh\Addons\ProductVariations\Product\Group\Repository|\PHPUnit\Framework\MockObject\MockObject */
    protected $group_repository;

    /** @var \Tygh\Addons\ProductVariations\Product\ProductIdMap */
    protected $product_id_map;

    protected function setUp(): void
    {
        $this->group_repository = $this->getMockBuilder(Repository::class)
            ->setMethods(['getParentProductIdMap', 'getProductChildrenIds'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->product_id_map = new ProductIdMap($this->group_repository);
    }

    public function testGetParentProductId()
    {
        $this->product_id_map->addProductIdsToPreload([1, 2, 3, 4, 5, 6, 7]);
        $this->product_id_map->addProductIdsToPreload(range(1000, 1600));
        $this->product_id_map->setParentProductIdMapByProducts([
            ['product_type' => Type::PRODUCT_TYPE_VARIATION, 'product_id' => 8, 'parent_product_id' => 10],
            ['product_type' => Type::PRODUCT_TYPE_VARIATION, 'product_id' => 7, 'parent_product_id' => 10],
            ['product_type' => Type::PRODUCT_TYPE_SIMPLE, 'product_id' => 10, 'parent_product_id' => 0],
            ['product_type' => Type::PRODUCT_TYPE_SIMPLE, 'product_id' => 11, 'parent_product_id' => 0],
        ]);

        $this->group_repository->expects($this->exactly(5))
            ->method('getParentProductIdMap')
            ->withConsecutive(
                [[1, 2, 3, 4, 5, 6, 7]],
                [range(1000, 1600)],
                [[42]],
                [[43]],
                [[44]]
            )
            ->willReturnOnConsecutiveCalls(
                [7 => 10, 6 => 9],
                [1500 => 10],
                [42 => 10],
                [43 => 10],
                []
            );

        $this->assertEquals(9, $this->product_id_map->getParentProductId(6));
        $this->assertEquals(10, $this->product_id_map->getParentProductId(7));
        $this->assertEquals(10, $this->product_id_map->getParentProductId(8));
        $this->assertEquals(0, $this->product_id_map->getParentProductId(10));
        $this->assertEquals(0, $this->product_id_map->getParentProductId(11));

        $this->assertEquals(0, $this->product_id_map->getParentProductId(1000));
        $this->assertEquals(0, $this->product_id_map->getParentProductId(1001));

        $this->assertEquals(10, $this->product_id_map->getParentProductId(1500));
        $this->assertEquals(0, $this->product_id_map->getParentProductId(1501));

        $this->assertEquals(10, $this->product_id_map->getParentProductId(42));
        $this->assertEquals(10, $this->product_id_map->getParentProductId(43));
        $this->assertEquals(0, $this->product_id_map->getParentProductId(44));
    }

    public function testGetProductChildrenIds()
    {
        $this->group_repository->expects($this->exactly(2))
            ->method('getProductChildrenIds')
            ->withConsecutive([10], [11])
            ->willReturnOnConsecutiveCalls(
                [7, 9, 10],
                []
            );

        $this->assertEquals([7, 9, 10], $this->product_id_map->getProductChildrenIds(10));
        $this->assertNull($this->product_id_map->getProductChildrenIds(11));
    }
}