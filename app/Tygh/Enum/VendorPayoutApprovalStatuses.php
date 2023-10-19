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

class VendorPayoutApprovalStatuses
{
    const COMPLETED = 'C';
    const PENDING = 'P';
    const DECLINED = 'D';

    public static function getAll()
    {
        return array(
            self::PENDING => self::PENDING,
            self::COMPLETED => self::COMPLETED,
            self::DECLINED => self::DECLINED,
        );
    }

    public static function getWithDescriptions($lang_code = CART_LANGUAGE)
    {
        static $statuses;
        if (!$statuses) {
            $statuses = array();
            foreach (self::getAll() as $status) {
                $statuses[$status] = __("vendor_payouts.approval_status.{$status}", array(), $lang_code);
            }
        }

        return $statuses;
    }
}
