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

use Tygh\Addons\VendorRating\Service\VendorService;
use Tygh\Addons\VendorRating\ServiceProvider;
use Tygh\Application;

/**
 * Class ProductsHookHandler contains product-specific hook processors.
 *
 * @package Tygh\Addons\VendorRating\HookHandlers
 */
class ProductsHookHandler
{
    /**
     * @var \Tygh\Application
     */
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "products_sorting" hook handler.
     *
     * Actions performed:
     *     - Adds the "Vendor rating" to the list of possible products sort criteria.
     *
     * @see \fn_get_products_sorting()
     */
    public function onGetSorting(&$sorting)
    {
        $sorting['absolute_vendor_rating'] = [
            'description'   => __('vendor_rating.vendor_rating'),
            'default_order' => 'desc',
        ];
    }

    /**
     * The "get_products" hook handler.
     *
     * Actions performed:
     *     - Adds query conditions to implement the "Vendor rating" sorting criteria.
     *
     * @see \fn_get_products()
     */
    public function onGetProducts(
        $params,
        &$fields,
        &$sortings,
        $condition,
        &$join,
        $sorting,
        $group_by,
        $lang_code,
        $having
    ) {
        $params = array_merge(
            [
                'sort_by' => null,
                'extend'  => [],
            ],
            $params
        );
        if (!$params['sort_by']) {
            return;
        }

        /** @var \Tygh\Database\Connection $db */
        $db = $this->application['db'];

        if ($params['sort_by'] === 'absolute_vendor_rating' && !in_array('absolute_vendor_rating', $params['extend'])) {
            $params['extend'][] = 'absolute_vendor_rating';
        }

        if (in_array('absolute_vendor_rating', $params['extend'])) {
            $fields['absolute_vendor_rating'] = 'absolute_rating.rating AS absolute_vendor_rating';
            $join .= $db->quote(
                ' LEFT JOIN ?:absolute_rating AS absolute_rating'
                . ' ON absolute_rating.object_id = products.company_id'
                . ' AND absolute_rating.object_type = ?s',
                VendorService::RATING_STORAGE_OBJECT_TYPE
            );

            $sortings['absolute_vendor_rating'] = 'absolute_rating.rating';
        }
    }

    /**
     * The "get_product_data_post" hook handler.
     *
     * Actions performed:
     *     - Loads relative vendor rating for a product.
     *
     * @see \fn_get_product_data()
     */
    public function afterGetProduct(&$product_data, $auth, $preview, $lang_code)
    {
        if (empty($product_data['company_id'])) {
            return;
        }

        $service = ServiceProvider::getVendorService();
        $product_data['relative_vendor_rating'] = $service->getRelativeRating($product_data['company_id']);
    }
}
