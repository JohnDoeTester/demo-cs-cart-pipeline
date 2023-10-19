<?php

namespace Tygh\Tests\Unit\Functions\Location;

use Tygh\Tygh;
use Tygh\Tests\Unit\ATestCase;

class GetCountryListTest extends ATestCase
{
    protected $app;

    /**
     * Sets the configuration for the test
     */
    protected function setUp(): void
    {
        define('CART_LANGUAGE', 'en');

        $this->requireCore('functions/fn.locations.php');
        $this->requireCore('functions/fn.database.php');
        $this->requireMockFunction('fn_set_hook');

        $this->app = Tygh::createApplication();

        $driver = $this->getMockBuilder('\Tygh\Backend\Database\Pdo')
            ->onlyMethods(array('escape'))
            ->getMock();
        $driver->method('escape')->will($this->returnCallback('addslashes'));
        $this->app['db.driver'] = $driver;

        $db = $this->getMockBuilder('\Tygh\Database\Connection')
            ->setMethods(array('getField', 'getArray'))
            ->setConstructorArgs(array($driver))
            ->getMock();
        $this->app['db'] = $db;

        parent::setUp();
    }

    /**
     * @param $params - parameters of countries selection
     * @param $items_per_page - number of countries per page
     * @param $query - generated sql query
     * @dataProvider dpGetCountriesQuery
     */
    public function testGetCountriesFunc($params, $items_per_page, $count_items_query, $total_query)
    {
        $this->app['db']
            ->expects($this->once())
            ->method('getField')
            ->with($count_items_query);

        $this->app['db']
            ->expects($this->once())
            ->method('getArray')
            ->with($total_query);

        fn_get_countries($params, $items_per_page);
    }

    /**
     * Generates data for testGetCountriesFunc
     *
     * @return array
     */
    public function dpGetCountriesQuery()
    {
        return array(
            array (
                'params' => array('q' => 'Andorra'),
                'items_per_page' => 1,
                'count_items_query' => "SELECT count(*) FROM ?:countries as a LEFT JOIN ?:country_descriptions as b"
                                        . " ON b.code = a.code AND b.lang_code = 'en'"
                                        . " WHERE 1 AND b.country LIKE '%Andorra%'",
                'total_query' => "SELECT a.code, a.code_A3, a.code_N3, a.status, a.region, b.country"
                                . " FROM ?:countries as a"
                                . " LEFT JOIN ?:country_descriptions as b ON b.code = a.code AND b.lang_code = 'en'"
                                . " WHERE 1 AND b.country LIKE '%Andorra%'  ORDER BY b.country  LIMIT 0, 1"
            )
        );
    }
}
