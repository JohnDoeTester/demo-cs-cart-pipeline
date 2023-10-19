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

namespace Tygh\Addons\VendorRating\HookHandlers;

use Tygh\Enum\YesNo;
use Tygh\Registry;

/**
 * Class MasterProductsHookHandler contains vendor plan-specific hook processors.
 *
 * @package Tygh\Addons\VendorRating\HookHandlers
 */
class MasterProductsHookHandler
{

    /**
     * The "get_best_product_offer_post" hook handler.
     *
     * Actions performed:
     *     - Changes the data of the best product offer
     *
     * @param int   $master_product_id        Master product identifier
     * @param int   $best_product_offer_id    Best product offer id
     * @param float $best_product_offer_price Best product offer data
     * @param array $vendor_product_offers    List of all offers
     *
     * @return void
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function onGetBestProductOfferPost($master_product_id, &$best_product_offer_id, &$best_product_offer_price, array $vendor_product_offers)
    {
        if (!YesNo::toBool(Registry::get('addons.vendor_rating.rating_above_price'))) {
            return;
        }

        $rating_list = db_get_hash_array('SELECT rating, object_id FROM ?:absolute_rating', 'object_id');

        if (empty($rating_list)) {
            return;
        }

        $best_vendor_product_id = 0;
        foreach ($vendor_product_offers as $product_offer) {
            // phpcs:ignore
            if (
                !$best_vendor_product_id
                || (
                    isset($rating_list[$product_offer['company_id']]['rating'])
                    && isset($rating_list[$vendor_product_offers[$best_vendor_product_id]['company_id']]['rating'])
                    && $rating_list[$product_offer['company_id']]['rating']
                    > $rating_list[$vendor_product_offers[$best_vendor_product_id]['company_id']]['rating']
                )
            ) {
                $best_vendor_product_id = $product_offer['product_id'];
            }
        }

        $best_product_offer_id = $best_vendor_product_id;
        $best_product_offer_price = $vendor_product_offers[$best_vendor_product_id]['price'];
    }
}
