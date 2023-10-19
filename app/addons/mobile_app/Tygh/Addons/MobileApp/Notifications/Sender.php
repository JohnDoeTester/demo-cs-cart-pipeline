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

use Tygh\Http;

class Sender
{
    /**
     * @var \Tygh\Http $http
     */
    protected $http;

    /**
     * @var string $service_url
     */
    protected $service_url = 'https://fcm.googleapis.com/fcm/send';

    /**
     * @var array $addon_settings
     */
    protected $addon_settings;

    /**
     * @var string[] $notifications_pull
     */
    protected $notifications_pull;

    /**
     * Sender constructor.
     *
     * @param array      $addon_settings `Mobile app` add-on settings
     * @param \Tygh\Http $http           Netwowrk requestor
     */
    public function __construct(array $addon_settings, Http $http)
    {
        $this->addon_settings = $addon_settings;
        $this->http = $http;
    }

    /**
     * Puts notification into a pull.
     *
     * @param string                                             $receiver     Unique device token
     * @param \Tygh\Addons\MobileApp\Notifications\INotification $notification Configured notification
     *
     * @return string|bool Thread ID on success, false on failure
     */
    public function addNotification($receiver, INotification $notification)
    {
        $data = $this->getPayload($receiver, $notification);
        $headers = $this->getHeaders();

        $pulled_request = $this->http->mpost(
            $this->service_url,
            $data,
            array('headers' => $headers)
        );

        if ($pulled_request) {
            $this->notifications_pull[] = $pulled_request;
        }

        return $pulled_request;
    }

    /**
     * Sends current notifications pull.
     *
     * @return bool Whether notification have been sent
     */
    public function send()
    {
        $are_notification_sent = false;

        if ($this->notifications_pull) {
            $this->http->processMultiRequest();

            $are_notification_sent = true;
        }

        $this->notifications_pull = array();

        return $are_notification_sent;
    }

    /**
     * Provides headers for notifications request.
     *
     * @return string[]
     */
    protected function getHeaders()
    {
        return array(
            'Content-Type: application/json',
            'Authorization: key=' . $this->addon_settings['app_settings']['utility']['fcmApiKey'],
        );
    }

    /**
     * Provides notification request payload.
     *
     * @param string                                             $receiver     Unique device token
     * @param \Tygh\Addons\MobileApp\Notifications\INotification $notification Configured notification
     *
     * @return string
     */
    protected function getPayload($receiver, INotification $notification)
    {
        $payload = $notification->getBody();

        $payload['to'] = $receiver;

        return json_encode($payload);
    }

    /**
     * Checks whether the Mobile app add-on is set up to send notification.
     *
     * @return bool
     */
    public function isSetUp()
    {
        return !empty($this->addon_settings['app_settings']['utility']['fcmApiKey']);
    }
}