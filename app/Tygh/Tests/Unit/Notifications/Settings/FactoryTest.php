<?php

namespace Tygh\Tests\Unit\Notifications\Settings;

use Tygh\Notifications\Settings\Factory;
use Tygh\Tests\Unit\ATestCase;

class FactoryTest extends ATestCase
{
    /**
     * @var \Tygh\Notifications\Settings\Factory
     */
    protected $factory;

    public function setUp(): void
    {
        $this->factory = new Factory(['A', 'C', 'V'], ['mail', 'internal']);
    }

    /**
     * @dataProvider dpConvertToRules
     */
    public function testConvertToRules($overrides, array $expected)
    {
        $actual = $this->factory->convertToRules($overrides);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dpFilterReceivers
     */
    public function testFilterReceivers(array $overrides, array $expected)
    {
        $actual = $this->factory->filterReceivers($overrides);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dpNormalizeRules
     */
    public function testNormalizeRules(array $overrides, array $expected)
    {
        $actual = $this->factory->normalizeRules($overrides);
        $this->assertEquals($expected, $actual);
    }

    public function dpConvertToRules()
    {
        return [
            [
                false,
                [
                    'A' => false,
                    'C' => false,
                    'V' => false,
                ],
            ],

            [
                'Y',
                [
                    'A' => 'Y',
                    'C' => 'Y',
                    'V' => 'Y',
                ],
            ],

            [
                [
                    'C' => true,
                    'A' => false,
                ],
                [
                    'C' => [
                        'mail' => true,
                        'internal' => true,
                    ],
                    'A' => [
                        'mail' => false,
                        'internal' => false,
                    ],
                ],
            ],

            [
                [
                    'C' => [
                        'mail'     => true,
                        'internal' => false,
                    ],
                    'A' => false,
                ],
                [
                    'C' => [
                        'mail'     => true,
                        'internal' => false,
                    ],
                    'A' => [
                        'mail' => false,
                        'internal' => false,
                    ],
                ],
            ],
        ];
    }

    public function dpFilterReceivers()
    {
        return [
            [
                [
                    'C' => true,
                    'A' => true,
                    'V' => true,
                ],
                [
                    'C' => true,
                    'A' => true,
                    'V' => true,
                ],
            ],

            [
                [
                    'C' => true,
                    'P' => false,
                ],
                [
                    'C' => true,
                ],
            ],
        ];
    }

    public function dpNormalizeRules()
    {
        return [
            [
                [
                    'C' => 'Y',
                    'A' => 'N',
                    'V' => true,
                ],
                [
                    'C' => true,
                    'A' => false,
                    'V' => true,
                ],
            ],

            [
                [
                    'C' => [],
                    'A' => [
                        'mail'     => true,
                        'internal' => false,
                    ],
                    'V' => [
                        'mail'     => 'N',
                        'internal' => 'Y',
                    ],
                ],
                [
                    'C' => false,
                    'A' => [
                        'mail'     => true,
                        'internal' => false,
                    ],
                    'V' => [
                        'mail'     => false,
                        'internal' => true,
                    ],
                ],
            ],
        ];
    }
}
