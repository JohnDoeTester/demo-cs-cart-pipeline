<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

defined('BOOTSTRAP') or die('Access denied');

// key is field in the data base, value is the property name in the Faker library
$schema = array(
    'email'          => 'safeEmail',
    'firstname'      => 'firstNameMale',
    'lastname'       => 'lastName',
    'b_firstname'    => 'firstNameMale',
    'b_lastname'     => 'lastName',
    'b_address'      => 'address',
    'b_address_2'    => 'secondaryAddress',
    'b_city'         => 'city',
    'b_country'      => 'country',
    'b_state'        => 'stateAbbr',
    'b_county'       => '',
    'b_zipcode'      => 'postcode',
    'b_phone'        => 'tollFreePhoneNumber',
    's_firstname'    => 'firstNameMale',
    's_lastname'     => 'lastName',
    's_address'      => 'address',
    's_address_2'    => 'secondaryAddress',
    's_city'         => 'city',
    's_country'      => 'country',
    's_state'        => 'stateAbbr',
    's_county'       => '',
    's_zipcode'      => 'postcode',
    's_phone'        => 'tollFreePhoneNumber',
    's_address_type' => '',
    'phone'          => 'tollFreePhoneNumber',
    'fax'            => '',
    'url'            => 'url',
    'ip_address'     => 'ipv4',
    'b_state_descr'  => '',
    's_state_descr'  => '',
    'address'        => 'address',
    'address_2'      => 'secondaryAddress',
    'city'           => 'city',
    'country'        => 'country',
    'state'          => 'stateAbbr',
    'county'         => '',
    'zipcode'        => 'postcode',
    'country_descr'  => '',
    'state_descr'    => '',
    'birthday'       => '',
    'user_login'     => 'userName',
    'name'           => 'name',
);

return $schema;
