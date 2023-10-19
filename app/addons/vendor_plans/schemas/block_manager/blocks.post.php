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

use Tygh\Models\VendorPlan;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @var array<string, array> $schema
 */
$schema['vendor_plan_info'] = [
    'content' => [
        'vendor_plans' => [
            'type' => 'function',
            'function' => [[VendorPlan::class, 'getAvailablePlans']],
        ],
    ],
    'templates' => [
        'addons/vendor_plans/blocks/vendor_plan_info.tpl' => [],
    ],
    'wrappers' => 'blocks/wrappers',
    'cache' => [
        'request_handlers' => ['plan_id'],
        'update_handlers' => [
            'vendor_plans', 'vendor_plan_descriptions'
        ]
    ]
];

$schema['vendor_categories']['cache']['update_handlers'][] = 'vendor_plans';

return $schema;
