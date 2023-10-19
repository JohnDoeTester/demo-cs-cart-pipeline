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

use Tygh\PriceList\AGenerator;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_settings_variants_addons_price_list_price_list_fields()
{
    $schema = fn_get_schema('price_list', 'schema');
    $result = array();

    if (!empty($schema['fields'])) {
        foreach ($schema['fields'] as $field_id => $field) {
            $result[$field_id] = $field['title'];
        }
    }

    return $result;
}

function fn_settings_variants_addons_price_list_price_list_sorting()
{
    $schema = fn_get_schema('price_list', 'schema');
    $fields = array();

    if (!empty($schema['fields'])) {
        foreach ($schema['fields'] as $field => $field_info) {
            if (!empty($field_info['sort_by'])) {
                $fields[$field] = $field_info['title'];
            }
        }
    }

    return $fields;
}

function fn_price_list_info()
{
    $schema = fn_get_schema('price_list', 'schema');
    if (empty($schema)) { // workaround to avoid notices when installing addon
        return;
    }
    $repository = StorefrontProvider::getRepository();
    $storefront_id = isset($_REQUEST['storefront_id'])
        ? $_REQUEST['storefront_id']
        : null;
    if ($storefront_id !== null) {
        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = $repository->findById($storefront_id);
    } else {
        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = $repository->findByCompanyId(fn_get_runtime_company_id(), true);
        if ($storefront === null) {
            $storefront = $repository->findDefault();
        }
        if ($storefront) {
            $storefront_id = $storefront->storefront_id;
        }
    }

    if ($storefront) {
        $storefront_url = fn_get_storefront_protocol() . '://' . $storefront->url;
    } else {
        $storefront_url = fn_get_storefront_url(fn_get_storefront_protocol());
    }
    if (
        fn_allowed_for('ULTIMATE')
        && !Registry::get('runtime.company_id')
        && !Registry::get('runtime.simple_ultimate')
    ) {
        $storefront_url = '';
    }

    if (!empty($storefront_url)) {
        return __('price_list.text_regenerate', [
            '[buttons]' => fn_price_list_generate_buttons($schema, $storefront_id),
            '[links]'   => fn_price_list_generate_links($schema, $storefront_url, $storefront_id),
        ]);
    }

    return __('price_list.text_select_storefront');
}

/**
 * Create links and visual representation of buttons for generating price lists.
 *
 * @param array<string, array<string, string>> $schema        Schema of price lists types and file extensions.
 * @param int|null                             $storefront_id Storefront id.
 *
 * @return string
 */
function fn_price_list_generate_buttons(array $schema, $storefront_id)
{
    $buttons = array();
    foreach ($schema['types'] as $type => $options) {
        $url = 'price_list.generate?display=' . $type;
        if ($storefront_id) {
            $url .= '&storefront_id=' . $storefront_id;
        }
        $buttons[] = '<a class="cm-ajax cm-comet btn btn-primary" href="' . fn_url($url)  . '">' . $type . '</a>';
    }

    return implode('&nbsp;', $buttons);
}

/**
 * Generates links for downloading price list files.
 *
 * @param array<string, array<string, array<string, string>>> $schema         Price list types schema.
 * @param string                                              $storefront_url Storefront url.
 * @param int|null                                            $storefront_id  Storefront identifier.
 * @param string                                              $lang_code      Language code.
 *
 * @return string
 */
function fn_price_list_generate_links(array $schema, $storefront_url, $storefront_id, $lang_code = CART_LANGUAGE)
{
    $links = [];
    if (empty($storefront_url)) {
        return implode('<br />', $links);
    }
    foreach ($schema['types'] as $options) {
        $url = $storefront_url . '/price_list.' . $options['extension'] . '?sl=' . $lang_code;
        if ($storefront_id) {
            $url .= '&storefront_id=' . $storefront_id;
        }
        $links[] = '<a target="_blank" href="' . $url . '">' . $url . '</a>';
    }
    return implode('<br />', $links);
}

/**
 * Gets all possible options combinations
 *
 * @param array $options  Product options
 * @param array $variants Options variants
 * @param array $string   Array of combinations values
 * @param int   $cycle    Iteration level
 *
 * @return array Options combination: keys are option IDs, values are variants
 */
