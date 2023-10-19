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

namespace Tygh\Enum\Addons\Seo;

/**
 * Class ObjectTypes contains default types of seo objects: orders, products, etc.
 *
 * @package Tygh\Enum\Addons\Seo
 */
class ObjectTypes
{
    const PRODUCT = 'p';
    const CATEGORY = 'c';
    const PAGE = 'a';
    const EXTENDED = 'e';
    const STATIC_DISPATCH = 's';
    const VENDOR = 'm';
}
