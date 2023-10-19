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

use Tygh\Enum\ImagePairTypes;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Settings;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Saves fees product integrity after disabling-enabling add-on.
 *
 * @param string $new_status New status code
 *
 * @return void
 */
function fn_settings_actions_addons_post_vendor_debt_payout($new_status)
{
    if (defined('VENDOR_DEBT_PAYOUT_INSTALL')) {
        return;
    }

    $payout_category_id = (int) db_get_field(
        'SELECT category_id'
        . ' FROM ?:categories'
        . ' WHERE category_type = ?s',
        'D' // debt_payout category type
    );

    if ($new_status === ObjectStatuses::DISABLED) {
        if ($payout_category_id) {
            fn_change_category_status($payout_category_id, ObjectStatuses::DISABLED);
        }
        return;
    }

    if (empty($payout_category_id)) {
        $payout_category_id = fn_update_category(
            [
                'category'             => __('vendor_debt_payout.debt_payout'),
                'parent_id'            => 0,
                'description'          => '',
                'status'               => ObjectStatuses::HIDDEN,
                'page_title'           => '',
                'meta_description'     => '',
                'meta_keywords'        => '',
                'usergroup_ids'        => 0,
                'position'             => '',
                'product_details_view' => 'default',
                'use_custom_templates' => YesNo::NO,
                'category_type'        => 'D', // debt_payout category type
            ]
        );
    } else {
        fn_change_category_status($payout_category_id, ObjectStatuses::HIDDEN);
    }

    $payout_product_id = (int) db_get_field(
        'SELECT product_id'
        . ' FROM ?:products'
        . ' WHERE product_type = ?s',
        'D' // debt_payout product type
    );

    if (empty($payout_product_id)) {
        $_REQUEST['product_main_image_data'] = [
            [
                'detailed_alt' => '',
                'type'         => ImagePairTypes::MAIN,
                'object_id'    => 0,
                'position'     => 0,
            ],
        ];

        Registry::set('runtime.allow_upload_external_paths', true, true);

        $_REQUEST['file_product_main_image_detailed'] = [
            fn_get_theme_path('[themes]/[theme]/media/images/addons/vendor_debt_payout/product_image.png'),
        ];
        $_REQUEST['type_product_main_image_detailed'] = [
            'server',
        ];

        fn_update_product(
            [
                'product'              => __('vendor_debt_payout.debt_payout'),
                'company_id'           => 0,
                'category_ids'         => [$payout_category_id],
                'main_category'        => $payout_category_id,
                'price'                => 0,
                'full_description'     => '',
                'status'               => ObjectStatuses::HIDDEN,
                'options_type'         => '',
                'exceptions_type'      => '',
                'product_code'         => '',
                'list_price'           => 0,
                'amount'               => '1',
                'zero_price_action'    => '',
                'tracking'             => '',
                'min_qty'              => null,
                'max_qty'              => null,
                'qty_step'             => null,
                'list_qty_count'       => null,
                'tax_ids'              => '',
                'usergroup_ids'        => 0,
                'avail_since'          => '',
                'out_of_stock_actions' => YesNo::NO,
                'details_layout'       => 'default',
                'short_description'    => '',
                'search_words'         => '',
                'promo_text'           => '',
                'page_title'           => '',
                'meta_description'     => '',
                'meta_keywords'        => '',
                'weight'               => 0,
                'free_shipping'        => YesNo::YES,
                'shipping_freight'     => 0,
                'min_items_in_box'     => 0,
                'max_items_in_box'     => 0,
                'prices'               => [
                    1 => [
                        'lower_limit'  => '',
                        'price'        => 0,
                        'type'         => 'A',
                        'usergroup_id' => 0,
                    ],
                ],
                'product_features'     => [],
                'product_type'         => 'D', // dept_payout product type
                'is_edp'               => YesNo::YES,
            ]
        );
    } else {
        fn_update_product(
            [
                'product'              => __('vendor_debt_payout.debt_payout'),
                'company_id'           => 0,
                'category_ids'         => [$payout_category_id],
                'main_category'        => $payout_category_id,
                'status'               => ObjectStatuses::HIDDEN,
                'out_of_stock_actions' => YesNo::NO,
                'is_edp'               => YesNo::YES,
            ],
            $payout_product_id
        );
    }
}

/**
 * The `settings_actions` handler.
 *
 * Action performed:
 *     - Update taxes into debt payout product data.
 *
 * @param string                       $value     New settings value.
 * @param array<string, string>|string $old_value Old settings value.
 * @param Tygh\Settings                $settings  Settings instance.
 *
 * @return void
 */
function fn_settings_actions_addons_vendor_debt_payout_vendor_taxes($value, $old_value, Settings $settings)
{
    $product_id = fn_vendor_debt_payout_get_payout_product();
    if (empty($product_id)) {
        return;
    }
    /** @var array<string, string> $new_taxes */
    $new_taxes = $settings->unserializeValue($value);
    if (!is_array($new_taxes)) {
        $new_taxes = [];
    }
    fn_update_product(
        [
            'product_id' => $product_id,
            'tax_ids'    => array_keys($new_taxes),
        ],
        $product_id
    );
}
