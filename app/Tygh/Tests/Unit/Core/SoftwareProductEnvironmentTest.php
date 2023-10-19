<?php

namespace Tygh\Tests\Unit\Core;

use Tygh\SoftwareProductEnvironment;
use Tygh\Tests\Unit\ATestCase;

class SoftwareProductEnvironmentTest extends ATestCase
{
    /**
     * @dataProvider dpTestGetReleaseTime
     */
    public function testGetReleaseTime($release_date, $now_date, $expected_message, $expected_numeric_diff = null)
    {
        $env = new SoftwareProductEnvironment(
            'Test Product',
            '1.0.0',
            '',
            '',
            '',
            'ULTIMATE',
            strtotime($release_date)
        );

        $actual = $env->getReleaseTime(strtotime($now_date));
        $actual_message = $actual['message'];
        $actual_numeric_diff = isset($actual['params'][0])
            ? $actual['params'][0]
            : null;

        $this->assertEquals($expected_message, $actual_message);
        $this->assertEquals($expected_numeric_diff, $actual_numeric_diff);
    }

    public function dpTestGetReleaseTime()
    {
        return [
            ['2022-02-01', '2022-02-10', 'product_env.released_this_month'],
            ['2022-02-01', '2022-02-01', 'product_env.released_this_month'],

            ['2022-02-01', '2022-03-10', 'product_env.released_n_months_ago', 1],
            ['2022-02-01', '2022-05-10', 'product_env.released_n_months_ago', 3],

            ['2022-02-01', '2023-02-10', 'product_env.released_n_years_ago', 1],
            ['2022-02-01', '2024-05-10', 'product_env.released_n_years_ago', 2],

            ['2022-02-01', '2023-01-10', 'product_env.released_n_months_ago', 11],
            ['2022-12-01', '2023-01-10', 'product_env.released_n_months_ago', 1],
            ['2025-02-01', '2022-02-01', 'product_env.released_this_month'],
        ];
    }
}
