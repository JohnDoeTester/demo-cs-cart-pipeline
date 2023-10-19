<?php


namespace Tygh\Tests\Unit\Functions\Promotions;


use Tygh\Tests\Unit\ATestCase;

class FilterConditionValueTest extends ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->requireCore('functions/fn.common.php');
        $this->requireCore('functions/fn.promotions.php');
    }

    /**
     * @param $value
     * @param $operator
     * @param $expected
     * @dataProvider dpFilterFloatConditionValue
     */
    public function testFilterFloatConditionValue($value, $operator, $expected)
    {
        $this->assertEquals($expected, fn_promotions_filter_float_condition_value($value, $operator));
    }

    public function dpFilterFloatConditionValue()
    {
        return array(
            array('10.50$', 'eq', 10.50),
            array('$10.50', 'eq', 0.0),
            array('10.50$', 'in', 10.50),
            array('10.50, 12, 14.10', 'in', '10.5,12,14.1'),
            array('10.50, 12, 14.10', 'nin', '10.5,12,14.1'),
        );
    }

    /**
     * @param $value
     * @param $operator
     * @param $expected
     * @dataProvider dpFilterIntConditionValue
     */
    public function testFilterIntConditionValue($value, $operator, $expected)
    {
        $this->assertEquals($expected, fn_promotions_filter_int_condition_value($value, $operator));
    }

    public function dpFilterIntConditionValue()
    {
        return array(
            array('10.50$', 'eq', 10),
            array('$10', 'eq', 0),
            array('10$', 'in', '10'),
            array('10.50, 12, 14.10', 'in', '10,12,14'),
            array('10.50, 12, 14.10', 'nin', '10,12,14'),
        );
    }
}