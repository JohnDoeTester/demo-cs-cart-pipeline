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

namespace Tygh\Payments\Addons\Pingpp\Channels;

interface IChannel
{
    /**
     * IChannel constructor.
     *
     * @param array $settings
     */
    public function __construct(array $settings);

    /**
     * Provides extra data for charge request.
     *
     * @return array
     */
    public function getExtra();

    /**
     * Set notification URL used in extra.
     *
     * @param string $url
     */
    public function setNotificationUrl($url);

    /**
     * Set fail URL used in extra.
     *
     * @param string $url
     */
    public function setFailUrl($url);

    /**
     * Set cancel URL used in extra.
     *
     * @param string $url
     */
    public function setCancelUrl($url);

    /**
     * Set order number used in extra.
     *
     * @param string $number
     */
    public function setOrderNumber($number);

    /**
     * Sets custom extra field.
     *
     * @param string $key   Extra key
     * @param mixed  $value Extra value
     */
    public function setExtra($key, $value);
}