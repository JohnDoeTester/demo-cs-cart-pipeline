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

namespace Tygh\Location;

use Tygh\Tygh;

/**
 * Class CartUserDataStorage provides a user data storage that modifies the cart object that is stored in the current session.
 *
 * @package Tygh\Location
 */
class CartUserDataStorage implements IUserDataStorage
{
    /**
     * @var array
     */
    protected $storage;

    public function __construct($storage = null)
    {
        if ($storage === null) {
            $this->storage = &Tygh::$app['session']['cart']['user_data'];
        } else {
            $this->storage = $storage;
        }
    }

    /** @inheritdoc */
    public function getAll()
    {
        return $this->storage;
    }

    /** @inheritdoc */
    public function get($key)
    {
        if (array_key_exists($key, $this->storage)) {
            return $this->storage[$key];
        }

        return null;
    }

    /** @inheritdoc */
    public function set($key, $value)
    {
        $this->storage[$key] = $value;
    }

    /** @inheritdoc */
    public function delete($key)
    {
        unset($this->storage[$key]);
    }
}
