<?php

namespace Tygh\Tests\Unit\Storefront;

use Tygh\Storefront\Normalizer;
use Tygh\Tests\Unit\ATestCase;

class NormalizerTest extends ATestCase
{
    /** @var Normalizer */
    protected $normalizer;

    public function setUp(): void
    {
        $this->normalizer = new Normalizer();
    }

    /**
     * @dataProvider dpNormalizeStorefrontData
     */
    public function testNormalizeStorefrontData($data, $expected)
    {
        $actual = $this->normalizer->normalizeStorefrontData($data);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dpGetEnumeration
     */
    public function testGetEnumeration($data, $expected)
    {
        $actual = $this->normalizer->getEnumeration($data);

        $this->assertEquals($expected, $actual);
    }

    public function dpNormalizeStorefrontData()
    {
        return [
            [
                [
                    'country_codes' => 'RU,US,UK',
                ],
                [
                    'country_codes' => ['RU', 'US', 'UK'],
                ],
            ],
            [
                [
                    'language_ids' => '  1,   2,   3   ',
                ],
                [
                    'language_ids' => [1, 2, 3],
                ],
            ],
            [
                [
                    'currency_ids' => ' ',
                ],
                [
                    'currency_ids' => [],
                ],
            ],
            [
                [
                    'access_key' => 'unchanged',
                ],
                [
                    'access_key' => 'unchanged',
                ],
            ],
            [
                [
                    'url' => 'http://example.com/',
                ],
                [
                    'url' => 'example.com',
                ],
            ],
        ];
    }

    public function dpGetEnumeration() {
        return [
            [
                '',
                []
            ],
            [
                1,
                [1]
            ],
            [
                [1, 2],
                [1, 2]
            ],
            [
                [1, 2, ''],
                [1, 2]
            ],
            [
                '1, 2,  3',
                ['1', '2', '3']
            ],
            [
                '1,,,,,2,,   ,,,3',
                ['1', '2', '3']
            ],
        ];
    }

}
