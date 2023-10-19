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

$schema['call_requests'] = array(
    'params'                => array(
        'fields_list' => array('phone', 'name'),
    ),
    'collect_data_callback' => function ($params) {
        $call_requests = array();

        if (!empty($params['email'])) {
            list($call_requests) = fn_get_call_requests(array(
                'order_email' => $params['email'],
            ));
        }

        return $call_requests;
    },
    'update_data_callback' => function ($call_requests) {
        foreach ((array) $call_requests as $request) {
            if (isset($request['request_id'])) {
                fn_update_call_request($request, $request['request_id']);
            }
        }
    },
);

return $schema;
