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

return [
    'orders_data'           => [ // TODO: implement no user_id logic
        'params'                => [
            'fields_list'          => [
                'email',
                'firstname',
                'lastname',
                'b_firstname',
                'b_lastname',
                'b_address',
                'b_address_2',
                'b_city',
                'b_country',
                'b_state',
                'b_county',
                'b_zipcode',
                'b_phone',
                's_firstname',
                's_lastname',
                's_address',
                's_address_2',
                's_city',
                's_county',
                's_state',
                's_country',
                's_zipcode',
                's_phone',
                's_address_type',
                'phone',
                'fax',
                'url',
                'ip_address',
                'b_state_descr',
                's_state_descr',
                'address',
                'address_2',
                'city',
                'state',
                'country',
                'zipcode',
                'country_descr',
                'state_descr',
            ],
            'ignore_subarray_list' => [
                'origination',
            ],
        ],
        'collect_data_callback' => function ($params) {
            $orders = [];

            if (!empty($params['user_id'])) {
                $conditions = db_quote('user_id = ?i', $params['user_id']);

                if (!empty($params['email'])) {
                    $conditions .= db_quote(' OR email = ?s', $params['email']);
                }

                $orders = db_get_hash_array('SELECT * FROM ?:orders WHERE ?p', 'order_id', $conditions);
                $order_ids = array_keys($orders);

                foreach ($orders as $order_id => $order) {
                    $orders[$order_id] = ['orders' => $order];
                }

                if ($order_ids) {
                    $orders['force_display'] = [
                        'orders_list'     => array_combine(
                            $order_ids,
                            array_map(function ($order_id) {
                                return fn_url("orders.details&order_id={$order_id}");
                            }, $order_ids)
                        ),
                        'orders_quantity' => count($order_ids),
                    ];

                    $orders_data = db_get_array('SELECT * FROM ?:order_data WHERE order_id IN (?n)', $order_ids);
                    $orders_details = db_get_array('SELECT * FROM ?:order_details WHERE order_id IN (?n)', $order_ids);

                    foreach ($orders_data as $data) {

                        if ($data['type'] == 'P') {
                            $data['data'] = fn_decrypt_text($data['data']);
                        }

                        $data['data'] = @unserialize($data['data']);
                        $order_id = $data['order_id'];
                        $orders[$order_id]['order_data'][] = $data;
                    }

                    foreach ($orders_details as $detail) {
                        $detail['extra'] = @unserialize($detail['extra']);
                        $order_id = $detail['order_id'];
                        $orders[$order_id]['order_details'][] = $detail;
                    }
                }
            }

            return $orders;
        },
        'update_data_callback'  => static function ($orders_list) {
            if (is_array($orders_list)) {

                foreach ($orders_list as $order_id => $order) {

                    if (isset($order['orders']['order_id']) && $order_id == $order['orders']['order_id']) {
                        db_replace_into('orders', $order['orders']);
                    }

                    if (isset($order['order_data']) && is_array($order['order_data'])) {
                        foreach ($order['order_data'] as $data) {

                            if (isset($data['type']) && isset($data['order_id']) && $data['order_id'] == $order_id) {
                                $data['data'] = serialize((array) $data['data']);

                                if ($data['type'] == 'P') {
                                    $data['data'] = fn_encrypt_text($data['data']);
                                }
                                db_replace_into('order_data', $data);
                            }
                        }
                    }

                    if (isset($order['order_details']) && is_array($order['order_details'])) {
                        foreach ($order['order_details'] as $detail) {

                            if (isset($detail['order_id']) && $detail['order_id'] == $order_id) {
                                $detail['extra'] = serialize((array) $detail['extra']);
                                db_replace_into('order_details', $detail);
                            }
                        }
                    }
                }
            }
        },
    ],
    'user_data'             => [
        'params'                => [
            'fields_list' => [
                'email',
                'user_login',
                'firstname',
                'lastname',
                'b_firstname',
                'b_lastname',
                'b_address',
                'b_address_2',
                'b_city',
                'b_country',
                'b_state',
                'b_county',
                'b_zipcode',
                'b_phone',
                's_firstname',
                's_lastname',
                's_address',
                's_address_2',
                's_city',
                's_county',
                's_state',
                's_country',
                's_zipcode',
                's_phone',
                's_address_type',
                'phone',
                'fax',
                'url',
                'b_state_descr',
                's_state_descr',
                'address',
                'address_2',
                'birthday',
            ],
        ],
        'collect_data_callback' => static function ($params) {
            $user_info = [];

            if (isset($params['user_id'])) {
                $user_info = fn_get_user_info((int) $params['user_id'], false);
            }

            return $user_info;
        },
        'update_data_callback'  => static function ($user_data) {
            if (!empty($user_data['user_id'])) {

                // Disable anonymous user
                $user_data['status'] = 'D';

                fn_update_user($user_data['user_id'], $user_data, Tygh::$app['session']['auth'], false, false);
            }
        },
    ],
    'user_profiles'         => [
        'params'                => [
            'fields_list' => [
                'email',
                'user_login',
                'firstname',
                'lastname',
                'b_firstname',
                'b_lastname',
                'b_address',
                'b_address_2',
                'b_city',
                'b_country',
                'b_state',
                'b_county',
                'b_zipcode',
                'b_phone',
                's_firstname',
                's_lastname',
                's_address',
                's_address_2',
                's_city',
                's_county',
                's_state',
                's_country',
                's_zipcode',
                's_phone',
                's_address_type',
                'phone',
                'fax',
                'url',
                'b_state_descr',
                's_state_descr',
                'address',
                'address_2',
                'birthday',
            ],
        ],
        'collect_data_callback' => static function ($params) {

            $user_profiles = [];
            $user_id = isset($params['user_id'])
                ? $params['user_id']
                : 0;

            if (!$user_id) {
                return $user_profiles;
            }

            $profiles_list = fn_get_user_profiles((int) $params['user_id']);

            foreach ($profiles_list as $profile) {
                $profile_id = isset($profile['profile_id'])
                    ? $profile['profile_id']
                    : 0;

                if (!$profile_id) {
                    continue;
                }

                $user_profiles[$profile_id] = fn_get_user_info((int) $params['user_id'], true, $profile_id);
            }

            return $user_profiles;
        },
        'update_data_callback'  => static function ($user_profiles) {

            foreach ((array) $user_profiles as $profile_id => $profile) {
                fn_update_user_profile($profile['user_id'], $profile);
            }

            if (!empty($user_data['user_id'])) {
                fn_update_user($user_data['user_id'], $user_data, Tygh::$app['session']['auth'], false, false);
            }
        },
    ],
    'product_subscriptions' => [
        'params'                => [
            'fields_list' => ['email'],
        ],
        'collect_data_callback' => function ($params) {
            $subscribers = [];

            if (!empty($params['email'])) {
                $subscribers = db_get_array('SELECT * FROM ?:product_subscriptions WHERE email = ?s', $params['email']);
            }

            return $subscribers;
        },
        'update_data_callback'  => static function ($subscribers) {
            if (!empty($subscribers)) {
                $subscription_ids = array_column($subscribers, 'subscription_id');

                if ($subscription_ids) {
                    db_query('DELETE FROM ?:product_subscriptions WHERE subscription_id IN (?n)', $subscription_ids);
                }
            }
        },
    ],
];
