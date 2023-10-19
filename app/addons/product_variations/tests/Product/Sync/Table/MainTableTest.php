<?php


namespace Tygh\Tests\Unit\Addons\ProductVariations\Product\Sync\Table;


use Tygh\Addons\ProductVariations\Product\Sync\Table\MainTable;
use Tygh\Addons\ProductVariations\Tools\QueryFactory;
use Tygh\Tests\Unit\ATestCase;

class MainTableTest extends ATestCase
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
                ['products', 'product_id', ['product_type']],
                1, //$source_product_id
                [12, 7, 58], //$destination_product_ids
                [
                    ['getArray', 'SELECT * FROM ?:products WHERE product_id = ?i', [1], [['product_id' => 1, 'price' => 100, 'product_type' => 'P']]],
                    ['query', 'UPDATE ?:products SET ?u WHERE product_id IN (?n)', [['price' => 100], [12, 7, 58]], null],
                ]
            ],
        ];
    }

    protected function createTableInstance($table_id, $product_id_field, array $excluded_fields = [])
    {
        return new MainTable($this->query_factory, $table_id, $product_id_field, $excluded_fields);
    }
}