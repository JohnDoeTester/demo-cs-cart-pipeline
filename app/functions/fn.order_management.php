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

use Tygh\Enum\ProductOptionsApplyOrder;
use Tygh\Enum\SiteArea;
use Tygh\Registry;
use Tygh\Tools\SecurityHelper;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Prepares the array of data of the product that is being changed in cart or in the order,
 * and returns the template of the product with the product data.
 * The function is used only in controllers.
 *
 * @param array  $params An array of parameters.
 * @param array  $auth   Authentication data.
 * @param string $mode   The directory mode.
 *
 * @return array|bool An array with the product data.
 */
function fn_get_data_of_changed_product(&$params, $auth, $mode)
{
    $cart_products = array();
    $_auth = $auth;
    if (isset($_REQUEST['product_id'], $_REQUEST['product_data'][$_REQUEST['product_id']]['product_options'])) {
        $product_id = $_REQUEST['product_id'];
        $product_options = $_REQUEST['product_data'][$product_id]['product_options'];

        if (empty($params['product_data'])) {
            $params['product_data'] = [
                $product_id => [
                    'product_options' => $product_options
                ],
            ];
        }

        $params['appearance']['details_page'] = true;
    }

    if (empty($params['product_data']) && empty($params['cart_products'])) {
        return false;
    }

    if (!empty($params['product_data'])) {
        unset($params['product_data']['custom_files']);

        $product = fn_get_additional_product_data($params, $auth);
        $product_data = $params;

        fn_update_product_image_in_template($params);

        if (AREA == 'C') {
            if (!empty($params['appearance']['quick_view'])) {
                $display_tpl = 'views/products/quick_view.tpl';

            } elseif (!empty($params['appearance']['details_page'])) {
                $display_tpl = 'views/products/view.tpl';

            } else {
                $display_tpl = 'common/product_data.tpl';
            }
        } else {
            $display_tpl = 'views/products/components/select_product_options.tpl';

            Tygh::$app['view']->assign('product_options', $product['product_options']);
        }

    } else {
        fn_enable_checkout_mode();

        unset($params['cart_products']['custom_files']);

        $cart_products = $params['cart_products'];
        if (!empty($cart_products)) {
            foreach ($cart_products as $cart_id => $product) {
                if (!empty($product['object_id'])) {
                    unset($cart_products[$cart_id]);
                    $cart_products[$product['object_id']] = $product;
                }
            }
        }

        if (AREA == 'A') {
            $_auth = Tygh::$app['session']['customer_auth'];
            if (empty($_auth)) {
                $_auth = fn_fill_auth(array(), array(), false, 'C');
            }
        }

        $_cart = Tygh::$app['session']['cart'];

        $product_data = fn_get_product_options_data($cart_products, $_cart, $params);

        fn_set_hook('calculate_options', $cart_products, $_cart, $auth);

        $exclude_products = array();
        foreach ($_cart['products'] as $cart_id => $product) {
            if (!empty($product['extra']['exclude_from_calculate'])) {
                $exclude_products[$cart_id] = true;
            }
        }

        list($cart_products) = fn_calculate_cart_content($_cart, $_auth, 'S', true, 'F', true);

        fn_gather_additional_products_data($cart_products, array('get_icon' => true, 'get_detailed' => true, 'get_options' => true, 'get_discounts' => false));

        $changed_options = false;
        foreach ($cart_products as $item_id => $product) {
            if ($_cart['products'][$item_id]['product_options'] != $product['selected_options']) {
                $_cart['products'][$item_id]['product_options'] = $product['selected_options'];
                $changed_options = true;
            }
        }

        if ($changed_options) {
            list($cart_products) = fn_calculate_cart_content($_cart, $_auth, 'S', true, 'F', true);
            fn_gather_additional_products_data($cart_products, array('get_icon' => true, 'get_detailed' => true, 'get_options' => true, 'get_discounts' => false));
        }

        if (count(Tygh::$app['session']['cart']['products']) != count($_cart['products'])) {
            $_recalculate = false;

            foreach (Tygh::$app['session']['cart']['products'] as $cart_id => $product) {
                if (!isset($_cart['products'][$cart_id]) && !isset($exclude_products[$cart_id])) {
                    $_recalculate = true;
                    break;
                }
            }

            if ($_recalculate) {
                $_cart = Tygh::$app['session']['cart'];
                list($cart_products) = fn_calculate_cart_content($_cart, $_auth, 'S', true, 'F', true);
            }
        }

        fn_change_product_data_in_cart($cart_products, $_cart, $params);

        Registry::set('navigation', array());
        Tygh::$app['view']->assign('cart_products', $cart_products);
        Tygh::$app['view']->assign('cart', $_cart);

        $params['cart'] = $_cart;

        if (AREA == 'C') {
            $display_tpl = 'views/checkout/components/cart_items.tpl';
        } else {
            $display_tpl = 'views/order_management/components/products.tpl';
        }
    }

    $data = isset($product_data) ? $product_data : $cart_products;

    fn_set_hook('after_options_calculation', $mode, $data, $auth);

    Tygh::$app['view']->display($display_tpl);

    return true;
}

