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
use Tygh\Enum\NotificationSeverity;
use Tygh\Tools\Formatter;
use Tygh\Tygh;

/**
 * Class NotificationsCenter provides means to work with notifications in the Notifications center.
 *
 * @package Tygh\NotificationsCenter
 */
class NotificationsCenter
{
    const SECTION_ALL = 'all';
    const SECTION_ADMINISTRATION = 'administration';
    const SECTION_OTHER = 'other';
    const SECTION_PRODUCTS = 'products';
    const SECTION_COMMUNICATION = 'communication';

    const TAG_UPDATE = 'update';
    const TAG_LICENSE = 'license';
    const TAG_OTHER = 'other';
    const TAG_MESSAGES = 'messages';

    /**
     * @var \Tygh\NotificationsCenter\IRepository
     */
    protected $repository;

    /**
     * @var int
     */
    protected $default_items_per_page;

    /**
     * @var \Tygh\NotificationsCenter\IFactory
     */
    protected $notification_builder_factory;

    /**
     * @var string
     */
    protected $area;

    /**
     * @var int
     */
    protected $user_id;

    /**
     * @var string
     */
    protected $url_formatter_callback;

    /**
     * @var \Tygh\Tools\Formatter
     */
    protected $formatter;

    /**
     * @var array
     */
    protected $sections;

    /**
     * @var array
     */
    protected $tags;

    /**
     * @var int The minimum age of the notification in days after which it will be displayed.
     */
    protected $notification_age_in_days;

    /**
     * NotificationsCenter constructor.
     *
     * @param int                                   $user_id
     * @param string                                $area
     * @param \Tygh\NotificationsCenter\IRepository $repository
     * @param \Tygh\NotificationsCenter\IFactory    $notification_builder_factory
     * @param \Tygh\Tools\Formatter                 $formatter
     * @param array                                 $sections
     * @param int                                   $default_items_per_page
     * @param string                                $url_formatter_callback
     * @param int                                   $notification_age_in_days     The minimum age of the notification in days after which it will be displayed.
     */
    public function __construct(
        $user_id,
        $area,
        IRepository $repository,
        IFactory $notification_builder_factory,
        Formatter $formatter,
        array $sections,
        $default_items_per_page,
        $url_formatter_callback = 'fn_url',
        $notification_age_in_days = 30
    ) {
        $this->user_id = $user_id;
        $this->area = $area;
        $this->repository = $repository;
        $this->notification_builder_factory = $notification_builder_factory;
        $this->formatter = $formatter;
        $this->sections = $sections;
        $this->default_items_per_page = $default_items_per_page;
        $this->url_formatter_callback = $url_formatter_callback;
        $this->notification_age_in_days = $notification_age_in_days;
    }

    /**
     * Adds a notification to the Notifications center.
     *
     * @param array|\Tygh\NotificationsCenter\Notification $notification
     */
    public function add($notification)
    {
        if (!$notification instanceof Notification) {
            if (isset($notification['template']) || isset($notification['template_code'])) {
                $notification_builder = $this->notification_builder_factory->getNotificationBuilder('db_template');
            } else {
                $notification_builder = $this->notification_builder_factory->getNotificationBuilder('default');
            }

            $notification = $notification_builder->createNotification($notification, $notification['area'], $notification['language_code']);
        }

        if (!$notification instanceof Notification) {
            return;
        }

        if (!$notification->user_id) {
            $notification->user_id = $this->user_id;
        }

        $result = $this->repository->save($notification);
        if (!$result->isSuccess()) {
            $result->showNotifications();
        }
    }

    /**
     * Gets notifications for the Notifications center.
     *
     * @param array $params
     * @param int   $items_per_page
     *
     * @return \Tygh\NotificationsCenter\Notification[]
     */
    public function get(array $params = [], $items_per_page = null)
    {
        if ($items_per_page === null) {
            $items_per_page = $this->default_items_per_page;
        }

        $params['user_id'] = $this->user_id;
        $params['area'] = $this->area;

        $notifications = $this->repository->find($params, $items_per_page);

        return $notifications;
    }

    /**
     * Counts notifications for the Notifications center.
     *
     * @param array $params
     *
     * @return int
     */
    public function getCount(array $params = [])
    {
        $params['user_id'] = $this->user_id;
        $params['area'] = $this->area;

        $count = $this->repository->getCount($params);

        return $count;
    }

