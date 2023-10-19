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

class GeoMapsHookHandler
{
    /**
     * The `geo_maps_get_product_shipping_methods_before_estimation` hook handler.
     *
     * Action performed:
     *    - Marks product that will be delivered by marketplace for geo maps shipping estimations.
     *
     * @param array{company_id: int} $product Information about product.
     *
     * @see ShippingEstimator::getShippingEstimation()
     *
     * @return void
     */
    public function onGetProductShippingMethodsBeforeEstimation(array &$product)
    {
        $product['shipping_by_marketplace'] = fn_are_company_orders_fulfilled_by_marketplace($product['company_id']);
    }
}
