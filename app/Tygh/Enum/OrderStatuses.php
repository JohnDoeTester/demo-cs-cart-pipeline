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
 * Class OrderTypes contains default statuses of orders.
 *
 * @package Tygh\Enum
 */
class OrderStatuses
{
    const PAID = 'P';
    const COMPLETE = 'C';
    const OPEN = 'O';
    const FAILED = 'F';
    const DECLINED = 'D';
    const BACKORDERED = 'B';
    const CANCELED = 'I';
    const INCOMPLETED = 'N';
    const PARENT = 'T';
}
