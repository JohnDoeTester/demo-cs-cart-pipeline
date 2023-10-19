<?php

namespace Tygh\Tests\Unit\Shippings\Services;

use Tygh\Shippings\Services\Dhl as Dhl;
use Tygh\Tests\Unit\ATestCase;

class DhlTest extends ATestCase
{
    /**
     * @dataProvider dpTestSortRatesByCurrency
     */
    public function testSortRatesByCurrency($rates, $currency, $expected)
    {
        $dhl = new Dhl('USD');

        $actual = $dhl->sortRatesByCurrency($rates, $currency);

        $this->assertEquals($expected, $actual);
    }

    public function dpTestSortRatesByCurrency()
    {
        return array(
            array(
                array(),
                null,
                array(),
            ),
            array(
                array('GBP' => 2, 'USD' => 1, 'JPY' => 3),
                'USD',
                array('USD' => 1, 'GBP' => 2, 'JPY' => 3),
            ),
            array(
                array('GBP' => 2, 'USD' => 1, 'JPY' => 3),
                'RUB',
                array('GBP' => 2, 'USD' => 1, 'JPY' => 3),
            ),
        );
    }
}