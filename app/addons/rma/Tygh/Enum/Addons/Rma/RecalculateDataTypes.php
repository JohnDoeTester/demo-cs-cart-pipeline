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

namespace Tygh\Enum\Addons\Rma;

/**
 * The class declares available recalculate data type
 *
 * @package Tygh\Enum\Addons\Rma\RecalculateDataTypes
 */
class RecalculateDataTypes
{
    const CHANGE_ORDER_AND_RELATED = 'M-O+';
    const CHANGE_RELATED_DATA = 'O-';
    const CHANGE_ONLY_ORDER_DATA = 'M+';
}