/**
 * Gets the data about the product's stock and options based on the passed data
 * of the product that is being changed in cart or in the order.
 * The function is used only in controllers.
 *
 * @param array $product_data The data of the chaged product.
 * @param array $auth Authentication data.
 *
 * @return bool|array The array with the product data.
 */
function fn_get_additional_product_data(&$product_data, $auth)
{
    $_auth = $auth;

    $_data = reset($product_data['product_data']);
    $product_id = key($product_data['product_data']);

    $product_id = isset($_data['product_id']) ? $_data['product_id'] : $product_id;
    $selected_options = empty($_data['product_options']) ? array() : $_data['product_options'];

    unset($selected_options['AOC']);

    if (isset($product_data['additional_info']['info_type']) && $product_data['additional_info']['info_type'] == 'D') {
        $product = fn_get_product_data($product_id, $_auth, CART_LANGUAGE, '', true, true, true, true, ($auth['area'] == 'A'));
    } else {
        $specific_settings['pid'] = $product_id;
        list($product) = fn_get_products($specific_settings);
        $product = reset($product);
    }

    if (empty($product)) {
        return false;
    }

    $product['changed_option'] = isset($product_data['changed_option']) ? reset($product_data['changed_option']) : '';
    $product['selected_options'] = $selected_options;

    if (!empty($_data['amount'])) {
        $product['selected_amount'] = $_data['amount'];
    }

    // Get specific settings
    $specific_settings = [
        'get_icon'                    => isset($product_data['additional_info']['get_icon']) ? $product_data['additional_info']['get_icon'] : false,
        'get_detailed'                => isset($product_data['additional_info']['get_detailed']) ? $product_data['additional_info']['get_detailed'] : false,
        'get_options'                 => isset($product_data['additional_info']['get_options']) ? $product_data['additional_info']['get_options'] : true,
        'get_discounts'               => isset($product_data['additional_info']['get_discounts']) ? $product_data['additional_info']['get_discounts'] : true,
        'get_features'                => isset($product_data['additional_info']['get_features']) ? $product_data['additional_info']['get_features'] : false,
        'get_only_selectable_options' => SiteArea::isAdmin(AREA) && isset($product_data['additional_info']['get_only_selectable_options'])
            ? $product_data['additional_info']['get_only_selectable_options']
            : false,
    ];

    fn_set_hook('get_additional_information', $product, $product_data);

    fn_gather_additional_product_data(
        $product,
        $specific_settings['get_icon'],
        $specific_settings['get_detailed'],
        $specific_settings['get_options'],
        $specific_settings['get_discounts'],
        $specific_settings['get_features'],
        $specific_settings
    );

    fn_set_hook('get_additional_information_post', $product, $product_data);

    if (isset($product['inventory_amount'])) {
        $product['amount'] = $product['inventory_amount'];
    }

    if (!empty($product_data['extra_id'])) {
        $product['product_id'] = $product_data['extra_id'];
    }

    Tygh::$app['view']->assign('product', $product);

    return $product;
}

/**
 * Updates the image of the product in the product list template.
 * The function is used only in controllers.
 *
 * @param array $params An array of parameters.
 *
 * @return void
 */
