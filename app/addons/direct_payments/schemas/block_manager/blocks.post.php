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

if (isset($schema['cart_content'])) {
    $schema['cart_content']['content'] = [
        'cart' => [
            'type'     => 'function',
            'function' => ['fn_direct_payments_get_mini_cart'],
        ],
    ];

    unset($schema['cart_content']['cache']);
}

$schema['carts_summary'] = [
    'templates' => [
        'addons/direct_payments/blocks/carts_summary.tpl' => [],
    ],
    'wrappers'  => 'blocks/wrappers',
];

return $schema;
