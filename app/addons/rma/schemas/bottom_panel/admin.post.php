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

use Tygh\Tools\Url;

$schema['rma.returns'] = [
    'from' => [
        'dispatch' => 'rma.returns',
    ],
    'to_customer' => [
        'dispatch' => 'rma.returns'
    ]
];

$schema['rma.details'] = [
    'from' => [
        'dispatch' => 'rma.details',
        'return_id'
    ],
    'to_customer' => function (Url $url) {
        $return_id = (int) $url->getQueryParam('return_id');

        $return_info = fn_get_return_info($return_id);
        $auth = Tygh::$app['session']['auth'];

        if (!empty($return_info) && $return_info['user_id'] == $auth['user_id'] && fn_is_order_allowed($return_info['order_id'], $auth)) {
            return [
                'dispatch' => 'rma.details',
                'return_id' => '%return_id%'
            ];
        } else {
            return [
                'dispatch'  => 'rma.returns'
            ];
        }
    }
];

return $schema;