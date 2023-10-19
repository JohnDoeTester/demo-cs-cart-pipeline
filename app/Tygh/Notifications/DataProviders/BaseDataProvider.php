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

namespace Tygh\Notifications\DataProviders;


use Tygh\Notifications\Data;

class BaseDataProvider implements IDataProvider
{
    protected $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function get($receiver_type)
    {
        return new Data($this->data);
    }

    public static function factory(array $data)
    {
        return new static($data);
    }
}
