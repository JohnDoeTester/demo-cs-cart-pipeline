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
    'name'        => 'PaymentMethod',
    'description' => 'Represents a payment method',
    'fields'      => [
        'payment_id'  => [
            'type'        => Type::int(),
            'description' => 'ID',
        ],
        'payment'     => [
            'type'        => Type::string(),
            'description' => 'Name',
        ],
        'description' => [
            'type'        => Type::string(),
            'description' => 'Description',
        ],
    ],
];

return $schema;
