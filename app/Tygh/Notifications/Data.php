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

namespace Tygh\Notifications;

/**
 * Class Data gets and stores an array of data for a notification event.
 *
 * @package Tygh\Notifications
 */
class Data
{
    protected $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function get($key, $default_value = null)
    {
        if (strpos($key, '.') === false) {
            return array_key_exists($key, $this->data) ? $this->data[$key] : $default_value;
        }

        $data = $this->data;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default_value;
            }

            $data = &$data[$segment];
        }

        return $data;
    }

    public function toArray()
    {
        return $this->data;
    }
}