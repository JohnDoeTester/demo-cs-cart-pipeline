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

use Tygh\Registry;
use Tygh\Shippings\Shippings;
use Tygh\Tygh;

class ShippingsHookHandler
{
    /**
     * The 'shippings_group_products_list' hook handler.
     *
     * Action performed:
     *    - Create temporary product group for shippings sent by marketplace.
     *
     * @param array<string, array<string>> $products Products.
     * @param array<string, array<string>> $groups   Product groups.
     *
     * @see Shippings::groupProductsList()
     *
     * @return void
     */
    public function onGroupProductsList(array $products, array &$groups)
    {
        if (isset(Tygh::$app['session']['place_order'])) {
            return;
        }
        if (!empty($groups['marketplace']) || empty($products)) {
            return;
        }
        $marketplace_data = Registry::get('settings.Company');
        $delivered_vendor_names = [];
        $marketplace_origination = [
            'name'       => $marketplace_data['company_name'],
            'address'    => $marketplace_data['company_address'],
            'city'       => $marketplace_data['company_city'],
            'country'    => $marketplace_data['company_country'],
            'state'      => $marketplace_data['company_state'],
            'zipcode'    => $marketplace_data['company_zipcode'],
            'phone'      => $marketplace_data['company_phone'],
            'company_id' => 0,
        ];
        $group = reset($groups);
        $marketplace_group = [
            'name'        => $marketplace_origination['name'],
            'company_id'  => 0,
            'origination' => $marketplace_origination,
            'location'    => $group['location'],
        ];

        $is_group_needed_to_replace = false;
        foreach ($products as $key_product => $product) {
            $company_id = $product['company_id'];
            if (!fn_are_company_orders_fulfilled_by_marketplace((int) $company_id)) {
                continue;
            }
            $delivered_vendor_names[$product['company_id']] = $product['company_name'];
            $marketplace_group['products'][$key_product] = $product;
            $is_group_needed_to_replace = isset($product['shipping_by_marketplace']);
        }
        if (empty($marketplace_group['products'])) {
            return;
        }
        $group_name = implode(', ', array_values($delivered_vendor_names));
        $marketplace_group['name'] = $group_name;
        $marketplace_group['marketplace_shipping'] = true;
        if ($is_group_needed_to_replace) {
            $groups = ['marketplace' => $marketplace_group];
        } else {
            $groups['marketplace'] = $marketplace_group;
        }
    }

    /**
     * The 'shippings_get_shippings_list' hook handler.
     *
     * Action performed:
     *     - Filter shippings for marketplace shipping group.
     *
     * @param array<string, int|string> $group     Group products information.
     * @param array<int>                $shippings List of company shipping ids.
     * @param string                    $condition WHERE condition.
     *
     * @see Shippings::getShippingsList()
     *
     * @return void
     */
    public function onGetShippingsList(array $group, array &$shippings, $condition)
    {
        if ((int) $group['company_id'] !== 0) {
            return;
        }
        $shippings = array_filter($shippings, static function ($shipping) {
            return Shippings::isSentByMarketplace(['shipping_id' => $shipping]);
        });
    }

    /**
     * The `is_shipping_sent_by_marketplace` hook handler.
     *
     * Action performed:
     *     - Changes shipping sender.
     *
     * @param array<string, int|string> $shipping Shipping information.
     * @param bool                      $result   True if shipping ships by marketplace false otherwise.
     *
     * @see Shippings::isSentByMarketplace()
     *
     * @return void
     */
    public function onIsShippingSentByMarketplace(array $shipping, &$result)
    {
        if (isset($shipping['company_id'])) {
            $result = empty($shipping['company_id']);
        } elseif (isset($shipping['shipping_id'])) {
            $shipping_info = fn_get_shipping_info((int) $shipping['shipping_id']);
            $result = empty($shipping_info['company_id']);
        }
    }

    /**
     * The `update_shipping_post` hook handler.
     *
     * Action performed:
     *     - Reassign shipping from vendors if owner was changed.
     *
     * @param array<string> $shipping_data Shipping info.
     * @param int           $shipping_id   Shipping identifier.
     * @param string        $lang_code     Two-letter language code (e.g. 'en', 'ru', etc.).
     * @param string        $action        Action that is performed with the shipping method.
     *
     * @see \fn_update_shipping()
     *
     * @return void
     */
    public function onUpdateShippingPost(array $shipping_data, $shipping_id, $lang_code, $action)
    {
        $fulfillment_status = Shippings::isSentByMarketplace($shipping_data);
        $all_companies_ids = fn_get_all_companies_ids();
        foreach ($all_companies_ids as $company_id) {
            /** @var array<string, array<string>> $company_data */
            $company_data = fn_get_company_data($company_id);
            if (
                $fulfillment_status
                && $fulfillment_status === fn_are_company_orders_fulfilled_by_marketplace($company_id)
            ) {
                if (empty($company_data['shippings_ids']) || !in_array($shipping_id, $company_data['shippings_ids'])) {
                    $company_data['shippings_ids'][] = $shipping_id;
                    $company_data['shippings'] = $company_data['shippings_ids'];
                    fn_update_company($company_data, $company_id);
                }
            } elseif (!empty($company_data['shippings_ids']) && in_array($shipping_id, $company_data['shippings_ids'])) {
                $shippings = array_filter(
                    $company_data['shippings_ids'],
                    static function ($company_shipping_id) use ($shipping_id) {
                        return $company_shipping_id !== $shipping_id;
                    }
                );
                $company_data['shippings'] = $shippings;
                fn_update_company($company_data, $company_id);
            }
        }
    }

    /**
     * The `get_shipping_ids_available_for_new_vendors_pre` hook handler.
     *
     * Action performed:
     *     - Empties result array.
     *
     * @param array<string> $data Array of IDs of shippings available for new vendors.
     *
     * @see \fn_get_shipping_ids_available_for_new_vendors()
     *
     * @return void
     */
    public function onGetShippingIdsAvailableForNewVendorsPost(array &$data)
    {
        $data = [];
    }
}
