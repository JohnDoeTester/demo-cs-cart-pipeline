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

use Tygh\NotificationsCenter\IFactory;

/**
 * Class DefaultNotificationBulder builds on-site notifications from their data.
 *
 * @package Tygh\NotificationsCenter\NotificationBuilders
 */
class DefaultNotificationBulder implements INotificationBuilder
{
    /**
     * @var \Tygh\NotificationsCenter\IFactory
     */
    protected $factory;

    public function __construct(IFactory $factory)
    {
        $this->factory = $factory;
    }

    public function createNotification($params, $area, $lang_code)
    {
        return $this->factory->fromArray($params);
    }
}
