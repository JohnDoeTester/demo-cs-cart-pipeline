<?php

namespace Tygh\Tests\Unit\Addons\VendorDataPremoderation;

use Tygh\Addons\VendorDataPremoderation\State;
use Tygh\Addons\VendorDataPremoderation\StateFactory;
use Tygh\Tests\Unit\ATestCase;

class StateFactoryTest extends ATestCase
{
    /**
     * @var \Tygh\Addons\VendorDataPremoderation\StateFactory
     */
    protected $state_factory;

    public function setUp(): void
    {
        $source_conditions = [
            'src1' => [
                'id' => '$id',
            ],
            'src2' => [
                'object_id' => '$id',
                'type'      => 'type',
            ],
        ];

        $data_loader = function ($source, $conditions) {
            static $data = [
                'src1.id.1'                  => [
                    [
                        'id'        => 1,
                        'lang_code' => 'en',
                    ],
                    [
                        'id'        => 1,
                        'lang_code' => 'de',
                    ],
                ],
                'src1.id.2'                  => [
                    [
                        'id'        => 2,
                        'lang_code' => 'en',
                    ],
                ],
                'src2.object_id.1.type.type' => [
                    [
                        'id'      => 1,
                        'type'    => 'type',
                        'subtype' => 'subtype1',
                    ],
                    [
                        'id'      => 1,
                        'type'    => 'type',
                        'subtype' => 'subtype2',
                    ],
                ],
            ];

            $data_key = $source;
            foreach ($conditions as $key => $value) {
                $data_key .= '.' . $key . '.' . $value;
            }

            if (isset($data[$data_key])) {
                return $data[$data_key];
            }

            return [];
        };

        $this->state_factory = new StateFactory(
            $source_conditions,
            $data_loader
        );
    }

    /**
     * @dataProvider dpTestGetState
     */
    public function testGetState($id, $expected_state)
    {
        $actual_state = $this->state_factory->getState($id);
        $this->assertEquals($expected_state, $actual_state);
    }

    public function dpTestGetState()
    {
        return [
            [
                1,
                new State(
                    [
                        'src1' => [
                            [
                                'id'        => 1,
                                'lang_code' => 'en',
                            ],
                            [
                                'id'        => 1,
                                'lang_code' => 'de',
                            ],
                        ],
                        'src2' => [
                            [
                                'id'      => 1,
                                'type'    => 'type',
                                'subtype' => 'subtype1',
                            ],
                            [
                                'id'      => 1,
                                'type'    => 'type',
                                'subtype' => 'subtype2',
                            ],
                        ],
                    ]
                ),
            ],
            [
                2,
                new State(
                    [
                        'src1' => [
                            [
                                'id'        => 2,
                                'lang_code' => 'en',
                            ],
                        ],
                        'src2' => [],
                    ]
                ),
            ],
            [
                3,
                new State(
                    [
                        'src1' => [],
                        'src2' => [],
                    ]
                ),
            ],
        ];
    }
}
