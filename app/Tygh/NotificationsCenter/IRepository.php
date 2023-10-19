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
 * Interface IRepository describes class that fetches, saves and removes Notifications.
 *
 * @package Tygh\NotificationsCenter
 */
interface IRepository
{
    /**
     * Finds notifications by search parameters.
     *
     * @param array $params         Search parameters
     * @param int   $items_per_page Amount of items per page
     *
     * @return \Tygh\NotificationsCenter\Notification[]
     */
    public function find(array $params = [], $items_per_page = 0);

    /**
     * Counts amount of notifications that match criteria.
     *
     * @param array $params Search parameters
     *
     * @return int
     */
    public function getCount(array $params = []);

    /**
     * Counts amount of notifications that match criteria and groups them by criteria value.
     *
     * @param array  $params   Search parameters
     *
     * @return int[]
     */
    public function getCountByGroup(array $params);

    /**
     * Creates or updates notification.
     *
     * @param \Tygh\NotificationsCenter\Notification $notification
     *
     * @return \Tygh\Common\OperationResult
     */
    public function save(Notification $notification);

    /**
     * Deletes a notification.
     *
     * @param \Tygh\NotificationsCenter\Notification $notification
     *
     * @return \Tygh\Common\OperationResult
     */
    public function delete(Notification $notification);

    /**
     * Bulk update notifications by search parameters.
     *
     * @param Notification                        $notification_template Notification template
     * @param array<string>                       $update_fields         Array of fields to be updated
     * @param array<int|string|array<int|string>> $params                Search parameters
     *
     * @return \PDOStatement|bool|int|\mysqli_result
     */
    public function bulkUpdate(Notification $notification_template, array $update_fields = [], array $params = []);
}
