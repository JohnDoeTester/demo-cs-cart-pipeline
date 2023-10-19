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

use Tygh\Registry;

/**
 * Class DataValue
 *
 * @package Tygh\Notifications
 */
class DataValue
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed
     */
    protected $default_value;

    /**
     * DataValue constructor.
     *
     * @param string $key
     * @param mixed  $default_value
     */
    public function __construct($key, $default_value = null)
    {
        $this->key = $key;
        $this->default_value = $default_value;
    }

    /**
     * @param string $key
     * @param mixed  $default_value
     *
     * @return \Tygh\Notifications\DataValue
     */
    public static function create($key, $default_value = null)
    {
        return new self($key, $default_value);
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->default_value;
    }
}