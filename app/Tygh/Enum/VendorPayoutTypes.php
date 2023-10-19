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

class VendorPayoutTypes
{
    const OTHER = 'other';
    const ORDER_PLACED = 'order_placed';
    const ORDER_CHANGED = 'order_changed';
    const ORDER_REFUNDED = 'order_refunded';
    const WITHDRAWAL = 'withdrawal';
    const PAYOUT = 'payout';

    public static function getAll()
    {
        $types = array(
            self::OTHER => self::OTHER,
            self::ORDER_PLACED => self::ORDER_PLACED,
            self::ORDER_CHANGED => self::ORDER_CHANGED,
            self::ORDER_REFUNDED => self::ORDER_REFUNDED,
            self::WITHDRAWAL => self::WITHDRAWAL,
            self::PAYOUT => self::PAYOUT
        );

        /**
         * Allows to expand list of payout types.
         *
         * @param array $types Payout types
         */
        fn_set_hook('vendor_payout_types_get_all', $types);

        return $types;
    }
}
