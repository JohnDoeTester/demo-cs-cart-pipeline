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

use Tygh\NotificationsCenter\NotificationsCenter;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Describes an interface of the Notifications center.
 * Each item of this schema represent a single tab of the Notification center.
 *
 * Structure:
 * 'section_id' => [ // Section Identifier
 *     'section'      => 'section_id', // Must match the Section Identifier
 *     'section_name' => 'Section name to be displayed on a tab in the Notifications center',
 *     'tags'         => [
 *         'tag_id'  => [ // Tag Identifier
 *             'tag'      => 'tag_id', // Must match the Tag Identifier
 *             'tag_name' => 'Tag name to be displayed in the filter in the Notifications center',
 *         ],
 *     ]
 * ],
 */
$schema = [
    NotificationsCenter::SECTION_ALL            => [
        'section'      => NotificationsCenter::SECTION_ALL,
        'section_name' => __('notifications_center.section.all'),
        'tags'         => [],
    ],
    NotificationsCenter::SECTION_ADMINISTRATION => [
        'section'      => NotificationsCenter::SECTION_ADMINISTRATION,
        'section_name' => __('notifications_center.section.administration'),
        'tags'         => [
            NotificationsCenter::TAG_UPDATE  => [
                'tag'      => NotificationsCenter::TAG_UPDATE,
                'tag_name' => __('notifications_center.tag.administration.update'),
            ],
            NotificationsCenter::TAG_LICENSE => [
                'tag'      => NotificationsCenter::TAG_LICENSE,
                'tag_name' => __('notifications_center.tag.administration.license'),
            ],
            NotificationsCenter::TAG_OTHER   => [
                'tag'      => NotificationsCenter::TAG_OTHER,
                'tag_name' => __('notifications_center.tag.other'),
            ],
        ],
    ],
    NotificationsCenter::SECTION_PRODUCTS       => [
        'section'      => NotificationsCenter::SECTION_PRODUCTS,
        'section_name' => __('notifications_center.section.products'),
        'tags'         => [
            NotificationsCenter::TAG_OTHER => [
                'tag'      => NotificationsCenter::TAG_OTHER,
                'tag_name' => __('notifications_center.tag.other'),
            ],
        ],
    ],
    NotificationsCenter::SECTION_OTHER          => [
        'section'      => NotificationsCenter::SECTION_OTHER,
        'section_name' => __('notifications_center.section.other'),
        'tags'         => [
            NotificationsCenter::TAG_OTHER => [
                'tag'      => NotificationsCenter::TAG_OTHER,
                'tag_name' => __('notifications_center.tag.other'),
            ],
        ],
    ],
];

return $schema;
