<?php

namespace Tygh\Tests\Unit\Addons\StorefrontRestApi\ProfileFields;

use Tygh\Addons\StorefrontRestApi\ProfileFields\Hydrator;
use Tygh\Tests\Unit\ATestCase;

class HydratorTest extends ATestCase
{
    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    protected $schema;

    /**
     * @param array|null $schema
     * @param array      $data
     * @param array      $expected
     *
     * @dataProvider getTestHydrate
     */
    public function testHydrate($schema, $data, $expected)
    {
        $hydrator = new Hydrator();
        if ($schema === null) {
            $schema = $this->schema;
        }

        $actual = $hydrator->hydrate($schema, $data);

        $this->assertEquals($expected, $actual);
    }

    public function getTestHydrate()
    {
        return [
            [
                [],
                [],
                [],
            ],

            // non-hydrated
            [
                null,
                [],
                [
                    'E' => [
                        'description' => '',
                        'fields'      => [
                            [
                                'field_id'    => 'email',
                                'field_type'  => 'I',
                                'field_name'  => 'email',
                                'description' => 'E-mail',
                                'is_default'  => true,
                                'required'    => true,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'password1',
                                'field_type'  => 'W',
                                'field_name'  => 'password1',
                                'description' => 'Password',
                                'required'    => false,
                                'is_default'  => true,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'password2',
                                'field_type'  => 'W',
                                'field_name'  => 'password2',
                                'description' => 'Confirm password',
                                'required'    => false,
                                'is_default'  => true,
                                'value'       => null,
                            ],
                        ],
                    ],
                    'C' => [
                        'description' => 'Contact information',
                        'fields'      => [
                            [
                                'field_id'    => '50',
                                'field_name'  => 'foo',
                                'field_type'  => 'S',
                                'is_default'  => false,
                                'description' => 'Foo',
                                'required'    => false,
                                'values'      => [
                                    1 => 'Foo',
                                    2 => 'Bar',
                                    3 => 'Baz',
                                ],
                                'value'       => null,
                            ],
                            [
                                'field_id'    => '42',
                                'field_name'  => 'bar',
                                'field_type'  => 'C',
                                'is_default'  => false,
                                'description' => 'Bar',
                                'required'    => false,
                                'value'       => null,
                            ],
                        ],
                    ],
                    'B' => [
                        'description' => 'Billing address',
                        'fields'      => [
                            [
                                'field_id'    => '51',
                                'field_name'  => 'b_bar',
                                'field_type'  => 'I',
                                'is_default'  => false,
                                'description' => 'Bar',
                                'required'    => true,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_firstname',
                                'field_name'  => 'b_firstname',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'First name',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_lastname',
                                'field_name'  => 'b_lastname',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Last name',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'email',
                                'field_name'  => 'email',
                                'field_type'  => 'E',
                                'is_default'  => true,
                                'description' => 'E-mail',
                                'required'    => true,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_phone',
                                'field_name'  => 'b_phone',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Phone',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_address',
                                'field_name'  => 'b_address',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Address',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_address_2',
                                'field_name'  => 'b_address_2',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Address',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_city',
                                'field_name'  => 'b_city',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'City',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_country',
                                'field_name'  => 'b_country',
                                'field_type'  => 'O',
                                'is_default'  => true,
                                'description' => 'Country',
                                'required'    => false,
                                'values'      => [
                                    'NL' => 'Netherlands',
                                    'US' => 'United States',
                                    'KZ' => 'Kazakhstan'
                                ],
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_state',
                                'field_name'  => 'b_state',
                                'field_type'  => 'A',
                                'is_default'  => true,
                                'description' => 'State/province',
                                'required'    => false,
                                'values'      => [
                                    'NL' => [
                                        'DR' => 'Drenthe',
                                        'FL' => 'Flevoland',
                                        'FR' => 'Friesland',
                                    ],
                                    'US' => [
                                        'AL' => 'Alabama',
                                        'AK' => 'Alaska',
                                        'AZ' => 'Arizona',
                                    ],
                                ],
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_zipcode',
                                'field_name'  => 'b_zipcode',
                                'field_type'  => 'Z',
                                'is_default'  => true,
                                'description' => 'Zip/postal code',
                                'required'    => false,
                                'value'       => null,
                            ],
                        ],
                    ],
                    'S' => [
                        'description' => 'Shipping address',
                        'fields'      => [
                            [
                                'field_id'    => '52',
                                'field_name'  => 's_bar',
                                'field_type'  => 'I',
                                'is_default'  => false,
                                'description' => 'Bar',
                                'required'    => true,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_firstname',
                                'field_name'  => 's_firstname',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'First name',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_lastname',
                                'field_name'  => 's_lastname',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Last name',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_phone',
                                'field_name'  => 's_phone',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Phone',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_address',
                                'field_name'  => 's_address',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Address',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_address_2',
                                'field_name'  => 's_address_2',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Address',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_city',
                                'field_name'  => 's_city',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'City',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_country',
                                'field_name'  => 's_country',
                                'field_type'  => 'O',
                                'is_default'  => true,
                                'description' => 'Country',
                                'required'    => false,
                                'values'      => [
                                    'NL' => 'Netherlands',
                                    'US' => 'United States',
                                    'KZ' => 'Kazakhstan'
                                ],
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_state',
                                'field_name'  => 's_state',
                                'field_type'  => 'A',
                                'is_default'  => true,
                                'description' => 'State/province',
                                'required'    => false,
                                'values'      => [
                                    'NL' => [
                                        'DR' => 'Drenthe',
                                        'FL' => 'Flevoland',
                                        'FR' => 'Friesland',
                                    ],
                                    'US' => [
                                        'AL' => 'Alabama',
                                        'AK' => 'Alaska',
                                        'AZ' => 'Arizona',
                                    ],
                                ],
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_zipcode',
                                'field_name'  => 's_zipcode',
                                'field_type'  => 'Z',
                                'is_default'  => true,
                                'description' => 'Zip/postal code',
                                'required'    => false,
                                'value'       => null,
                            ],
                        ],
                    ],
                ],
            ],

            // hydrated
            [
                null,
                [
                    'email'  => 'user@example.com',
                    'fields' => [
                        42 => 'Y',
                        51 => 2,
                        52 => 'FOOBAR',
                    ],
                ],
                [
                    'E' => [
                        'description' => '',
                        'fields'      => [
                            [
                                'field_id'    => 'email',
                                'field_type'  => 'I',
                                'field_name'  => 'email',
                                'description' => 'E-mail',
                                'is_default'  => true,
                                'required'    => true,
                                'value'       => 'user@example.com',
                            ],
                            [
                                'field_id'    => 'password1',
                                'field_type'  => 'W',
                                'field_name'  => 'password1',
                                'description' => 'Password',
                                'required'    => false,
                                'is_default'  => true,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'password2',
                                'field_type'  => 'W',
                                'field_name'  => 'password2',
                                'description' => 'Confirm password',
                                'required'    => false,
                                'is_default'  => true,
                                'value'       => null,
                            ],
                        ],
                    ],
                    'C' => [
                        'description' => 'Contact information',
                        'fields'      => [
                            [
                                'field_id'    => '50',
                                'field_name'  => 'foo',
                                'field_type'  => 'S',
                                'is_default'  => false,
                                'description' => 'Foo',
                                'required'    => false,
                                'values'      => [
                                    1 => 'Foo',
                                    2 => 'Bar',
                                    3 => 'Baz',
                                ],
                                'value'       => null,
                            ],
                            [
                                'field_id'    => '42',
                                'field_name'  => 'bar',
                                'field_type'  => 'C',
                                'is_default'  => false,
                                'description' => 'Bar',
                                'required'    => false,
                                'value'       => true,
                            ],
                        ],
                    ],
                    'B' => [
                        'description' => 'Billing address',
                        'fields'      => [
                            [
                                'field_id'    => '51',
                                'field_name'  => 'b_bar',
                                'field_type'  => 'I',
                                'is_default'  => false,
                                'description' => 'Bar',
                                'required'    => true,
                                'value'       => 2,
                            ],
                            [
                                'field_id'    => 'b_firstname',
                                'field_name'  => 'b_firstname',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'First name',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_lastname',
                                'field_name'  => 'b_lastname',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Last name',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'email',
                                'field_name'  => 'email',
                                'field_type'  => 'E',
                                'is_default'  => true,
                                'description' => 'E-mail',
                                'required'    => true,
                                'value'       => 'user@example.com',
                            ],
                            [
                                'field_id'    => 'b_phone',
                                'field_name'  => 'b_phone',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Phone',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_address',
                                'field_name'  => 'b_address',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Address',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_address_2',
                                'field_name'  => 'b_address_2',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Address',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_city',
                                'field_name'  => 'b_city',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'City',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_country',
                                'field_name'  => 'b_country',
                                'field_type'  => 'O',
                                'is_default'  => true,
                                'description' => 'Country',
                                'required'    => false,
                                'values'      => [
                                    'NL' => 'Netherlands',
                                    'US' => 'United States',
                                    'KZ' => 'Kazakhstan'
                                ],
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_state',
                                'field_name'  => 'b_state',
                                'field_type'  => 'A',
                                'is_default'  => true,
                                'description' => 'State/province',
                                'required'    => false,
                                'values'      => [
                                    'NL' => [
                                        'DR' => 'Drenthe',
                                        'FL' => 'Flevoland',
                                        'FR' => 'Friesland',
                                    ],
                                    'US' => [
                                        'AL' => 'Alabama',
                                        'AK' => 'Alaska',
                                        'AZ' => 'Arizona',
                                    ],
                                ],
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 'b_zipcode',
                                'field_name'  => 'b_zipcode',
                                'field_type'  => 'Z',
                                'is_default'  => true,
                                'description' => 'Zip/postal code',
                                'required'    => false,
                                'value'       => null,
                            ],
                        ],
                    ],
                    'S' => [
                        'description' => 'Shipping address',
                        'fields'      => [
                            [
                                'field_id'    => '52',
                                'field_name'  => 's_bar',
                                'field_type'  => 'I',
                                'is_default'  => false,
                                'description' => 'Bar',
                                'required'    => true,
                                'value'       => 'FOOBAR',
                            ],
                            [
                                'field_id'    => 's_firstname',
                                'field_name'  => 's_firstname',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'First name',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_lastname',
                                'field_name'  => 's_lastname',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Last name',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_phone',
                                'field_name'  => 's_phone',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Phone',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_address',
                                'field_name'  => 's_address',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Address',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_address_2',
                                'field_name'  => 's_address_2',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'Address',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_city',
                                'field_name'  => 's_city',
                                'field_type'  => 'I',
                                'is_default'  => true,
                                'description' => 'City',
                                'required'    => false,
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_country',
                                'field_name'  => 's_country',
                                'field_type'  => 'O',
                                'is_default'  => true,
                                'description' => 'Country',
                                'required'    => false,
                                'values'      => [
                                    'NL' => 'Netherlands',
                                    'US' => 'United States',
                                    'KZ' => 'Kazakhstan'
                                ],
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_state',
                                'field_name'  => 's_state',
                                'field_type'  => 'A',
                                'is_default'  => true,
                                'description' => 'State/province',
                                'required'    => false,
                                'values'      => [
                                    'NL' => [
                                        'DR' => 'Drenthe',
                                        'FL' => 'Flevoland',
                                        'FR' => 'Friesland',
                                    ],
                                    'US' => [
                                        'AL' => 'Alabama',
                                        'AK' => 'Alaska',
                                        'AZ' => 'Arizona',
                                    ],
                                ],
                                'value'       => null,
                            ],
                            [
                                'field_id'    => 's_zipcode',
                                'field_name'  => 's_zipcode',
                                'field_type'  => 'Z',
                                'is_default'  => true,
                                'description' => 'Zip/postal code',
                                'required'    => false,
                                'value'       => null,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function setUp(): void
    {
        $this->schema = json_decode(file_get_contents(__DIR__ . '/data/schema.json'), true);
    }
}
