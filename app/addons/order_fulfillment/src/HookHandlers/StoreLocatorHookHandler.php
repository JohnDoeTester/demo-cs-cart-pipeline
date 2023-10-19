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

use Tygh\Shippings\Services\StoreLocator;

class StoreLocatorHookHandler
{
    /**
     * The `get_store_locations_for_shipping_before_select` hook handler.
     *
     * Action performed:
     *      - Selecting stores that owned by marketplace, which should be used for fulfillment shipping.
     *
     * @param int                                   $destination_id Specified rate area for selecting stores.
     * @param array<string, string>                 $fields         Requesting fields.
     * @param array<string, string>                 $joins          Joining tables for requests.
     * @param array<string, string>                 $conditions     Conditions of selecting stores.
     * @param \Tygh\Shippings\Services\StoreLocator $instance       Current state of StoreLocator class.
     *
     * @return void
     */
    public function onGetStoresForShippingBeforeSelect($destination_id, array $fields, array $joins, array &$conditions, StoreLocator $instance)
    {
        if (!isset($conditions['company_id'])) {
            return;
        }
        //phpcs:ignore
        if (fn_are_company_orders_fulfilled_by_marketplace($instance->company_id)) {
            $conditions['company_id'] = db_quote('locations.company_id = ?i', 0);
        }
    }


    /**
     * The `get_store_locations_before_select` hook handler.
     *
     * Action performed:
     *    - Changes selecting stores accordingly to context.
     *
     * @param array<string, string> $params         Parameters of selecting process.
     * @param array<string, string> $fields         List of fields for retrieving
     * @param string                $joins          String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string                $conditions     String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param array<string, string> $sortings       Possible sortings for a query
     * @param int                   $items_per_page Amount of items per page
     * @param string                $lang_code      Two-letter language code
     *
     * @see fn_get_store_locations()
     *
     * @return void
     */
    public function onGetStoreLocationsBeforeSelect(array $params, array $fields, $joins, &$conditions, array $sortings, $items_per_page, $lang_code)
    {
        if (isset($params['storefront_search'])) {
            if (empty($params['company_id'])) {
                $company_ids = fn_what_companies_orders_are_fulfilled_by_marketplace();
                if (!empty($company_ids)) {
                    if ($needed_company_ids = array_diff(fn_get_all_companies_ids(), $company_ids)) {
                        unset($conditions['company_status']);
                        $needed_company_ids[] = 0;
                    }
                    $conditions['company_id'] = db_quote('?:store_locations.company_id IN (?n)', $needed_company_ids);
                }
            } else {
                if (fn_are_company_orders_fulfilled_by_marketplace($params['company_id'])) {
                    $conditions['company_id'] = db_quote('?:store_locations.company_id = 0');
                    unset($conditions['company_status']);
                }
            }
        } elseif (empty($params['company_id'])) {
            unset($conditions['company_id']);
        }
    }
}