function fn_update_product_image_in_template($params)
{
    // Update the images in the list/grid templates
    if (!empty($params['image'])) {
        $images_data = array();

        foreach ($params['image'] as $div_id => $value) {
            list($obj_id, $width, $height, $type) = explode(',', $value['data']);
            $images_data[$div_id] = array(
                'obj_id' => $obj_id,
                'width' => $width,
                'height' => $height,
                'type' => $type,
                'link' => isset($value['link']) ? $value['link'] : '',
            );
        }

        Tygh::$app['view']->assign('images', $images_data);
    }
}

/**
 * Gets the product data depending on the newly-selected options,
 * and records the data in the $cart session array.
 * The function is used only in controllers.
 *
 * @param array $cart_products The data of the product.
 * @param array $cart          Array of cart content.
 * @param array $params        An array of parameters.
 *
 * @return array|null The array with the product data.
 */
function fn_get_product_options_data($cart_products, &$cart, $params)
{
    foreach ($cart_products as $cart_id => $item) {
        if (isset($cart['products'][$cart_id])) {
            $amount = isset($item['amount']) ? $item['amount'] : 1;
            $product_data = fn_get_product_data($item['product_id'], $auth, CART_LANGUAGE, '', false, false, false, false, false, false, false);

            if (
                isset($product_data['options_type'])
                && $product_data['options_type'] === ProductOptionsApplyOrder::SEQUENTIAL
                && isset($item['product_options'])
                && isset($params['changed_option'][$cart_id])
            ) {
                $item['product_options'] = fn_fill_sequential_options($item, $params['changed_option'][$cart_id]);
                unset($params['changed_option']);
            }

            $product_options = isset($item['product_options']) ? $item['product_options'] : array();
            $amount = fn_check_amount_in_stock($item['product_id'], $amount, $product_options, $cart_id, $cart['products'][$cart_id]['is_edp'], 0, $cart);

            if ($amount === false) {
                continue;
            }

            $cart['products'][$cart_id]['amount'] = $amount;
            $cart['products'][$cart_id]['selected_options'] = isset($item['product_options']) ? $item['product_options'] : array();
            $cart['products'][$cart_id]['product_options'] = fn_get_selected_product_options($item['product_id'], $cart['products'][$cart_id]['selected_options']);
            $cart['products'][$cart_id] = fn_apply_options_rules($cart['products'][$cart_id]);
            $cart['products'][$cart_id]['product_options'] = $cart['products'][$cart_id]['selected_options'];

            if (!empty($cart['products'][$cart_id]['extra']['saved_options_key'])) {
                $cart['saved_product_options'][$cart['products'][$cart_id]['extra']['saved_options_key']] = $cart['products'][$cart_id]['product_options'];
            }

            if (!empty($item['object_id'])) {
                $cart['products'][$cart_id]['object_id'] = $item['object_id'];

                if (!empty($cart['products'][$cart_id]['extra']['saved_options_key'])) {
                    // Product from promotion. Save object_id for this product
                    $cart['saved_object_ids'][$cart['products'][$cart_id]['extra']['saved_options_key']] = $item['object_id'];
                }
            }

            unset($cart['products'][$cart_id]['extra']['exclude_from_calculate']);
        }
    }

    return isset($product_data) ? $product_data : null;
}

/**
 * Changes the product data in the $cart array.
 * The function is used only in controllers.
 *
 * @param array  $cart_products  The data of the product.
 * @param array  $cart           Array of cart content.
 * @param array  $params         An array of parameters.
 *
 * @return void
 */
function fn_change_product_data_in_cart(&$cart_products, &$cart, $params)
{
    if (!empty($cart_products)) {
        foreach ($cart_products as $k => $product) {
            if (!empty($product['object_id'])) {
                $c_product = !empty($cart['products'][$k]) ? $cart['products'][$k] : array();

                unset($cart_products[$k], $cart['products'][$k]);

                $cart['products'][$product['object_id']] = $c_product;
                $cart_products[$product['object_id']] = $product;
                $k = $product['object_id'];
            }

            if (isset($product['object_id'], $params['changed_option'][$product['object_id']])) {
                $cart_products[$k]['changed_option'] = $params['changed_option'][$product['object_id']];
            } elseif (isset($params['changed_option'][$k])) {
                $cart_products[$k]['changed_option'] = $params['changed_option'][$k];
            } else {
                $cart_products[$k]['changed_option'] = '';
            }
        }
    }
}

