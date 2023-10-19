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

namespace Tygh\Tests\Unit\Addons\VendorPlans;

use ReflectionClass;
use Tygh\Models\Components\Relation;
use Tygh\Models\VendorPlan;
use Tygh\Tests\Unit\ATestCase;
use Tygh\Registry;

class VendorPlanTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    protected $app;
    
    protected function setUp(): void
    {
        define('AREA', 'A');
        define('CART_LANGUAGE', 'en');

        $this->requireCore('functions/fn.database.php');

        $this->requireMockFunction('fn_set_hook');
        $this->requireMockFunction('fn_set_notification');
        $this->requireMockFunction('__');

        $this->app = \Tygh\Tygh::createApplication();

        // // Session
        // // $this->app['session'] = new \Tygh\Web\Session($this->app);

        // Driver
        $driver = $this->getMockBuilder('\Tygh\Backend\Database\Pdo')
            ->setMethods(array('escape', 'query', 'insertId'))
            ->getMock();
        $driver->method('escape')->will($this->returnCallback('addslashes'));
        $this->app['db.driver'] = $driver;

        // Connection
        $this->app['db'] = $this->getMockBuilder('\Tygh\Database\Connection')
            ->setMethods(array('error', 'hasError'))
            ->setConstructorArgs(array($driver))
            ->getMock();

        $reflection = new ReflectionClass($this->app['db']);
        $table_prefix = $reflection->getProperty('table_prefix');
        $table_prefix->setAccessible(true);
        $table_prefix->setValue($this->app['db'], '');
    }

    public function testBase()
    {
        $plan = new VendorPlan;
        $this->assertInstanceOf('Tygh\Models\Components\IModel', $plan);
        $this->assertInstanceOf('Tygh\Models\Components\AModel', $plan);
        $this->assertSame('?:vendor_plans', $plan->getTableName());
        $this->assertSame('plan_id', $plan->getPrimaryField());
        $this->assertSame('?:vendor_plan_descriptions', $plan->getDescriptionTableName());
    }

    public function testGetFields()
    {
        $plan = new VendorPlan;
        $fields = $plan->getFields(array());

        $this->assertContains('?:vendor_plan_descriptions.*', $fields);
        $this->assertContains('?:vendor_plans.*', $fields);
    }

    public function testJoin()
    {
        $plan = new VendorPlan;
        $joins = $plan->getJoins(array());

        $this->assertContains(" LEFT JOIN ?:vendor_plan_descriptions ON ?:vendor_plan_descriptions.plan_id = ?:vendor_plans.plan_id AND ?:vendor_plan_descriptions.lang_code = 'en'", $joins);
    }

    public function testSearchFields()
    {
        $plan = new VendorPlan;
        $search_fields = $plan->getSearchFields();

        $this->assertArraySubset(array('number' => array('is_default')), $search_fields);
        $this->assertArraySubset(array('string' => array('status')), $search_fields);
        $this->assertArraySubset(array('text' => array('plan')), $search_fields);
        $this->assertArraySubset(array('range' => array('price')), $search_fields);
        $this->assertArraySubset(array('in' => array('periodicity')), $search_fields);
    }

    public function testRelations()
    {
        $plan = new VendorPlan;
        $relations = $plan->getRelations();

        $this->assertArraySubset(array('companies' => array(Relation::HAS_MANY, 'Tygh\Models\Company', 'plan_id')), $relations);
        $this->assertArraySubset(
            array('companiesCount' => array(Relation::HAS_MANY, 'Tygh\Models\Company', 'plan_id', null, array('get_count' => true))),
            $relations
        );
    }

    public function testGetExtraCondition()
    {
        $plan = new VendorPlan;
        
        $condition = $plan->getExtraCondition(array('allowed_for_company_id' => 5));
        $this->assertArraySubset(array('allowed_for_company_id' => "(status IN('A', 'H'))"), $condition);

        Registry::set('runtime.company_id', 5);
        $condition = $plan->getExtraCondition(array('allowed_for_company_id' => 5));

        $this->assertArraySubset(array('allowed_for_company_id' => "((status IN('A') AND (storefronts IS NULL OR storefronts = \"\")))"), $condition);
        
        $condition = $plan->getExtraCondition(array('company_id' => 5));
        $this->assertArraySubset(array('company_id' => "?:vendor_plans.plan_id = 0"), $condition);
    }

    public function testGatherAdditionalItemsNeverQueries()
    {
        $this->app['db.driver']
            ->expects($this->never())
            ->method('query');

        $plan = new VendorPlan;
        $items = array();
        $plan->gatherAdditionalItemsData($items, array());
    }

    public function testGatherAdditionalItemsDataCompanies()
    {
        $this->app['db.driver']
            ->expects($this->once())
            ->method('query')
            ->with('SELECT plan_id, COUNT(company_id) as companies FROM companies WHERE plan_id IN(1) GROUP BY plan_id');

        $plan = new VendorPlan;
        $items = array(array('plan_id' => 1));
        $plan->gatherAdditionalItemsData($items, array('get_companies_count' => true));
    }

    public function testBeforeSave()
    {
        // 1st
        $plan = new VendorPlan;
        $this->assertEquals(true, $plan->beforeSave());
        
        // 2nd
        $plan = new VendorPlan;
        $plan->status = 'D';
        $plan->companiesCount = 2;
        
        $plan_refl = new \ReflectionClass('\Tygh\Models\VendorPlan');
        $refl_prop = $plan_refl->getProperty('current_attributes');
        $refl_prop->setAccessible(true);
        $refl_prop->setValue($plan, array('status' => 'A'));

        $this->assertEquals(false, $plan->beforeSave());

        // 3rd
        $plan->companiesCount = 0;
        $this->assertEquals(true, $plan->beforeSave());

        // 4th
        $plan = new VendorPlan;
        $plan->categories = array(1, 2, 3, 4, 5, 6);
        $plan->beforeSave();
        $this->assertEquals('1,2,3,4,5,6', $plan->categories);
    }

}

