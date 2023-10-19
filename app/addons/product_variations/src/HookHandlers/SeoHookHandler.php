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



namespace Tygh\Addons\ProductVariations\HookHandlers;


use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Addons\ProductVariations\Product\Group\GroupProduct;
use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Application;
use Tygh\Enum\SiteArea;
use Tygh\Registry;
use Tygh\Tygh;

/**
 * This class describes hook handlers related to routing and the seo add-on
 *
 * @package Tygh\Addons\ProductVariations\HookHandlers
 */
class SeoHookHandler
{
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "url_pre" hook handler.
     *
     * Actions performed:
     *  - If a URL is built for the page of a child variation, the result is modified
     *      to use the SEO name of the parent product and to pass the identifier of variation in a GET parameter.
     *
     * @see fn_url
     */
    public function onUrlPre(&$url, $area, $protocol, $lang_code)
    {
        if ($area !== 'C'
            || strpos($url, 'products.view') === false
            || Registry::get('addons.seo.status') !== 'A'
        ) {
            return;
        }

        $parsed_url = parse_url($url);
        $dispatch = null;

        if (empty($parsed_url['query'])) {
            return;
        }

        parse_str($parsed_url['query'], $parsed_query);

        if (isset($parsed_query['dispatch'])) {
            $dispatch = $parsed_query['dispatch'];
        } elseif (isset($parsed_url['path'])) {
            $dispatch = $parsed_url['path'];
        }

        if (empty($parsed_query['product_id']) || $dispatch !== 'products.view') {
            return;
        }

        $product_id = $parsed_query['product_id'];
        $parent_product_id = ServiceProvider::getProductIdMap()->getParentProductId($product_id);

        if (!$parent_product_id) {
            ServiceProvider::getProductIdMap()->setParentProductId($product_id, 0);
            return;
        }

        if (Registry::get('runtime.seo.is_creating_canonical_url')) {
            $url = strtr($url, ["product_id={$product_id}" => "product_id={$parent_product_id}"]);
        } else {
            $url = strtr($url, ["product_id={$product_id}" => "product_id={$parent_product_id}&variation_id={$product_id}"]);
        }
    }

    /**
     * The "get_route" hook handler.
     *
     * IMPORTANT! This handler must run after the handler of the SEO add-on,
     * because it expects the correct `product_id` retrieved from the SEO name.
     *
     * Actions performed:
     *  - If the request refers to a child variation, this hook handler forms a redirect to the correct URL (with the SEO name of the parent product and a GET parameter)
     *  - If the request contains the GET parameter of a child variation, this hook handler substitutes `product_id`; that way, the controller always works with the correct product identifiers.
     *
     * @see fn_get_route
     */
    public function onGetRoute(&$req, &$result, $area, &$is_allowed_url)
    {
        if ($area !== 'C'
            || empty($req['dispatch'])
            || $req['dispatch'] !== 'products.view'
            || empty($req['product_id'])
            || Registry::get('addons.seo.status') !== 'A'
        ) {
            return;
        }

        $product_id_map = ServiceProvider::getProductIdMap();

        if (empty($req['variation_id'])) {
            $parent_product_id = $product_id_map->getParentProductId($req['product_id']);

            if ($parent_product_id) {
                $lang_code = Registry::get('settings.Appearance.frontend_default_language');

                $result = [INIT_STATUS_REDIRECT, fn_url("products.view?product_id={$req['product_id']}", 'C', 'rel', $lang_code)];
            }
        } else {
            if ((int) $req['variation_id'] === (int) $req['product_id']) {
                unset($req['variation_id']);
            } elseif ($product_id_map->getParentProductId($req['variation_id']) === (int) $req['product_id']) {
                $req['product_id'] = (int) $req['variation_id'];
                unset($req['variation_id']);
            } else {
                $is_allowed_url = false;
            }
        }
    }

    /**
     * The "variation_group_mark_product_as_main_post" hook handler.
     *
     * Actions performed:
     *  - Moves SEO name from the old parent product to a new one.
     *    This is necessary, because SEO name must remain the same when the main product changes.
     *
     * @see \Tygh\Addons\ProductVariations\Service::saveGroup
     */
    public function onVariationGroupMarkProductAsMainPost($service, $group, GroupProduct $from_group_product, GroupProduct $to_group_product)
    {
        $new_main_product_id = $to_group_product->getProductId();
        $old_main_product_id = $from_group_product->getProductId();

        $query = ServiceProvider::getQueryFactory()->createQuery(
            'seo_names',
            ['type' => 'p', 'object_id' => [$old_main_product_id, $new_main_product_id]],
            ['*']
        );

        $on_insert_list = [];

        foreach ($query->select() as $item) {
            if ($item['object_id'] == $old_main_product_id) {
                $item['object_id'] = $new_main_product_id;
            } elseif ($item['object_id'] == $new_main_product_id) {
                $item['object_id'] = $old_main_product_id;
            }

            $on_insert_list[] = $item;
        }

        if ($on_insert_list) {
            $query = ServiceProvider::getQueryFactory()->createQuery(
                'seo_names',
                ['type' => 'p', 'object_id' => [$old_main_product_id, $new_main_product_id]]
            );
            $query->delete();

            $query = ServiceProvider::getQueryFactory()->createQuery('seo_names');
            $query->multipleInsert($on_insert_list);
        }
    }

