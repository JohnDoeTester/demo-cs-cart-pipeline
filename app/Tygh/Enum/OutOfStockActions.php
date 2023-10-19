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

namespace Tygh\Enum;

/**
 * Class OutOfStockActions containg possible product out of stock actions.
 *
 * @package Tygh\Enum
 */
class OutOfStockActions
{
    const NONE = 'N';
    const BUY_IN_ADVANCE = 'B';
    const SUBSCRIBE = 'S';

    public static function getAll()
    {
        return array(
            static::NONE           => static::NONE,
            static::BUY_IN_ADVANCE => static::BUY_IN_ADVANCE,
            static::SUBSCRIBE      => static::SUBSCRIBE,
        );
    }
}