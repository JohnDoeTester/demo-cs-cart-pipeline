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

namespace Tygh\Notifications\Transports;

use Tygh\Notifications\Data;
use Tygh\Notifications\DataValue;

abstract class BaseMessageSchema
{
    /**
     * @var callable|null
     */
    public $data_modifier = null;

    /**
     * @var array
     */
    public $data = [];

    public function init(Data $data)
    {
        $self = clone $this;

        if ($this->data_modifier && is_callable($this->data_modifier)) {
            $data = new Data(call_user_func($this->data_modifier, $data->toArray()));
        }

        foreach (get_object_vars($self) as $var => $value) {
            if ($value instanceof DataValue) {
                $self->{$var} = $this->retrieveDataValue($value, $data);
            }
        }

        $self->data = $data->toArray();

        return $self;
    }

    protected function retrieveDataValue(DataValue $value, Data $data)
    {
        return $data->get($value->getKey(), $value->getDefaultValue());
    }

    protected static function get(array $data, $key, $default_value = null)
    {
        return array_key_exists($key, $data) ? $data[$key] : $default_value;
    }
}
