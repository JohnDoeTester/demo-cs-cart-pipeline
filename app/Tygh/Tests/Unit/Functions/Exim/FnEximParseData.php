<?php

namespace Tygh\Tests\Unit\Functions\Exim;

use Tygh\Tests\Unit\ATestCase;

class FnEximParseData extends ATestCase
{
    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    protected function setUp(): void
    {
        $this->requireMockFunction('fn_allowed_for');
        $this->requireCore('schemas/exim/products.functions.php');
        $this->requireCore('functions/fn.common.php');
    }

    /**
     * @dataProvider dpData
     */
    public function testParseData($data, $variants_delimiter = ',', $features_delimiter = '///', $is_option = false, $expected)
    {
        $result = fn_exim_parse_data($data, $variants_delimiter, $features_delimiter, $is_option);

        $this->assertEquals($expected, $result);
    }

    public function dpData()
    {
        return [
            [
                '(Test group (test)) Test feature: S[abc]',
                ',',
                '///',
                false,
                [
                    10 => [
                        'type' => 'S',
                        'name' => 'Test feature',
                        'group_name' => 'Test group (test)',
                        'variants' => [
                            10 => ['name' => 'abc']
                        ],
                    ],
                ],
            ],
            [
                '(Test group) Test feature: S[abc]',
                ',',
                '///',
                false,
                [
                    10 => [
                        'type' => 'S',
                        'name' => 'Test feature',
                        'group_name' => 'Test group',
                        'variants' => [
                            10 => ['name' => 'abc']
                        ],
                    ],
                ],
            ],
            [
                '(Test group ((test))) Test feature: S[abc]',
                ',',
                '///',
                false,
                [
                    10 => [
                        'type' => 'S',
                        'name' => 'Test feature',
                        'group_name' => 'Test group ((test))',
                        'variants' => [
                            10 => ['name' => 'abc']
                        ],
                    ],
                ],
            ],
            [
                '(Test group)Test feature: S[abc]',
                ',',
                '///',
                false,
                [
                    10 => [
                        'type' => 'S',
                        'name' => 'Test feature',
                        'group_name' => 'Test group',
                        'variants' => [
                            10 => ['name' => 'abc']
                        ],
                    ],
                ],
            ],
            [
                '(Test group (test))Test feature: S[abc]',
                ',',
                '///',
                false,
                [
                    10 => [
                        'type' => 'S',
                        'name' => 'Test feature',
                        'group_name' => 'Test group (test)',
                        'variants' => [
                            10 => ['name' => 'abc']
                        ],
                    ],
                ],
            ],
            [
                'Test feature: S[test]',
                ',',
                '///',
                false,
                [
                    10 => [
                        'type' => 'S',
                        'name' => 'Test feature',
                        'variants' => [
                            10 => ['name' => 'test']
                        ],
                    ],
                ],
            ],
            [
                'Test feature',
                ',',
                '///',
                false,
                [
                    10 => [
                        'type' => '',
                        'name' => 'Test feature',
                    ],
                ],
            ],
            [
                '(Electronics) Display: S[27"]; Brand: E[Acer]',
                ',',
                '///',
                false,
                [
                    10 => [
                        'type' => 'S',
                        'name' => 'Display',
                        'group_name' => 'Electronics',
                        'variants' => [
                            10 => ['name' => '27"']
                        ],
                    ],
                    20 => [
                        'type' => 'E',
                        'name' => 'Brand',
                        'variants' => [
                            10 => ['name' => 'Acer']
                        ],
                    ],
                ],
            ],
            [
                '(Electronics) Operating System: S[Windows 7 Home Basic]; (Electronics) Storage Capacity: S[320GB]; Brand: E[ASUS]',
                ',',
                '///',
                false,
                [
                    10 => [
                        'type' => 'S',
                        'name' => 'Operating System',
                        'group_name' => 'Electronics',
                        'variants' => [
                            10 => ['name' => 'Windows 7 Home Basic']
                        ],
                    ],
                    20 => [
                        'type' => 'S',
                        'name' => 'Storage Capacity',
                        'group_name' => 'Electronics',
                        'variants' => [
                            10 => ['name' => '320GB']
                        ],
                    ],
                    30 => [
                        'type' => 'E',
                        'name' => 'Brand',
                        'variants' => [
                            10 => ['name' => 'ASUS']
                        ],
                    ],
                ],
            ],
        ];
    }
}
