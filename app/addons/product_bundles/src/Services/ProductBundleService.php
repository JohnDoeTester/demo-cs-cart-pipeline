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

namespace Tygh\Addons\ProductBundles\Services;

use Tygh\Addons\ProductBundles\Enum\DiscountType;
use Tygh\Addons\ProductBundles\ServiceProvider;
use Tygh\Addons\ProductVariations\ServiceProvider as ProductVariationsServiceProvider;
use Tygh\Enum\ImagePairTypes;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProductTracking;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Languages\Languages;
use Tygh\Registry;
use Tygh\Tools\SecurityHelper;

class ProductBundleService
{
    const BUNDLE_CACHE_KEY = 'addons.product_bundles.created_bundle';
    /**
     * Gets all pairs of bundle id and linked to it promotion id.
     *
     * @return array<string, array<string, int>>
     */
    public function getLinkedPromotions()
    {
        static $promotions_data = [];
        if (!empty($promotions_data)) {
            return $promotions_data;
        }
        return db_get_hash_array('SELECT bundle_id, linked_promotion_id FROM ?:product_bundles', 'linked_promotion_id');
    }

    /**
     * Gets all bundle ids with specified product.
     *
     * @param int $product_id Product identifier.
     *
     * @return array<string>
     */
    public function getBundleIdsByProductId($product_id)
    {
        return db_get_fields('SELECT DISTINCT(bundle_id) FROM ?:product_bundle_product_links WHERE product_id = ?i', $product_id);
    }

    /**
     * Get bundles according to search parameters.
     *
     * @param array<string|int|bool|array<string>> $params Parameters for bundles search.
     *
     * @return array<string, array<string>>
     */
    public function getBundles(array $params)
    {
        $default_params = [
            'bundle_id'         => null,
            'with_image'        => true,
            'lang_code'         => CART_LANGUAGE,
            'area'              => AREA,
            'page'              => 1,
            'items_per_page'    => null,
            'get_total'         => false,
            'company_ids'       => [],
            'active'            => SiteArea::isStorefront(AREA),
        ];

        $params = array_merge($default_params, $params);
        if ($params['items_per_page'] === null) {
            $params['items_per_page'] = Registry::get('settings.Appearance.admin_elements_per_page');
        }

        $product_variants = [];
        if (isset($params['product_variants'])) {
            foreach ($params['product_variants'] as $complex_index => $product_variant) {
                list(, $index) = explode('_', $complex_index, 2);
                $product_variants[$index] = $product_variant;
            }
        }
        $params['product_variants'] = $product_variants;

        /**
         * Allows modifying parameters before selecting bundles.
         *
         * @param array<string|int|array<string>> $params Parameters for bundles search.
         */
        fn_set_hook('product_bundle_service_get_bundles_pre', $params);

        $fields = '*';

        $limit = [];
        if ((int) $params['items_per_page'] !== 0) {
            $limit['items_per_page'] = (int) $params['items_per_page'];
        }
        if ($params['page']) {
            $limit['page'] = $params['page'];
        }

        $sortings = [
            'bundle_id'       => 'bundles.bundle_id',
            'name'            => 'descriptions.name',
            'storefront_name' => 'descriptions.storefront_name',
            'status'          => 'bundles.status'
        ];

        $joins = [
            'descriptions' => db_quote(
                'LEFT JOIN ?:product_bundle_descriptions as descriptions ON bundles.bundle_id = descriptions.bundle_id AND descriptions.lang_code = ?s',
                $params['lang_code']
            )
        ];
        $conditions = [];
        if (!empty($params['bundle_id'])) {
            $conditions['bundle_id'] = db_quote(' AND bundles.bundle_id IN (?n)', $params['bundle_id']);
        }
        if (!empty($params['company_id'])) {
            $conditions['company_id'] = db_quote(' AND bundles.company_id = ?s', $params['company_id']);
        }
        if (!empty($params['company_ids'])) {
            $conditions['company_id'] = db_quote(' AND bundles.company_id IN (?n)', $params['company_ids']);
        }
        if (!empty($params['product_id'])) {
            $joins['links'] = db_quote(' LEFT JOIN ?:product_bundle_product_links as links ON bundles.bundle_id = links.bundle_id');
            $conditions['product_id'] = db_quote(' AND links.product_id = ?i', $params['product_id']);
        }

        if (!empty($params['show_on_products_page'])) {
            $joins['links'] = db_quote(' LEFT JOIN ?:product_bundle_product_links as links ON bundles.bundle_id = links.bundle_id');
            $conditions['show_on_product_page'] = db_quote(' AND links.show_on_product_page = ?s', YesNo::YES);
        }

        if (!empty($params['q'])) {
            $conditions['bundle_name'] = db_quote(' AND descriptions.name LIKE ?l', '%' . $params['q'] . '%');
        }

        if (!empty($params['display_in_promotions'])) {
            $conditions['display_in_promotions'] = db_quote(' AND bundles.display_in_promotions = ?s', YesNo::YES);
        }

        if (!empty($params['status'])) {
            $conditions['status'] = db_quote(' AND bundles.status = ?s', $params['status']);
        }

        if (!empty($params['active'])) {
            $conditions['active'] = db_quote(
                ' AND IF(bundles.date_from, bundles.date_from <= ?i, 1) AND IF(bundles.date_to, bundles.date_to >= ?i, 1)',
                TIME,
                TIME
            );
        }

        /**
         * Allows modifying queries for selecting bundles right before sql request.
         *
         * @param array<string|int>     $params     Parameters for bundles search.
         * @param string                $fields     Requesting product bundles fields.
         * @param array<string, string> $joins      Joining tables for request.
         * @param array<string, string> $conditions Conditions of request.
         * @param array<string, string> $limit      Limit conditions of request.
         */
        fn_set_hook('product_bundle_service_get_bundles', $params, $fields, $joins, $conditions, $limit);

        $joins_query = implode('', $joins);
        $condition_query = implode('', $conditions);
        if (isset($limit['page'], $limit['items_per_page'])) {
            $limit_query = db_paginate($limit['page'], $limit['items_per_page']);
        } else {
            $limit_query = '';
        }

        $sorting_query = db_sort($params, $sortings, 'bundle_id', 'asc');

        $bundles = db_get_array(
            'SELECT ?p FROM ?:product_bundles as bundles ?p WHERE 1=1 ?p GROUP BY bundles.bundle_id ?p ?p',
            $fields,
            $joins_query,
            $condition_query,
            $sorting_query,
            $limit_query
        );

        if ($params['get_total']) {
            $params['total_items'] = db_get_field(
                'SELECT COUNT(1) FROM ?:product_bundles as bundles ?p WHERE 1 ?p',
                $joins_query,
                $condition_query
            );
        }

        if (empty($bundles)) {
            return [$bundles, $params];
        }
        if (!empty($params['full_info'])) {
            $bundles = $this->getAdditionalInfoForBundles($bundles, $params);
        }

        if ($params['with_image']) {
            if (!empty($bundles) && is_array($bundles)) {
                $bundle_image_ids = $this->getBundleImageIds(array_filter(array_column($bundles, 'bundle_id')));
                $images = fn_get_image_pairs(
                    array_column($bundle_image_ids, 'bundle_image_id'),
                    'product_bundle',
                    ImagePairTypes::MAIN
                );
                foreach ($bundles as &$bundle) {
                    if (empty($bundle_image_ids[$bundle['bundle_id']])) {
                        continue;
                    }
                    $bundle['main_pair'] = reset($images[$bundle_image_ids[$bundle['bundle_id']]['bundle_image_id']]);
                }
                unset($bundle);
            }
        }
        /**
         * Allows modifying selected bundles set and perform needed operations after selecting bundles.
         *
         * @param array<string> $params  Bundle parameters for selected set of bundles.
         * @param array<string> $bundles Selected bundles.
         */
        fn_set_hook('product_bundle_service_get_bundles_post', $params, $bundles);

        return [$bundles, $params];
    }

