<?php

namespace Tygh\Tests\Unit\Addons\ProductVariations\Tools;

use Tygh\Addons\ProductVariations\Tools\Query;
use Tygh\Addons\ProductVariations\Tools\QueryFactory;
use Tygh\Tests\Unit\ATestCase;

class QueryTest extends ATestCase
{
    /** @var  \PHPUnit\Framework\MockObject\MockObject|\Tygh\Database\Connection */
    protected $db_connection;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->db_connection = $this->getMockBuilder(\Tygh\Database\Connection::class)
            ->setMethods(['error', 'getRow', 'query', 'getArray', 'hasError', 'getColumn', 'getSingleHash', 'getHash', 'getMultiHash'])
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    public function testSelect()
    {
        $this->db_connection->expects($this->once())->method('getArray')->with(
            'SELECT p.product_id, p.product_code, p.parent_product_id, pd.product FROM ?:products AS p'
            . ' INNER JOIN ?:products_categories AS pc ON p.product_id = pc.product_id AND pc.link_type = ?s'
            . ' LEFT JOIN ?:product_descriptions AS pd ON pd.product_id = p.product_id AND pd.lang_code = ?s'
            . ' WHERE (p.parent_product_id > 0 OR p.parent_product_id IN (?n)) AND p.product_id IN (?n)'
            . ' AND p.product_code IN (?a) AND (p.product_type, p.status) IN ((?s, ?s), (?s, ?s))'
            . ' AND p.company_id NOT IN (?n) AND pc.category_id = ?i'
            . ' GROUP BY p.product_id HAVING COUNT(p.product_id) > 1 ORDER BY p.product_id ASC, pd.product ASC'
            . ' LIMIT 10 OFFSET 100',
            'M', 'en', [1, 2], [10, 20], ['code1', 'code2'], 'P', 'A', 'V', 'H', [6, 7], 100
        );

        $query = new Query($this->db_connection, 'products', 'p');

        $query->addCondition('(p.parent_product_id > 0 OR p.parent_product_id IN (?n))', [[1, 2]]);
        $query->addConditions(['product_id' => [10, 20], 'product_code' => ['code1', 'code2']]);

        $query->addField('p.product_id');
        $query->addField('p.product_code');
        $query->addField('p.parent_product_id');

        $query->addInCondition(['product_type', 'status'], [
            ['product_type' => 'P', 'status' => 'A'],
            ['product_type' => 'V', 'status' => 'H'],
        ]);

        $query->addNotInCondition('company_id', [6, 7], 'p');

        $query->addInnerJoin('pc', 'products_categories', ['product_id' => 'product_id'], ['link_type' => 'M']);
        $query->addLeftJoin('pd', 'product_descriptions', ['pd.product_id' => 'p.product_id'], ['lang_code' => 'en']);

        $query->addField('pd.product');

        $query->addConditions([
            'category_id' => 100
        ], 'pc');

        $query->setOrderBy(['p.product_id ASC', 'pd.product ASC']);
        $query->setGroupBy(['p.product_id']);
        $query->setHaving('COUNT(p.product_id) > 1');
        $query->setLimit(10);
        $query->setOffset(100);

        $query->select();
    }

    public function testUpdate()
    {
        $this->db_connection->expects($this->at(0))->method('query')->with(
            'UPDATE ?:products AS p SET ?u WHERE p.product_id IN (?n)',
            ['product_code' => 'code1'], [1, 2, 3]
        );

        $this->db_connection->expects($this->at(1))->method('query')->with(
            'UPDATE ?:products SET ?u WHERE product_id IN (?n)',
            ['product_code' => 'code1'], [1, 2, 3]
        );

        $query = new Query($this->db_connection, 'products', 'p');

        $query->addConditions(['product_id' => [1, 2, 3]]);
        $query->update(['product_code' => 'code1']);

        $query = new Query($this->db_connection, 'products');

        $query->addConditions(['product_id' => [1, 2, 3]]);
        $query->update(['product_code' => 'code1']);
    }

    public function testDelete()
    {
        $this->db_connection->expects($this->at(0))->method('query')->with(
            'DELETE FROM ?:products AS p WHERE p.product_id IN (?n)',
            [1, 2, 3]
        );

        $this->db_connection->expects($this->at(1))->method('query')->with(
            'DELETE FROM ?:products WHERE product_id IN (?n)',
            [1, 2, 3]
        );

        $query = new Query($this->db_connection, 'products', 'p');

        $query->addConditions(['product_id' => [1, 2, 3]]);
        $query->delete();

        $query = new Query($this->db_connection, 'products');

        $query->addConditions(['product_id' => [1, 2, 3]]);
        $query->delete();
    }

    public function testInsert()
    {
        $this->db_connection->expects($this->once())->method('query')->with(
            'INSERT INTO ?:products ?e',
            ['product_id' => 1, 'product_code' => 'code1']
        );
        $query = new Query($this->db_connection, 'products', 'p');

        $query->insert(['product_id' => 1, 'product_code' => 'code1']);
    }

    public function testMultipleInsert()
    {
        $this->db_connection->expects($this->once())->method('query')->with(
            'INSERT INTO ?:products ?m',
            [
                ['product_id' => 1, 'product_code' => 'code1'],
                ['product_id' => 2, 'product_code' => 'code2']
            ]
        );
        $query = new Query($this->db_connection, 'products', 'p');

        $query->multipleInsert([
            ['product_id' => 1, 'product_code' => 'code1'],
            ['product_id' => 2, 'product_code' => 'code2']
        ]);
    }
}