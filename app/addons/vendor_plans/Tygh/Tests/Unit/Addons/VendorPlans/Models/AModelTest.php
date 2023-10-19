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

namespace Tygh\Tests\Unit\Addons\VendorPlans\Models;

use Tygh\Tests\Unit\ATestCase;

class AModelTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    protected $app;
    
    protected $driver;
    protected $connection;
    
    protected $model;

    protected function setUp(): void
    {
        define('AREA', 'A');
        define('CART_LANGUAGE', 'en');

        $this->requireCore('functions/fn.database.php');

        $this->requireMockFunction('fn_set_hook');

        $this->app = \Tygh\Tygh::createApplication();

        // // Session
        // // $this->app['session'] = new \Tygh\Web\Session($this->app);

        /**
         * Database driver
         */
        $driver = $this->getMockBuilder('\Tygh\Backend\Database\Pdo')
            ->addMethods(array('escape', 'query', 'insertId', 'fetch', 'fetchRow', 'freeResult'))
            ->getMock();
        $driver->method('escape')->will($this->returnCallback('addslashes'));
        $this->driver = $this->app['db.driver'] = $driver;

        /**
         * Database connection
         */
        $connection = $this->getMockBuilder('\Tygh\Database\Connection')
            ->setMethods(array('error', 'getTableFields', 'hasError'))
            ->setConstructorArgs(array($driver))
            ->getMock();
        $this->connection = $this->app['db'] = $connection;
        
        /**
         * Abstract model instance
         */
        $this->model = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField', 'getDescriptionTableName', 'isNewRecord', 'getLangCodes'))
            ->getMock();

        // rewrite method getTableName()
        $this->model->expects($this->any())->method('getTableName')->willReturn('fake_table');

        // rewrite method getPrimaryField()
        $this->model->expects($this->any())->method('getPrimaryField')->willReturn('fake_id');
        
        // rewrite method getLangCodes()
        $this->model->expects($this->any())->method('getLangCodes')->willReturn(array('en', 'ru'));
    }

    public function testMagicMethods()
    {
        $model = $this->model;
        $model->__construct(array(), array('param1' => 'val1', 'param2' => 'val2'));
        $this->assertSame('val1', $model->param1);
        $this->assertSame('val2', $model->param2);
    }

    public function testArrayAccess()
    {
        $model = $this->model;
        $model->__construct(array(), array('param1' => 'val1', 'param2' => 'val2'));
        $this->assertSame('val1', $model['param1']);
        $this->assertSame('val2', $model['param2']);
    }

    public function testIteratorAggregate()
    {
        $model = $this->model;
        $model->__construct(array(), array('param1' => 'val1', 'param2' => 'val2'));
        
        $result = array();
        $i = 0;
        foreach ($model as $key => $value) {
            $result[$i][$key] = $value;
            $i ++;
        }
        $this->assertEquals(array(
            0 => array(
                'param1' => 'val1',
            ),
            1 => array(
                'param2' => 'val2',
            ),
        ), $result);
    }

    public function testAttributes()
    {
        $model = $this->model;

        $array = array('param1' => 'val1', 'param2' => 'val2', 'param3' => array('a' => 1, 'b' => 2));
        $model->attributes($array);
        $this->assertEquals($array, $model->attributes());
        $this->assertEquals($array + array('new_param' => 'new_value'), $model->attributes(array('new_param' => 'new_value')));
    }

    public function testJoins()
    {
        $model = $this->model;

        $this->assertEquals(array(), $model->getJoins(array()));

        $model->expects($this->any())->method('getDescriptionTableName')->willReturn('fake_descriptions');
        
        $this->assertEquals(array(
            " LEFT JOIN fake_descriptions ON fake_descriptions.fake_id = fake_table.fake_id AND fake_descriptions.lang_code = 'en'"
        ), $model->getJoins(array()));
    }

    public function testInsert()
    {
        $model = $this->model;

        $model->expects($this->any())->method('isNewRecord')->willReturn(true);
        
        $this->connection->expects($this->any())->method('getTableFields')->willReturn(array('fake_id', 'param1', 'other_id'));
        $this->driver->expects($this->any())->method('query')->with($this->logicalOr(
            "INSERT INTO fake_table (`param1`, `other_id`) VALUES ('val1', 5)",
            "SELECT fake_table.* FROM fake_table WHERE fake_table.fake_id IN('4') ORDER BY fake_table.fake_id asc LIMIT 0, 1"
        ))->willReturn(4);

        $model->attributes(array('param1' => 'val1', 'param2' => 'val2', 'other_id' => 5));
        $model->save();
    }

    public function testInsertWithId()
    {
        $model = $this->model;

        $model->expects($this->any())->method('isNewRecord')->willReturn(true);
        
        $this->connection->expects($this->any())->method('getTableFields')->willReturn(array('fake_id', 'param1', 'other_id'));
        $this->driver->expects($this->any())->method('query')->with($this->logicalOr(
            "INSERT INTO fake_table (`param1`, `fake_id`, `other_id`) VALUES ('val1', 4, 5)",
            "SELECT fake_table.* FROM fake_table WHERE fake_table.fake_id IN('4') ORDER BY fake_table.fake_id asc LIMIT 0, 1"
        ))->willReturn(4);

        $model->attributes(array('param1' => 'val1', 'param2' => 'val2', 'fake_id' => 4, 'other_id' => 5));
        $model->save();
    }

    public function testInsertWithDescriptions()
    {
        $model = $this->model;

        $model->expects($this->any())->method('isNewRecord')->willReturn(true);
        $model->expects($this->any())->method('getDescriptionTableName')->willReturn('fake_descriptions');
        
        $this->connection->expects($this->any())->method('getTableFields')->willReturn(array('fake_id', 'param1', 'other_id', 'lang_code'));

        $this->driver->expects($this->any())->method('query')->with($this->logicalOr(
            "INSERT INTO fake_table (`param1`, `other_id`) VALUES ('val1', 5)",
            "INSERT INTO fake_descriptions (`param1`, `other_id`, `fake_id`, `lang_code`) VALUES ('val1', 5, 4, 'en')",
            "INSERT INTO fake_descriptions (`param1`, `other_id`, `fake_id`, `lang_code`) VALUES ('val1', 5, 4, 'ru')",
            "SELECT fake_table.* FROM fake_table  LEFT JOIN fake_descriptions ON fake_descriptions.fake_id = fake_table.fake_id AND fake_descriptions.lang_code = 'en' WHERE fake_table.fake_id IN('4') ORDER BY fake_table.fake_id asc LIMIT 0, 1"
        ))->willReturn(4);

        $model->attributes(array('param1' => 'val1', 'param2' => 'val2', 'other_id' => 5));
        $model->save();
    }

    public function testUpdate()
    {
        $model = $this->model;

        $this->connection->expects($this->any())->method('getTableFields')->willReturn(array('fake_id', 'param1', 'other_id'));
        $this->driver->expects($this->exactly(2))->method('query')->with($this->logicalOr(
            "UPDATE fake_table SET `param1` = 'val1', `other_id` = 5 WHERE fake_id = '6'",
            "SELECT fake_table.* FROM fake_table WHERE fake_table.fake_id IN('6') ORDER BY fake_table.fake_id asc LIMIT 0, 1"
        ));

        // $model->id = 6;
        $reflection = new \ReflectionClass('\Tygh\Models\Components\AModel');
        $refl_prop = $reflection->getProperty('id');
        $refl_prop->setAccessible(true);
        $refl_prop->setValue($model, 6);

        $model->expects($this->any())->method('isNewRecord')->willReturn(false);

        $model->attributes(array('param1' => 'val1', 'param2' => 'val2', 'fake_id' => 5, 'other_id' => 5));
        $model->save();
    }

    public function testUpdateWithDescriptions()
    {
        $model = $this->model;

        $model->expects($this->any())->method('isNewRecord')->willReturn(false);
        $model->expects($this->any())->method('getDescriptionTableName')->willReturn('fake_descriptions');

        $this->connection->expects($this->any())->method('getTableFields')->willReturn(array('fake_id', 'param1', 'other_id'));
        $this->driver->expects($this->exactly(3))->method('query')->with($this->logicalOr(
            "UPDATE fake_table SET `param1` = 'val1', `other_id` = 5 WHERE fake_id = '6'",
            "UPDATE fake_descriptions SET `param1` = 'val1', `other_id` = 5 WHERE fake_id = '6' AND lang_code = 'en'",
            "SELECT fake_table.* FROM fake_table  LEFT JOIN fake_descriptions ON fake_descriptions.fake_id = fake_table.fake_id AND fake_descriptions.lang_code = 'en' WHERE fake_table.fake_id IN('6') ORDER BY fake_table.fake_id asc LIMIT 0, 1"
        ));

        // $model->id = 6;
        $reflection = new \ReflectionClass('\Tygh\Models\Components\AModel');
        $refl_prop = $reflection->getProperty('id');
        $refl_prop->setAccessible(true);
        $refl_prop->setValue($model, 6);

        $model->attributes(array('param1' => 'val1', 'param2' => 'val2', 'fake_id' => 5, 'other_id' => 5));
        $model->save();
    }

    public function testDelete()
    {
        $model = $this->model;

        $model->expects($this->any())->method('isNewRecord')->willReturn(false);

        // $model->id = 6;
        $reflection = new \ReflectionClass('\Tygh\Models\Components\AModel');
        $refl_prop = $reflection->getProperty('id');
        $refl_prop->setAccessible(true);
        $refl_prop->setValue($model, 6);

        $this->driver->expects($this->any())->method('query')->with("DELETE FROM fake_table WHERE fake_id = '6'");

        $model->delete();
    }

    public function testDeleteWithDescriptions()
    {
        $model = $this->model;

        $model->expects($this->any())->method('isNewRecord')->willReturn(false);
        $model->expects($this->any())->method('getDescriptionTableName')->willReturn('fake_descriptions');

        // $model->id = 6;
        $reflection = new \ReflectionClass('\Tygh\Models\Components\AModel');
        $refl_prop = $reflection->getProperty('id');
        $refl_prop->setAccessible(true);
        $refl_prop->setValue($model, 6);

        $this->driver->expects($this->exactly(2))->method('query')
            ->with($this->logicalOr(
                "DELETE FROM fake_table WHERE fake_id = '6'",
                "DELETE FROM fake_descriptions WHERE fake_id = '6'"
            ))
            ->willReturn(2);

        $model->delete();
    }

    public function testFindById()
    {
        $model = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField', 'findMany'))
            ->getMock();

        $model->expects($this->once())->method('findMany')->willReturn(array('elm'))->with(array(
            'ids' => 34,
            'limit' => 1,
        ));

        $result = $model->find(34);
        $this->assertEquals('elm', $result);
    }

    public function testFindByIdAndParams()
    {
        // Find by id and params
        $model = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField', 'findMany'))
            ->getMock();
        
        $model->expects($this->once())->method('findMany')->willReturn(array())->with(array(
            'ids' => 34,
            'limit' => 1,
            'param' => 'val',
        ));
        
        $model->find(34, array('param' => 'val'));
    }

    public function testFindByParams()
    {
        // Find by id and params
        $model = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField', 'findMany'))
            ->getMock();
        
        $model->expects($this->once())->method('findMany')->willReturn(array())->with(array(
            'limit' => 1,
            'param' => 'val',
            'param2' => 'val2',
        ));
        
        $model->find(array('param' => 'val', 'param2' => 'val2'));
    }

    public function testFindAll()
    {
        // Find by id and params
        $model = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField', 'findMany'))
            ->getMock();
        
        $model->expects($this->once())->method('findMany')->with(array());
        
        $model->findAll();
    }

    public function testFindAllWithParams()
    {
        // Find by id and params
        $model = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField', 'findMany'))
            ->getMock();
        
        $params = array(
            'param' => 'value',
            'param2' => null,
        );

        $model->expects($this->once())->method('findMany')->with($params);
        
        $model->findAll($params);
    }

}