    /**
     * Updating bundle status and all other entities associated with this bundle.
     *
     * @param int    $bundle_id Bundle identifier.
     * @param string $status    New bundle status.
     *
     * @return void
     */
    public function updateBundleStatus($bundle_id, $status)
    {
        $bundle_service = ServiceProvider::getService();
        list($bundle,) = $bundle_service->getBundles(['bundle_id' => $bundle_id]);
        $bundle = reset($bundle);
        db_query('UPDATE ?:promotions SET status = ?s WHERE promotion_id = ?i', $status, $bundle['linked_promotion_id']);
        db_query('UPDATE ?:product_bundles SET status = ?s WHERE bundle_id = ?i', $status, $bundle['bundle_id']);
    }

    /**
     * Gets image identifiers for specified bundles.
     *
     * @param array<string> $bundle_ids Bundle identifiers.
     *
     * @return array<int, array<string>>
     */
    private function getBundleImageIds(array $bundle_ids)
    {
        return db_get_hash_array('SELECT bundle_image_id, bundle_id FROM ?:product_bundle_images WHERE bundle_id IN (?n)', 'bundle_id', $bundle_ids);
    }

    /**
     * Gets additional information for products into bundles.
     *
     * @param array<array<string, string>> $bundles Bundles.
     * @param array<string>                $params  Parameters for request.
     *
     * @return array<string>
     */
    private function getAdditionalInfoForBundles(array $bundles, array $params = [])
    {
        $is_empty_bundle_allowed = !empty($params['allow_empty_products']);

        /* Getting information about all products in all bundles */
        $product_ids = [];
        foreach ($bundles as &$bundle) {
            $bundle['products'] = array_values(unserialize($bundle['products']));
            $product_ids = array_merge(
                $product_ids,
                array_column($bundle['products'], 'product_id', 'product_id')
            );
        }
        unset($bundle);
        if (empty($product_ids) && !$is_empty_bundle_allowed) {
            return [];
        }

        list($products) = fn_get_products(['pid' => $product_ids]);
        /* Finish */

        /* Merging additional information about product with info snippet about product into bundle. */
        foreach ($bundles as $bundle_key => &$bundle) {
            $is_valid = true;

            /* Filtering empty products bundles */
            if (empty($bundle['products'])) {
                if (!$is_empty_bundle_allowed) {
                    unset($bundles[$bundle_key]);
                }
                continue;
            }
            /* Finish */

            /* Calculation of total products into bundle for check if there is enough products at the store to complete bundle */
            $bundle_products_amount = [];
            foreach ($bundle['products'] as $bundle_product) {
                if (isset($bundle_products_amount[$bundle_product['product_id']])) {
                    $bundle_products_amount[$bundle_product['product_id']] += $bundle_product['amount'];
                } else {
                    $bundle_products_amount[$bundle_product['product_id']] = $bundle_product['amount'];
                }
            }
            /* Finish */

            /* Represent products with options as separate bundle items to properly set product options */
            if (SiteArea::isStorefront($params['area'])) {
                $regrouped_products = [];
                foreach ($bundle['products'] as &$bundle_product) {
                    if (
                        !empty($bundle_product['product_options'])
                        || !isset($bundle_product['aoc'])
                    ) {
                        $regrouped_products[] = $bundle_product;
                        continue;
                    }

                    $product_with_options_count = $bundle_product['amount'];
                    $bundle_product['amount'] = 1;
                    for ($i = 0; $i < $product_with_options_count; $i++) {
                        $regrouped_products[] = $bundle_product;
                    }
                }
                unset($bundle_product);
                $bundle['products'] = $regrouped_products;
            }
            /* Finish */

            $total_price = 0;
            $discounted_price = 0;
            $taxed_price = 0;
            $product_number = -1;

            $auth = fn_fill_auth();
            $show_taxed_prices = YesNo::toBool(Registry::get('settings.Appearance.show_prices_taxed_clean'));
            $is_tax_calculation_by_unit_price = Registry::get('settings.Checkout.tax_calculation') === 'unit_price';
            $tax_exempt = !YesNo::toBool($auth['tax_exempt']);
            $included_tax = false;
            $is_tax_exists = false;
            foreach ($bundle['products'] as $hash => &$bundle_product) {
                $product_number++;
                /* Removing products which are not exists into the store */
                if (!isset($products[$bundle_product['product_id']])) {
                    unset($bundle['products'][$hash]);
                    $is_valid = false;
                    break;
                }
                /* Finish */

                $store_product = $products[$bundle_product['product_id']];
                if (
                    SiteArea::isStorefront($params['area'])
                    && (
                        $store_product['status'] === ObjectStatuses::HIDDEN
                        || (
                            $store_product['amount'] < $bundle_products_amount[$bundle_product['product_id']]
                            && $store_product['tracking'] !== ProductTracking::DO_NOT_TRACK
                        )
                    )
                ) {
                    $is_valid = false;
                    break;
                }

                if (!empty($bundle_product['product_options'])) {
                    $store_product['selected_options'] = $bundle_product['product_options'];
                } elseif (isset($params['product_variants'][$product_number]['product_options'])) {
                    $store_product['selected_options'] = $params['product_variants'][$product_number]['product_options'];
                }

                fn_gather_additional_products_data($store_product, [
                    'get_icon'       => true,
                    'get_detailed'   => true,
                    'get_additional' => false,
                    'get_options'    => true,
                    'get_discounts'  => true
                ]);

                if (!empty($bundle_product['product_options'])) {
                    $store_product['amount'] = isset($store_product['inventory_amount'])
                        ? $store_product['inventory_amount']
                        : $store_product['amount'];
                } else {
                    $store_product['amount'] = fn_get_product_amount($bundle_product['product_id']);
                }

                $store_product['min_qty'] = ($store_product['min_qty'] > 0) ? $store_product['min_qty'] : 1;

                $bundle_product['product_name'] = $store_product['product'];
                $bundle_product['min_qty'] = $store_product['min_qty'];
                $bundle_product['price'] = empty($bundle_product['price']) ? $store_product['price'] : $bundle_product['price'];
                $bundle_product['list_price'] = $store_product['list_price'];

                if (isset($store_product['main_pair'])) {
                    $bundle_product['main_pair'] = $store_product['main_pair'];
                }

                $bundle_product['discount'] = $this->calculateDiscount(
                    empty($bundle_product['modifier_type']) ? 'to_fixed' : $bundle_product['modifier_type'],
                    $store_product['price'],
                    empty($bundle_product['modifier']) ? 0 : $bundle_product['modifier']
                );
                $bundle_product['discounted_price'] = $store_product['price'] - $bundle_product['discount'];

                /* Calculation of taxed discounted price for every product into bundle if store settings require this at storefront. */
                if ($show_taxed_prices && $is_tax_calculation_by_unit_price && $tax_exempt) {
                    $this->calculateTaxedDiscountedProductPrice($bundle_product, $store_product, $auth);
                }
                /* Finish */

                if ($bundle_product['discounted_price'] < 0) {
                    $bundle_product['discounted_price'] = 0;
                }

                $bundle_product['options_type'] = $store_product['options_type'];
                $bundle_product['exceptions_type'] = $store_product['exceptions_type'];
                $bundle_product['options_update'] = true;

                $total_price += $store_product['price'] * $bundle_product['amount'];
                $discounted_price += $bundle_product['discounted_price'] * $bundle_product['amount'];
                $taxed_price += isset($bundle_product['taxed_price'])
                    ? $bundle_product['taxed_price'] * $bundle_product['amount']
                    : $bundle_product['discounted_price'] * $bundle_product['amount'];
                $included_tax = isset($bundle_product['included_tax']) ? $included_tax || $bundle_product['included_tax'] : $included_tax;
                $is_tax_exists = isset($bundle_product['clean_price'], $bundle_product['taxed_price'])
                    ? ($is_tax_exists || ($bundle_product['clean_price'] - $bundle_product['taxed_price']))
                    : $is_tax_exists;

                if (!empty($bundle_product['product_options'])) {
                    $bundle_product['product_options_short'] = $bundle_product['product_options'];

                    $options = fn_get_selected_product_options_info($bundle_product['product_options'], DESCR_SL);
                    $bundle_product['product_options'] = $options;
                } elseif (!empty($store_product['product_options'])) {
                    $bundle_product['aoc'] = YesNo::YES;
                    $bundle_product['product_options'] = $store_product['product_options'];
                }

                $bundle_product['product_data'] = $store_product;

                $bundles[$bundle_key]['products'][$hash]['price'] = (float) $bundle_product['price'];
                $bundles[$bundle_key]['products'][$hash]['discount'] = (float) $bundle_product['discount'];
                $bundles[$bundle_key]['products'][$hash]['discounted_price'] = (float) $bundle_product['discounted_price'];
            }
            unset($bundle_product);

            if (!$is_valid) {
                unset($bundles[$bundle_key]);
                continue;
            }

            $bundle['total_price'] = $total_price;
            $bundle['discounted_price'] = $discounted_price;
            $bundle['taxed_price'] = $included_tax ? $taxed_price : 0;
            $bundle['is_tax_exists'] = $is_tax_exists;
        }
        unset($bundle);

        return $bundles;
    }

