<?php


namespace Tygh\Tests\Unit\Addons\ProductVariations\Product\Sync\Table;


use Tygh\Addons\ProductVariations\Product\Sync\Table\OneToManyViaFieldTable;
use Tygh\Addons\ProductVariations\Product\Sync\ProductDataIdentityMapRepository;
use Tygh\Addons\ProductVariations\Tools\QueryFactory;
use Tygh\Tests\Unit\ATestCase;

class OneToManyViaFieldTableTest extends ATestCase
{
    /** @var  \PHPUnit\Framework\MockObject\MockObject|\Tygh\Database\Connection */
    protected $db_connection;

    /** @var \Tygh\Addons\ProductVariations\Product\Sync\ProductDataIdentityMapRepository */
    protected $product_data_map_repository;

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
        $this->product_data_map_repository = new ProductDataIdentityMapRepository($this->query_factory);

        parent::setUp();
    }

    /**
     * @param array $table_params
     * @param       $source_product_id
     * @param array $destination_product_ids
     * @param array $conditions
     * @param array $expected_queries
     *
     * @dataProvider dpSync
     */
    public function testSync(array $table_params, $source_product_id, array $destination_product_ids, array $conditions, array $expected_queries)
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

        $table->sync($source_product_id, $destination_product_ids, $conditions);
    }

    public function dpSync()
    {
        return [
            [
                /**
                 * buy_together:
                 * __________________________________________
                 * chain_id  |        product_id  | modifier
                 * ------------------------------------------
                 *       701 |                 1 |         1
                 *
                 *
                 * product_variation_data_identity_map:
                 * ____________________________________________________________
                 *       id  |     parent_id  |   product_id  |    table_id
                 * ------------------------------------------------------------
                 *
                 */
                ['buy_together', ['chain_id'], 'product_id'],
                1, //$from_product_id
                [11, 12], //$to_product_ids
                [],
                [
                    ['getArray', 'SELECT * FROM ?:buy_together WHERE product_id = ?i', [1], [
                        ['chain_id' => 701, 'product_id' => 1, 'modifier' => 1],
                    ]],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n)', [['parent_id', 'id', 'product_id'], 'buy_together', [1, 11, 12]], []],

                    ['query', 'DELETE FROM ?:buy_together WHERE product_id IN (?n)', [[11, 12]], null],

                    ['query', 'INSERT INTO ?:buy_together ?e', [['product_id' => 11, 'modifier' => 1]], 711],
                    ['query', 'INSERT INTO ?:buy_together ?e', [['product_id' => 12, 'modifier' => 1]], 712],
                    ['query', 'INSERT INTO ?:product_variation_data_identity_map ?m', [[
                        ['product_id' => 1, 'id' => 701, 'parent_id' => 701, 'table_id' => 'buy_together'],
                        ['product_id' => 11, 'id' => 711, 'parent_id' => 701, 'table_id' => 'buy_together'],
                        ['product_id' => 12, 'id' => 712, 'parent_id' => 701, 'table_id' => 'buy_together']
                    ]], null],
                ]
            ],
            [
                /**
                 * buy_together:
                 * __________________________________________
                 * chain_id  |        product_id  | modifier
                 * ------------------------------------------
                 *       701 |                 1 |         1
                 *       706 |                11 |         0
                 *       707 |                11 |         0
                 *       709 |                12 |         0
                 *
                 *
                 * product_variation_data_identity_map:
                 * ____________________________________________________________
                 *       id  |     parent_id  |   product_id  |    table_id
                 * ------------------------------------------------------------
                 *       701 |            701 |             1 |    buy_together
                 *       706 |            701 |            11 |    buy_together
                 *       702 |            702 |             1 |    buy_together
                 *       707 |            702 |            11 |    buy_together
                 *       709 |            702 |            12 |    buy_together
                 *
                 *
                 * product_id: 1, to_product_ids: 11, 12, chain_ids: 701, 702
                 */
                ['buy_together', ['chain_id'], 'product_id'],
                1, //$from_product_id
                [11, 12], //$to_product_ids
                ['chain_id' => [701, 702]],
                [
                    ['getArray', 'SELECT * FROM ?:buy_together WHERE chain_id IN (?n) AND product_id = ?i', [[701, 702], 1], [
                        ['chain_id' => 701, 'product_id' => 1, 'modifier' => 1],
                    ]],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n) AND parent_id IN (?n)', [['parent_id', 'id', 'product_id'], 'buy_together', [1, 11, 12], [701, 702]], [
                        701 => [701 => 1, 706 => 11],
                        702 => [702 => 1, 707 => 11, 709 => 12],
                    ]],

                    ['query', 'UPDATE ?:buy_together SET ?u WHERE chain_id IN (?n)', [['modifier' => 1], [706]], null],

                    ['query', 'INSERT INTO ?:buy_together ?e', [['product_id' => 12, 'modifier' => 1]], 712],
                    ['query', 'INSERT INTO ?:product_variation_data_identity_map ?m', [[['product_id' => 12, 'id' => 712, 'parent_id' => 701, 'table_id' => 'buy_together']]], null],

                    ['query', 'DELETE FROM ?:buy_together WHERE chain_id IN (?n)', [[707, 709]], null],
                    ['query', 'DELETE FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND id IN (?n)', ['buy_together', [707, 709]], null],

                    ['getColumn', 'SELECT parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s GROUP BY parent_id HAVING COUNT(id) = 1', ['buy_together'], [702]],
                    ['query', 'DELETE FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND parent_id IN (?n)', ['buy_together', [702]], null]
                ]
            ],
            [
                /**
                 * buy_together:
                 * __________________________________________
                 * chain_id  |        product_id  | modifier
                 * ------------------------------------------
                 *       701 |                 1 |         1
                 *       702 |                11 |         1
                 *
                 *
                 * product_variation_data_identity_map:
                 * ____________________________________________________________
                 *       id  |     parent_id  |   product_id  |    table_id
                 * ------------------------------------------------------------
                 *
                 */
                ['buy_together', ['chain_id'], 'product_id'],
                1, //$from_product_id
                [11, 12], //$to_product_ids
                [],
                [
                    ['getArray', 'SELECT * FROM ?:buy_together WHERE product_id = ?i', [1], [
                        ['chain_id' => 701, 'product_id' => 1, 'modifier' => 1],
                    ]],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n)', [['parent_id', 'id', 'product_id'], 'buy_together', [1, 11, 12]], []],

                    ['query', 'DELETE FROM ?:buy_together WHERE product_id IN (?n)', [[11, 12]], null],

                    ['query', 'INSERT INTO ?:buy_together ?e', [['product_id' => 11, 'modifier' => 1]], 711],
                    ['query', 'INSERT INTO ?:buy_together ?e', [['product_id' => 12, 'modifier' => 1]], 712],
                    ['query', 'INSERT INTO ?:product_variation_data_identity_map ?m', [[
                        ['product_id' => 1, 'id' => 701, 'parent_id' => 701, 'table_id' => 'buy_together'],
                        ['product_id' => 11, 'id' => 711, 'parent_id' => 701, 'table_id' => 'buy_together'],
                        ['product_id' => 12, 'id' => 712, 'parent_id' => 701, 'table_id' => 'buy_together']
                    ]], null],
                ]
            ],
            [
                /**
                 * buy_together:
                 * __________________________________________
                 * chain_id  |        product_id  | modifier
                 * ------------------------------------------
                 *       701 |                 1 |         1
                 *       702 |                11 |         1
                 *       703 |                12 |         1
                 *
                 *
                 * product_variation_data_identity_map:
                 * ____________________________________________________________
                 *       id  |     parent_id  |   product_id  |    table_id
                 * ------------------------------------------------------------
                 *       701 |            701 |             1 |    buy_together
                 *       702 |            701 |            11 |    buy_together
                 *
                 */
                ['buy_together', ['chain_id'], 'product_id'],
                1, //$from_product_id
                [11, 12], //$to_product_ids
                [],
                [
                    ['getArray', 'SELECT * FROM ?:buy_together WHERE product_id = ?i', [1], [
                        ['chain_id' => 701, 'product_id' => 1, 'modifier' => 1],
                    ]],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n)', [['parent_id', 'id', 'product_id'], 'buy_together', [1, 11, 12]], [
                        701 => [701 => 1, 702 => 11]
                    ]],

                    ['query', 'DELETE FROM ?:buy_together WHERE product_id IN (?n) AND chain_id NOT IN (?n)', [[11, 12], [702]], null],

                    ['query', 'UPDATE ?:buy_together SET ?u WHERE chain_id IN (?n)', [['modifier' => 1], [702]], null],

                    ['query', 'INSERT INTO ?:buy_together ?e', [['product_id' => 12, 'modifier' => 1]], 712],
                    ['query', 'INSERT INTO ?:product_variation_data_identity_map ?m', [[
                       ['product_id' => 12, 'id' => 712, 'parent_id' => 701, 'table_id' => 'buy_together']
                    ]], null],
                ]
            ],
        ];
    }

    protected function createTableInstance($table_id, $primary_key, $product_id_field, array $conditions = [], array $excluded_fields = [])
    {
        return new OneToManyViaFieldTable($this->query_factory, $this->product_data_map_repository, $table_id, $primary_key, $product_id_field, $excluded_fields, ['conditions' => $conditions]);
    }
}