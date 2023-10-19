<?php


namespace Tygh\Tests\Unit\Addons\ProductVariations\Product\Sync\Table;


use Tygh\Addons\ProductVariations\Product\Sync\Table\OneToManyViaPrimaryKeyTable;
use Tygh\Addons\ProductVariations\Tools\QueryFactory;
use Tygh\Tests\Unit\ATestCase;

class OneToManyViaPrimaryKeyTableTest extends ATestCase
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
                ['product_descriptions', ['product_id', 'lang_code'], 'product_id'],
                1, //$from_product_id
                [12, 7, 58], //$to_product_ids
                [],
                [
                    ['getArray', 'SELECT * FROM ?:product_descriptions WHERE product_id = ?i', [1], [
                        ['product_id' => 1, 'name' => 'EnProduct1', 'lang_code' => 'en'],
                        ['product_id' => 1, 'name' => 'RuProduct1', 'lang_code' => 'ru'],
                    ]],
                    ['getArray', 'SELECT product_id, lang_code FROM ?:product_descriptions WHERE product_id IN (?n)', [[12, 7, 58]], [
                        ['product_id' => 12, 'lang_code' => 'en'], ['product_id' => 12, 'lang_code' => 'ru'],
                        ['product_id' => 7, 'lang_code' => 'en'], ['product_id' => 7, 'lang_code' => 'ru'],
                        ['product_id' => 58, 'lang_code' => 'en'], ['product_id' => 58, 'lang_code' => 'ru'],
                    ]],
                    ['query', 'UPDATE ?:product_descriptions SET ?u WHERE product_id IN (?n) AND lang_code IN (?a)', [['name' => 'EnProduct1', 'lang_code' => 'en'], [12, 7, 58], ['en']], null],
                    ['query', 'UPDATE ?:product_descriptions SET ?u WHERE product_id IN (?n) AND lang_code IN (?a)', [['name' => 'RuProduct1', 'lang_code' => 'ru'], [12, 7, 58], ['ru']], null],
                ],
            ],
            [
                ['product_descriptions', ['product_id', 'lang_code'], 'product_id', [], ['description']],
                1, //$from_product_id
                [12, 7, 58], //$to_product_ids
                ['lang_code' => 'ru'],
                [
                    ['getArray', 'SELECT * FROM ?:product_descriptions WHERE lang_code = ?s AND product_id = ?i', ['ru', 1], [
                        ['product_id' => 1, 'name' => 'RuProduct1', 'lang_code' => 'ru', 'description' => 'RuProductDescription1'],
                    ]],
                    ['getArray', 'SELECT product_id, lang_code FROM ?:product_descriptions WHERE lang_code = ?s AND product_id IN (?n)', ['ru', [12, 7, 58]], [
                        ['product_id' => 12, 'lang_code' => 'ru'],
                        ['product_id' => 7, 'lang_code' => 'ru'],
                        ['product_id' => 58, 'lang_code' => 'ru'],
                    ]],
                    ['query', 'UPDATE ?:product_descriptions SET ?u WHERE product_id IN (?n) AND lang_code IN (?a)', [['name' => 'RuProduct1', 'lang_code' => 'ru'], [12, 7, 58], ['ru']], null],
                ],
            ],
            [
                ['product_descriptions', ['product_id', 'lang_code'], 'product_id'],
                1, //$from_product_id
                [12, 7, 58], //$to_product_ids
                ['lang_code' => 'ru'],
                [
                    ['getArray', 'SELECT * FROM ?:product_descriptions WHERE lang_code = ?s AND product_id = ?i', ['ru', 1], []],
                    ['getArray', 'SELECT product_id, lang_code FROM ?:product_descriptions WHERE lang_code = ?s AND product_id IN (?n)', ['ru', [12, 7, 58]], [
                        ['product_id' => 12, 'lang_code' => 'ru'],
                        ['product_id' => 7, 'lang_code' => 'ru'],
                        ['product_id' => 58, 'lang_code' => 'ru'],
                    ]],
                    ['query', 'DELETE FROM ?:product_descriptions WHERE product_id IN (?n) AND lang_code IN (?a)', [[12, 7, 58], ['ru']], null],
                ],
            ],
            [
                ['product_descriptions', ['product_id', 'lang_code'], 'product_id'],
                1, //$from_product_id
                [12, 7, 58], //$to_product_ids
                [],
                [
                    ['getArray', 'SELECT * FROM ?:product_descriptions WHERE product_id = ?i', [1], []],
                    ['getArray', 'SELECT product_id, lang_code FROM ?:product_descriptions WHERE product_id IN (?n)', [[12, 7, 58]], [
                        ['product_id' => 12, 'lang_code' => 'ru'],
                        ['product_id' => 7, 'lang_code' => 'ru'],
                        ['product_id' => 58, 'lang_code' => 'ru'],
                        ['product_id' => 12, 'lang_code' => 'en'],
                        ['product_id' => 7, 'lang_code' => 'en'],
                        ['product_id' => 58, 'lang_code' => 'en'],
                    ]],
                    ['query', 'DELETE FROM ?:product_descriptions WHERE product_id IN (?n) AND lang_code IN (?a)', [[12, 7, 58], ['ru']], null],
                    ['query', 'DELETE FROM ?:product_descriptions WHERE product_id IN (?n) AND lang_code IN (?a)', [[12, 7, 58], ['en']], null],
                ],
            ],
            [
                ['product_descriptions', ['product_id', 'lang_code'], 'product_id'],
                1, //$from_product_id
                [12, 7, 58], //$to_product_ids
                ['lang_code' => 'ru'],
                [
                    ['getArray', 'SELECT * FROM ?:product_descriptions WHERE lang_code = ?s AND product_id = ?i', ['ru', 1], [
                        ['product_id' => 1, 'name' => 'RuProduct1', 'lang_code' => 'ru'],
                    ]],
                    ['getArray', 'SELECT product_id, lang_code FROM ?:product_descriptions WHERE lang_code = ?s AND product_id IN (?n)', ['ru', [12, 7, 58]], [
                        ['product_id' => 12, 'lang_code' => 'ru'],
                    ]],
                    ['query', 'UPDATE ?:product_descriptions SET ?u WHERE product_id IN (?n) AND lang_code IN (?a)', [['name' => 'RuProduct1', 'lang_code' => 'ru'], [12], ['ru']],  null],
                    ['query', 'INSERT INTO ?:product_descriptions ?m', [[
                        ['product_id' => 7, 'name' => 'RuProduct1', 'lang_code' => 'ru'],
                        ['product_id' => 58, 'name' => 'RuProduct1', 'lang_code' => 'ru']
                    ]], null]
                ],
            ],
            [
                ['product_descriptions', ['product_id', 'lang_code'], 'product_id'],
                1, //$from_product_id
                [12, 7, 58], //$to_product_ids
                [],
                [
                    ['getArray', 'SELECT * FROM ?:product_descriptions WHERE product_id = ?i', [1], [
                        ['product_id' => 1, 'name' => 'EnProduct1', 'lang_code' => 'en'],
                        ['product_id' => 1, 'name' => 'RuProduct1', 'lang_code' => 'ru'],
                    ]],
                    ['getArray', 'SELECT product_id, lang_code FROM ?:product_descriptions WHERE product_id IN (?n)', [[12, 7, 58]], [
                        ['product_id' => 12, 'lang_code' => 'en'], ['product_id' => 12, 'lang_code' => 'ru'], ['product_id' => 12, 'lang_code' => 'de'],
                        ['product_id' => 7, 'lang_code' => 'en'], ['product_id' => 7, 'lang_code' => 'ru'], ['product_id' => 7, 'lang_code' => 'es'],
                    ]],
                    ['query', 'UPDATE ?:product_descriptions SET ?u WHERE product_id IN (?n) AND lang_code IN (?a)', [['name' => 'EnProduct1', 'lang_code' => 'en'], [12, 7], ['en']], null],
                    ['query', 'UPDATE ?:product_descriptions SET ?u WHERE product_id IN (?n) AND lang_code IN (?a)', [['name' => 'RuProduct1', 'lang_code' => 'ru'], [12, 7], ['ru']], null],
                    ['query', 'INSERT INTO ?:product_descriptions ?m', [[
                        ['product_id' => 58, 'name' => 'EnProduct1', 'lang_code' => 'en'],
                        ['product_id' => 58, 'name' => 'RuProduct1', 'lang_code' => 'ru']
                    ]], null],
                    ['query', 'DELETE FROM ?:product_descriptions WHERE product_id IN (?n) AND lang_code IN (?a)', [[12], ['de']], null],
                    ['query', 'DELETE FROM ?:product_descriptions WHERE product_id IN (?n) AND lang_code IN (?a)', [[7], ['es']], null]
                ],
            ],
            [
                ['images_links', ['object_id', 'image_id', 'detailed_id'], 'object_id', ['object_type' => 'product'], ['pair_id']],
                1, //$from_product_id
                [12, 7, 58], //$to_product_ids
                ['image_id' => 0, 'detailed_id' => [1, 2]],
                [
                    ['getArray', 'SELECT * FROM ?:images_links WHERE object_type = ?s AND image_id = ?i AND detailed_id IN (?n) AND object_id = ?i', ['product', 0, [1, 2], 1], [
                        ['pair_id' => 1, 'object_id' => 1, 'image_id' => 0, 'detailed_id' => 1, 'type' => 'M', 'position' => 0],
                        ['pair_id' => 2, 'object_id' => 1, 'image_id' => 0, 'detailed_id' => 2, 'type' => 'M', 'position' => 10],
                    ]],
                    ['getArray',
                     'SELECT object_id, image_id, detailed_id FROM ?:images_links WHERE object_type = ?s AND image_id = ?i AND detailed_id IN (?n) AND object_id IN (?n)',
                     ['product', 0, [1, 2], [12, 7, 58]],
                     [
                         ['object_id' => 12, 'image_id' => 0, 'detailed_id' => 1, 'type' => 'M', 'position' => 40],
                         ['object_id' => 12, 'image_id' => 0, 'detailed_id' => 2, 'type' => 'M', 'position' => 40],
                         ['object_id' => 12, 'image_id' => 1, 'detailed_id' => 2, 'type' => 'M', 'position' => 40],
                         ['object_id' => 7, 'image_id' => 0, 'detailed_id' => 1, 'type' => 'M', 'position' => 40],
                     ]
                    ],
                    ['query', 'UPDATE ?:images_links SET ?u WHERE object_type = ?s AND object_id IN (?n) AND image_id IN (?n) AND detailed_id IN (?n)',
                     [['image_id' => 0, 'detailed_id' => 1, 'type' => 'M', 'position' => 0], 'product', [12, 7], [0], [1]], null
                    ],
                    ['query', 'UPDATE ?:images_links SET ?u WHERE object_type = ?s AND object_id IN (?n) AND image_id IN (?n) AND detailed_id IN (?n)',
                     [['type' => 'M', 'position' => 10, 'image_id' => 0, 'detailed_id' => 2], 'product', [12], [0], [2]], null
                    ],
                    ['query', 'INSERT INTO ?:images_links ?m', [[
                        ['object_id' => 58, 'image_id' => 0, 'detailed_id' => 1, 'type' => 'M', 'position' => 0],
                        ['object_id' => 7, 'image_id' => 0, 'detailed_id' => 2, 'type' => 'M', 'position' => 10],
                        ['object_id' => 58, 'image_id' => 0, 'detailed_id' => 2, 'type' => 'M', 'position' => 10],
                    ]], null],
                    ['query', 'DELETE FROM ?:images_links WHERE object_type = ?s AND object_id IN (?n) AND image_id IN (?n) AND detailed_id IN (?n)', ['product', [12], [1], [2]], null]
                ]
            ],
        ];
    }

    protected function createTableInstance($table_id, $primary_key, $product_id_field, array $conditions = [], array $excluded_fields = [])
    {
        return new OneToManyViaPrimaryKeyTable($this->query_factory, $table_id, $primary_key, $product_id_field, $excluded_fields, ['conditions' => $conditions]);
    }
}