/**
 * Fills sequential options with default values. Necessary for cart total calculation
 *
 * @param array $item Cart item
 * @param int $changed_option Changed option identifier
 * @return array New options list
 */
function fn_fill_sequential_options($item, $changed_option)
{
    $params['pid'] = $item['product_id'];
    list($product) = fn_get_products($params);
    $product = reset($product);

    $product['changed_option'] = $changed_option;
    $product['selected_options'] = $item['product_options'];

    fn_gather_additional_product_data($product, false, false, true, false, false);

    if (count($item['product_options']) != count($product['selected_options'])) {
        foreach ($item['product_options'] as $option_id => $variant_id) {
            if (isset($product['selected_options'][$option_id]) || (in_array($product['product_options'][$option_id]['option_type'], array('I', 'T', 'F')))) {
                continue;
            }

            if (!empty($product['product_options'][$option_id]['variants'])) {
                reset($product['product_options'][$option_id]['variants']);
                $variant_id = key($product['product_options'][$option_id]['variants']);
            } else {
                $variant_id = '';
            }

            $product['selected_options'][$option_id] = $variant_id;
            $product['changed_option'] = $option_id;

            fn_gather_additional_product_data($product, false, false, true, false, false);
        }
    }

    return $product['selected_options'];
}

/**
 * Generates print invoices for orders.
 *
 * @param int|array<int>                  $order_ids Order IDs to print invoices for
 * @param bool|array<string, string|bool> $params    Print parameters
 *
 * @return string Generated invoices
 */