    /**
     * Checks if specified products with specified amounts were linked into some bundle potentially.
     *
     * @param array<string, float|int> $cart_product_amounts Product amounts.
     * @param array<string|int>        $promotions           Promotions for bundles.
     *
     * @return array<string>
     */
    public function checkForPotentialCompleteBundles(array $cart_product_amounts, array $promotions)
    {
        $result = [];
        $bundles_with_products = db_get_hash_array(
            'SELECT DISTINCT(?:product_bundle_product_links.bundle_id), linked_promotion_id FROM ?:product_bundle_product_links'
            . ' LEFT JOIN ?:product_bundles ON ?:product_bundles.bundle_id = ?:product_bundle_product_links.bundle_id'
            . ' WHERE product_id IN (?n) AND linked_promotion_id IN (?n)',
            'linked_promotion_id',
            array_keys($cart_product_amounts),
            $promotions
        );
        $promotions = array_filter($promotions, static function ($promotion) use ($bundles_with_products) {
            return in_array($promotion, array_keys($bundles_with_products));
        });
        $bundles_with_products = fn_array_merge(array_flip($promotions), $bundles_with_products, true);
        foreach ($bundles_with_products as $bundle_id) {
            list($result[$bundle_id['bundle_id']], $cart_product_amounts) =
                $this->howManyBundlesCanBeInCart($bundle_id['bundle_id'], $cart_product_amounts);
        }
        return $result;
    }

