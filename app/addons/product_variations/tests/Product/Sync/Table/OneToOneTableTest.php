<?php


namespace Tygh\Tests\Unit\Addons\ProductVariations\Product\Sync\Table;


use Tygh\Addons\ProductVariations\Product\Sync\Table\OneToOneTable;
use Tygh\Addons\ProductVariations\Tools\QueryFactory;
use Tygh\Tests\Unit\ATestCase;

class OneToOneTableTest extends ATestCase
{
    /** @var  \PHPUnit\Framework\MockObject\MockObject|\Tygh\Database\Connection */
    protected $db_connection;

    /** @var \Tygh\Addons\ProductVariations\Tools\QueryFactory */
    protected $query_factory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->db_connection = $this->getMockBuilder(\Tygh\Database\Connection::class)
            ->setMethods(['error', 'getRow', 'query', 'getArray', 'hasError', 'getColumn', 'getSingleHash', 'getHash', 'getMultiHash'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->query_factory = new QueryFactory($this->db_connection);

        parent::setUp();
    }

    /**
     * @param array $table_params
     * @param       $source_product_id
     * @param array $destination_product_ids
     * @param array $expected_queries
     *
     * @dataProvider dpSync
     */
    public function testSync(array $table_params, $source_product_id, array $destination_product_ids, array $expected_queries)
    {
        $table = $this->createTableInstance(...$table_params);

        foreach ($expected_queries as $key => $query) {
            list($method, $query, $params, $return_value) = $query;

            $this->db_connection
                ->expects($this->at($key))
                ->method($method)
                ->with($query, ...$params)
                ->willReturn($return_value);
        }

        $table->sync($source_product_id, $destination_product_ids);
    }

    public function dpSync()
    {
        return [
            [
                ['product_popularity', 'product_id'],
                1, //$source_product_id
                [12, 7, 58], //$destination_product_ids
                [
                    ['getHash', 'SELECT * FROM ?:product_popularity WHERE product_id = ?i', ['product_id', 1], [1 => ['product_id' => 1, 'viewed' => 100]]],
                    ['getColumn', 'SELECT product_id FROM ?:product_popularity WHERE product_id IN (?n)', [[12, 7, 58]], [12, 7]],
                    ['query', 'UPDATE ?:product_popularity SET ?u WHERE product_id IN (?n)', [['viewed' => 100], [12, 7]], null],
                    ['query', 'INSERT INTO ?:product_popularity ?m', [[['product_id' => 58, 'viewed' => 100]]], null],
                ]
            ],
            [
                ['product_popularity', 'product_id'],
                1, //$source_product_id
                [12, 7, 58], //$destination_product_ids
                [
                    ['getHash', 'SELECT * FROM ?:product_popularity WHERE product_id = ?i', ['product_id', 1], []],
                    ['getColumn', 'SELECT product_id FROM ?:product_popularity WHERE product_id IN (?n)', [[12, 7, 58]], [12, 7]],
                    ['query', 'DELETE FROM ?:product_popularity WHERE product_id IN (?n)', [[12, 7]], null],
                ]
            ],
        ];
    }

    protected function createTableInstance($table_id, $product_id_field, array $excluded_fields = [])
    {
        return new OneToOneTable($this->query_factory, $table_id, $product_id_field, $excluded_fields);
    }
}