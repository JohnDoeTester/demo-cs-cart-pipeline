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

namespace Tygh\Addons\MobileApp\Notifications;

use Tygh\Exceptions\DeveloperException;

class Factory
{
    /**
     * Builds plaform-specific notification.
     *
     * @param string $plaform       Device platform
     * @param string $title         Notification title
     * @param string $message       Message
     * @param string $target_screen Target screen to open when clicking on the notification
     *
     * @throws \Tygh\Exceptions\DeveloperException When device type is not supported
     *
     * @return \Tygh\Addons\MobileApp\Notifications\INotification Notification
     */
    public function get($plaform, $title, $message, $target_screen = '')
    {
        $notification_handler = $this->getHandlerClassName($plaform);

        if (!class_exists($notification_handler)) {
            throw new DeveloperException('Unknown device platform');
        }

        /** @var \Tygh\Addons\MobileApp\Notifications\INotification $notification */
        $notification = new $notification_handler;

        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setTargetScreen($target_screen);

        return $notification;
    }

    /**
     * Provides name of the plafrom-specific notification class.
     *
     * @param string $type Platform
     *
     * @return string FQN of notification handler class
     */
    protected function getHandlerClassName($type)
    {
        $type = ucfirst(fn_strtolower($type));

        $fqn = '\\Tygh\\Addons\\MobileApp\\Notifications\\' . $type . 'Notification';

        return $fqn;
    }
}