function fn_price_list_build_combination($options, $variants, $string, $cycle)
{
    if (empty($variants[$cycle])) {
        return false;
    }

    // Look through all variants
    foreach ($variants[$cycle] as $variant_id) {
        if (count($options) - 1 > $cycle) {
            $string[$cycle][$options[$cycle]] = $variant_id;
            $cycle ++;
            $combination[] = fn_price_list_build_combination($options, $variants, $string, $cycle);
            $cycle --;
            unset($string[$cycle]);
        } else {
            $_combination = array();
            if (!empty($string)) {
                foreach ($string as $val) {
                    foreach ($val as $opt => $var) {
                        $_combination[$opt] = $var;
                    }
                }
            }
            $_combination[$options[$cycle]] = $variant_id;
            $combination[] = $_combination;
        }
    }

    if (!empty($combination[0][0])) {
        if (is_array($combination[0][0])) {
            $_combination = array();

            foreach ($combination as $c) {
                $_combination = array_merge($_combination, $c);
            }

            $combination = $_combination;
            unset($_combination);
        }
    }

    if (!empty($combination)) {
        return $combination;

    } else {
        return false;
    }
};

function fn_price_list_get_combination($product)
{
    $poptions = $product['product_options'];

    if (!empty($poptions)) {

        $product['product_code'] = db_get_field("SELECT product_code FROM ?:products WHERE product_id= ?i", $product['product_id']);

        //Get variants combinations
        $_options = array_keys($poptions);

        foreach ($_options as $key => $option_id) {
            $variants[$key] = empty($poptions[$option_id]['variants']) ? null : array_keys($poptions[$option_id]['variants']);
        }

        $combinations = fn_price_list_build_combination($_options, $variants, array(), 0);
        if (!empty($combinations)) {
            foreach ($combinations as $c_id => $c_value) {
                $m_price = 0;
                $m_weight = 0;

                foreach ($c_value as $option_id => $variant_id) {
                    if ($poptions[$option_id]['variants'][$variant_id]['modifier_type'] == 'A') {
                        $m_price += $poptions[$option_id]['variants'][$variant_id]['modifier'];
                        $m_weight += $poptions[$option_id]['variants'][$variant_id]['weight_modifier'];
                    } else {
                        $m_price += $product['base_price'] * $poptions[$option_id]['variants'][$variant_id]['modifier'] / 100;
                        $m_weight += $product['weight'] * $poptions[$option_id]['variants'][$variant_id]['weight_modifier'] / 100;
                    }
                }

                $product['combination_prices'][$c_id] = $product['base_price'] + $m_price;
                $product['combination_weight'][$c_id] = $product['weight'] + $m_weight;

                $amount = $product_code = '';


                $product['combination_amount'][$c_id] = empty($amount) ? $product['amount'] : $amount;
                $product['combination_code'][$c_id] = empty($product_code) ? $product['product_code'] : $product_code;
            }
        }

        $product['combinations'] = $combinations;
    }

    return $product;
}

function fn_price_list_build_category_name($id_path)
{
    $result = array();
    $cat_ids = explode('/', $id_path);

    if (!empty($cat_ids)) {
        $cats = fn_get_category_name($cat_ids);

        foreach ($cats as $cat_id => $cat_name) {
            $result[] = $cat_name;
        }
    }

    return implode(' - ', $result);
}


/**
 * The "price_list_process_products_before_gather_additional_products_data" hook handler.
 *
 * Actions performed:
 *  - Adds flag to get warehouse total amount for products from list.
 *
 * @param \Tygh\PriceList\AGenerator $generator AGenerator instance
 * @param array                      $products  List of products
 * @param array                      $params    Array of flags which determines which data should be gathered
 *
 * @see \Tygh\PriceList\AGenerator::processProducts()
 */
function fn_warehouses_price_list_process_products_before_gather_additional_products_data($generator, $products, &$params)
{
    $params['get_warehouse_total_amount'] = true;
}

/**
 * The "price_list_process_products_before_get_products" hook handler.
 *
 * Actions performed:
 *  - Adds flags to get products with variations for price list.
 *
 * @param \Tygh\PriceList\AGenerator                                    $generator      AGenerator instance
 * @param array<string, string|bool|int|array<string, string|bool|int>> $params         Array of flags which determines which data should be received
 * @param int                                                           $items_per_page Items per page
 *
 * @return void
 *
 * @see \Tygh\PriceList\AGenerator::processProducts()
 */
function fn_product_variations_price_list_process_products_before_get_products(AGenerator $generator, &$params, $items_per_page)
{
    $params['include_child_variations'] = true;
    $params['group_child_variations'] = false;
}