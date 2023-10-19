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
    'name'        => 'Tax',
    'description' => 'Represents a tax',
    'fields'      => [
        'description'        => [
            'type'        => Type::string(),
            'description' => 'Name',
        ],
        'price_includes_tax' => [
            'type'        => Type::boolean(),
            'description' => 'Whether a tax is included into price',
        ],
        'tax_subtotal'       => [
            'type'        => Type::float(),
            'description' => 'Tax value',
        ],
    ],
];

return $schema;
