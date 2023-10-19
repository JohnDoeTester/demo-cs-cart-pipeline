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

$schema = [
    'checkout' => [
        'id' => 'checkout',
        'name' => 'payment_processors.category.checkout',
        'position' => 10,
        'criteria' => [
            'type' => ['B', 'C'],
        ],
    ],
    'gateway' => [
        'id' => 'gateway',
        'name' => 'payment_processors.category.gateway',
        'position' => 20,
        'criteria' => [
            'type' => ['P'],
        ]
    ],
];

return $schema;