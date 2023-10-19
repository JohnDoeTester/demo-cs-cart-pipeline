<?php

namespace Tygh\Tests\Unit\Addons\StorefrontRestApi\ProfileFields;

use Tygh\Addons\StorefrontRestApi\ProfileFields\Validator;
use Tygh\Tests\Unit\ATestCase;

class ValidatorTest extends ATestCase
{
    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    protected $schema;

    /**
     * @param array|null $schema
     * @param array      $data
     * @param bool       $expected_success
     * @param array      $expected_data
     *
     * @dataProvider getTestValidate
     */
    public function testValidate($schema, $data, $expected_success, $expected_data)
    {
        $validator = new Validator();
        if ($schema === null) {
            $schema = $this->schema;
        }

        $actual = $validator->validate($schema, $data);

        $this->assertEquals($expected_success, $actual->isSuccess());
        $this->assertEquals($expected_data, $actual->getData());
    }

    public function getTestValidate()
    {
        return [
            // empty schema and request
            [
                [],
                [],
                true,
                ['required' => [], 'invalid' => []],
            ],

            // default + custom fields
            [
                null,
                [
                    'email'       => 'user@example.com',
                    'b_firstname' => 'Firstname',
                    's_firstname' => 'Firstname',
                    'b_lastname'  => 'Lastname',
                    's_lastname'  => 'Lastname',
                    'b_phone'     => 'Phone',
                    's_phone'     => 'Phone',
                    'b_address'   => 'Address',
                    's_address'   => 'Address',
                    'b_address_2' => 'Address 2',
                    's_address_2' => 'Address 2',
                    'b_city'      => 'City',
                    's_city'      => 'City',
                    'b_country'   => 'US',
                    's_country'   => 'US',
                    'b_state'     => 'AK',
                    's_state'     => 'AK',
                    'b_zipcode'   => '12345',
                    's_zipcode'   => '12345',
                    'fields'      => [
                        '50' => '1',
                        '51' => 'FOOBAR',
                        '52' => 'FOOBAR',
                    ],
                ],
                true,
                ['required' => [], 'invalid' => []],
            ],

            // required fields
            [
                null,
                [
                    'email'       => 'user@example.com',
                    's_firstname' => 'Firstname',
                    's_lastname'  => 'Lastname',
                    's_phone'     => 'Phone',
                    's_address'   => 'Address',
                    's_address_2' => 'Address 2',
                    's_city'      => 'City',
                    's_country'   => 'US',
                    's_state'     => 'AK',
                    's_zipcode'   => '12345',
                    'fields'      => [
                        '51' => 'FOOBAR',
                        '52' => 'FOOBAR',
                    ],
                ],
                true,
                ['required' => [], 'invalid' => []],
            ],

            // required field - default
            [
                null,
                [
                    's_firstname' => 'Firstname',
                    's_lastname'  => 'Lastname',
                    's_phone'     => 'Phone',
                    's_address'   => 'Address',
                    's_address_2' => 'Address 2',
                    's_city'      => 'City',
                    's_country'   => 'US',
                    's_state'     => 'AK',
                    's_zipcode'   => '12345',
                    'fields'      => [
                        '51' => 'FOOBAR',
                        '52' => 'FOOBAR',
                    ],
                ],
                false,
                [
                    'required' => [
                        'email' => [
                            'is_default' => true,
                            'field_id'   => 'email',
                        ],
                    ],
                    'invalid'  => [],
                ],
            ],

            // required field - custom
            [
                null,
                [
                    'email'       => 'user@example.com',
                    's_firstname' => 'Firstname',
                    's_lastname'  => 'Lastname',
                    's_phone'     => 'Phone',
                    's_address'   => 'Address',
                    's_address_2' => 'Address 2',
                    's_city'      => 'City',
                    's_country'   => 'US',
                    's_state'     => 'AK',
                    's_zipcode'   => '12345',
                    'fields'      => [
                        '52' => 'FOOBAR',
                    ],
                ],
                false,
                [
                    'required' => [
                        '51' => [
                            'is_default' => false,
                            'field_id'   => '51',
                        ],
                    ],
                    'invalid'  => [],
                ],
            ],

            // invalid field - default
            [
                null,
                [
                    'email'       => 'user@example.com',
                    's_firstname' => 'Firstname',
                    's_lastname'  => 'Lastname',
                    's_phone'     => 'Phone',
                    's_address'   => 'Address',
                    's_address_2' => 'Address 2',
                    's_city'      => 'City',
                    's_country'   => 'MISSING_COUNTRY',
                    's_state'     => 'AK',
                    's_zipcode'   => '12345',
                    'fields'      => [
                        '50' => '1',
                        '51' => 'FOOBAR',
                        '52' => 'FOOBAR',
                    ],
                ],
                false,
                [
                    'required' => [],
                    'invalid'  => [
                        [
                            'is_default' => true,
                            'field_id'   => 's_country',
                            'value'      => 'MISSING_COUNTRY',
                            'values'     => [
                                'NL' => 'Netherlands',
                                'US' => 'United States',
                                'KZ' => 'Kazakhstan',
                            ],
                        ],
                    ],
                ],
            ],

            // invalid field - custom
            [
                null,
                [
                    'email'       => 'user@example.com',
                    'b_firstname' => 'Firstname',
                    's_firstname' => 'Firstname',
                    'b_lastname'  => 'Lastname',
                    's_lastname'  => 'Lastname',
                    'b_phone'     => 'Phone',
                    's_phone'     => 'Phone',
                    'b_address'   => 'Address',
                    's_address'   => 'Address',
                    'b_address_2' => 'Address 2',
                    's_address_2' => 'Address 2',
                    'b_city'      => 'City',
                    's_city'      => 'City',
                    'b_country'   => 'US',
                    's_country'   => 'US',
                    'b_state'     => 'AK',
                    's_state'     => 'AK',
                    'b_zipcode'   => '12345',
                    's_zipcode'   => '12345',
                    'fields'      => [
                        '50' => '5',
                        '42' => 'bazinga!',
                        '51' => 'FOOBAR',
                        '52' => 'FOOBAR',
                    ],
                ],
                false,
                [
                    'required' => [],
                    'invalid'  => [
                        [
                            'is_default' => false,
                            'field_id'   => '50',
                            'value'      => '5',
                            'values'     => [
                                '1' => 'Foo',
                                '2' => 'Bar',
                                '3' => 'Baz',
                            ],
                        ],
                        [
                            'is_default' => false,
                            'field_id'   => '42',
                            'value'      => 'bazinga!',
                            'values' => [
                                'true'  => 'Y',
                                'false' => 'N',
                            ],
                        ],
                    ],
                ],
            ],

            // state update, no country speicifed
            [
                null,
                [
                    'email'   => 'user@example.com',
                    's_state' => 'foo',
                    'b_state' => 'bar',
                    'fields'  => [
                        '51' => 'FOOBAR',
                        '52' => 'FOOBAR',
                    ],
                ],
                false,
                [
                    'required' => [
                        's_country' => [
                            'is_default' => true,
                            'field_id'   => 's_country',
                        ],
                        'b_country' => [
                            'is_default' => true,
                            'field_id'   => 'b_country',
                        ],
                    ],
                    'invalid'  => [],
                ],
            ],

            // state update, country with states, invalid state
            [
                null,
                [
                    'email'     => 'user@example.com',
                    's_state'   => 'MISSING_STATE',
                    's_country' => 'US',
                    'fields'    => [
                        '51' => 'FOOBAR',
                        '52' => 'FOOBAR',
                    ],
                ],
                false,
                [
                    'required' => [],
                    'invalid'  => [
                        [
                            'is_default' => true,
                            'field_id'   => 's_state',
                            'value'      => 'MISSING_STATE',
                            'values'     => ['AL', 'AK', 'AZ'],
                        ],
                    ],
                ],
            ],

            // state update, country with states, valid state
            [
                null,
                [
                    'email'     => 'user@example.com',
                    's_state'   => 'AL',
                    's_country' => 'US',
                    'fields'    => [
                        '51' => 'FOOBAR',
                        '52' => 'FOOBAR',
                    ],
                ],
                true,
                [
                    'required' => [],
                    'invalid'  => [],
                ],
            ],

            // state update, country with no states
            [
                null,
                [
                    'email'     => 'user@example.com',
                    's_state'   => 'Astana',
                    's_country' => 'KZ',
                    'fields'    => [
                        '51' => 'FOOBAR',
                        '52' => 'FOOBAR',
                    ],
                ],
                true,
                [
                    'required' => [],
                    'invalid'  => [],
                ],
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->schema = json_decode(file_get_contents(__DIR__ . '/data/schema.json'), true);
        $this->requireMockFunction('fn_validate_email');
    }
}
