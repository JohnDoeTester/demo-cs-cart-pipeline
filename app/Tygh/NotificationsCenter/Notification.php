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
 * Class Notification represents a notification of the Notification center.
 *
 * @package Tygh\NotificationsCenter
 */
class Notification
{
    /**
     * @var int
     */
    public $notification_id;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $severity;

    /**
     * @var string
     */
    public $section;

    /**
     * @var string
     */
    public $tag;

    /**
     * @var string
     */
    public $area;

    /**
     * @var string
     */
    public $action_url;

    /**
     * @var bool
     */
    public $is_read;

    /**
     * @var int
     */
    public $timestamp;

    /**
     * @var bool
     */
    public $pinned;

    /**
     * @var bool
     */
    public $remind;

    /**
     * Notification constructor.
     *
     * @param int    $notification_id
     * @param int    $user_id
     * @param string $title
     * @param string $message
     * @param string $severity
     * @param string $section
     * @param string $tag
     * @param string $area
     * @param string $action_url
     * @param bool   $is_read
     * @param int    $timestamp
     * @param bool   $pinned          Determines whether a notification should be pinned
     * @param bool   $remind          Determines if a remind notification is needed
     */
    public function __construct(
        $notification_id,
        $user_id,
        $title,
        $message,
        $severity,
        $section,
        $tag,
        $area,
        $action_url,
        $is_read,
        $timestamp,
        $pinned = false,
        $remind = false
    ) {
        $this->notification_id = (int) $notification_id;
        $this->user_id = (int) $user_id;
        $this->title = $title;
        $this->message = $message;
        $this->severity = $severity;
        $this->section = $section;
        $this->tag = $tag;
        $this->area = $area;
        $this->action_url = $action_url;
        $this->is_read = (bool) $is_read;
        $this->timestamp = (int) $timestamp;
        $this->pinned = $pinned;
        $this->remind = $remind;
    }

    /**
     * Converts a notification to an array.
     *
     * @param bool $get_id Whether to get notification ID.
     *
     * @return array
     */
    public function toArray($get_id = true)
    {
        $notification_data = [
            'user_id'      => (int) $this->user_id,
            'title'        => $this->title,
            'message'      => $this->message,
            'severity'     => $this->severity,
            'section'      => $this->section,
            'tag'          => $this->tag,
            'area'         => $this->area,
            'action_url'   => $this->action_url,
            'is_read'      => (int) $this->is_read,
            'timestamp'    => (int) $this->timestamp,
            'pinned'       => (int) $this->pinned,
            'remind'       => (int) $this->remind
        ];

        if ($get_id) {
            $notification_data['notification_id'] = (int) $this->notification_id;
        }

        return $notification_data;
    }
}
