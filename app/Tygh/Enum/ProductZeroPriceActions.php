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
 * ProductZeroPriceActions contains possible values for actions if product price is zero
 *
 * @package Tygh\Enum
 */
class ProductZeroPriceActions
{
    /**
     * Do not allow to add product to cart
     */
    const NOT_ALLOW_ADD_TO_CART = 'R';

    /**
     * Allow to add product to cart
     */
    const ALLOW_ADD_TO_CART = 'P';

    /**
     * Ask customer to enter product price
     */
    const ASK_TO_ENTER_PRICE = 'A';
}
