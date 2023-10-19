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

use Tygh\Enum\Addons\VendorCommunication\CommunicationTypes;
use Tygh\Enum\UserTypes;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */

$is_customer_communication_active = fn_vendor_communication_is_communication_type_active(
    CommunicationTypes::VENDOR_TO_CUSTOMER
);
$is_admin_communication_active = fn_vendor_communication_is_communication_type_active(
    CommunicationTypes::VENDOR_TO_ADMIN
);

if ($is_customer_communication_active || $is_admin_communication_active) {
    $schema['central']['vendor_communication'] = [
        'title' => __('vendor_communication.message_center_name'),
        'position' => 250,
    ];

    $auth = & Tygh::$app['session']['auth'];
    $enabled_communication_type_list = [];

    if ($is_customer_communication_active === true) {
        $enabled_communication_type_list[] = CommunicationTypes::VENDOR_TO_CUSTOMER;
    }
    if ($is_admin_communication_active === true) {
        $enabled_communication_type_list[] = CommunicationTypes::VENDOR_TO_ADMIN;
    }

    foreach ($enabled_communication_type_list as $communication_type) {
        if ($auth['user_type'] === UserTypes::VENDOR && $communication_type === CommunicationTypes::VENDOR_TO_ADMIN) {
            $schema['central']['vendor_communication']['items']['vendor_communication.tab_' . $communication_type . '_for_vendor_panel'] = [
                'title' => __('vendor_communication.tab_' . $communication_type . '_for_vendor_panel'),
                'root_title' => __('vendor_communication.message_center_name'),
                'href'  => 'vendor_communication.threads?communication_type=' . $communication_type,
                'position' => 200,
            ];
        } else {
            $schema['central']['vendor_communication']['items']['vendor_communication.tab_' . $communication_type] = [
                'title' => __('vendor_communication.tab_' . $communication_type),
                'root_title' => __('vendor_communication.message_center_name'),
                'href'  => 'vendor_communication.threads?communication_type=' . $communication_type,
                'position' => 200,
            ];
        }
    }
}

return $schema;
