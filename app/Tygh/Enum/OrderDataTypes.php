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
 * Class OrderDataTypes stores possible type identifiers for records in ?:order_data table.
 *
 * @package Tygh\Enum
 */
class OrderDataTypes
{
    /**
     * @const SHIPPING Shipping information
     */
    const SHIPPING = 'L';

    /**
     * @const CURRENCY Secondary currency
     */
    const CURRENCY = 'R';

    /**
     * @const PAYMENT Payment information
     */
    const PAYMENT = 'P';

    /**
     * @const GROUPS Product groups
     */
    const GROUPS = 'G';

    /**
     * @const TAXES Taxes information
     */
    const TAXES = 'T';

    /**
     * @const COUPONS Promotion coupons information
     */
    const COUPONS = 'C';

    /**
     * @const PAYMENT_STARTED Whether payment was started
     */
    const PAYMENT_STARTED = 'S';
}