function fn_print_order_invoices($order_ids, $params = [])
{
    // Backward compatibility
    if (is_bool($params)) {
        // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
        $args = func_get_args();
        $params = [];

        /**
         * Executes when normalizing parameters for invoice printing, allows you to populate additional parameters.
         *
         * @param array<int, string|bool>    $args   Function arguments
         * @param array<string, string|bool> $params Normalized parameters
         */
        fn_set_hook('print_order_invoices_normalize_parameters', $args, $params);

        if (isset($args[2])) {
            $params['area'] = $args[2];
        }
        if (isset($args[3])) {
            $params['lang_code'] = $args[3];
        }
    }

    // Default params
    $params = array_merge(
        [
            'area'               => AREA,
            'lang_code'          => CART_LANGUAGE,
            'secondary_currency' => CART_SECONDARY_CURRENCY,
            'html_wrap'          => true,
            'add_page_break'     => true,
        ],
        $params
    );

    $order_ids = (array) $order_ids;

    /**
     * Executes before printing order invoices, allows you to modify parameters passed to the function.
     *
     * @param array<int>                 $order_ids Order IDs to print invoices for
     * @param array<string, string|bool> $params    Print parameters
     */
    fn_set_hook('print_order_invoices_pre', $order_ids, $params);

    $html = [];
    $data = [];

    $data['order_status_descr'] = fn_get_simple_statuses(STATUSES_ORDER, true, true, $params['lang_code']);
    $data['profile_fields'] = fn_get_profile_fields('I', [], $params['lang_code']);

    foreach ($order_ids as $order_id) {
        if (Registry::get('settings.Appearance.email_templates') === 'old') {
            $order_info = fn_get_order_info($order_id, false, true, false, false, $params['lang_code']);

            if (empty($order_info)) {
                continue;
            }

            if (!empty($order_info['notes'])) {
                $order_info['notes'] = SecurityHelper::sanitizeHtml($order_info['notes']);
            }
            if (!empty($order_info['details'])) {
                $order_info['details'] = SecurityHelper::sanitizeHtml($order_info['details']);
            }

            if (fn_allowed_for('MULTIVENDOR')) {
                $data['take_surcharge_from_vendor'] = fn_take_payment_surcharge_from_vendor($order_info['products']);
            }

            list($shipments) = fn_get_shipments_info(['order_id' => $order_info['order_id'], 'advanced_info' => true]);
            $use_shipments = !fn_one_full_shipped($shipments);

            $data['order_info'] = $order_info;
            $data['shipments'] = $shipments;
            $data['use_shipments'] = $use_shipments;
            $data['payment_method'] = fn_get_payment_data(
                !empty($order_info['payment_method']['payment_id']) ? $order_info['payment_method']['payment_id'] : 0,
                $order_info['order_id'],
                $params['lang_code']
            );
            $data['order_status'] = fn_get_status_data(
                $order_info['status'],
                STATUSES_ORDER,
                $order_info['order_id'],
                $params['lang_code'],
                $order_info['company_id']
            );
            $data['status_settings'] = fn_get_status_params($order_info['status']);

            if (fn_allowed_for('MULTIVENDOR') && SiteArea::isStorefront($params['area'])) {
                $data['company_data'] = fn_filter_company_data_by_profile_fields(
                    fn_get_company_placement_info($order_info['company_id'], $params['lang_code']),
                    [
                        'field_prefix' => 'company_',
                    ]
                );
            } else {
                $data['company_data'] = fn_get_company_placement_info($order_info['company_id'], $params['lang_code']);
            }

            /** @var \Tygh\SmartyEngine\Core $view */
            $view = Tygh::$app['view'];
            foreach ($data as $key => $value) {
                $view->assign($key, $value);
            }

            $template = $params['html_wrap'] ? 'orders/print_invoice.tpl' : 'orders/invoice.tpl';
            $html[] = $view->displayMail(
                $template,
                false,
                $params['area'],
                $order_info['company_id'],
                $params['lang_code']
            );
        } else {
            /** @var \Tygh\Template\Document\Order\Type $document_type */
            $document_type = Tygh::$app['template.document.order.type'];
            $template_code = isset($params['template_code']) ? $params['template_code'] : 'invoice';

            $order_params = [];
            if (fn_allowed_for('MULTIVENDOR') && !empty($params['use_i18n_company_fields'])) {
                $order_params = [
                    'use_i18n_company_fields' => true
                ];
            }

            $template = $document_type->renderById(
                $order_id,
                $template_code,
                $params['lang_code'],
                $params['secondary_currency'],
                $params['area'],
                $order_params
            );

            if ($params['html_wrap']) {
                /** @var \Tygh\SmartyEngine\Core $view */
                $view = Tygh::$app['view'];
                $view->assign('content', $template);
                $template = $view->displayMail('common/wrap_document.tpl', false, 'A');
            }

            $html[] = $template;
        }

        if (
            !$params['add_page_break']
            || $order_id === end($order_ids)
        ) {
            continue;
        }

        $html[] = "<div style='page-break-before: always;'>&nbsp;</div>";
    }

    $output = implode(PHP_EOL, $html);

    /**
     * Executes after order invoices are generated, allows you to execute additional invoice data modifications.
     *
     * @param array<int>                 $order_ids Order IDs to print invoices for
     * @param array<string, string|bool> $params    Print parameters
     * @param array<string>              $html      Invoice HTML
     * @param string                     $output    Generated invoices
     */
    fn_set_hook('print_order_invoices_post', $order_ids, $params, $html, $output);

    return $output;
}

/**
 * Generates packing slips for orders.
 *
 * @param int|array<int>                  $order_ids Order IDs to print slips for
 * @param bool|array<string, string|bool> $params    Print parameters
 *
 * @return string Generated packing slips.
 */
