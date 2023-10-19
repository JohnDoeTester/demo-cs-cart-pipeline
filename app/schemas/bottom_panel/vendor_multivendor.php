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

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

$schema = include_once(__DIR__ . '/admin.php');

$schema['companies.update'] = [
    'from' => [
        'dispatch' => 'companies.update',
        'company_id'
    ],
    'to_customer' => [
        'dispatch' => 'companies.view',
        'company_id' => '%company_id%'
    ]
];

$schema['companies.manage'] = [
    'from' => [
        'dispatch' => 'companies.manage',
    ],
    'to_customer' => [
        'dispatch' => 'companies.catalog'
    ]
];

/** @var array<string, string> $schema */
$schema['products.manage'] = [
    'from' => [
        'dispatch' => 'products.manage'
    ],
    'to_customer' => [
        'dispatch' => 'companies.products',
        'company_id' => Registry::get('runtime.company_id')
    ]
];

$schema['products.manage&cid'] = [
    'from' => [
        'dispatch' => 'products.manage',
        'cid'
    ],
    'to_customer' => [
        'dispatch' => 'companies.products',
        'category_id' => '%cid%',
        'company_id' => Registry::get('runtime.company_id')
    ]
];

return $schema;
