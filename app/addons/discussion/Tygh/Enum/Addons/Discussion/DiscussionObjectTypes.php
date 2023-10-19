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

namespace Tygh\Enum\Addons\Discussion;

/**
 * DiscussionObjectTypes contains available discussion object types: products, categories, etc.
 *
 * @package Tygh\Enum
 */
class DiscussionObjectTypes
{
    const PAGE = 'A';
    const ORDER = 'O';
    const PRODUCT = 'P';
    const COMPANY = 'M'; // Vendor
    const CATEGORY = 'C';
    const TESTIMONIALS_AND_LAYOUT = 'E';
}
