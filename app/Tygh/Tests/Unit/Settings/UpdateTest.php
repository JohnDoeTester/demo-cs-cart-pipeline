<?php

namespace Tygh;

function fn_allowed_for($edition)
{
    return $edition === 'ULTIMATE';
}

function fn_get_edition_acronym($edition)
{
    return 'ult';
}

namespace Tygh\Tests\Unit\Settings;

use ReflectionClass;
use Tygh\Database\Connection;
use Tygh\Settings;
use Tygh\Tests\Unit\ATestCase;

class UpdateTest extends ATestCase
{
    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $db;

    public function setUp(): void
    {
        $this->db = $this->createMock(Connection::class);
        define('PRODUCT_EDITION', 'ULTIMATE');
    }

    public function invokeMethod(&$object, $method_name, ...$parameters)
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($method_name);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * @dataProvider dpGetCompanyId
     */
    public function testGetCompanyId($params, $company_id, $expected)
    {
        $params['db'] = $this->db;
        $settings = Settings::instance($params);
        $actual = $this->invokeMethod($settings, 'getCompanyId', $company_id);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dpGetStorefrontId
     */
    public function testGetStorefrontId($params, $company_id, $storefront_id, $expected)
    {
        $params['db'] = $this->db;
        $settings = Settings::instance($params);
        $actual = $this->invokeMethod($settings, 'getStorefrontId', $company_id, $storefront_id);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dpGetValueSelectionJoinTables
     */
    public function testGetValueSelectionJoinTables($params, $company_id, $storefront_id, $expected)
    {
        $params['db'] = $this->db;
        $settings = Settings::instance($params);
        $actual = $this->invokeMethod($settings, 'getValueSelectionJoinTables', $company_id, $storefront_id);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dpGetValueSelectionCriteria
     */
    public function testGetValueSelectionCriteria($params, $company_id, $storefront_id, $expected)
    {
        $params['db'] = $this->db;
        $settings = Settings::instance($params);
        $actual = $this->invokeMethod($settings, 'getValueSelectionCriteria', $company_id, $storefront_id);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dpGetUpdateValueData
     */
    public function testGetUpdateValueData(
        $params,
        $object_id,
        $value,
        $edition_types,
        $company_id,
        $storefront_id,
        $expected
    ) {
        $params['db'] = $this->db;
        $settings = Settings::instance($params);
        $actual = $this->invokeMethod(
            $settings,
            'getUpdateValueData',
            $object_id,
            $value,
            $edition_types,
            $company_id,
            $storefront_id
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dpAreOverriddenValuesSupportedByEdition
     */
    public function testAreOverriddenValuesSupportedByEdition($params, $edition_types, $expected)
    {
        $params['db'] = $this->db;
        $settings = Settings::instance($params);
        $actual = $settings->areOverriddenValuesSupportedByEdition($edition_types);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dpCheckPermissionCompanyId
     */
    public function testCheckPermissionCompanyId($section_id, $company_id, $expected)
    {
        switch ($section_id) {
            case 'Storefront':
                $this->db->method('getRow')->willReturn(['edition_type' => 'ROOT,STOREFRONT']);
                break;
            case 'Vendor':
                $this->db->method('getRow')->willReturn(['edition_type' => 'ROOT,VENDOR']);
                break;
            case 'Root':
                $this->db->method('getRow')->willReturn(['edition_type' => 'ROOT']);
                break;
        }

        $settings = Settings::instance([
            'company_id'               => 1,
            'storefront_id'            => 1,
            'has_multiple_storefronts' => true,
            'area'                     => 'A',
            'db'                       => $this->db,
        ]);

        $actual = $settings->checkPermissionCompanyId($section_id, $company_id);

        $this->assertEquals($expected, $actual);
    }

    public function dpGetCompanyId()
    {
        return [
            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'C', 'has_multiple_storefronts' => false],
                0,
                1,
            ],
            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'C', 'has_multiple_storefronts' => false],
                2,
                1,
            ],
            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'C', 'has_multiple_storefronts' => false],
                0,
                0,
            ],
            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'C', 'has_multiple_storefronts' => false],
                1,
                1,
            ],
            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'C', 'has_multiple_storefronts' => true],
                0,
                1,
            ],
            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'C', 'has_multiple_storefronts' => true],
                2,
                1,
            ],
            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'C', 'has_multiple_storefronts' => true],
                0,
                0,
            ],
            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'C', 'has_multiple_storefronts' => true],
                1,
                1,
            ],
        ];
    }

    public function dpGetStorefrontId()
    {
        return [
            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'C', 'has_multiple_storefronts' => false],
                0,
                0,
                1,
            ],
            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'C', 'has_multiple_storefronts' => false],
                0,
                2,
                1,
            ],
            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'C', 'has_multiple_storefronts' => false],
                0,
                0,
                0,
            ],
            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'C', 'has_multiple_storefronts' => false],
                1,
                1,
                1,
            ],
            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'C', 'has_multiple_storefronts' => true],
                0,
                0,
                1,
            ],
            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'C', 'has_multiple_storefronts' => true],
                0,
                2,
                1,
            ],
            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'C', 'has_multiple_storefronts' => true],
                0,
                0,
                0,
            ],
            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'C', 'has_multiple_storefronts' => true],
                1,
                1,
                1,
            ],
        ];
    }

    public function dpGetValueSelectionJoinTables()
    {
        $params = ['company_id' => 0, 'storefront_id' => 0, 'area' => 'C', 'has_multiple_storefronts' => false];

        return [
            [
                $params,
                0,
                0,
                [],
            ],

            [
                $params,
                1,
                0,
                [
                    'company_values' => null,
                ],
            ],

            [
                $params,
                0,
                1,
                [
                    'storefront_values' => null,
                ],
            ],

            [
                $params,
                1,
                1,
                [
                    'company_values'    => null,
                    'storefront_values' => null,
                ],
            ],
        ];
    }

    public function dpGetValueSelectionCriteria()
    {
        $params = ['company_id' => 0, 'storefront_id' => 0, 'area' => 'C', 'has_multiple_storefronts' => false];

        $params = ['company_id' => 0, 'storefront_id' => 0, 'area' => 'C', 'has_multiple_storefronts' => false];

        return [
            [
                $params,
                0,
                0,
                '?:settings_objects.value AS value',
            ],

            [
                $params,
                1,
                0,
                '(CASE WHEN company_values.object_id IS NOT NULL THEN company_values.value ELSE ?:settings_objects.value END) AS value',
            ],

            [
                $params,
                0,
                1,
                '(CASE WHEN storefront_values.object_id IS NOT NULL THEN storefront_values.value ELSE ?:settings_objects.value END) AS value',
            ],

            [
                $params,
                1,
                1,
                '(CASE WHEN company_values.object_id IS NOT NULL THEN company_values.value'
                . ' WHEN storefront_values.object_id IS NOT NULL THEN storefront_values.value'
                . ' ELSE ?:settings_objects.value END) AS value',
            ],
        ];
    }

    public function dpGetUpdateValueData()
    {
        return [
            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'A', 'has_multiple_storefronts' => true],
                5,
                null,
                ['ROOT', 'STOREFRONT'],
                1,
                1,
                ['object_id' => 5, 'value' => null, 'company_id' => 1, 'storefront_id' => 1],
            ],

            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'A', 'has_multiple_storefronts' => true],
                5,
                null,
                ['ROOT', 'STOREFRONT'],
                0,
                0,
                ['object_id' => 5, 'value' => null],
            ],

            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'A', 'has_multiple_storefronts' => true],
                5,
                null,
                ['ROOT', 'STOREFRONT'],
                1,
                0,
                ['object_id' => 5, 'value' => null],
            ],

            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'A', 'has_multiple_storefronts' => false],
                5,
                null,
                ['ROOT', 'STOREFRONT'],
                1,
                1,
                ['object_id' => 5, 'value' => null],
            ],
        ];
    }

    public function dpAreOverriddenValuesSupportedByEdition()
    {
        return [
            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'A', 'has_multiple_storefronts' => false],
                'ROOT',
                false,
            ],

            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'A', 'has_multiple_storefronts' => false],
                'ROOT,ULT:VENDOR',
                false,
            ],

            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'A', 'has_multiple_storefronts' => false],
                'ROOT',
                false,
            ],

            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'A', 'has_multiple_storefronts' => false],
                'ROOT,STOREFRONT',
                false,
            ],

            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'A', 'has_multiple_storefronts' => true],
                'ROOT',
                false,
            ],

            [
                ['company_id' => 1, 'storefront_id' => 1, 'area' => 'A', 'has_multiple_storefronts' => true],
                'ROOT,ULT:VENDOR',
                false,
            ],

            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'A', 'has_multiple_storefronts' => true],
                'ROOT,ULT:VENDOR',
                true,
            ],

            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'A', 'has_multiple_storefronts' => true],
                'ROOT,STOREFRONT',
                true,
            ],

            [
                ['company_id' => 0, 'storefront_id' => 0, 'area' => 'A', 'has_multiple_storefronts' => true],
                'ROOT,MVE:STOREFRONT',
                false,
            ],
        ];
    }

    public function dpCheckPermissionCompanyId()
    {
        return [
            [
                'Storefront',
                0,
                true,
            ],
            [
                'Storefront',
                1,
                true,
            ],
            [
                'Vendor',
                0,
                true,
            ],
            [
                'Vendor',
                1,
                true,
            ],
            [
                'Root',
                0,
                true,
            ],
            [
                'Root',
                1,
                false,
            ],
        ];
    }
}