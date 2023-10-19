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

use Tygh\Models\Components\Relation;
use Tygh\Tests\Unit\ATestCase;

class RelationTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    protected function setUp(): void
    {
        define('CART_LANGUAGE', 'en');
    }

    public function testHasOne()
    {
        $owner = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField'))
            ->getMock();

        $related = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField', 'find'))
            ->getMock();

        $relation = $this->getMockBuilder('\Tygh\Models\Components\Relation')
            ->setMethods(array('getModel'))
            ->getMock();

        $relation->expects($this->once())->method('getModel')->willReturn($related);

        $owner->field = 456;
        $related->expects($this->once())->method('find')->with(array(
            'target_field' => 456
        ));

        $relation->setOwner($owner);
        $relation->setRule(Relation::HAS_ONE, get_class($related), 'field', 'target_field');
        $relation->find();
    }

    public function testHasOneWithParams()
    {
        $owner = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField'))
            ->getMock();

        $related = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField', 'find'))
            ->getMock();

        $relation = $this->getMockBuilder('\Tygh\Models\Components\Relation')
            ->setMethods(array('getModel'))
            ->getMock();

        $relation->expects($this->once())->method('getModel')->willReturn($related);

        $owner->field = 456;
        $related->expects($this->once())->method('find')->with(array(
            'param1' => 'val1',
            'target_field' => 456,
        ));

        $relation->setOwner($owner);
        $relation->setRule(Relation::HAS_ONE, get_class($related), 'field', 'target_field', array('param1' => 'val1'));
        $relation->find();
    }

    public function testHasMany()
    {
        $owner = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField'))
            ->getMock();

        $related = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField', 'findMany'))
            ->getMock();

        $relation = $this->getMockBuilder('\Tygh\Models\Components\Relation')
            ->setMethods(array('getModel'))
            ->getMock();

        $relation->expects($this->once())->method('getModel')->willReturn($related);

        $owner->target_field = 234;
        $related->expects($this->once())->method('findMany')->with(array(
            'field' => 234,
        ));

        $relation->setOwner($owner);
        $relation->setRule(Relation::HAS_MANY, get_class($related), 'field', 'target_field');
        $relation->find();
    }

    public function testHasManyWithParams()
    {
        $owner = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField'))
            ->getMock();

        $related = $this->getMockBuilder('\Tygh\Models\Components\AModel')
            ->setMethods(array('getTableName', 'getPrimaryField', 'findMany'))
            ->getMock();

        $relation = $this->getMockBuilder('\Tygh\Models\Components\Relation')
            ->setMethods(array('getModel'))
            ->getMock();

        $relation->expects($this->once())->method('getModel')->willReturn($related);

        $owner->target_field = 234;
        $related->expects($this->once())->method('findMany')->with(array(
            'field' => 234,
            'param1' => 'val1',
        ));

        $relation->setOwner($owner);
        $relation->setRule(Relation::HAS_MANY, get_class($related), 'field', 'target_field', array('param1' => 'val1'));
        $relation->find();
    }

}
