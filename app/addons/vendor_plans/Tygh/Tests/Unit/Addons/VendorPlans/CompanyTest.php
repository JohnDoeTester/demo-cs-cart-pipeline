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
use Tygh\Models\Company;
use Tygh\Models\Components\Relation;
use Tygh\Tests\Unit\ATestCase;

class CompanyTest extends ATestCase
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
        $this->requireMockFunction('fn_get_order_payout_statuses');

        $this->app = \Tygh\Tygh::createApplication();

        // // Session
        // // $this->app['session'] = new \Tygh\Web\Session($this->app);

        /**
         * Database driver
         */
        $driver = $this->getMockBuilder('\Tygh\Backend\Database\Pdo')
            ->setMethods(array('escape', 'query', 'insertId'))
            ->getMock();
        $driver->method('escape')->will($this->returnCallback('addslashes'));
        $this->app['db.driver'] = $driver;

        /**
         * Database connection
         */
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
        $company = new Company;
        $this->assertInstanceOf('Tygh\Models\Components\IModel', $company);
    }

    public function testJoin()
    {
        $company = new Company;
        $joins = $company->getJoins(array());

        $this->assertContains(" LEFT JOIN ?:company_descriptions ON ?:company_descriptions.company_id = ?:companies.company_id AND ?:company_descriptions.lang_code = 'en'", $joins);
        $this->assertContains(" LEFT JOIN ?:vendor_plans p ON p.plan_id = ?:companies.plan_id", $joins);
    }

    public function testSearchFields()
    {
        $company = new Company;
        $search_fields = $company->getSearchFields();

        $this->assertArraySubset(array('in' => array('plan_id' => '?:companies.plan_id')), $search_fields);
        $this->assertArraySubset(array('in' => array('status' => '?:companies.status')), $search_fields);
    }

    public function testRelations()
    {
        $company = new Company;
        $relations = $company->getRelations();

        $this->assertArraySubset(array('plan' => array(Relation::HAS_ONE, 'Tygh\Models\VendorPlan', 'plan_id')), $relations);
    }

    public function testGatherAdditionalItemsData()
    {
        $items = array(
            array(
                'company_id' => 1,
                'categories' => '1,2,3,4,5'
            ),
            array(
                'company_id' => 2,
                'categories' => ''
            ),
            array(
                'company_id' => 3,
                'categories' => '6'
            ),
            array(
                'company_id' => 4,
            ),
        );
        $company = new Company;
        $company->gatherAdditionalItemsData($items, array());
        $this->assertEquals(array(
            array(
                'company_id' => 1,
                'categories' => '1,2,3,4,5',
                'category_ids' => array(1, 2, 3, 4, 5),
                'storefront_ids' => array(),
            ),
            array(
                'company_id' => 2,
                'categories' => '',
                'category_ids' => array(),
                'storefront_ids' => array(),
            ),
            array(
                'company_id' => 3,
                'categories' => '6',
                'category_ids' => array(6),
                'storefront_ids' => array(),
            ),
            array(
                'company_id' => 4,
                'category_ids' => array(),
                'storefront_ids' => array(),
            ),
        ), $items);
    }

    public function testGetCurrentRevenue()
    {
        define('TIME', 123456789);
        $company = new Company;
        $company->company_id = 5;

        $this->app['db.driver']
            ->expects($this->once())
            ->method('query')
            ->with("SELECT SUM(order_amount) - SUM(commission_amount) FROM vendor_payouts p JOIN orders o USING(order_id) WHERE p.company_id = 5 AND p.end_date >= 120960000 AND p.end_date <= 123456789 AND o.status IN ('P', 'C', 'O')");

        $company->getCurrentRevenue();
    }

}