function fn_print_order_packing_slips($order_ids, $params = [])
{
    // Backward compatibility
    if (is_bool($params)) {
        // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
        $args = func_get_args();
        $params = [];

        /**
         * Executes when normalizing parameters for packaging slips printing, allows you to populate additional parameters.
         *
         * @param array<int, string|bool>    $args   Function arguments
         * @param array<string, string|bool> $params Normalized parameters
         */
        fn_set_hook('print_order_packing_slips_normalize_parameters', $args, $params);

        if (isset($args[2])) {
            $params['lang_code'] = $args[2];
        }
    }

    // Default params
    $params = array_merge(
        [
            'area'           => SiteArea::ADMIN_PANEL,
            'lang_code'      => CART_LANGUAGE,
            'add_page_break' => true,
        ],
        $params
    );

    $order_ids = (array) $order_ids;

    /**
     * Executes before printing order packing slips, allows you to modify parameters passed to the function.
     *
     * @param array<int>                 $order_ids Order IDs to print slips for
     * @param array<string, string|bool> $params    Print parameters
     */
    fn_set_hook('print_order_packing_slips_pre', $order_ids, $params);

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $html = [];

    foreach ($order_ids as $order_id) {
        if (Registry::get('settings.Appearance.email_templates') === 'old') {
            $order_info = fn_get_order_info($order_id, false, true, false, false);
            if (!empty($order_info['notes'])) {
                $order_info['notes'] = SecurityHelper::sanitizeHtml($order_info['notes']);
            }
            if (!empty($order_info['details'])) {
                $order_info['details'] = SecurityHelper::sanitizeHtml($order_info['details']);
            }

            if (empty($order_info)) {
                continue;
            }

            list($shipments) = fn_get_shipments_info(['order_id' => $order_info['order_id'], 'advanced_info' => true]);

            $view->assign('order_info', $order_info);
            $view->assign('shipments', $shipments);

            $html[] = $view->displayMail('orders/print_packing_slip.tpl', false, $params['area'], $order_info['company_id'], $params['lang_code']);
        } else {
            /** @var \Tygh\Template\Document\PackingSlip\Type $packing_slip */
            $packing_slip = Tygh::$app['template.document.packing_slip.type'];
            $result = $packing_slip->renderByOrderId($order_id, $params['lang_code']);

            if (!$result) {
                continue;
            }

            $view->assign('content', $result);
            $result = $view->displayMail('common/wrap_document.tpl', false, $params['area']);
            $html[] = $result;
        }

        if (
            !$params['add_page_break']
            || $order_id === end($order_ids)
        ) {
            continue;
        }

        $html[] = "<div style='page-break-before: always;'>&nbsp;</div>";
    }

    $output = implode(PHP_EOL, $html);

    /**
     * Executes after order packing slips are generated, allows you to execute additional slip data modifications.
     *
     * @param array<int>                 $order_ids Order IDs to print slips for
     * @param array<string, string|bool> $params    Print parameters
     * @param array<string>              $html      Slips HTML
     * @param string                     $output    Generated slips
     */
    fn_set_hook('print_order_packing_slips_post', $order_ids, $params, $html, $output);

    return $output;
}

/**
 * Sends modified order invoice.
 *
 * @param array<string, string> $order_info Order information
 * @param array<string, string> $params     Invoice parameters
 *
 * @return bool
 */
function fn_send_order_invoice(array $order_info, array $params)
{
    $subject = $params['subject'];
    $invoice = $params['body'];
    $email = $params['email'];
    $attachments = [];

    /**
     * Executes before sending custom order invoice, allows you to modify invoice data and its receiver.
     *
     * @param array<string, string> $order_info  Order information
     * @param array<string, string> $params      Invoice parameters
     * @param string                $subject     Mail subject
     * @param string                $invoice     Invoice body
     * @param string                $email       Invoice receiver
     * @param array<string, string> $attachments Email attachments
     */
    fn_set_hook('send_order_invoice', $order_info, $params, $subject, $invoice, $email, $attachments);

    /** @var \Tygh\Mailer\Mailer $mailer */
    $mailer = Tygh::$app['mailer'];

    $result = $mailer->send(
        [
            'to'          => $email,
            'from'        => 'company_orders_department',
            'body'        => $invoice,
            'subj'        => $subject,
            'company_id'  => $order_info['company_id'],
            'attachments' => $attachments,
        ],
        SiteArea::ADMIN_PANEL,
        $order_info['lang_code']
    );

    foreach ($attachments as $path) {
        fn_rm($path);
    }

    return $result;
}

/**
 * Gets packing info for a shipment.
 *
 * @param int $shipment_id Shipment ID
 *
 * @return array<array<string, mixed>>
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 *
 * @psalm-suppress PossiblyInvalidArrayAssignment
 */
