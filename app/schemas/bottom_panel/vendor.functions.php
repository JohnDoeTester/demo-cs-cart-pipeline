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

use Tygh\Tools\Url;
use Tygh\Registry;
use Tygh\Enum\YesNo;

/**
 * Checks availability product for company and gets url params. Lead to product update, products list otherwise
 *
 * @param \Tygh\Tools\Url $url Url which user come from
 *
 * @return array Redirecting params
 */
function fn_bottom_panel_mve_get_product_url_params(Url $url)
{
    $product_id = $url->getQueryParam('product_id');

    if (fn_company_products_check($product_id)) {
        return [
            'dispatch' => 'products.update',
            'product_id' => $product_id
        ];
    } else {
        return [
            'dispatch' => 'products.manage',
        ];
    }
};

/**
 * Checks availability page for company and gets url params. Lead to page update, pages list otherwise
 *
 * @param \Tygh\Tools\Url $url Url which user come from
 *
 * @return array Redirecting params
 */
function fn_bottom_panel_mve_get_page_url_params(Url $url)
{
    $page_id = $url->getQueryParam('page_id');

    if (!empty(fn_get_page_data($page_id))) {
        return [
            'dispatch' => 'pages.update',
            'page_id' => $page_id
        ];
    } else {
        return [
            'dispatch' => 'pages.manage',
        ];
    }
};

/**
 * Checks availability company for company and gets url params. Lead to company update, companies list otherwise
 *
 * @param \Tygh\Tools\Url $url Url which user come from
 *
 * @return array Redirecting params
 */
function fn_bottom_panel_mve_get_company_url_params(Url $url)
{
    $company_id = $url->getQueryParam('company_id');

    if (Registry::get('runtime.company_id') && Registry::get('runtime.company_id') != $company_id) {
        return [
            'dispatch' => 'companies.manage',
        ];
    } else {
        return [
            'dispatch' => 'companies.update',
            'company_id' => $company_id
        ];
    }
};

/**
 * Checks availability order for company and gets url params. Lead to order details, orders list otherwise
 *
 * @param \Tygh\Tools\Url $url Url which user come from
 *
 * @return array Redirecting params
 */
function fn_bottom_panel_mve_get_order_url_params(Url $url)
{
    $order_id = $url->getQueryParam('order_id');

    $order_info = fn_get_order_info($order_id, false, true, true, false);

    if (!empty($order_info)) {
        return [
            'dispatch' => 'orders.details',
            'order_id' => $order_id
        ];
    } else {
        return [
            'dispatch' => 'orders.manage',
        ];
    }
}

/**
 * Checks availability products for company and gets url params.
 * Lead to products list, dashboard otherwise
 *
 * @param Url $url Url which come from customer
 *
 * @psalm-return array{dispatch: string, cid?: int, subcats?: "Y"}
 *
 * @return array{string: string|int}
 */
function fn_bottom_panel_mve_get_company_products_url_params(Url $url)
{
    $category_id = $url->getQueryParam('category_id');

    if (empty($category_id)) {
        return [
            'dispatch' => 'index.index'
        ];
    }

    return [
        'dispatch' => 'products.manage',
        'cid' => (int) $category_id,
        'subcats' => YesNo::YES
    ];
}