    /**
     * Verify potentially complete bundles in the cart by checking product in the cart, including options.
     *
     * @param array<string, string>        $bundle_data   Bundle information.
     * @param array<string, array<string>> $cart_products All products in the cart.
     *
     * @return bool
     */
    public function checkCartForCompleteBundles(array $bundle_data, array $cart_products)
    {
        if (empty($bundle_data) || empty($cart_products)) {
            return false;
        }

        $bundle_complete = false;
        uasort($bundle_data['products'], static function ($product) {
            return (!isset($product['product_options']) || YesNo::toBool($product['aoc'])) ? 1 : -1;
        });
        foreach ($bundle_data['products'] as $product_info) {
            $product_ids = [(int) $product_info['product_id']];

            /**
             * Executes when checking cart for presence of complete product bundles, before checking individual
             * bundle product, allows you to modify product ID to search for.
             *
             * @param array<string, string>        $bundle_data   Bundle information
             * @param array<string, array<string>> $cart_products All products in the cart
             * @param array<string, string>        $product_info  Checked bundle product data
             * @param array<int>                   $product_ids   Product IDs to search against
             */
            fn_set_hook(
                'product_bundle_service_check_cart_for_complete_bundles_before_getting_product_ids',
                $bundle_data,
                $cart_products,
                $product_info,
                $product_ids
            );

            foreach ($cart_products as $cart_id => $cart_product) {
                if (!in_array($cart_product['product_id'], $product_ids)) {
                    continue;
                }

                if (!isset($product_info['product_options']) || (isset($product_info['aoc']) && YesNo::toBool($product_info['aoc']))) {
                    $bundle_complete = true;
                    $cart_products[$cart_id]['amount'] -= $product_info['amount'];
                    if (empty($cart_products[$cart_id]['amount'])) {
                        unset($cart_products[$cart_id]);
                    }
                    break;
                }

                if (empty($cart_product['product_options'])) {
                    continue;
                }
                $cart_product_options = array_column($cart_product['product_options'], 'value', 'option_id');
                $bundle_product_options = array_column($product_info['product_options'], 'value', 'option_id');
                $missing_options = array_diff_assoc($bundle_product_options, $cart_product_options);
                if (!empty($missing_options)) {
                    continue;
                }
                $cart_products[$cart_id]['amount'] -= $product_info['amount'];
                if (empty($cart_products[$cart_id]['amount'])) {
                    unset($cart_products[$cart_id]);
                }
                $bundle_complete = true;
                break;
            }
            if (!$bundle_complete) {
                break;
            }
        }

        return $bundle_complete;
    }

    /**
     * Checks how many times specific bundle added to cart and which products in what amount was not included in this bundle.
     *
     * @param int             $bundle_id            Bundle identifier.
     * @param array<int, int> $cart_product_amounts Cart products amounts.
     *
     * @return array{int, array<int, int>}
     */
    private function howManyBundlesCanBeInCart($bundle_id, array $cart_product_amounts)
    {
        $amount_of_bundles = 0;
        $bundle_products = db_get_hash_array(
            'SELECT product_id, amount, all_variants FROM ?:product_bundle_product_links WHERE bundle_id = ?i',
            'product_id',
            $bundle_id
        );
        if (empty($bundle_products)) {
            return [$amount_of_bundles, $cart_product_amounts];
        }

        /**
         * Executes when checking cart contents for specific bundle presence,
         * allows you to modify detected amount of each product in the cart.
         *
         * @param int                          $bundle_id            Bundle identifier
         * @param array<int, int>              $cart_product_amounts Cart products amounts
         * @param array<array<string, string>> $bundle_products      Bundle products
         * @param array<int, int>              $product_amounts      Product amounts
         */
        fn_set_hook(
            'product_bundle_service_how_many_bundles_can_be_in_cart_before_getting_product_amounts',
            $bundle_id,
            $cart_product_amounts,
            $bundle_products
        );

        foreach ($bundle_products as $bundle_product) {
            $product_id = $bundle_product['product_id'];

            /**
             * Executes when getting product amount, before getting product ID,
             * allows you to change product ID to detect amount for.
             *
             * @param int                          $bundle_id            Bundle identifier
             * @param array<int, int>              $cart_product_amounts Cart products amounts
             * @param array<array<string, string>> $bundle_products      Bundle products
             * @param array<string, string>        $bundle_product       Bundle product
             * @param int                          $product_id           Product identifier to check amount against
             */
            fn_set_hook(
                'product_bundle_service_how_many_bundles_can_be_in_cart_before_getting_product_id',
                $bundle_id,
                $cart_product_amounts,
                $bundle_products,
                $bundle_product,
                $product_id
            );

            if (!isset($cart_product_amounts[$product_id])) {
                $amount_of_bundles = 0;
                break;
            }
            $is_bundle_complete = $cart_product_amounts[$product_id] >= $bundle_product['amount'];
            if (!$is_bundle_complete) {
                $amount_of_bundles = 0;
                break;
            }
            $new_amount = (int) floor($cart_product_amounts[$product_id] / $bundle_product['amount']);
            if ($amount_of_bundles === 0) {
                $amount_of_bundles = $new_amount;
            } elseif ($amount_of_bundles > $new_amount) {
                $amount_of_bundles = $new_amount;
            }
        }
        if ($amount_of_bundles) {
            foreach ($bundle_products as $bundle_product) {
                $cart_product_amounts[$bundle_product['product_id']] -= $bundle_product['amount'] * $amount_of_bundles;
            }
        }

        return [$amount_of_bundles, $cart_product_amounts];
    }

