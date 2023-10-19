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

namespace Tygh;

use Tygh\Enum\TaxApplies;
use Tygh\Enum\YesNo;

/**
 * @psalm-type PayoutComponents = array{
 *      order_tax_amount: float,
 *      shipping_tax_amount: float,
 *      order_shipping_cost: float,
 *      order_total: float,
 *      order_payment_surcharge: float,
 *      order_surcharge_tax_amount: float,
 *      product_tax_amount: float,
 *      order_products_cost: float,
 *      promotion_discount: array{int, float}
 * }
 */
class VendorPayoutDetailsBuilder
{
    /**
     * Calculates all components of order for detailed representation and potential additional operations.
     *
     * @param array<string, float|array<string, float|array<string, float>>> $order_info Order information.
     * @param array<string, string>                                          $cart       Cart information.
     *
     * @psalm-param array{
     *     taxes: array<string, array<string, float>>,
     *     payment_surcharge: float,
     *     shipping_cost: float,
     *     products: array<string, array<string, float>>,
     *     total: float,
     *     subtotal_discount: float,
     *     promotions?: array<string, array<string, float>>,
     *    } $order_info
     *
     * @return array<string, float>
     */
    public function createDetails(array $order_info, array $cart = []): array
    {
        $payout_details = [];

        list($included_payment_surcharge_taxes, $not_included_payment_surcharge_taxes)
            = $this->calculateTaxes($order_info['taxes'], TaxApplies::PAYMENT_SURCHARGE);
        if ($included_payment_surcharge_taxes > 0) {
            $payout_details['order_payment_surcharge'] = $order_info['payment_surcharge'] - $included_payment_surcharge_taxes;
        } else {
            $payout_details['order_payment_surcharge'] = $order_info['payment_surcharge'];
        }
        $payout_details['included_order_surcharge_tax_amount'] = $included_payment_surcharge_taxes;
        $payout_details['not_included_order_surcharge_tax_amount'] = $not_included_payment_surcharge_taxes;
        $payout_details['order_surcharge_tax_amount'] = $included_payment_surcharge_taxes + $not_included_payment_surcharge_taxes;

        list($included_shipping_taxes, $not_included_shipping_taxes) =
            $this->calculateTaxes($order_info['taxes'], TaxApplies::SHIPPING);
        if ($included_shipping_taxes > 0) {
            $payout_details['order_shipping_cost'] = $order_info['shipping_cost'] - $included_shipping_taxes;
        } else {
            $payout_details['order_shipping_cost'] = $order_info['shipping_cost'];
        }
        $payout_details['included_shipping_tax_amount'] = $included_shipping_taxes;
        $payout_details['not_included_shipping_tax_amount'] = $not_included_shipping_taxes;
        $payout_details['shipping_tax_amount'] = $included_shipping_taxes + $not_included_shipping_taxes;

        list($included_product_taxes, $not_included_product_taxes) = $this->calculateTaxes($order_info['taxes'], TaxApplies::PRODUCT);
        $payout_details['included_product_tax_amount'] = $included_product_taxes;
        $payout_details['not_included_product_tax_amount'] = $not_included_product_taxes;
        $payout_details['product_tax_amount'] = $included_product_taxes + $not_included_product_taxes;

        $order_products_cost = 0.0;
        foreach ($order_info['products'] as $product) {
            if (isset($product['subtotal'])) {
                $order_products_cost += $product['subtotal'];
                continue;
            }
            $order_products_cost += $product['price'] * $product['amount'];
        }
        $payout_details['order_products_cost'] = $order_products_cost - $included_product_taxes;
        $payout_details['order_products_discount'] = $order_info['subtotal_discount'];
        $payout_details['order_tax_amount'] = array_sum(array_column($order_info['taxes'], 'tax_subtotal'));
        $payout_details['order_total'] = $order_info['total'];

        $payout_details['promotion_discount'] = $this->calculateDiscountsByPromotions($order_info);

        /**
         * Executes after creation all payout components. Allows adding specific components.
         *
         * @param VendorPayoutDetailsBuilder $this
         * @param array<string>              $order_info
         * @param array<string, string>      $cart
         * @param PayoutComponents           $payout_details
         */
        fn_set_hook('vendor_payout_details_builder_create_details_post', $this, $order_info, $cart, $payout_details);

        return $payout_details;
    }

    /**
     * Creates details for payout created for updated order.
     *
     * @param array<string, float|array<string, array<string, float>>> $updated_order_info Updated order information.
     * @param array<string, float>                                     $old_details        Details from previous payout.
     *
     * @return array<string, float>
     */
    public function createUpdatedDetails(array $updated_order_info, array $old_details): array
    {
        $new_details = $this->createDetails($updated_order_info);
        $updated_details = [];
        foreach ($new_details as $new_detail_key => $new_detail_value) {
            if (!is_numeric($new_detail_value)) {
                continue;
            }
            $diff_value = $new_detail_value;
            if (isset($old_details[$new_detail_key])) {
                $diff_value -= $old_details[$new_detail_key];
            }
            $updated_details[$new_detail_key] = $diff_value;
        }

        /**
         * Executes before returning details for payout for updating order.
         *
         * @param VendorPayoutDetailsBuilder $builder            Object of VendorPayoutDetailsBuilder class.
         * @param array<string, string>      $updated_order_info Updated order info.
         * @param PayoutComponents           $old_details        Payout details from previous payout at this order.
         * @param PayoutComponents           $updated_details    Updated payout details for updated order.
         */
        fn_set_hook('vendor_payout_details_builder_create_updated_details_post', $this, $updated_order_info, $old_details, $updated_details);

        return $updated_details;
    }

    /**
     * Calculates taxes of specific type and splits them at included and not included into price.
     *
     * @param array<string, array<string, float|string|array<string, float>>> $taxes    Information about taxes.
     * @param string                                                          $tax_type Taxes type. See \Tygh\Enum\TaxApplies for variants.
     *
     * @return array<float>
     */
    private function calculateTaxes(array $taxes, string $tax_type): array
    {
        $included = $not_included = 0.0;
        foreach ($taxes as $tax) {
            $is_included = YesNo::toBool($tax['price_includes_tax']);
            foreach ($tax['applies'] as $hash => $tax_amount) {
                list($code) = explode('_', $hash);
                if ($code !== $tax_type) {
                    continue;
                }
                if ($is_included) {
                    $included += $tax_amount;
                } else {
                    $not_included += $tax_amount;
                }
            }
        }
        return [$included, $not_included];
    }

    /**
     * Calculates discounts from all applied promotions to specified order.
     *
     * @param array{taxes: array<string, array<string, float>>, payment_surcharge: float, shipping_cost: float, products: array<string, array<string, float>>, total: float, promotions?: string|array<string, array<string, float>>} $order_info Order information.
     *
     * @return array<string, float>
     */
    private function calculateDiscountsByPromotions(array $order_info): array
    {
        $promotions_discounts = [];
        if (empty($order_info['promotions'])) {
            return $promotions_discounts;
        }
        if (is_string($order_info['promotions'])) {
            $order_info['promotions'] = unserialize($order_info['promotions']);
        }

        foreach ($order_info['promotions'] as $promotion_id => $promotion) {
            if (!isset($promotion['total_discount'])) {
                continue;
            }
            $promotions_discounts[$promotion_id] = isset($promotions_discounts[$promotion_id])
                ? $promotions_discounts[$promotion_id] + (float) $promotion['total_discount']
                : (float) $promotion['total_discount'];
        }
        return $promotions_discounts;
    }
}
