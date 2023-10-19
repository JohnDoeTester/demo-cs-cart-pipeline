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

namespace Tygh\Addons\PaypalCommercePlatform\Enum;

/**
 * Class ProductType contains possible values of supported products.
 *
 * @see https://developer.paypal.com/docs/api/partner-referrals/v2/#definition-product_name
 *
 * @package Tygh\Addons\PaypalCommercePlatform\Enum
 */
class ProductType
{
    const PAYPAL_COMPLETE_PAYMENTS = 'PPCP';
    const EXPRESS_CHECKOUT = 'EXPRESS_CHECKOUT';
    const PAYPAL_PLUS = 'PPPLUS';
    const PAYPAL_PROFESSIONAL = 'WEBSITE_PAYMENT_PRO';
}
