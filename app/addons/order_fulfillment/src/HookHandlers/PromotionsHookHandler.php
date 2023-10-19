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

namespace Tygh\Addons\OrderFulfillment\HookHandlers;

class PromotionsHookHandler
{
    /**
     * The `pre_promotion_validate` hook handler.
     *
     * Action performed:
     *     - Hides temporary created product group.
     *
     * @param int                                 $promotion_id    Promotion id.
     * @param array<string>                       $promotion       Promotion information.
     * @param array<array<string, array<string>>> $data            Cart data.
     * @param bool                                $stop_validating Flag to stop validate this promotion.
     * @param bool                                $result          Result of validation.
     * @param array<string>|null                  $auth            Authentication data.
     * @param array<string>|null                  $cart_products   All products in the cart.
     *
     * @see \fn_promotion_validate()
     *
     * @return void
     */
    public function onPrePromotionValidate(
        $promotion_id,
        array $promotion,
        array &$data,
        $stop_validating,
        $result,
        $auth,
        $cart_products
    ) {
        if (!isset($data['product_groups'])) {
            return;
        }
        foreach ($data['product_groups'] as $key => $product_group) {
            if (!isset($product_group['marketplace_shipping'])) {
                continue;
            }
            unset($data['product_groups'][$key]);
        }
    }
}
