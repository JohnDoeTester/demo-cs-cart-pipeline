<?php

namespace Tygh\Tests\Unit\Addons\VendorDataPremoderation;

use Tygh\Addons\VendorDataPremoderation\PremoderationSchema;
use Tygh\Tests\Unit\ATestCase;

class PremoderationSchemaTest extends ATestCase
{
    /**
     * @var \Tygh\Addons\VendorDataPremoderation\PremoderationSchema
     */
    protected $schema;

    public function setUp(): void
    {
        $this->schema = new PremoderationSchema(
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
                                'skipField',
                            ],
                        ],
                        'serviceCheckField' => [
                            'requires_premoderation' => [
                                'service',
                                'checkField',
                            ],
                        ],
                    ],
                ],
                'serviceSkipSource'  => [
                    'requires_premoderation' => [
                        'service',
                        'skipSource',
                    ],
                ],
                'serviceCheckSource' => [
                    'requires_premoderation' => [
                        'service',
                        'checkSource',
                    ],
                ],
            ]
        );
    }

    /**
     * @dataProvider dpTestGetSourcePremoderation
     */
    public function testGetSourcePremoderation($source, $expected)
    {
        $actual = $this->schema->getSourcePremoderation($source);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dpTestGetFieldPremoderation
     */
    public function testGetFieldPremoderation($source, $field, $expected)
    {
        $actual = $this->schema->getFieldPremoderation($source, $field);
        $this->assertEquals($expected, $actual);
    }

    public function dpTestGetSourcePremoderation()
    {
        return [
            [
                'skipSource',
                false,
            ],
            [
                'checkSource',
                true,
            ],
            [
                'serviceSkipSource',
                [
                    'service',
                    'skipSource',
                ],
            ],
            [
                'serviceCheckSource',
                [
                    'service',
                    'checkSource',
                ],
            ],
            [
                'missingSource',
                true,
            ],
        ];
    }

    public function dpTestGetFieldPremoderation()
    {
        return [
            [
                'skipSource',
                'anyField',
                false,
            ],
            [
                'checkSource',
                'checkField',
                true,
            ],
            [
                'checkSource',
                'skipField',
                false,
            ],
            [
                'checkSource',
                'missingField',
                true,
            ],
        ];
    }
}
