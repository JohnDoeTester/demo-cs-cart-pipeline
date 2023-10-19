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
 * Class ProductFilterProductFieldTypes contains product properties that can be used for product filters.
 *
 * @package Tygh\Enum
 */
class ProductFilterProductFieldTypes
{
    const PRICE = 'P';
    const FREE_SHIPPING = 'F';
    const IN_STOCK = 'A';
    const VENDOR = 'S';
}