    /**
     * The "google_sitemap_generate_link_post" hook handler.
     *
     * Actions performed:
     *  - Unlink the variation products to hide it from sitemap.
     *
     * @see fn_google_sitemap_generate_link
     */
    public function onGoogleSiteMapGenerateLinkPost($type, $id, $languages, $extra, $storefront_id, &$links)
    {
        if ($type != 'product') {
            return;
        }

        $product_id_map = ServiceProvider::getProductIdMap();

        if ($product_id_map->isChildProduct($id)) {
            $links = [];
        }
    }

    /**
     * The "seo_get_schema_org_markup_items_post" hook handler.
     *
     * Actions performed:
     *  - Adds aggregate offer into the Product markup item when viewing a product with variations.
     *
     * @param array<string, int>                                              $product_data Product data to get markup items from
     * @param bool                                                            $show_price   Whether product price must be shown
     * @param string                                                          $currency     Currency to get product price in
     * @param array<string, array<string, array<int, array<string, string>>>> $markup_items Schema.org markup items
     *
     * @param-out array<string, array<string, array<int, array<string, int|list<array{@type: string, availability: string, price?: float, priceCurrency?: string, url: string}>|mixed|string>>>> $markup_items Schema.org markup items
     *
     * @see fn_seo_get_schema_org_markup_items()
     */
    public function onSeoGetSchemaOrgMarkupItemsPost(array $product_data, $show_price, $currency, array &$markup_items)
    {
        if (!$show_price) {
            return;
        }

        if (empty($product_data['variation_group_id'])) {
            return;
        }

        $aggregate_offer = reset($markup_items['product']['offers']);
        if (!isset($aggregate_offer['@type']) || $aggregate_offer['@type'] !== 'http://schema.org/Offer') {
            return;
        }

        $group_repository = ServiceProvider::getGroupRepository();
        $group = $group_repository->findGroupById($product_data['variation_group_id']);

        if (!$group || !$group->hasFeaturePurpose(FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM)) {
            return;
        }

        $offers = $group->getProducts();

        if (count($offers) === 0) {
            return;
        }

        $offer_id = key($markup_items['product']['offers']);

        $base_offer_url = $aggregate_offer['url'];
        $low_price = $aggregate_offer['price'];
        $aggregate_offer = [
            '@type'         => 'http://schema.org/AggregateOffer',
            'lowPrice'      => $low_price,
            'priceCurrency' => $aggregate_offer['priceCurrency'],
            'availability'  => $aggregate_offer['availability'],
            'offerCount'    => count($offers),
            'offers'        => [],
        ];

        $auth = Tygh::$app['session']['auth'];
        if (count($offers) <= Registry::ifGet('config.product_variations.seo_snippet_offers_threshold', 100)) {
            $product_repository = ServiceProvider::getProductRepository();
            $products = $product_repository->findProducts(array_keys($offers->toArray()), ['product_name'], SiteArea::STOREFRONT);
            $product_features = fn_seo_get_schema_org_products_features(array_column($products, 'product_id'));

            foreach ($products as $product) {
                fn_promotion_apply('catalog', $product, $auth);

                $price = fn_format_price_by_currency(
                    $product['price'],
                    CART_PRIMARY_CURRENCY,
                    $currency
                );

                if ($price < $low_price) {
                    $low_price = $price;
                }

                if (!isset($product['schema_org_features'])) {
                    $product['schema_org_features'] = isset($product_features[$product['product_id']])
                        ? $product_features[$product['product_id']]
                        : [];
                }

                $aggregate_offer['offers'][] = [
                    '@type'         => 'http://schema.org/Offer',
                    'name'          => fn_seo_get_schema_org_product_name($product),
                    'sku'           => fn_seo_get_schema_org_product_sku($product),
                    'gtin'          => fn_seo_get_schema_org_product_feature($product['schema_org_features'], 'gtin'),
                    'mpn'           => fn_seo_get_schema_org_product_feature($product['schema_org_features'], 'mpn'),
                    'availability'  => fn_seo_get_schema_org_product_availability($product),
                    'url'           => fn_link_attach($base_offer_url, "variation_id={$product['product_id']}"),
                    'price'         => $price,
                    'priceCurrency' => $aggregate_offer['priceCurrency'],
                ];
            }
        }

        $aggregate_offer['lowPrice'] = $low_price;

        $markup_items['product']['offers'][$offer_id] = $aggregate_offer;
    }
}
