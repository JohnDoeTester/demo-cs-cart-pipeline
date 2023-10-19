<?php

namespace Tygh\Tests\Unit\Notifications\Settings;

use Tygh\Notifications\Settings\Ruleset;
use Tygh\Tests\Unit\ATestCase;

class RulesetTest extends ATestCase
{
    /**
     * @var array
     */
    protected $event_settings;

    public function setUp(): void
    {
        $this->event_settings = [
            'C'     => [
                'mail' => true,
                'internal' => false,
            ],
            'A'     => [
                'mail' => true,
                'internal' => true,
            ],
            'V'     => [
                'mail' => false,
                'internal' => true,
            ],
        ];
    }

    /**
     * @dataProvider dpOverride
     */
    public function testOverride(array $overrides, array $expected)
    {
        $settings = new Ruleset($overrides);
        $actual = $settings->apply($this->event_settings);
        $this->assertEquals($expected, $actual);
    }

    public function dpOverride()
    {
        return [
            [
                [],
                [
                    'C'     => [
                        'mail' => true,
                        'internal' => false,
                    ],
                    'A'     => [
                        'mail' => true,
                        'internal' => true,
                    ],
                    'V'     => [
                        'mail' => false,
                        'internal' => true,
                    ],
                ],
            ],

            [
                [
                    'C' => false,
                    'V' => false,
                ],
                [
                    'C'     => [
                        'mail' => false,
                        'internal' => false,
                    ],
                    'A'     => [
                        'mail' => true,
                        'internal' => true,
                    ],
                    'V'     => [
                        'mail' => false,
                        'internal' => false,
                    ],
                ],
            ],

            [
                [
                    'C' => [
                        'mail' => false,
                    ],
                    'A' => [
                        'mail' => false,
                    ],
                    'V' => [
                        'internal' => false,
                    ]
                ],
                [
                    'C'     => [
                        'mail' => false,
                        'internal' => false,
                    ],
                    'A'     => [
                        'mail' => false,
                        'internal' => true,
                    ],
                    'V'     => [
                        'mail' => false,
                        'internal' => false,
                    ],
                ],
            ],
        ];
    }
}
