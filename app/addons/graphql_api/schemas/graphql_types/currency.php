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

use Tygh\Addons\GraphqlApi\Type;

$schema = [
    'name'        => 'Currency',
    'description' => 'Represents a currency',
    'fields'      => [
        'currency_code'       => [
            'type'        => Type::string(),
            'description' => 'Code',
        ],
        'description'         => [
            'type'        => Type::string(),
            'description' => 'Name',
        ],
        'is_primary'          => [
            'type'        => Type::boolean(),
            'description' => 'Whether a currency is primary',
        ],
        'symbol'              => [
            'type'        => Type::string(),
            'description' => 'Currency symbol',
        ],
        'after'               => [
            'type'        => Type::boolean(),
            'description' => 'Whether a currency symbol must be displayed after the sum',
        ],
        'decimals'            => [
            'type'        => Type::int(),
            'description' => 'Number of digits after the decimal sign.',
        ],
        'decimals_separator'  => [
            'type'        => Type::string(),
            'description' => 'Decimal separator',
        ],
        'thousands_separator' => [
            'type'        => Type::string(),
            'description' => 'Thousand separator',
        ],
    ],
];

return $schema;