    /**
     * Updates bundle information or creating new bundle.
     *
     * @param array<string, array<string, string>> $bundle_data Bundle info.
     * @param int                                  $bundle_id   Bundle id.
     *
     * @return int
     */
    public function updateBundle(array $bundle_data, $bundle_id = 0)
    {
        SecurityHelper::sanitizeObjectData('product_bundle', $bundle_data);

        $bundle_products = [];
        if (isset($bundle_data['products'])) {
            $bundle_data['products'] = array_filter($bundle_data['products'], static function ($product) {
                return !empty($product['amount']);
            });

            $bundle_data['products'] = array_map(static function ($product) {
                if (isset($product['aoc']) && YesNo::isTrue($product['aoc'])) {
                    unset($product['product_options']);
                }

                return $product;
            }, $bundle_data['products']);

            $bundle_product_ids = [];
            foreach ($bundle_data['products'] as $key => $product) {
                if (in_array($product['product_id'], $bundle_product_ids)) {
                    unset($bundle_data['products'][$key]);
                }
                $bundle_product_ids[] = $product['product_id'];
            }

            $bundle_products = $bundle_data['products'];

            $products_without_options = array_filter($bundle_data['products'], static function ($product) {
                return !isset($product['product_options']);
            });

            fn_gather_additional_products_data($products_without_options, ['get_options' => true, 'get_variation_name' => false]);
            foreach ($products_without_options as $detailed_product) {
                if (!empty($detailed_product['has_options'])) {
                    continue;
                }
                foreach ($bundle_products as &$bundle_product) {
                    if ((int) $detailed_product['product_id'] !== (int) $bundle_product['product_id']) {
                        continue;
                    }
                    unset($bundle_product['aoc']);
                }
                unset($bundle_product);
            }

            $bundle_products = array_values($bundle_products);
            $bundle_data['products'] = serialize($bundle_products);
        }

        $bundle_data['date_from'] = !empty($bundle_data['date_from']) ? fn_parse_date($bundle_data['date_from']) : 0;
        $bundle_data['date_to'] = !empty($bundle_data['date_to']) ? fn_parse_date($bundle_data['date_to'], true) : 0;

        if (!empty($bundle_data['date_to']) && $bundle_data['date_to'] < $bundle_data['date_from']) {
            $bundle_data['date_from'] = fn_parse_date($bundle_data['date_to']);
            $bundle_data['date_to'] = fn_parse_date($bundle_data['date_from'], true);
        }

        $bundle_description = [];
        if (!empty($bundle_data['name'])) {
            $bundle_description['name'] = $bundle_data['name'];
        }
        if (!empty($bundle_data['storefront_name'])) {
            $bundle_description['storefront_name'] = $bundle_data['storefront_name'];
        } else {
            $bundle_description['storefront_name'] = $bundle_data['name'];
        }
        if (isset($bundle_data['description'])) {
            $bundle_description['description'] = $bundle_data['description'];
        }
        $lang_code = DESCR_SL;
        if (!empty($bundle_data['lang_code'])) {
            $lang_code = $bundle_data['lang_code'];
        }

        /**
         * Allows to update some information right before updating product bundle.
         *
         * @param array<string> $bundle_data Bundle information.
         * @param int           $bundle_id   Bundle identifier.
         */
        fn_set_hook('product_bundle_service_update_bundle', $bundle_data, $bundle_id);

        if (empty($bundle_id)) {
            $bundle_data['linked_promotion_id'] = $this->updateHiddenPromotion($bundle_data, $bundle_description);
            $bundle_id = db_query('INSERT INTO ?:product_bundles ?e', $bundle_data);
            $bundle_description['bundle_id'] = $bundle_id;

            foreach (array_keys(Languages::getAll()) as $bundle_description['lang_code']) {
                db_query('INSERT INTO ?:product_bundle_descriptions ?e', $bundle_description);
            }
        } else {
            if (!empty($bundle_data) && !empty($bundle_description)) {
                if (empty($bundle_data['linked_promotion_id'])) {
                    $bundle_data['linked_promotion_id'] = db_get_field('SELECT linked_promotion_id FROM ?:product_bundles WHERE bundle_id = ?i', $bundle_id);
                }
                $bundle_data['linked_promotion_id'] = $this->updateHiddenPromotion($bundle_data, $bundle_description, $bundle_data['linked_promotion_id']);
            }
            if (!empty($bundle_data)) {
                db_query('UPDATE ?:product_bundles SET ?u WHERE bundle_id = ?i', $bundle_data, $bundle_id);
            }
            if (!empty($bundle_description)) {
                db_query(
                    'UPDATE ?:product_bundle_descriptions SET ?u WHERE bundle_id = ?i AND lang_code = ?s',
                    $bundle_description,
                    $bundle_id,
                    $lang_code
                );
            }
        }
        $this->updateImage($bundle_id, $lang_code);

        /**
         * Allows to update some information after updating product bundle.
         *
         * @param array<string> $bundle_data        Bundle information.
         * @param int           $bundle_id          Bundle identifier.
         * @param array<string> $bundle_description Bundle description.
         */
        fn_set_hook('product_bundle_service_update_bundle_post', $bundle_data, $bundle_id, $bundle_description);

        $this->updateLinks($bundle_id, $bundle_products);

        return $bundle_id;
    }

