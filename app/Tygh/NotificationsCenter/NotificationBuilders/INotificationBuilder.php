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

namespace Tygh\NotificationsCenter\NotificationBuilders;

/**
 * Interface INotificationBuilder describes the class responsible for building an on-site notification from the
 * parameters.
 *
 * @package Tygh\NotificationsCenter\NotificationBuilders
 */
interface INotificationBuilder
{
    /**
     * @param array  $params
     * @param string $area
     * @param string $lang_code
     *
     * @return \Tygh\NotificationsCenter\Notification
     */
    public function createNotification($params, $area, $lang_code);
}
