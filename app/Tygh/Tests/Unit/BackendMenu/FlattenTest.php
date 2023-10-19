<?php

namespace Tygh\Tests\BackendMenu\BlockManager;

use Tygh\BackendMenu;
use Tygh\Tests\Unit\ATestCase;

class FlattenTest extends ATestCase
{
    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    protected function setUp(): void
    {
        define('AREA', 'A');
        define('TIME', time());

        $this->requireCore('functions/fn.common.php');
    }

    /**
     * @dataProvider dpTestFlattenRequest
     */
    public function testFlattenRequest($request, $expected)
    {
        $menu = new BackendMenu();

        $actual = $menu->flattenRequest($request);

        $this->assertEquals($expected, $actual);
    }

    public function dpTestFlattenRequest()
    {
        return [
            [
                [],
                [],
            ],
            [
                [
                    'foo' => 'bar',
                    'bar' => 'baz',
                ],
                [
                    'foo' => 'bar',
                    'bar' => 'baz',
                ],
            ],
            [
                [
                    1,
                    2,
                    3,
                    4,
                    5,
                ],
                [
                    1,
                    2,
                    3,
                    4,
                    5,
                ],
            ],
            [
                [
                    [1, 2],
                    [3, 4],
                ],
                [
                    '0[0]' => 1,
                    '0[1]' => 2,
                    '1[0]' => 3,
                    '1[1]' => 4,
                ],
            ],
            [
                [
                    'foo' => 'bar',
                    'bar' => [
                        'baz',
                        'baz',
                        'baz',
                    ],
                ],
                [
                    'foo'    => 'bar',
                    'bar[0]' => 'baz',
                    'bar[1]' => 'baz',
                    'bar[2]' => 'baz',
                ],
            ],
            [
                [
                    'foo' => 'bar',
                    'bar' => [
                        'baz',
                        'baz',
                        'baz' => [
                            'bad',
                            'bad',
                            'bad',
                        ],
                        'bak' => [
                            'foobar' => 'barbaz',
                            'barbaz' => 'bazbad',
                        ],
                    ],
                ],
                [
                    'foo'              => 'bar',
                    'bar[0]'           => 'baz',
                    'bar[1]'           => 'baz',
                    'bar[baz][0]'      => 'bad',
                    'bar[baz][1]'      => 'bad',
                    'bar[baz][2]'      => 'bad',
                    'bar[bak][foobar]' => 'barbaz',
                    'bar[bak][barbaz]' => 'bazbad',
                ],
            ],
        ];
    }
}
