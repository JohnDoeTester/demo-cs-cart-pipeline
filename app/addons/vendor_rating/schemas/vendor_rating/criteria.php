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

use Tygh\Addons\VendorRating\Service\VendorPlanService;
use Tygh\Addons\VendorRating\Service\VendorService;

defined('BOOTSTRAP') or die('Access denied');

$all_order_statuses = fn_get_statuses();
$paid_order_statuses = fn_get_settled_order_statuses();
$paid_order_status_names = [];
foreach ($paid_order_statuses as $order_id) {
    $paid_order_status_names[] = $all_order_statuses[$order_id]['description'];
}
$paid_order_status_names = implode(', ', $paid_order_status_names);

/**
 * This schema describes vendor rating criteria registered in the product.
 * Each element of the schema has the following syntax:
 *
 * 'name'           => [
 *     'template' => (string) LanguageVariable,
 *     'params'   => [
 *         (string) LanguageVariableParameter => (string) LanguageVariableParameterValue,
 *     ],
 * ],
 * 'description'    => [
 *     'template' => (string) LanguageVariable,
 *     'params'   => [
 *         (string) LanguageVariableParameter => (string) LanguageVariableParameterValue,
 *     ],
 * ],
 * 'value_provider' => [
 *     (string) CriteriaProviderIdentifier,
 *     (string) CriteriaProviderMethod,
 * ],
 * 'variable_name'  => (string) FormulaVariableName,
 * 'addon'          => (string|null|array) AddonIdentifier
 *
 * Where:
 * - LanguageVariable is the identifier of the language variable that stores the template of the criteria name
 *   or description.
 * - LanguageVariableParameter is the language variable parameter name (e.g. '[param]').
 * - LanguageVariableParameterValue is the language variable parameter value.
 * - CriteriaProviderIdentifier is the identifier of the criteria provider.
 *   The provider must be registered in the service container Tygh::$app.
 * - CriteriaProviderMethod is the name of the method that must be called in the provider to get the critierion value.
 * - FormulaVariableName is the name of the variable that is used in the formula.
 *   Must contain only alphabetic characters (e.g. 'randomNumber').
 * - AddonIdentifier is the identifier of the add-on this criteria is provided by.
 */
return [
    'paid_orders_count'         => [
        'name'           => [
            'template' => 'vendor_rating.criteria.paid_orders_count.name',
            'params'   => [],
        ],
        'description'    => [
            'template' => 'vendor_rating.criteria.paid_orders_count.description',
            'params'   => [
                '[statuses]' => $paid_order_status_names,
            ],
        ],
        'value_provider' => [
            /** @see \Tygh\Addons\VendorRating\Criteria\OrdersCriteria::getPaidCount() */
            'addons.vendor_rating.orders_criteria',
            'getPaidCount',
        ],
        'variable_name'  => 'paidOrdersCount',
        'addon'          => null,
    ],
    'paid_orders_total'         => [
        'name'           => [
            'template' => 'vendor_rating.criteria.paid_orders_total.name',
            'params'   => [],
        ],
        'description'    => [
            'template' => 'vendor_rating.criteria.paid_orders_total.description',
            'params'   => [
                '[statuses]' => $paid_order_status_names,
            ],
        ],
        'value_provider' => [
            /** @see \Tygh\Addons\VendorRating\Criteria\OrdersCriteria::getPaidTotal() */
            'addons.vendor_rating.orders_criteria',
            'getPaidTotal',
        ],
        'variable_name'  => 'paidOrdersTotal',
        'addon'          => null,
    ],
    'active_products_count'     => [
        'name'           => [
            'template' => 'vendor_rating.criteria.active_products_count.name',
            'params'   => [],
        ],
        'description'    => [
            'template' => 'vendor_rating.criteria.active_products_count.description',
            'params'   => [],
        ],
        'value_provider' => [
            /** @see \Tygh\Addons\VendorRating\Criteria\ProductsCriteria::getActiveCount() */
            'addons.vendor_rating.products_criteria',
            'getActiveCount',
        ],
        'variable_name'  => 'activeProductsCount',
        'addon'          => null,
    ],
    'manual_vendor_rating'      => [
        'name'           => [
            'template' => 'vendor_rating.criteria.manual_vendor_rating.name',
            'params'   => [],
        ],
        'description'    => [
            'template' => 'vendor_rating.criteria.manual_vendor_rating.description',
            'params'   => [],
        ],
        'value_provider' => [
            /** @see \Tygh\Addons\VendorRating\Criteria\VendorsCriteria::getManualRating() */
            'addons.vendor_rating.vendors_criteria',
            'getManualRating',
        ],
        'variable_name'  => 'manualVendorRating',
        'addon'          => null,
    ],
    'vendor_reviews_rating'     => [
        'name'           => [
            'template' => 'vendor_rating.criteria.vendor_reviews_rating.name',
            'params'   => [],
        ],
        'description'    => [
            'template' => 'vendor_rating.criteria.vendor_reviews_rating.description',
            'params'   => [],
        ],
        'range'          => [
            'min' => VendorService::MIN_RATING,
        ],
        'value_provider' => [
            /** @see \Tygh\Addons\VendorRating\Criteria\ReviewsCriteria::getVendorRating() */
            'addons.vendor_rating.reviews_criteria',
            'getVendorRating',
        ],
        'variable_name'  => 'vendorReviewsRating',
        'addon'          => 'discussion',
    ],
    'product_reviews_rating'   => [
        'name'           => [
            'template' => 'vendor_rating.criteria.product_reviews_rating.name',
            'params'   => [],
        ],
        'description'    => [
            'template' => 'vendor_rating.criteria.product_reviews_rating.description',
            'params'   => [],
        ],
        'value_provider' => [
            /** @see \Tygh\Addons\VendorRating\Criteria\ReviewsCriteria::getProductsRating() */
            'addons.vendor_rating.reviews_criteria',
            'getProductsRating',
        ],
        'variable_name'  => 'productReviewsRating',
        'addon'          => [
            'discussion',
            'product_reviews',
        ],
    ],
    'manual_vendor_plan_rating' => [
        'name'           => [
            'template' => 'vendor_rating.criteria.manual_vendor_plan_rating.name',
            'params'   => [],
        ],
        'description'    => [
            'template' => 'vendor_rating.criteria.manual_vendor_plan_rating.description',
            'params'   => [],
        ],
        'range'          => [
            'min' => VendorPlanService::MIN_RATING,
            'max' => VendorPlanService::MAX_RATING,
        ],
        'value_provider' => [
            /** @see \Tygh\Addons\VendorRating\Criteria\VendorPlansCriteria::getManualRating() */
            'addons.vendor_rating.vendor_plans_criteria',
            'getManualRating',
        ],
        'variable_name'  => 'manualVendorPlanRating',
        'addon'          => 'vendor_plans',
    ],
    'returns_count'             => [
        'name'           => [
            'template' => 'vendor_rating.criteria.returns_count.name',
            'params'   => [],
        ],
        'description'    => [
            'template' => 'vendor_rating.criteria.returns_count.description',
            'params'   => [],
        ],
        'value_provider' => [
            /** @see \Tygh\Addons\VendorRating\Criteria\ReturnsCriteria::getCount() */
            'addons.vendor_rating.returns_criteria',
            'getCount',
        ],
        'variable_name'  => 'returnsCount',
        'addon'          => 'rma',
    ],
];
