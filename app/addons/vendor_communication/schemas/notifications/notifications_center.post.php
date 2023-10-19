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

$schema[NotificationsCenter::SECTION_COMMUNICATION] = [
    'section'      => NotificationsCenter::SECTION_COMMUNICATION,
    'section_name' => __('notifications_center.section.communication'),
    'tags'         => [
        NotificationsCenter::TAG_MESSAGES => [
            'tag'      => NotificationsCenter::TAG_MESSAGES,
            'tag_name' => __('notifications_center.tag.messages'),
        ],
    ],
];

return $schema;