    /**
     * Validates bundle data, accordingly to context or specified params.
     *
     * @param array<string> $bundle_data Validating data.
     * @param array<string> $params      Specified params.
     *
     * @return array<string>
     */
    public function validateBundleData(array $bundle_data, array $params)
    {
        SecurityHelper::sanitizeObjectData('product_bundle', $bundle_data);

        $required_company_id = isset($params['company_id']) ? $params['company_id'] : fn_get_runtime_company_id();
        if (!$required_company_id) {
            $required_company_id = $bundle_data['company_id'];
        } elseif ((int) $bundle_data['company_id'] !== $required_company_id) {
            $bundle_data['company_id'] = $required_company_id;
        }
        if (!fn_check_permissions('promotions', 'manage', 'admin', 'POST')) {
            $bundle_data['display_in_promotions'] = YesNo::NO;
        }

        if (isset($bundle_data['products'])) {
            $bundle_data['products'] = array_filter($bundle_data['products'], static function ($product) {
                return !empty($product['amount']);
            });
            $product_ids = array_column($bundle_data['products'], 'product_id');

            list($products,) = fn_get_products(['pid' => $product_ids]);
            $products = array_filter($products, static function ($product) use ($required_company_id) {
                return (int) $product['company_id'] === (int) $required_company_id;
            });

            $product_ids = array_keys($products);

            foreach ($bundle_data['products'] as $cart_id => &$product) {
                if (!in_array($product['product_id'], $product_ids)) {
                    unset($bundle_data['products'][$cart_id]);
                    continue;
                }
                $product['product_id'] = (int) $product['product_id'];
                $product['amount'] = (int) $product['amount'];
                $product['modifier'] = (float) $product['modifier'];
            }
            unset($product);
        }
        return $bundle_data;
    }

    /**
     * Create promotion for just created product bundle.
     *
     * @param array<string> $bundle_data         Bundle information.
     * @param array<string> $bundle_descriptions Bundle descriptions.
     * @param int           $promotion_id        Promotion identifier.
     *
     * @return int
     */
    private function updateHiddenPromotion(array $bundle_data, array $bundle_descriptions, $promotion_id = 0)
    {
        if (is_string($bundle_data['products'])) {
            $bundle_data['products'] = unserialize($bundle_data['products']);
        }
        if (empty($bundle_data['products'])) {
            if (!$promotion_id) {
                return 0;
            }
            $bundle_data['status'] = ObjectStatuses::DISABLED;
        }
        $data = [
            'zone' => 'cart',
            'name' => $bundle_descriptions['name'],
            'from_date' => $bundle_data['date_from'],
            'to_date' => $bundle_data['date_to'],
            'priority' => 0,
            'stop_other_rules' => isset($bundle_data['stop_other_rules']) ? $bundle_data['stop_other_rules'] : YesNo::NO,
            'display_in_promotions' => $bundle_data['display_in_promotions'],
            'status' => $bundle_data['status'],
            'conditions' =>
                [
                    'set' => 'all',
                    'set_value' => '1',
                ],
            'bonuses' =>
                [
                    [
                        'bonus' => 'order_discount',
                        'discount_bonus' => 'by_fixed',
                        'discount_value' => isset($bundle_data['total_price'], $bundle_data['price_for_all'])
                            ? (float) $bundle_data['total_price'] - (float) $bundle_data['price_for_all']
                            : $this->calculatePromotionBonus($bundle_data),
                    ],
                ]
        ];
        $condition_index = 1;
        foreach ($bundle_data['products'] as $product_key => $product) {
            $data['conditions']['conditions'][$condition_index] =
                [
                    'operator' => 'in',
                    'condition' => 'products',
                    'value' => [
                        $product_key => [
                            'product_id' => $product['product_id'],
                            'amount'     => $product['amount'],
                        ],
                    ],
                ];
            if (!empty($product['product_options'])) {
                $data['conditions']['conditions'][$condition_index]['value'][$product_key]['product_options']
                    = $product['product_options'];
            }
            $condition_index++;
        }

        /**
         * Executes before updating promotion allows changing promotion data.
         *
         * @param array<string> $bundle_data         Bundle information.
         * @param array<string> $bundle_descriptions Bundle descriptions.
         * @param int           $promotion_id        Promotion identifier.
         * @param array<string> $data                Promotion data.
         */
        fn_set_hook('product_bundles_service_update_hidden_promotion_before_update', $bundle_data, $bundle_descriptions, $promotion_id, $data);

        $lang_code = isset($bundle_data['lang_code']) ? $bundle_data['lang_code'] : CART_LANGUAGE;

        return fn_update_promotion($data, $promotion_id, $lang_code);
    }

    /**
     * Calculates promotion bonus according to bundle data.
     *
     * @param array<string>     $bundle_data    Bundle information.
     * @param array<int, float> $product_prices Current prices of product.
     *
     * @return float|int
     */
    public function calculatePromotionBonus(array $bundle_data, array $product_prices = [])
    {
        $result = 0;
        if (empty($bundle_data['products'])) {
            return $result;
        }

        if (is_string($bundle_data['products'])) {
            $bundle_data['products'] = unserialize($bundle_data['products']);
        }
        foreach ($bundle_data['products'] as $product) {
            if (isset($product_prices[$product['product_id']])) {
                $price = $product_prices[$product['product_id']];
            } else {
                $auth = fn_fill_auth();
                $price = fn_get_product_price($product['product_id'], $product['amount'], $auth);
            }
            $discount = $this->calculateDiscount($product['modifier_type'], $price, $product['modifier']);
            $result += $discount * $product['amount'];
        }
        return $result;
    }

    /**
     * Calculates taxed prices for every product in the bundle.
     *
     * @param array<string, array<string>> $bundle_product_info Product information from bundle.
     * @param array<string>                $product_data        Product information from fn_get_products.
     * @param array<string, string>        $auth                Authentication information.
     *
     * @return void
     */
    public function calculateTaxedDiscountedProductPrice(array &$bundle_product_info, array $product_data, array $auth)
    {
        $bundle_product_info['tax_ids'] = $product_data['tax_ids'];
        fn_get_taxed_and_clean_prices($bundle_product_info, $auth);
    }

