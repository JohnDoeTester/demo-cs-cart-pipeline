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

use Tygh\Common\OperationResult;
use Tygh\Database\Connection;

/**
 * Class Repository fetches, saves and removes Notifications. The Repository saves notifications in the store database.
 *
 * @package Tygh\NotificationsCenter
 */
class Repository implements IRepository
{
    /**
     * @var \Tygh\Database\Connection
     */
    protected $db;

    /**
     * @var \Tygh\NotificationsCenter\IFactory
     */
    protected $factory;

    public function __construct(Connection $db, IFactory $factory)
    {
        $this->db = $db;
        $this->factory = $factory;
    }

    /**
     * Finds notifications by search parameters.
     *
     * @param array $params         Search parameters
     * @param int   $items_per_page Amount of items per page
     *
     * @return \Tygh\NotificationsCenter\Notification[]
     *
     * @psalm-suppress InvalidReturnType
     */
    public function find(array $params = [], $items_per_page = 0)
    {
        $params = $this->populateDefaultFindParameters($params);

        $fields = [
            '' => 'notifications.*',
        ];
        $join = $this->buildJoins($params);
        $conditions = $this->buildConditions($params);
        $order_by = $this->buildOrderBy($params);
        $group_by = $this->buildGroupBy($params);
        $having = [];
        $limit = $this->buildLimit($params, $items_per_page);

        $notifications = $this->db->getHash(
            'SELECT ?p FROM ?:notifications AS notifications ?p WHERE ?p ?p ?p ?p ?p',
            'notification_id',
            implode(',', $fields),
            implode(' ', $join),
            implode(' ', $conditions),
            $group_by ? 'GROUP BY ' . $group_by : '',
            $having ? 'HAVING ' . implode(' ', $having) : '',
            $order_by,
            $limit
        );

        foreach ($notifications as &$notification) {
            $notification = $this->factory->fromArray($notification);
        }
        unset($notification);

        /** @psalm-suppress InvalidReturnStatement */
        return $notifications;
    }

    /**
     * Counts amount of notifications that match criteria.
     *
     * @param array $params Search parameters
     *
     * @return int
     */
    public function getCount(array $params = [])
    {
        $params = $this->populateDefaultFindParameters($params);

        $fields = [
            'count' => 'COUNT(*) AS count',
        ];
        $join = $this->buildJoins($params);
        $conditions = $this->buildConditions($params);

        $count = (int) $this->db->getField(
            'SELECT ?p FROM ?:notifications AS notifications ?p WHERE ?p',
            implode(',', $fields),
            implode(' ', $join),
            implode(' ', $conditions)
        );

        return $count;
    }

    /**
     * Counts amount of notifications that match criteria and groups them by criteria value.
     *
     * @param array  $params   Search parameters
     * @param string $group_by Group criteria
     *
     * @return int[]
     *
     * @psalm-suppress InvalidReturnType
     */
    public function getCountByGroup(array $params)
    {
        $params = $this->populateDefaultFindParameters($params);

        $join = $this->buildJoins($params);
        $conditions = $this->buildConditions($params);
        $group_by = $this->buildGroupBy($params);
        $fields = [
            $group_by => "{$group_by} AS group_value",
            'count'   => 'COUNT(*) AS count',
        ];

        $counts = $this->db->getSingleHash('SELECT ?p FROM ?:notifications AS notifications ?p WHERE ?p GROUP BY ?p',
            ['group_value', 'count'],
            implode(',', $fields),
            implode(' ', $join),
            implode(' ', $conditions),
            $group_by
        );

        foreach ($counts as &$count) {
            $count = (int) $count;
        }
        unset($count);

        /** @psalm-suppress InvalidReturnStatement */
        return $counts;
    }

    /**
     * Creates or updates notification.
     *
     * @param \Tygh\NotificationsCenter\Notification $notification
     *
     * @return \Tygh\Common\OperationResult
     */
    public function save(Notification $notification)
    {
        $notification_data = $notification->toArray(false);

        $result = new OperationResult(true);

        $notification_id = $this->updateNotification($notification->notification_id, $notification_data);

        $result->setData($notification_id);

        return $result;
    }

    /**
     * Deletes a notification.
     *
     * @param \Tygh\NotificationsCenter\Notification $notification
     *
     * @return \Tygh\Common\OperationResult
     */
    public function delete(Notification $notification)
    {
        $result = new OperationResult(true);

        $this->deleteNotification($notification->notification_id);

        return $result;
    }

    /**
     * Bulk update notifications by search parameters.
     *
     * @param Notification                        $notification_template Notification template
     * @param array<string>                       $update_fields         Array of fields to be updated
     * @param array<int|string|array<int|string>> $params                Search parameters
     *
     * @return \PDOStatement|bool|int|\mysqli_result
     *
     * @psalm-suppress InvalidReturnType
     */
    public function bulkUpdate(Notification $notification_template, array $update_fields = [], array $params = [])
    {
        $params = $this->populateDefaultFindParameters($params);
        $notification_data = $notification_template->toArray(false);

        foreach (array_keys($notification_data) as $field_name) {
            if (in_array($field_name, $update_fields)) {
                continue;
            }

            unset($notification_data[$field_name]);
        }

        $join = $this->buildJoins($params);
        $conditions = $this->buildConditions($params);

        return $this->db->query(
            'UPDATE ?:notifications notifications SET ?u'
            . ' ?p WHERE ?p',
            $notification_data,
            implode(' ', $join),
            implode(' ', $conditions)
        );
    }