    /**
     * Counts notifications by group for the Notifications center.
     *
     * @param array $params
     *
     * @return int[]
     */
    public function getCountByGroup(array $params = [])
    {
        $params['user_id'] = $this->user_id;
        $params['area'] = $this->area;

        $count_by_section = $this->repository->getCountByGroup($params);

        return $count_by_section;
    }

    /**
     * Marks notifications as read.
     *
     * @param int|int[] $notification_id
     *
     * @param bool      $is_read
     *
     * @return \Tygh\Common\OperationResult
     */
    public function setRead($notification_id, $is_read = true)
    {
        $result = new OperationResult(true);

        $params = [
            'user_id'         => $this->user_id,
            'notification_id' => $notification_id,
        ];

        $notifications = $this->repository->find($params);

        foreach ($notifications as $notification) {
            $notification->is_read = $is_read;
            $save_result = $this->repository->save($notification);
            if (!$save_result->isSuccess()) {
                return $save_result;
            }
        }

        return $result;
    }

    /**
     * Marks notifications as read.
     *
     * @param int $user_id User ID
     *
     * @return \Tygh\Common\OperationResult
     */
    public function setAllAsRead($user_id)
    {
        $result = new OperationResult(true);
        $notification_template = new Notification(
            0,
            $user_id,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            true,
            0
        );

        $this->repository->bulkUpdate($notification_template, ['is_read'], ['user_id' => $user_id]);

        return $result;
    }

    /**
     * Removes notifications.
     *
     * @param int|int[] $notification_id
     *
     * @return \Tygh\Common\OperationResult
     */
    public function dismiss($notification_id)
    {
        $result = new OperationResult(true);

        $params = [
            'user_id'         => $this->user_id,
            'notification_id' => $notification_id,
        ];

        $notifications = $this->repository->find($params);

        foreach ($notifications as $notification) {
            $delete_result = $this->repository->delete($notification);
            if (!$delete_result->isSuccess()) {
                return $delete_result;
            }
        }

        return $result;
    }

    /**
     * Provides action URL for a notification.
     *
     * @param string $action_url
     * @param string $area
     *
     * @return mixed|string
     */
    public function getActionUrl($action_url, $area)
    {
        if ($action_url) {
            return call_user_func($this->url_formatter_callback, $action_url, $area);
        }

        return '';
    }

    /**
     * Prepares Notifications center data for view.
     *
     * @param \Tygh\NotificationsCenter\Notification[] $notifications
     *
     * @return array
     */
    public function buildViewData(array $notifications)
    {
        $sections = $this->groupNotificationsBySection($notifications);
        $notifications_count = 0;
        $unread_notifications_count = 0;

        if ($sections) {
            $sections = $this->buildUnreadNotificationsCount($sections);
            $notifications_count = $sections[self::SECTION_ALL]['notifications_count'];
            $unread_notifications_count = $sections[self::SECTION_ALL]['unread_notifications_count'];
            $sections = $this->buildTags($sections);
            $sections = $this->buildUniqueSections($sections);
        }

        return [
            'sections'                   => $sections,
            'notifications_count'        => $notifications_count,
            'unread_notifications_count' => $unread_notifications_count,
        ];
    }

    /**
     * Gets valid notification section or a fallback if none found.
     *
     * @param string $section
     *
     * @return string
     */
    protected function getNotificationSection($section)
    {
        if (!isset($this->sections[$section])) {
            return self::SECTION_OTHER;
        }

        return $section;
    }

    /**
     * Gets valid notification tag or a fallback if none found.
     *
     * @param string $section
     * @param string $tag
     *
     * @return string
     */
    protected function getNotificationTag($section, $tag)
    {
        if (!isset($this->sections[$section]['tags'][$tag])) {
            return self::TAG_OTHER;
        }

        return $tag;
    }

    /**
     * Adds tags in section for view data.
     *
     * @param array $sections
     *
     * @return array
     */
    public function buildTags(array $sections)
    {
        foreach ($sections as $section_id => $section) {
            $sections[$section_id]['tags'] = array_values(array_filter($section['tags'], function ($tag) {
                return !empty($tag['is_used']);
            }));
            array_walk($sections[$section_id]['tags'], function (&$tag) {
                unset($tag['is_used']);
            });
        }

        return $sections;
    }

