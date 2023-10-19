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

namespace Tygh\Models\Components;

class Iterator implements \Iterator
{
    /**
     * @var array the data to be iterated through
     */
    private $data;

    /**
     * @var array list of keys in the map
     */
    private $keys;

    /**
     * @var mixed current key
     */
    private $key;

    /**
     * Constructor.
     *
     * @param array $data the data to be iterated through
     */
    public function __construct(&$data)
    {
        $this->data = &$data;
        $this->keys = array_keys($data);
        $this->key = reset($this->keys);
    }

    /**
     * Rewinds internal array pointer.
     * This method is required by the interface Iterator.
     */
    public function rewind()
    {
        $this->key = reset($this->keys);
    }

    /**
     * Returns the key of the current array element.
     * This method is required by the interface Iterator.
     *
     * @return mixed the key of the current array element
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Returns the current array element.
     * This method is required by the interface Iterator.
     *
     * @return mixed the current array element
     */
    public function current()
    {
        return $this->data[$this->key];
    }

    /**
     * Moves the internal pointer to the next array element.
     * This method is required by the interface Iterator.
     */
    public function next()
    {
        $this->key = next($this->keys);
    }

    /**
     * Returns whether there is an element at current position.
     * This method is required by the interface Iterator.
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->key !== false;
    }
}
