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

$schema['newsletters'] = array(
    'params'                => array(
        'fields_list' => array('email'),
    ),
    'collect_data_callback' => function ($params) {
        if (!empty($params['email'])) {
            list($subscribers) = (array) fn_get_subscribers(array('email' => $params['email']));
            return current($subscribers);
        }
    },
    'update_data_callback' => function ($subscriber) {
        if (isset($subscriber['subscriber_id'])) {
            fn_delete_subscribers(array($subscriber['subscriber_id']));
        }
    },
);

return $schema;