function fn_get_packing_info($shipment_id)
{
    $params = [
        'advanced_info' => true,
        'shipment_id'   => $shipment_id,
    ];

    $order_info = [];
    list($shipment) = fn_get_shipments_info($params);
    if (!empty($shipment)) {
        $shipment = array_pop($shipment);

        $order_info = fn_get_order_info($shipment['order_id'], false, true, true);

        $_products = db_get_array('SELECT item_id, SUM(amount) AS amount FROM ?:shipment_items WHERE order_id = ?i GROUP BY item_id', $shipment['order_id']);
        $shipped_products = [];

        if (!empty($_products)) {
            foreach ($_products as $_product) {
                $shipped_products[$_product['item_id']] = $_product['amount'];
            }
        }

        foreach ($order_info['products'] as $k => $oi) {
            if (isset($shipped_products[$k])) {
                $order_info['products'][$k]['shipment_amount'] = $oi['amount'] - $shipped_products[$k];
            } else {
                $order_info['products'][$k]['shipment_amount'] = $order_info['products'][$k]['amount'];
            }

            if (isset($shipment['products'][$k])) {
                $order_info['products'][$k]['amount'] = $shipment['products'][$k];
            } else {
                $order_info['products'][$k]['amount'] = 0;
            }
        }
    }

    return [$shipment, $order_info];
}

/**
 * Generates packing slips for shipments.
 *
 * @param int|array<int>                  $shipment_ids Shipment IDs to print slips for
 * @param bool|array<string, string|bool> $params       Print parameters
 *
 * @return string Generated packing slips.
 */
function fn_print_shipment_packing_slips($shipment_ids, $params = [])
{
    // Backward compatibility
    if (is_bool($params)) {
        // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
        $args = func_get_args();
        $params = [];

        /**
         * Executes when normalizing parameters for shipment slips printing, allows you to populate additional parameters.
         *
         * @param array<int, string|bool>    $args   Function arguments
         * @param array<string, string|bool> $params Normalized parameters
         */
        fn_set_hook('print_shipment_packing_slips_normalize_params', $args, $params);

        if (isset($args[2])) {
            $params['lang_code'] = $args[2];
        }
    }

    $params = array_merge(
        [
            'area'           => SiteArea::ADMIN_PANEL,
            'lang_code'      => CART_LANGUAGE,
            'add_page_break' => true,
        ],
        $params
    );

    $shipment_ids = (array) $shipment_ids;

    /**
     * Executes before printing shipment packing slips, allows you to modify parameters passed to the function.
     *
     * @param int|array<int>                  $shipment_ids Shipment IDs to print slips for
     * @param bool|array<string, string|bool> $params       Print parameters
     */
    fn_set_hook('print_shipment_packing_slips_pre', $shipment_ids, $params);

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $html = [];
    foreach ($shipment_ids as $shipment_id) {
        if (Registry::get('settings.Appearance.email_templates') === 'old') {
            list($shipment, $order_info) = fn_get_packing_info($shipment_id);
            if (empty($shipment)) {
                continue;
            }

            $view->assign('order_info', $order_info);
            $view->assign('shipment', $shipment);

            $html[] = $view->displayMail('orders/print_packing_slip.tpl', false, $params['area'], $order_info['company_id'], $params['lang_code']);
        } else {
            /** @var \Tygh\Template\Document\PackingSlip\Type $packing_slip */
            $packing_slip = Tygh::$app['template.document.packing_slip.type'];
            $result = $packing_slip->renderByShipmentId($shipment_id, $params['lang_code']);

            if (!$result) {
                continue;
            }

            $view->assign('content', $result);
            $result = $view->displayMail('common/wrap_document.tpl', false, $params['area']);

            $html[] = $result;
        }

        if (
            !$params['add_page_break']
            || $shipment_id === end($shipment_ids)
        ) {
            continue;
        }
        $html[] = "<div style='page-break-before: always;'>&nbsp;</div>";
    }

    $output = implode(PHP_EOL, $html);

    /**
     * Executes after shipment packing slips are generated, allows you to execute additional slip data modifications.
     *
     * @param array<int>                 $shipment_ids Shipment IDs to print slips for
     * @param array<string, string|bool> $params       Print parameters
     * @param array<string>              $html         Slips HTML
     * @param string                     $output       Generated slips
     */
    fn_set_hook('print_shipment_packing_slips_post', $shipment_ids, $params, $html, $output);

    return $output;
}