    /**
     * Deletes specified product bundle.
     *
     * @param int $bundle_id Bundle identifier.
     *
     * @return void
     */
    public function deleteBundle($bundle_id)
    {
        /**
         * Allows changing information before deleting product bundle.
         *
         * @param int $bundle_id Bundle identifier.
         */
        fn_set_hook('product_bundle_service_delete_bundle_pre', $bundle_id);

        $linked_promotion_id = db_get_field('SELECT linked_promotion_id FROM ?:product_bundles WHERE bundle_id = ?i', $bundle_id);
        if ($linked_promotion_id) {
            fn_delete_promotions([$linked_promotion_id]);
        }

        $this->deleteImage($bundle_id);

        db_query('DELETE FROM ?:product_bundles WHERE bundle_id = ?i', $bundle_id);
        db_query('DELETE FROM ?:product_bundle_descriptions WHERE bundle_id = ?i', $bundle_id);
        db_query('DELETE FROM ?:product_bundle_product_links WHERE bundle_id = ?i', $bundle_id);
    }

    /**
     * Creates link between bundle identifier and product identifier.
     *
     * @param int                          $bundle_id     Bundle identifier.
     * @param array<string, array<string>> $products_data Products info.
     *
     * @return void
     */
    private function updateLinks($bundle_id, array $products_data)
    {
        if (empty($products_data)) {
            return;
        }

        $data = [];
        db_query('DELETE FROM ?:product_bundle_product_links WHERE bundle_id = ?s', $bundle_id);
        foreach ($products_data as $product_info) {
            $data[] = [
                'product_id'           => $product_info['product_id'],
                'amount'               => (int) $product_info['amount'],
                'show_on_product_page' => $product_info['show_on_product_page'] ?? YesNo::NO,
                'bundle_id'            => $bundle_id,
                'all_variants'         => $product_info['any_variation'] ?? YesNo::NO,
            ];
        }

        /**
         * Allows changing links information before their creation.
         *
         * @param int                                  $bundle_id     Bundle identifier, to which products should be linked.
         * @param array<string, array<string>>         $products_data Bundle products information.
         * @param array<int, array<string, int|string> $data          Links information.
         */
        fn_set_hook('product_bundle_service_update_links', $bundle_id, $products_data, $data);

        db_query('INSERT INTO ?:product_bundle_product_links ?m', $data);
    }

    /**
     * Updates product bundle image.
     *
     * @param int    $bundle_id Bundle identifier.
     * @param string $lang_code Language code.
     *
     * @return void
     */
    protected function updateImage($bundle_id, $lang_code = DESCR_SL)
    {
        if (empty($bundle_id)) {
            return;
        }

        $exists_image_ids = db_get_hash_array(
            'SELECT bundle_image_id, lang_code FROM ?:product_bundle_images WHERE bundle_id = ?i',
            'lang_code',
            $bundle_id
        );

        if (fn_filter_uploaded_data('bundle_main_image_icon')) {
            $image_data = [
                'bundle_id' => $bundle_id,
                'lang_code' => $lang_code
            ];

            if (isset($exists_image_ids[$lang_code])) {
                $this->deleteImage($bundle_id, $lang_code);
            }

            $bundle_image_id = db_query('INSERT INTO ?:product_bundle_images ?e', $image_data);
        }

        if (empty($bundle_image_id) && empty($exists_image_ids[$lang_code])) {
            return;
        }

        $bundle_image_id = empty($bundle_image_id) ? $exists_image_ids[$lang_code] : $bundle_image_id;
        fn_attach_image_pairs('bundle_main', 'product_bundle', $bundle_image_id, $lang_code);

        if (!empty($exists_image_ids)) {
            return;
        }
        $this->addLinksToLangs($bundle_id, $lang_code);
    }

    /**
     * Deletes image attached to product bundle.
     *
     * @param int         $bundle_id Bundle identifier.
     * @param string|null $lang_code Language code.
     *
     * @return void
     */
    protected function deleteImage($bundle_id, $lang_code = null)
    {
        $conditions = [
            'bundle_id' => $bundle_id,
            'lang_code' => $lang_code,
        ];

        $images = db_get_array('SELECT bundle_image_id FROM ?:product_bundle_images WHERE ?w', array_filter($conditions));

        if (empty($images)) {
            return;
        }

        $bundle_image_ids = array_column($images, 'bundle_image_id');
        $bundle_image_pairs = fn_get_image_pairs($bundle_image_ids, 'product_bundle', ImagePairTypes::MAIN);

        db_query('DELETE FROM ?:product_bundle_images WHERE bundle_image_id IN (?n)', $bundle_image_ids);

        foreach ($bundle_image_pairs as $image_pairs) {
            $pair = reset($image_pairs);
            if (!isset($pair['image_id'], $pair['pair_id'])) {
                continue;
            }
            fn_delete_image($pair['image_id'], $pair['pair_id'], 'product_bundle');
        }
    }

    /**
     * Adds information about bundle to several specified languages.
     *
     * @param int           $bundle_id     Bundle identifier.
     * @param string        $original_lang Original language.
     * @param array<string> $cloned_langs  Additional languages.
     *
     * @return void
     */
    protected function addLinksToLangs($bundle_id, $original_lang = CART_LANGUAGE, array $cloned_langs = [])
    {
        if (empty($bundle_id)) {
            return;
        }

        if (empty($cloned_langs)) {
            $languages = Languages::getAll();
            unset($languages[$original_lang]);

            $cloned_langs = array_keys($languages);
        }

        $bundle_image = db_get_row(
            'SELECT promo_images.bundle_image_id, images_links.pair_id'
            . ' FROM ?:product_bundle_images AS promo_images'
            . ' INNER JOIN ?:images_links AS images_links'
            . '     ON images_links.object_id = promo_images.bundle_image_id AND images_links.object_type = ?s'
            . ' WHERE promo_images.bundle_id = ?i AND promo_images.lang_code = ?s',
            'product_bundle',
            $bundle_id,
            $original_lang
        );

        if (empty($bundle_image)) {
            return;
        }

        foreach ($cloned_langs as $lang_code) {
            $promo_image_id = db_replace_into('product_bundle_images', [
                'bundle_id' => $bundle_id,
                'lang_code'    => $lang_code
            ]);
            if (empty($promo_image_id)) {
                continue;
            }
            fn_add_image_link($promo_image_id, $bundle_image['pair_id']);
        }
    }

