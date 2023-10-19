<?php

namespace Tygh\Tests\Unit\Addons\VendorDataPremoderation;

use Tygh\Addons\VendorDataPremoderation\Comparator;
use Tygh\Addons\VendorDataPremoderation\PremoderationSchema;
use Tygh\Addons\VendorDataPremoderation\State;
use Tygh\Application;
use Tygh\Tests\Unit\ATestCase;

class Service
{
    public function source($source_name)
    {
        if ($source_name === 'skipSource' || $source_name === 'serviceSkipSource') {
            return false;
        }

        return true;
    }

    public function field($source_name, $field_name)
    {
        if ($field_name === 'skipField' || $field_name === 'serviceSkipField') {
            return false;
        }

        return true;
    }
}

class ChangesDetectorTest extends ATestCase
{
    /**
     * @var \Tygh\Addons\VendorDataPremoderation\PremoderationSchema
     */
    protected $premoderation_schema;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $service_provider;

    /**
     * @var \Tygh\Addons\VendorDataPremoderation\Comparator
     */
    protected $detector;

    public function setUp(): void
    {
        $premoderation_schema = new PremoderationSchema(
            [
                'skipSource'         => [
                    'requires_premoderation' => false,
                ],
                'checkSource'        => [
                    'requires_premoderation' => true,
                    'fields'                 => [
                        'skipField'         => [
                            'requires_premoderation' => false,
                        ],
                        'checkField'        => [
                            'requires_premoderation' => true,
                        ],
                        'serviceSkipField'  => [
                            'requires_premoderation' => [
                                'service',
                                'field',
                            ],
                        ],
                        'serviceCheckField' => [
                            'requires_premoderation' => [
                                'service',
                                'field',
                            ],
                        ],
                    ],
                ],
                'serviceSkipSource'  => [
                    'requires_premoderation' => [
                        'service',
                        'source',
                    ],
                ],
                'serviceCheckSource' => [
                    'requires_premoderation' => [
                        'service',
                        'source',
                    ],
                ],
            ]
        );
        $app = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->setMethods(['offsetGet'])
            ->getMock();

        $app->expects($this->any())
            ->method('offsetGet')
            ->will(
                $this->returnCallback(
                    function () {
                        return new Service();
                    }
                )
            );

        $this->detector = new Comparator($premoderation_schema, $app);
    }

    /**
     * @dataProvider dpTestCompare
     */
    public function testCompare(array $initial_state, array $resulting_state, array $expected_changed_sources)
    {
        $diff = $this->detector->compare(new State($initial_state), new State($resulting_state), true);

        $this->assertEquals($expected_changed_sources, $diff->getChangedSources());
    }

    public function dpTestCompare()
    {
        return [
            //
            [
                [],
                [],
                [],
            ],
            //
            [
                [
                    'skipSource'         => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                    ],
                    'serviceSkipSource'  => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                ],
                [
                    'skipSource'         => [
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                    ],
                    'serviceSkipSource'  => [
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                ],
                [],
            ],
            //
            [
                [
                    'skipSource'         => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                    ],
                    'serviceSkipSource'  => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                ],
                [
                    'skipSource'         => [
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                        [
                            'skipField'         => 12,
                            'checkField'        => 1,
                            'serviceSkipField'  => 22,
                            'serviceCheckField' => 3,
                        ],
                    ],
                    'serviceSkipSource'  => [
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                ],
                [],
            ],
            //
            [
                [
                    'skipSource'         => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                    ],
                    'serviceSkipSource'  => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                ],
                [
                    'skipSource'         => [
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                    ],
                    'serviceSkipSource'  => [
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                    ],
                ],
                [
                    'checkSource',
                    'serviceCheckSource',
                ],
            ],
            //
            [
                [
                    'skipSource'         => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'serviceSkipSource'  => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                ],
                [
                    'skipSource'         => [
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'serviceSkipSource'  => [
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                    ],
                ],
                [
                    'serviceCheckSource',
                ],
            ],
            //
            [
                [
                    'skipSource'         => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                    ],
                    'serviceSkipSource'  => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                ],
                [
                    'skipSource'         => [
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [
                            'skipField'         => 0,
                            'checkField'        => 11,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                    ],
                    'serviceSkipSource'  => [
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                ],
                [
                    'checkSource',
                ],
            ],
            //
            [
                [
                    'skipSource'         => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                    ],
                    'serviceSkipSource'  => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                ],
                [
                    'skipSource'         => [
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                        ],
                    ],
                    'serviceSkipSource'  => [
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                        [1, 2, 3],
                    ],
                ],
                [
                    'serviceCheckSource',
                ],
            ],
            //
            [
                [
                    'skipSource'         => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                            'missingField'      => 4,
                        ],
                    ],
                    'serviceSkipSource'  => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                ],
                [
                    'skipSource'         => [
                        [0, 1, 2],
                    ],
                    'checkSource'        => [
                        [
                            'skipField'         => 0,
                            'checkField'        => 1,
                            'serviceSkipField'  => 2,
                            'serviceCheckField' => 3,
                            'missingField'      => 44,
                        ],
                    ],
                    'serviceSkipSource'  => [
                    ],
                    'serviceCheckSource' => [
                        [0, 1, 2],
                        [0, 1, 2],
                    ],
                ],
                [
                    'checkSource',
                ],
            ],
        ];
    }
}
