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

class ProductAvailability
{
    const OUT_OF_STOCK = 'out of stock';
    const IN_STOCK     = 'in stock';
    const PRE_ORDER    = 'on backorder';

    const KEY_OUT_OF_STOCK = 'OUT_OF_STOCK';
    const KEY_IN_STOCK     = 'IN_STOCK';
    const KEY_PRE_ORDER    = 'PRE_ORDER';

    /**
     * @return array<string>
     */
    public static function getAll()
    {
        return [
            self::KEY_OUT_OF_STOCK => self::OUT_OF_STOCK,
            self::KEY_IN_STOCK     => self::IN_STOCK,
            self::KEY_PRE_ORDER    => self::PRE_ORDER
        ];
    }
}