    /**
     * Calculates discount for product.
     *
     * @param string $type  Discount type.
     * @param float  $price Product price.
     * @param float  $value Discount value.
     *
     * @return float|int
     */
    public function calculateDiscount($type, $price, $value)
    {
        if (empty($value) || empty($price)) {
            return 0;
        }

        switch ($type) {
            case DiscountType::TO_PERCENTAGE:
                $discount = $price * (100 - $value) / 100;
                break;
            case DiscountType::BY_PERCENTAGE:
                $discount = $price * $value / 100;
                break;
            case DiscountType::TO_FIXED_AMOUNT:
                $discount = $price - $value;
                break;
            case DiscountType::BY_FIXED_AMOUNT:
            default:
                $discount = $value;
                break;
        }

        if ($discount < 0) {
            $discount = 0;
        }

        return $discount;
    }

    /**
     * Changes product from bundle on another product which is a variant of original.
     *
     * @param array<string> $original_product Base product information.
     * @param int           $variant_id       Variant product id.
     *
     * @return array<string>
     */
    public function swapProductWithVariant(array $original_product, $variant_id)
    {
        if ((int) $original_product['product_id'] === $variant_id) {
            return $original_product;
        }
        list($new_product,) = fn_get_products(['pid' => $variant_id]);
        if (empty($new_product)) {
            return [];
        }
        $product = $original_product;

        fn_gather_additional_products_data($new_product, [
            'get_icon'       => true,
            'get_detailed'   => true,
            'get_additional' => false,
            'get_options'    => true,
            'get_discounts'  => true
        ]);
        $new_product = reset($new_product);

        $auth = fn_fill_auth();
        $show_taxed_prices = YesNo::toBool(Registry::get('settings.Appearance.show_prices_taxed_clean'));
        $tax_exempt = !YesNo::toBool($auth['tax_exempt']);
        if ($show_taxed_prices && $tax_exempt) {
            fn_get_taxed_and_clean_prices($new_product, $auth);
            if (!empty($new_product['clean_price'])) {
                $product['clean_price'] = $new_product['clean_price'];
            } else {
                unset($product['clean_price']);
            }

            if (!empty($new_product['taxed_price'])) {
                $product['taxed_price'] = $new_product['taxed_price'];
            } else {
                unset($product['taxed_price']);
            }

            if (!empty($new_product['taxes'])) {
                $product['taxes'] = $new_product['taxes'];
            } else {
                unset($product['taxes']);
            }

            if (!empty($new_product['included_tax'])) {
                $product['included_tax'] = $new_product['included_tax'];
            } else {
                unset($product['included_tax']);
            }
        } else {
            unset($product['clean_price'], $product['taxed_price'], $product['taxes'], $product['included_tax']);
        }

        $product['product_id'] = $new_product['product_id'];
        $product['product_name'] = $new_product['product'];
        $product['price'] = $new_product['price'];
        $product['amount'] = 1;
        $product['discount'] = $this->calculateDiscount($product['modifier_type'], $new_product['price'], (float) $product['modifier']);
        if (!empty($product['included_tax'])) {
            $product['discounted_price'] = $product['clean_price'] - $product['discount'];
            $product['taxed_price'] -= $product['discount'];
        } elseif (isset($product['clean_price'])) {
            $product['discounted_price'] = $product['taxed_price'] = $product['clean_price'] - $product['discount'];
        } else {
            $product['discounted_price'] = $product['taxed_price'] = $product['price'] - $product['discount'];
        }
        if ($product['discounted_price'] < 0) {
            $product['discounted_price'] = 0.0;
        }

        $product['any_variation'] = YesNo::NO;
        $variation_map = ProductVariationsServiceProvider::getProductIdMap();
        $is_parent_variation = $variation_map->isParentProduct($product['product_id']);
        if ($is_parent_variation) {
            $product['any_variation'] = empty($variation_map->getProductChildrenIds($product['product_id'])) ?
                YesNo::NO : YesNo::YES;
        } else {
            $product['any_variation'] = empty($variation_map->getVariationSubGroupProductIds($product['product_id'])) ?
                YesNo::NO : YesNo::YES;
        }

        $product['main_pair'] = $new_product['main_pair'];
        $product['product_data'] = $new_product;
        return $product;
    }

    /**
     * Recalculates total cost and discounted cost of specified bundle.
     *
     * @param array<string, float|array<string, float>> $bundle_data Bundle information.
     *
     * @return array<string, float|array<string, float>>
     */
    public function recalculateBundlePrices(array $bundle_data)
    {
        $bundle_data['total_price'] = $bundle_data['discounted_price'] = $bundle_data['taxed_price'] = 0.0;
        $included_tax = $is_tax_exists = false;
        foreach ($bundle_data['products'] as $product) {
            $included_tax = isset($product['included_tax']) ? $included_tax || $product['included_tax'] : $included_tax;
            $is_tax_exists = isset($product['taxes'])
                ? ($is_tax_exists || ($product['clean_price'] - $product['taxed_price']))
                : $is_tax_exists;

            $bundle_data['total_price'] += (float) $product['price'] * $product['amount'];
            $bundle_data['taxed_price'] += isset($product['taxed_price'])
                ? $product['taxed_price'] * $product['amount']
                : $product['discounted_price'] * $product['amount'];
            $bundle_data['discounted_price'] += (float) $product['discounted_price'] * $product['amount'];
        }
        $bundle_data['is_tax_exists'] = $is_tax_exists;
        if (!$included_tax) {
            $bundle_data['taxed_price'] = 0;
        }

        return $bundle_data;
    }
}