    protected function updateNotification($notification_id, array $notification_data)
    {
        if ($notification_id) {
            $notification_data['notification_id'] = $notification_id;
        }

        $this->db->replaceInto('notifications', $notification_data);
        if (!$notification_id) {
            $notification_id = $this->db->getInsertId();
        }

        return $notification_id;
    }

    /**
     * Populates default notifications search parameters.
     *
     * @param array $params Search parameters
     *
     * @return array
     */
    protected function populateDefaultFindParameters(array $params)
    {
        $populated_params = array_merge([
            'notification_id'  => null,
            'user_id'          => null,
            'title'            => null,
            'message'          => null,
            'severity'         => null,
            'section'          => null,
            'tag'              => null,
            'area'             => null,
            'is_read'          => null,
            'timestamp'        => null,
            'timestamp_before' => null,
            'timestamp_after'  => null,
            'remind'           => null,
            'sort_by'          => 'timestamp',
            'sort_order'       => 'desc',
            'page'             => 1,
            'group_by'         => 'notification_id',
        ], $params);

        return $populated_params;
    }

    /**
     * Provides WHERE part data of an SQL query for notifications search.
     *
     * @param array $params Search parameters
     *
     * @return string[]
     */
    protected function buildConditions(array $params)
    {
        $conditions = [
            '' => '1 = 1',
        ];

        if ($params['notification_id']) {
            $conditions['notification_id'] = $this->db->quote(
                'AND notifications.notification_id IN (?n)',
                (array) $params['notification_id']
            );
        }
        if ($params['user_id']) {
            $conditions['user_id'] = $this->db->quote(
                'AND notifications.user_id = ?i',
                $params['user_id']
            );
        }
        if ($params['area']) {
            $conditions['area'] = $this->db->quote(
                'AND notifications.area = ?s',
                $params['area']
            );
        }
        if ($params['section']) {
            $conditions['section'] = $this->db->quote(
                'AND notifications.section IN (?a)',
                $params['section']
            );
        }
        if ($params['tag']) {
            $conditions['tag'] = $this->db->quote(
                'AND notifications.tag IN (?a)',
                $params['tag']
            );
        }
        if ($params['severity']) {
            $conditions['severity'] = $this->db->quote(
                'AND notifications.severity IN (?a)',
                $params['severity']
            );
        }
        if ($params['title']) {
            $conditions['title'] = $this->db->quote(
                'AND notifications.title LIKE ?l',
                "%{$params['title']}%"
            );
        }
        if ($params['message']) {
            $conditions['message'] = $this->db->quote(
                'AND notifications.message LIKE ?l',
                "%{$params['message']}%"
            );
        }
        if ($params['is_read'] !== null) {
            $conditions['is_read'] = $this->db->quote(
                'AND notifications.is_read = ?i',
                (int) $params['is_read']
            );
        }
        if ($params['remind'] !== null) {
            $conditions['remind'] = $this->db->quote(
                'AND notifications.remind = ?i',
                (int) $params['remind']
            );
        }
        if ($params['timestamp']) {
            $conditions['timestamp'] = $this->db->quote(
                'AND notifications.timestamp = ?i',
                $params['timestamp']
            );
        }
        if ($params['timestamp_before']) {
            $conditions['timestamp_before'] = $this->db->quote(
                'AND notifications.timestamp <= ?i',
                $params['timestamp_before']
            );
        }
        if ($params['timestamp_after']) {
            $conditions['timestamp_after'] = $this->db->quote(
                'AND notifications.timestamp >= ?i',
                $params['timestamp_after']
            );
        }

        return $conditions;
    }

    /**
     * Provides JOIN part data of an SQL query for notifications search.
     *
     * @param array $params Search parameters
     *
     * @return string[]
     */
    protected function buildJoins(array $params)
    {
        $joins = [];

        return $joins;
    }

    /**
     * Provides ORDER BY part data of an SQL query for notifications search.
     *
     * @param array $params Search parameters
     *
     * @return string
     */
    protected function buildOrderBy(array $params)
    {
        $sortings = [
            'notification_id' => 'notifications.notification_id',
            'title'           => 'notifications.title',
            'message'         => 'notifications.message',
            'user_id'         => 'notifications.user_id',
            'is_read'         => 'notifications.is_read',
            'timestamp'       => 'notifications.timestamp',
            'section'         => 'notifications.section',
            'severity'        => 'notifications.severity',
            'pinned_timestamp' => [
                'notifications.pinned',
                'notifications.timestamp',
            ]
        ];

        $order_by = db_sort($params, $sortings, 'timestamp', 'desc');

        return $order_by;
    }

    /**
     * Provides LIMIT part data of an SQL query for notifications search.
     *
     * @param array $params         Search parameters
     * @param int   $items_per_page Items per page
     *
     * @return string[]
     */
    protected function buildLimit(array $params, $items_per_page = 0)
    {
        $limit = '';
        if ($items_per_page !== 0) {
            $limit = db_paginate($params['page'], $items_per_page);
        }

        return $limit;
    }

    /**
     * Provides GROUP BY part data of an SQL query for notifications search.
     *
     * @param array $params Search parameters
     *
     * @return string
     */
    protected function buildGroupBy(array $params)
    {
        $grouppings = [
            'notification_id' => 'notifications.notification_id',
            'section'         => 'notifications.section',
            'none'            => '',
        ];

        if (isset($grouppings[$params['group_by']])) {
            return $grouppings[$params['group_by']];
        }

        return '';
    }

    /**
     * Deletes notification.
     *
     * @param int $notification_id
     */
    protected function deleteNotification($notification_id)
    {
        $this->db->query('DELETE FROM ?:notifications WHERE notification_id = ?i', $notification_id);
    }
}
