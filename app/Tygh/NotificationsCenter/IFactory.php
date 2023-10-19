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

namespace Tygh\NotificationsCenter;

/**
 * Interface IFactory describes class that creates Notification builders.
 *
 * @package Tygh\NotificationsCenter
 */
interface IFactory
{
    /**
     * Creates on-site notification from its data.
     *
     * @param array $data
     *
     * @return \Tygh\NotificationsCenter\Notification
     */
    public function fromArray(array $data);

    /**
     * Gets builder to create on-site notifications.
     *
     * @param $type
     *
     * @return \Tygh\NotificationsCenter\NotificationBuilders\INotificationBuilder
     */
    public function getNotificationBuilder($type);
}