    /**
     * Populates notification fields for view.
     *
     * @param \Tygh\NotificationsCenter\Notification $notification
     * @param string                                 $notification_section
     * @param string                                 $notification_tag
     *
     * @return array
     */
    protected function getNotificationViewData(Notification $notification, $notification_section, $notification_tag)
    {
        $notification_data = $notification->toArray();
        $notification_data['action_url'] = $this->getActionUrl($notification->action_url, $notification->area);
        $notification_data['datetime'] = $this->formatter->asDatetime($notification->timestamp);
        $notification_data['section'] = $notification_section;
        $notification_data['tag'] = $notification_tag;

        return $notification_data;
    }

    /**
     * Adds unread notifications count for sections.
     *
     * @param array $sections
     *
     * @return array
     */
    protected function buildUnreadNotificationsCount(array $sections)
    {
        $section_ids = array_column($sections, 'section');

        $count_by_section = $this->getCountByGroup(['group_by' => 'section', 'section' => $section_ids]);
        $unread_count_by_section = $this->getCountByGroup(['group_by' => 'section', 'section' => $section_ids, 'is_read' => false]);

        $sections = array_map(function ($section) use ($count_by_section, $unread_count_by_section) {
            if (isset($count_by_section[$section['section']])) {
                $section['notifications_count'] = $count_by_section[$section['section']];
            }
            if (isset($unread_count_by_section[$section['section']])) {
                $section['unread_notifications_count'] = $unread_count_by_section[$section['section']];
            }

            return $section;
        }, $sections);

        $sections[self::SECTION_ALL]['notifications_count'] = array_sum($count_by_section);
        $sections[self::SECTION_ALL]['unread_notifications_count'] = array_sum($unread_count_by_section);

        return $sections;
    }

    /**
     * Removes "All" section if it contains only duplicate notifications from the single section.
     *
     * @param array $sections
     *
     * @return array
     */
    public function buildUniqueSections(array $sections)
    {
        if (count($sections) === 2) {
            unset($sections[self::SECTION_ALL]);
        }

        $sections = array_values($sections);

        return $sections;
    }

    /**
     * @param \Tygh\NotificationsCenter\Notification[] $notifications
     *
     * @return array
     */
    public function groupNotificationsBySection(array $notifications)
    {
        $sections = $this->sections;

        foreach ($notifications as $notification) {
            $section = $this->getNotificationSection($notification->section);
            $tag = $this->getNotificationTag($section, $notification->tag);

            $notification_data = $this->getNotificationViewData($notification, $section, $tag);

            foreach ([self::SECTION_ALL, $section] as $section_id) {
                if (!isset($sections[$section_id]['notifications'])) {
                    $sections[$section_id]['notifications'] = [];
                }

                $sections[$section_id]['notifications'][] = $notification_data;

                if (isset($sections[$section_id]['tags'][$tag])) {
                    $sections[$section_id]['tags'][$tag]['is_used'] = true;
                    $sections[self::SECTION_ALL]['tags'][$tag] = $sections[$section_id]['tags'][$tag];
                }
            }
        }

        $sections = array_filter($sections, function ($section) {
            return !empty($section['notifications']);
        });

        return $sections;
    }

    /**
     * Shows notification in pop-up and marks it as read
     *
     * @param \Tygh\NotificationsCenter\Notification[] $notifications Notifications
     *
     * @return void
     */
    public function showNotificationsInPopup(array $notifications)
    {
        if (empty($notifications)) {
            return;
        }

        /** @var \Tygh\SmartyEngine\Core $view */
        $view = Tygh::$app['view'];

        /** @var \Tygh\NotificationsCenter\Notification $notification */
        foreach ($notifications as $notification) {
            $view->assign('notification_id', $notification->notification_id);
            $view->assign('message_html', $notification->message);
            $message = $view->fetch('components/notifications_center/popup_notification_wrapper.tpl');

            fn_set_notification(
                NotificationSeverity::SUCCESS,
                $notification->title,
                $message,
                'S'
            );
        }
    }

    /**
     * Reminds about notifications. Shows notifications older than the param.
     *
     * @return void
     */
    public function remind()
    {
        /** @var \Tygh\NotificationsCenter\Notification[] $notifications */
        $notifications = $this->repository->find(
            [
                'remind' => true,
                'timestamp_before' => TIME - ($this->notification_age_in_days * 24 * 60 * 60)
            ]
        );

        if (empty($notifications)) {
            return;
        }

        $this->showNotificationsInPopup($notifications);

        /** @var \Tygh\NotificationsCenter\Notification $notification */
        foreach ($notifications as $notification) {
            $notification->timestamp = TIME;
            $notification->is_read = true;
            $this->repository->save($notification);
        }
    }
}
