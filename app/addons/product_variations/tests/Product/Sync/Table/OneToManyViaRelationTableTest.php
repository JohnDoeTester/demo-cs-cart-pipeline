<?php


namespace Tygh\Tests\Unit\Addons\ProductVariations\Product\Sync\Table;


use Tygh\Addons\ProductVariations\Product\Sync\Table\OneToManyViaRelationTable;
use Tygh\Addons\ProductVariations\Product\Sync\ProductDataIdentityMapRepository;
use Tygh\Addons\ProductVariations\Tools\QueryFactory;
use Tygh\Tests\Unit\ATestCase;

class OneToManyViaRelationTableTest extends ATestCase
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
                 *       706 |                11 |         0
                 *       707 |                11 |         0
                 *       708 |                12 |         0
                 *       709 |                12 |         0
                 *
                 *
                 *
                 * buy_together_descriptions:
                 * __________________________________________
                 * chain_id  |        lang_code  |      name
                 * ------------------------------------------
                 *       701 |                en | 701ChainEn
                 *       701 |                ru | 701ChainRu
                 *       706 |                en | 701ChainEn
                 *       708 |                en | 701ChainEn
                 *       707 |                en | 702ChainEn
                 *       707 |                ru | 702ChainRu
                 *       709 |                en | 702ChainEn
                 *       709 |                ru | 702ChainRu
                 *
                 * product_variation_data_identity_map:
                 * ____________________________________________________________
                 *       id  |     parent_id  |   product_id  |    table_id
                 * ------------------------------------------------------------
                 *       701 |            701 |             1 |    buy_together
                 *       706 |            701 |            11 |    buy_together
                 *       708 |            701 |            12 |    buy_together
                 *       702 |            702 |             1 |    buy_together
                 *       707 |            702 |            11 |    buy_together
                 *       709 |            702 |            12 |    buy_together
                 *    701_en |         701_en |             1 | buy_together_descriptions
                 *    701_ru |         701_ru |             1 | buy_together_descriptions
                 *    706_en |         701_en |            11 | buy_together_descriptions
                 *    708_en |         701_en |            12 | buy_together_descriptions
                 *    707_en |         702_en |            11 | buy_together_descriptions
                 *    709_en |         702_en |            12 | buy_together_descriptions
                 */

                ['buy_together_descriptions', ['chain_id', 'lang_code'], 'buy_together', ['chain_id' => 'chain_id'], 'product_id'],
                1, //$from_product_id
                [11, 12], //$to_product_ids
                ['chain_id' => 701, 'lang_code' => ['en', 'ru']],
                [
                    ['getArray',
                         'SELECT t1.*, t2.product_id FROM ?:buy_together_descriptions AS t1'
                         . ' INNER JOIN ?:buy_together AS t2 ON t1.chain_id = t2.chain_id'
                         . ' WHERE t1.chain_id = ?i AND t1.lang_code IN (?a) AND t2.product_id = ?i',
                         [701, ['en', 'ru'], 1],
                         [
                             ['chain_id' => 701, 'product_id' => 1, 'lang_code' => 'ru', 'name' => '701ChainRu'],
                             ['chain_id' => 701, 'product_id' => 1, 'lang_code' => 'en', 'name' => '701ChainEn'],
                         ]
                    ],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n) AND parent_id IN (?n)', [['parent_id', 'product_id', 'id'], 'buy_together', [11, 12], [701]], [
                        701 => [1 => 701, 11 => 706, 12 => 708]
                    ]],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n) AND parent_id IN (?a)', [['parent_id', 'id', 'product_id'], 'buy_together_descriptions', [1, 11, 12], ['701_en', '701_ru']], [
                        '701_en' => ['701_en' => 1, '706_en' => 11, '708_en' => 12],
                        '701_ru' => ['701_ru' => 1]
                    ]],
                    ['query', 'UPDATE ?:buy_together_descriptions SET ?u WHERE chain_id IN (?n) AND lang_code IN (?a)', [['name' => '701ChainEn', 'lang_code' => 'en'], [706, 708], ['en']], null],

                    ['query', 'DELETE FROM ?:buy_together_descriptions WHERE (chain_id, lang_code) IN ((?s, ?s), (?s, ?s))', [706, 'ru',  708, 'ru'], null], //avoid duplicate insert
                    ['query', 'INSERT INTO ?:buy_together_descriptions ?m', [[
                        ['chain_id' => 706, 'lang_code' => 'ru', 'name' => '701ChainRu'],
                        ['chain_id' => 708, 'lang_code' => 'ru', 'name' => '701ChainRu'],
                    ]], null],
                    ['query', 'INSERT INTO ?:product_variation_data_identity_map ?m', [[
                        ['id' => '706_ru', 'product_id' => 11, 'parent_id' => '701_ru', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '708_ru', 'product_id' => 12, 'parent_id' => '701_ru', 'table_id' => 'buy_together_descriptions']
                    ]], null]
                ]
            ],
            [
                /**
                 * buy_together:
                 * __________________________________________
                 * chain_id  |        product_id  | modifier
                 * ------------------------------------------
                 *       701 |                 1 |         1
                 *       702 |                 1 |         2
                 *       706 |                11 |         0
                 *       707 |                11 |         0
                 *       708 |                12 |         0
                 *       709 |                12 |         0
                 *
                 *
                 *
                 * buy_together_descriptions:
                 * __________________________________________
                 * chain_id  |        lang_code  |      name
                 * ------------------------------------------
                 *       701 |                en | 701ChainEn
                 *       701 |                ru | 701ChainRu
                 *       702 |                en | 702ChainEn
                 *       702 |                ru | 702ChainRu
                 *       706 |                en | 701ChainEn
                 *       708 |                en | 701ChainEn

                 *
                 * product_variation_data_identity_map:
                 * ____________________________________________________________
                 *       id  |     parent_id  |   product_id  |    table_id
                 * ------------------------------------------------------------
                 *       701 |            701 |             1 |    buy_together
                 *       706 |            701 |            11 |    buy_together
                 *       708 |            701 |            12 |    buy_together
                 *       702 |            702 |             1 |    buy_together
                 *       707 |            702 |            11 |    buy_together
                 *       709 |            702 |            12 |    buy_together
                 *    701_en |         701_en |             1 | buy_together_descriptions
                 *    701_ru |         701_ru |             1 | buy_together_descriptions
                 *    706_en |         701_en |            11 | buy_together_descriptions
                 *    708_en |         701_en |            12 | buy_together_descriptions
                 */

                ['buy_together_descriptions', ['chain_id', 'lang_code'], 'buy_together', ['chain_id' => 'chain_id'], 'product_id'],
                1, //$from_product_id
                [11, 12], //$to_product_ids
                [],
                [
                    ['getArray',
                         'SELECT t1.*, t2.product_id FROM ?:buy_together_descriptions AS t1'
                         . ' INNER JOIN ?:buy_together AS t2 ON t1.chain_id = t2.chain_id'
                         . ' WHERE t2.product_id = ?i', [1],
                         [
                             ['chain_id' => 701, 'product_id' => 1, 'lang_code' => 'en', 'name' => '701ChainEn'],
                             ['chain_id' => 701, 'product_id' => 1, 'lang_code' => 'ru', 'name' => '701ChainRu'],
                             ['chain_id' => 702, 'product_id' => 1, 'lang_code' => 'en', 'name' => '702ChainEn'],
                             ['chain_id' => 702, 'product_id' => 1, 'lang_code' => 'ru', 'name' => '702ChainRu'],
                         ]
                    ],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n) AND parent_id IN (?n)', [['parent_id', 'product_id', 'id'], 'buy_together', [11, 12], [701, 702]], [
                        701 => [11 => 706, 12 => 708],
                        702 => [11 => 707, 12 => 709],
                    ]],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n)', [['parent_id', 'id', 'product_id'], 'buy_together_descriptions', [1, 11, 12]], [
                        '701_en' => ['701_en' => 1, '706_en' => 11, '708_en' => 12],
                        '701_ru' => ['701_ru' => 1],
                    ]],

                    ['query', 'DELETE FROM ?:buy_together_descriptions WHERE chain_id NOT IN (SELECT chain_id FROM ?:buy_together)', [], null],
                    ['query', 'DELETE FROM ?:buy_together_descriptions WHERE chain_id IN (?n) AND (chain_id, lang_code) NOT IN ((?s, ?s), (?s, ?s))', [[706, 708, 707, 709], 706, 'en', 708, 'en'], null],

                    ['query', 'UPDATE ?:buy_together_descriptions SET ?u WHERE chain_id IN (?n) AND lang_code IN (?a)', [['name' => '701ChainEn', 'lang_code' => 'en'], [706, 708], ['en']], null],

                    ['query', 'INSERT INTO ?:buy_together_descriptions ?m', [[
                        ['chain_id' => 706, 'lang_code' => 'ru', 'name' => '701ChainRu'],
                        ['chain_id' => 708, 'lang_code' => 'ru', 'name' => '701ChainRu'],
                        ['chain_id' => 707, 'lang_code' => 'en', 'name' => '702ChainEn'],
                        ['chain_id' => 709, 'lang_code' => 'en', 'name' => '702ChainEn'],
                        ['chain_id' => 707, 'lang_code' => 'ru', 'name' => '702ChainRu'],
                        ['chain_id' => 709, 'lang_code' => 'ru', 'name' => '702ChainRu'],
                    ]], null],

                    ['query', 'INSERT INTO ?:product_variation_data_identity_map ?m', [[
                        ['id' => '706_ru', 'product_id' => 11, 'parent_id' => '701_ru', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '708_ru', 'product_id' => 12, 'parent_id' => '701_ru', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '702_en', 'product_id' => 1, 'parent_id' => '702_en', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '707_en', 'product_id' => 11, 'parent_id' => '702_en', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '709_en', 'product_id' => 12, 'parent_id' => '702_en', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '702_ru', 'product_id' => 1, 'parent_id' => '702_ru', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '707_ru', 'product_id' => 11, 'parent_id' => '702_ru', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '709_ru', 'product_id' => 12, 'parent_id' => '702_ru', 'table_id' => 'buy_together_descriptions']
                    ]], null]
                ]
            ],
            [
                /**
                 * buy_together:
                 * __________________________________________
                 * chain_id  |        product_id  | modifier
                 * ------------------------------------------
                 *       701 |                 1 |         1
                 *       702 |                 1 |         2
                 *       706 |                11 |         0
                 *       707 |                11 |         0
                 *       708 |                12 |         0
                 *       709 |                12 |         0
                 *
                 *
                 *
                 * buy_together_descriptions:
                 * __________________________________________
                 * chain_id  |        lang_code  |      name
                 * ------------------------------------------
                 *       701 |                en | 701ChainEn
                 *       701 |                ru | 701ChainRu
                 *       702 |                en | 702ChainEn
                 *       702 |                ru | 702ChainRu
                 *       706 |                en | 701ChainEn
                 *       708 |                en | 701ChainEn

                 *
                 * product_variation_data_identity_map:
                 * ____________________________________________________________
                 *       id  |     parent_id  |   product_id  |    table_id
                 * ------------------------------------------------------------
                 *       701 |            701 |             1 |    buy_together
                 *       706 |            701 |            11 |    buy_together
                 *       708 |            701 |            12 |    buy_together
                 *       702 |            702 |             1 |    buy_together
                 *       707 |            702 |            11 |    buy_together
                 *       709 |            702 |            12 |    buy_together
                 */

                ['buy_together_descriptions', ['chain_id', 'lang_code'], 'buy_together', ['chain_id' => 'chain_id'], 'product_id'],
                1, //$from_product_id
                [11, 12], //$to_product_ids
                [],
                [
                    ['getArray',
                         'SELECT t1.*, t2.product_id FROM ?:buy_together_descriptions AS t1'
                         . ' INNER JOIN ?:buy_together AS t2 ON t1.chain_id = t2.chain_id'
                         . ' WHERE t2.product_id = ?i', [1],
                         [
                             ['chain_id' => 701, 'product_id' => 1, 'lang_code' => 'en', 'name' => '701ChainEn'],
                             ['chain_id' => 701, 'product_id' => 1, 'lang_code' => 'ru', 'name' => '701ChainRu'],
                             ['chain_id' => 702, 'product_id' => 1, 'lang_code' => 'en', 'name' => '702ChainEn'],
                             ['chain_id' => 702, 'product_id' => 1, 'lang_code' => 'ru', 'name' => '702ChainRu'],
                         ]
                    ],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n) AND parent_id IN (?n)', [['parent_id', 'product_id', 'id'], 'buy_together', [11, 12], [701, 702]], [
                        701 => [11 => 706, 12 => 708],
                        702 => [11 => 707, 12 => 709],
                    ]],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n)', [['parent_id', 'id', 'product_id'], 'buy_together_descriptions', [1, 11, 12]], []],

                    ['query', 'DELETE FROM ?:buy_together_descriptions WHERE chain_id NOT IN (SELECT chain_id FROM ?:buy_together)', [], null],
                    ['query', 'DELETE FROM ?:buy_together_descriptions WHERE chain_id IN (?n)', [[706, 708, 707, 709]], null],

                    ['query', 'INSERT INTO ?:buy_together_descriptions ?m', [[
                        ['chain_id' => 706, 'lang_code' => 'en', 'name' => '701ChainEn'],
                        ['chain_id' => 708, 'lang_code' => 'en', 'name' => '701ChainEn'],
                        ['chain_id' => 706, 'lang_code' => 'ru', 'name' => '701ChainRu'],
                        ['chain_id' => 708, 'lang_code' => 'ru', 'name' => '701ChainRu'],
                        ['chain_id' => 707, 'lang_code' => 'en', 'name' => '702ChainEn'],
                        ['chain_id' => 709, 'lang_code' => 'en', 'name' => '702ChainEn'],
                        ['chain_id' => 707, 'lang_code' => 'ru', 'name' => '702ChainRu'],
                        ['chain_id' => 709, 'lang_code' => 'ru', 'name' => '702ChainRu'],
                    ]], null],

                    ['query', 'INSERT INTO ?:product_variation_data_identity_map ?m', [[
                        ['id' => '701_en', 'product_id' => 1, 'parent_id' => '701_en', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '706_en', 'product_id' => 11, 'parent_id' => '701_en', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '708_en', 'product_id' => 12, 'parent_id' => '701_en', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '701_ru', 'product_id' => 1, 'parent_id' => '701_ru', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '706_ru', 'product_id' => 11, 'parent_id' => '701_ru', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '708_ru', 'product_id' => 12, 'parent_id' => '701_ru', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '702_en', 'product_id' => 1, 'parent_id' => '702_en', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '707_en', 'product_id' => 11, 'parent_id' => '702_en', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '709_en', 'product_id' => 12, 'parent_id' => '702_en', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '702_ru', 'product_id' => 1, 'parent_id' => '702_ru', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '707_ru', 'product_id' => 11, 'parent_id' => '702_ru', 'table_id' => 'buy_together_descriptions'],
                        ['id' => '709_ru', 'product_id' => 12, 'parent_id' => '702_ru', 'table_id' => 'buy_together_descriptions']
                    ]], null]
                ]
            ],
            [
                /**
                 * buy_together:
                 * __________________________________________
                 * chain_id  |        product_id  | modifier
                 * ------------------------------------------
                 *       701 |                 1 |         1
                 *       702 |                 1 |         2
                 *       706 |                11 |         0
                 *       707 |                11 |         0
                 *       708 |                12 |         0
                 *       709 |                12 |         0
                 *
                 *
                 *
                 * buy_together_descriptions:
                 * __________________________________________
                 * chain_id  |        lang_code  |      name
                 * ------------------------------------------
                 *       701 |                en | 701ChainEn
                 *       701 |                ru | 701ChainRu
                 *       702 |                en | 702ChainEn
                 *       702 |                ru | 702ChainRu
                 *       706 |                en | 701ChainEn
                 *       708 |                en | 701ChainEn

                 *
                 * product_variation_data_identity_map:
                 * ____________________________________________________________
                 *       id  |     parent_id  |   product_id  |    table_id
                 * ------------------------------------------------------------
                 *       701 |            701 |             1 |    buy_together
                 *       706 |            701 |            11 |    buy_together
                 *       708 |            701 |            12 |    buy_together
                 *       702 |            702 |             1 |    buy_together
                 *       707 |            702 |            11 |    buy_together
                 *       709 |            702 |            12 |    buy_together
                 *    701_en |         701_en |             1 | buy_together_descriptions
                 *    701_ru |         701_ru |             1 | buy_together_descriptions
                 *    706_en |         701_en |            11 | buy_together_descriptions
                 *    708_en |         701_en |            12 | buy_together_descriptions
                 */

                ['buy_together_descriptions', ['chain_id', 'lang_code'], 'buy_together', ['chain_id' => 'chain_id'], 'product_id'],
                1, //$from_product_id
                [11, 12], //$to_product_ids
                [],
                [
                    ['getArray',
                         'SELECT t1.*, t2.product_id FROM ?:buy_together_descriptions AS t1'
                         . ' INNER JOIN ?:buy_together AS t2 ON t1.chain_id = t2.chain_id'
                         . ' WHERE t2.product_id = ?i', [1],
                         []
                    ],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n)', [['parent_id', 'id', 'product_id'], 'buy_together_descriptions', [1, 11, 12]], [
                        '701_en' => ['701_en' => 1, '706_en' => 11, '708_en' => 12],
                        '701_ru' => ['701_ru' => 1]
                    ]],

                    ['query', 'DELETE FROM ?:buy_together_descriptions WHERE chain_id NOT IN (SELECT chain_id FROM ?:buy_together)', [], null],

                    ['query', 'DELETE FROM ?:buy_together_descriptions WHERE chain_id IN (?n) AND lang_code IN (?a)', [[706, 708], ['en']], null],
                    ['query', 'DELETE FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND id IN (?a)', ['buy_together_descriptions', ['706_en', '708_en']], null],

                    ['getColumn', 'SELECT parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s GROUP BY parent_id HAVING COUNT(id) = 1', ['buy_together_descriptions'], ['701_en', '701_ru']],
                    ['query', 'DELETE FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND parent_id IN (?a)', ['buy_together_descriptions', ['701_en', '701_ru']], null]
                ]
            ],
            [
                /**
                 * discussion:
                 * ____________________________________________________
                 *    thread_id |   object_id |  object_type |   type
                 * ----------------------------------------------------
                 *            1 |           1 |            P |       B
                 *            2 |          11 |            P |       B
                 *            3 |          12 |            P |       B
                 *            4 |           1 |            P |       B
                 *            5 |          11 |            P |       B
                 *            6 |          12 |            P |       B
                 *
                 *
                 * discussion_posts:
                 * _____________________________________________________________
                 *      post_id |   thread_id |              name  |    user_id
                 * -------------------------------------------------------------
                 *            1 |           1 |  Customer Customer |         3
                 *            2 |           1 |  A PC Hardware Fan |         0
                 *            3 |           2 |  Customer Customer |         3
                 *            4 |           4 |              Guest |         0
                 *
                 * product_variation_data_identity_map:
                 * ____________________________________________________________
                 *       id  |     parent_id  |   product_id  |    table_id
                 * ------------------------------------------------------------
                 *         1 |              1 |             1 |    discussion
                 *         2 |              1 |            11 |    discussion
                 *         3 |              1 |            12 |    discussion
                 *         4 |              4 |             1 |    discussion
                 *         5 |              4 |            11 |    discussion
                 *         6 |              4 |            12 |    discussion
                 *         1 |              1 |             1 |    discussion_posts
                 *         3 |              1 |            11 |    discussion_posts
                 *
                 */

                ['discussion_posts', ['post_id'], 'discussion', ['thread_id' => 'thread_id'], 'object_id', [], ['object_type' => 'P']],
                1, //$from_product_id
                [11, 12], //$to_product_ids
                [],
                [
                    ['getArray',
                         'SELECT t1.*, t2.object_id FROM ?:discussion_posts AS t1'
                         . ' INNER JOIN ?:discussion AS t2 ON t1.thread_id = t2.thread_id AND t2.object_type = ?s'
                         . ' WHERE t2.object_id = ?i',
                         ['P', 1],
                         [
                             ['post_id' => 1, 'thread_id' => 1, 'name' => 'Customer Customer', 'user_id' => 3],
                             ['post_id' => 2, 'thread_id' => 1, 'name' => 'A PC Hardware Fan', 'user_id' => 0],
                             ['post_id' => 4, 'thread_id' => 4, 'name' => 'Guest', 'user_id' => 0],
                         ]
                    ],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n) AND parent_id IN (?n)', [['parent_id', 'product_id', 'id'], 'discussion', [11, 12], [1, 4]], [
                        1 => [11 => 2, 12 => 3],
                        4 => [11 => 5, 12 => 6],
                    ]],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n)', [['parent_id', 'id', 'product_id'], 'discussion_posts', [1, 11, 12]], [
                        1 => [1 => 1, 3 => 11]
                    ]],

                    ['query', 'DELETE FROM ?:discussion_posts WHERE thread_id NOT IN (SELECT thread_id FROM ?:discussion)', [], null],
                    ['query', 'DELETE FROM ?:discussion_posts WHERE thread_id IN (?n) AND post_id NOT IN (?n)', [[2, 3, 5, 6], [3]], null],

                    ['query', 'UPDATE ?:discussion_posts SET ?u WHERE post_id IN (?n)', [['name' => 'Customer Customer', 'user_id' => 3], [3]], null],

                    ['query', 'INSERT INTO ?:discussion_posts ?e', [['name' => 'Customer Customer', 'user_id' => 3, 'thread_id' => 3]], 6],
                    ['query', 'INSERT INTO ?:discussion_posts ?e', [['name' => 'A PC Hardware Fan', 'user_id' => 0, 'thread_id' => 2]], 7],
                    ['query', 'INSERT INTO ?:discussion_posts ?e', [['name' => 'A PC Hardware Fan', 'user_id' => 0, 'thread_id' => 3]], 8],
                    ['query', 'INSERT INTO ?:discussion_posts ?e', [['name' => 'Guest', 'user_id' => 0, 'thread_id' => 5]], 9],
                    ['query', 'INSERT INTO ?:discussion_posts ?e', [['name' => 'Guest', 'user_id' => 0, 'thread_id' => 6]], 10],

                    ['query', 'INSERT INTO ?:product_variation_data_identity_map ?m', [[
                        ['id' => 6, 'product_id' => 12, 'parent_id' => 1, 'table_id' => 'discussion_posts'],
                        ['id' => 2, 'product_id' => 1, 'parent_id' => 2, 'table_id' => 'discussion_posts'],
                        ['id' => 7, 'product_id' => 11, 'parent_id' => 2, 'table_id' => 'discussion_posts'],
                        ['id' => 8, 'product_id' => 12, 'parent_id' => 2, 'table_id' => 'discussion_posts'],
                        ['id' => 4, 'product_id' => 1, 'parent_id' => 4, 'table_id' => 'discussion_posts'],
                        ['id' => 9, 'product_id' => 11, 'parent_id' => 4, 'table_id' => 'discussion_posts'],
                        ['id' => 10, 'product_id' => 12, 'parent_id' => 4, 'table_id' => 'discussion_posts'],
                    ]], null]
                ]
            ],
            [
                /**
                 * discussion:
                 * ____________________________________________________
                 *    thread_id |   object_id |  object_type |   type
                 * ----------------------------------------------------
                 *            1 |           1 |            P |       B
                 *            2 |          11 |            P |       B
                 *            3 |          12 |            P |       B
                 *            4 |           1 |            P |       B
                 *            5 |          11 |            P |       B
                 *            6 |          12 |            P |       B
                 *
                 *
                 * discussion_posts:
                 * _____________________________________________________________
                 *      post_id |   thread_id |              name  |    user_id
                 * -------------------------------------------------------------
                 *            1 |           1 |  Customer Customer |         3
                 *            2 |           1 |  A PC Hardware Fan |         0
                 *            3 |           2 |  Customer Customer |         3
                 *            4 |           4 |              Guest |         0
                 *
                 * product_variation_data_identity_map:
                 * ____________________________________________________________
                 *       id  |     parent_id  |   product_id  |    table_id
                 * ------------------------------------------------------------
                 *         1 |              1 |             1 |    discussion
                 *         2 |              1 |            11 |    discussion
                 *         3 |              1 |            12 |    discussion
                 *         4 |              4 |             1 |    discussion
                 *         5 |              4 |            11 |    discussion
                 *         6 |              4 |            12 |    discussion
                 *         1 |              1 |             1 |    discussion_posts
                 *         3 |              1 |            11 |    discussion_posts
                 *
                 */

                ['discussion_posts', ['post_id'], 'discussion', ['thread_id' => 'thread_id'], 'object_id', [], ['object_type' => 'P']],
                1, //$from_product_id
                [11, 12], //$to_product_ids
                ['post_id' => 4],
                [
                    ['getArray',
                         'SELECT t1.*, t2.object_id FROM ?:discussion_posts AS t1'
                         . ' INNER JOIN ?:discussion AS t2 ON t1.thread_id = t2.thread_id AND t2.object_type = ?s'
                         . ' WHERE t1.post_id = ?i AND t2.object_id = ?i',
                         ['P', 4, 1],
                         [
                             ['post_id' => 4, 'thread_id' => 4, 'name' => 'Guest', 'user_id' => 0],
                         ]
                    ],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n) AND parent_id IN (?n)', [['parent_id', 'product_id', 'id'], 'discussion', [11, 12], [4]], [
                        4 => [11 => 5, 12 => 6],
                    ]],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n) AND parent_id IN (?n)', [['parent_id', 'id', 'product_id'], 'discussion_posts', [1, 11, 12], [4]], []],

                    ['query', 'INSERT INTO ?:discussion_posts ?e', [['name' => 'Guest', 'user_id' => 0, 'thread_id' => 5]], 9],
                    ['query', 'INSERT INTO ?:discussion_posts ?e', [['name' => 'Guest', 'user_id' => 0, 'thread_id' => 6]], 10],

                    ['query', 'INSERT INTO ?:product_variation_data_identity_map ?m', [[
                        ['id' => 4, 'product_id' => 1, 'parent_id' => 4, 'table_id' => 'discussion_posts'],
                        ['id' => 9, 'product_id' => 11, 'parent_id' => 4, 'table_id' => 'discussion_posts'],
                        ['id' => 10, 'product_id' => 12, 'parent_id' => 4, 'table_id' => 'discussion_posts'],
                    ]], null]
                ]
            ],
            [
                /**
                 * discussion:
                 * ____________________________________________________
                 *    thread_id |   object_id |  object_type |   type
                 * ----------------------------------------------------
                 *            1 |           1 |            P |       B
                 *            2 |          11 |            P |       B
                 *            3 |          12 |            P |       B
                 *            4 |           1 |            P |       B
                 *            5 |          11 |            P |       B
                 *            6 |          12 |            P |       B
                 *
                 *
                 * discussion_posts:
                 * _____________________________________________________________
                 *      post_id |   thread_id |              name  |    user_id
                 * -------------------------------------------------------------
                 *            1 |           1 |  Customer Customer |         3
                 *            2 |           1 |  A PC Hardware Fan |         0
                 *            3 |           2 |  Customer Customer |         3
                 *            4 |           4 |              Guest |         0
                 *
                 * product_variation_data_identity_map:
                 * ____________________________________________________________
                 *       id  |     parent_id  |   product_id  |    table_id
                 * ------------------------------------------------------------
                 *         1 |              1 |             1 |    discussion
                 *         2 |              1 |            11 |    discussion
                 *
                 */

                ['discussion_posts', ['post_id'], 'discussion', ['thread_id' => 'thread_id'], 'object_id', [], ['object_type' => 'P']],
                1, //$from_product_id
                [11, 12], //$to_product_ids
                [],
                [
                    ['getArray',
                         'SELECT t1.*, t2.object_id FROM ?:discussion_posts AS t1'
                         . ' INNER JOIN ?:discussion AS t2 ON t1.thread_id = t2.thread_id AND t2.object_type = ?s'
                         . ' WHERE t2.object_id = ?i',
                         ['P', 1],
                         [
                             ['post_id' => 1, 'thread_id' => 1, 'name' => 'Customer Customer', 'user_id' => 3],
                             ['post_id' => 2, 'thread_id' => 1, 'name' => 'A PC Hardware Fan', 'user_id' => 0],
                             ['post_id' => 4, 'thread_id' => 4, 'name' => 'Guest', 'user_id' => 0],
                         ]
                    ],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n) AND parent_id IN (?n)', [['parent_id', 'product_id', 'id'], 'discussion', [11, 12], [1, 4]], [1 => [11 => 2]]],
                    ['getMultiHash', 'SELECT id, product_id, parent_id FROM ?:product_variation_data_identity_map WHERE table_id = ?s AND product_id IN (?n)', [['parent_id', 'id', 'product_id'], 'discussion_posts', [1, 11, 12]], []],

                    ['query', 'DELETE FROM ?:discussion_posts WHERE thread_id NOT IN (SELECT thread_id FROM ?:discussion)', [], null],
                    ['query', 'DELETE FROM ?:discussion_posts WHERE thread_id IN (?n)', [[2]], null],

                    ['query', 'INSERT INTO ?:discussion_posts ?e', [['name' => 'Customer Customer', 'user_id' => 3, 'thread_id' => 2]], 6],
                    ['query', 'INSERT INTO ?:discussion_posts ?e', [['name' => 'A PC Hardware Fan', 'user_id' => 0, 'thread_id' => 2]], 7],

                    ['query', 'INSERT INTO ?:product_variation_data_identity_map ?m', [[
                        ['id' => 1, 'product_id' => 1, 'parent_id' => 1, 'table_id' => 'discussion_posts'],
                        ['id' => 6, 'product_id' => 11, 'parent_id' => 1, 'table_id' => 'discussion_posts'],
                        ['id' => 2, 'product_id' => 1, 'parent_id' => 2, 'table_id' => 'discussion_posts'],
                        ['id' => 7, 'product_id' => 11, 'parent_id' => 2, 'table_id' => 'discussion_posts'],
                        ['id' => 4, 'product_id' => 1, 'parent_id' => 4, 'table_id' => 'discussion_posts'],
                    ]], null],
                ]
            ]
        ];
    }

    protected function createTableInstance(
        $table_id,
        $primary_key,
        $related_table_id,
        $relation_link,
        $product_id_field,
        array $excluded_fields = [],
        array $relation_conditions = [],
        array $conditions = []
    ) {
        return new OneToManyViaRelationTable(
            $this->query_factory,
            $this->product_data_map_repository,
            $table_id,
            $primary_key,
            $related_table_id,
            $relation_link,
            $product_id_field,
            $excluded_fields,
            $relation_conditions,
            $conditions
        );
    }
}