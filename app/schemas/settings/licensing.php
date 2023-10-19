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

use Tygh\Enum\NotificationSeverity;
use Tygh\NotificationsCenter\NotificationsCenter;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$schema = [
    'license_error_license_is_invalid'          => [
        'title'      => null,
        'message'    => __('licensing.license_error_license_is_invalid'),
        'severity'   => NotificationSeverity::ERROR,
        'action_url' => '',
        'section'    => NotificationsCenter::SECTION_ADMINISTRATION,
        'tag'        => NotificationsCenter::TAG_LICENSE,
        'state'      => null,
    ],
    'license_error_license_is_disabled'         => [
        'title'      => null,
        'message'    => __('licensing.license_error_license_is_disabled'),
        'severity'   => NotificationSeverity::ERROR,
        'action_url' => '',
        'section'    => NotificationsCenter::SECTION_ADMINISTRATION,
        'tag'        => NotificationsCenter::TAG_LICENSE,
        'state'      => null,
    ],
    'license_error_wrong_version'               => [
        'title'      => null,
        'message'    => null,
        'severity'   => NotificationSeverity::ERROR,
        'action_url' => '',
        'section'    => NotificationsCenter::SECTION_ADMINISTRATION,
        'tag'        => NotificationsCenter::TAG_LICENSE,
        'state'      => null,
    ],
    'license_error_unallowed_stores_exist'      => [
        'title'      => null,
        'message'    => null,
        'severity'   => NotificationSeverity::ERROR,
        'action_url' => fn_get_storefront_status_manage_url(),
        'section'    => NotificationsCenter::SECTION_ADMINISTRATION,
        'tag'        => NotificationsCenter::TAG_LICENSE,
        'state'      => null,
    ],
    'rc_msg'                                    => [
        'title'      => null,
        'message'    => null,
        'severity'   => NotificationSeverity::WARNING,
        'action_url' => '',
        'section'    => NotificationsCenter::SECTION_ADMINISTRATION,
        'tag'        => NotificationsCenter::TAG_LICENSE,
        'state'      => null,
    ],
    'marketing'                                 => [
        'title'      => null,
        'message'    => null,
        'severity'   => null,
        'action_url' => '',
        'section'    => NotificationsCenter::SECTION_ADMINISTRATION,
        'tag'        => NotificationsCenter::TAG_OTHER,
        'state'      => null,
    ],
    'survey'                                    => [
        'title'      => null,
        'message'    => null,
        'severity'   => null,
        'action_url' => '',
        'section'    => NotificationsCenter::SECTION_ADMINISTRATION,
        'tag'        => null,
        'state'      => null,
    ],
    'license_error_wrong_edition_build'         => [
        'title'      => null,
        'message'    => __(
            'license_error_wrong_edition_build',
            ['[helpdesk_url]' => Registry::get('config.resources.helpdesk_url')]
        ),
        'severity'   => NotificationSeverity::ERROR,
        'action_url' => Registry::get('config.resources.helpdesk_url'),
        'section'    => NotificationsCenter::SECTION_ADMINISTRATION,
        'tag'        => NotificationsCenter::TAG_LICENSE,
        'state'      => null,
    ],
    'license_error_wrong_edition_build_entered' => [
        'title'      => null,
        'message'    => __(
            'license_error_wrong_edition_build_entered',
            ['[helpdesk_url]' => Registry::get('config.resources.helpdesk_url')]
        ),
        'severity'   => NotificationSeverity::ERROR,
        'action_url' => '',
        'section'    => NotificationsCenter::SECTION_ADMINISTRATION,
        'tag'        => NotificationsCenter::TAG_LICENSE,
        'state'      => null,
    ],
];

return $schema;
