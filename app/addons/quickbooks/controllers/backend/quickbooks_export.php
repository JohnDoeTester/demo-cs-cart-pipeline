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

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'export_to_iif') {
        header('Content-type: text/csv');
        header('Content-disposition: attachment; filename=orders.iif');

        foreach ($_REQUEST['order_ids'] as $k => $v) {
            $orders[$k] = fn_get_order_info($v);
        }

        $order_users = $order_products = array();
        foreach ($orders as $k => $v) {
            $order_users[$v['user_id'] . '_' . $v['email']] = $v;
            foreach ($v['products'] as $key => $value) {
                $order_products[$value['cart_id']] = $value;
                if (!empty($value['product_options'])) {
                    $selected_options = ', ' . __('product_options') . ': ';
                    foreach ($value['product_options'] as $option) {
                        $selected_options .= "$option[option_name]: $option[variant_name],";
                    }
                    $order_products[$value['cart_id']]['selected_options'] = $selected_options;
                }
            }
        }

        $export = fn_quickbooks_export($orders, $order_users, $order_products);
        fn_echo($export);

        exit;
    }
}

function fn_quickbooks_export($orders, $order_users, $order_products)
{
    $export = array();
    fn_quickbooks_export_customers($order_users, $export);
    fn_quickbooks_export_products($orders, $order_products, $export);
    fn_quickbooks_export_orders($orders, $order_products, $export);
    fn_quickbooks_export_payments($orders, $export);

    return implode("\r\n", $export);
}

function fn_quickbooks_export_customers($order_users, &$export)
{
    $export[] = "!CUST\tNAME\tBADDR1\tBADDR2\tBADDR3\tBADDR4\tBADDR5\tSADDR1\tSADDR2\tSADDR3\tSADDR4\tSADDR5\tPHONE1\tFAXNUM\tEMAIL\tCONT1\tSALUTATION\tCOMPANYNAME\tFIRSTNAME\tLASTNAME";

    $location_manager = Tygh::$app['location'];
    $cust = "CUST\t\"%s, %s\"\t%s %s\t%s %s\t%s\t\"%s, %s\"\t%s\t%s %s\t%s %s\t%s\t\"%s, %s\"\t%s\t%s\t%s\t%s\t\"%s, %s\"\t%s\t%s\t%s\t%s";
    foreach ($order_users as $order) {
        $order['title'] = !empty($order['title']) ? $order['title'] : '';

        $export[] = sprintf($cust,
            // NAME
            fn_quickbooks_escape_field($order['lastname']), fn_quickbooks_escape_field($order['firstname']),
            // BADDR1
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'firstname', '', BILLING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'lastname', '', BILLING_ADDRESS_PREFIX)),
            // BADDR2
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'address', '', BILLING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'address_2', '', BILLING_ADDRESS_PREFIX)),
            // BADDR3
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'city', '', BILLING_ADDRESS_PREFIX)),
            // BADDR4
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'state', '', BILLING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'zipcode', '', BILLING_ADDRESS_PREFIX)),
            // BADDR5
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'country_descr', '', BILLING_ADDRESS_PREFIX)),
            // SADDR1
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'firstname', '', SHIPPING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'lastname', '', SHIPPING_ADDRESS_PREFIX)),
            // SADDR2
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'address', '', SHIPPING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'address_2', '', SHIPPING_ADDRESS_PREFIX)),
            // SADDR3
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'city', '', SHIPPING_ADDRESS_PREFIX)),
            // SADDR4
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'state', '', SHIPPING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'zipcode', '', SHIPPING_ADDRESS_PREFIX)),
            // SADDR5
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'country_descr', '', SHIPPING_ADDRESS_PREFIX)),
            // PHONE
            $order['phone'],
            // FAXNUM
            $order['fax'],
            // EMAIL
            $order['email'],
            // CONT1
            fn_quickbooks_escape_field($order['lastname']), fn_quickbooks_escape_field($order['firstname']),
            // SALUTATION
            fn_quickbooks_escape_field($order['title']),
            // COMPANYNAME
            fn_quickbooks_escape_field($order['company']),
            // FIRSTNAME
            fn_quickbooks_escape_field($order['firstname']),
            // LASTNAME
            fn_quickbooks_escape_field($order['lastname'])
        );
    }
    $export[] = '';

    return true;
}

function fn_quickbooks_export_products($orders, $order_products, &$export)
{
    $export[] = "!INVITEM\tNAME\tINVITEMTYPE\tDESC\tPURCHASEDESC\tACCNT\tASSETACCNT\tCOGSACCNT\tPRICE\tCOST\tTAXABLE";

    $invitem = "INVITEM\t%s\tINVENTORY\t\"%s%s\"\t\"%s%s\"\t%s\t%s\t%s\t%01.2f\t0\tN";

    $accnt_product = Registry::get('addons.quickbooks.accnt_product');
    $accnt_asset = Registry::get('addons.quickbooks.accnt_asset');
    $accnt_cogs = Registry::get('addons.quickbooks.accnt_cogs');

    foreach ($order_products as $product) {
        $product_select_options = !empty($product['selected_options']) ? $product['selected_options'] : '';
        $product_name = !empty($product['product_code']) ? $product['product_code'] : $product['product_id'];

        $product_name = fn_quickbooks_escape_field($product_name);
        $product_select_options = fn_quickbooks_escape_field($product_select_options);
        $product_desc = fn_quickbooks_escape_field($product['product']);

        $export[] = sprintf($invitem,
            // NAME
            $product_name,
            // DESC
            $product_desc, $product_select_options,
            // PURCHASEDESC
            $product_desc, $product_select_options,
            // ACCNT
            $accnt_product,
            // ASSETACCNT
            $accnt_asset,
            // COGSACCNT
            $accnt_cogs,
            // PRICE
            $product['price']
        );
    }

    fn_set_hook('quickbooks_export_items', $orders, $invitem, $export);

    $export[] = '';

    return true;
}

function fn_quickbooks_export_orders($orders, $order_products, &$export)
{
    $location_manager = Tygh::$app['location'];

    $export[] = "!TRNS\tTRNSTYPE\tDATE\tACCNT\tNAME\tCLASS\tAMOUNT\tDOCNUM\tMEMO\tADDR1\tADDR2\tADDR3\tADDR4\tADDR5\tPAID\tSHIPVIA\tSADDR1\tSADDR2\tSADDR3\tSADDR4\tSADDR5\tTOPRINT";
    $export[] = "!SPL\tTRNSTYPE\tDATE\tACCNT\tNAME\tCLASS\tAMOUNT\tDOCNUM\tMEMO\tPRICE\tQNTY\tINVITEM\tTAXABLE\tEXTRA";
    $export[] = "!ENDTRNS\t";

    $trns = "TRNS\tINVOICE\t%s\tAccounts Receivable\t\"%s, %s\"\t%s\t%s\t%s\tWebsite Order: %s\t%s %s\t%s %s\t\"%s, %s %s\"\t%s\t\t%s\t\t%s %s\t%s %s\t\"%s, %s %s\"\t%s\t\tY";
    $spl = "SPL\tINVOICE\t%s\t%s\t\"%s, %s\"\t%s\t%01.2f\t%d\t\"%s%s\"\t%01.2f\t%d\t%s\t%s\t%s";

    $accnt_product = Registry::get('addons.quickbooks.accnt_product');
    $accnt_tax = Registry::get('addons.quickbooks.accnt_tax');
    $accnt_shipping = Registry::get('addons.quickbooks.accnt_shipping');
    $accnt_discount = Registry::get('addons.quickbooks.accnt_discount');
    $accnt_surcharge = Registry::get('addons.quickbooks.accnt_surcharge');
    $trns_class = Registry::get('addons.quickbooks.trns_class');

    foreach ($orders as $order) {
        $order_details = str_replace(array("\r\n", "\n", "\r", "\t"), " ", $order['details']);
        $order_date = fn_date_format($order['timestamp'], "%m/%d/%Y");
        $product_subtotal = 0;

        if (in_array($order['status'], fn_get_order_paid_statuses())) {
            $order_paid = 'Y';
        } else {
            $order_paid = 'N';
        }

        $order['s_countryname'] = $order['s_country'];
        $order['b_countryname'] = $order['b_country'];

        $export[] = sprintf($trns,
            // DATE
            $order_date,
            // NAME
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'lastname', '', BILLING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'firstname', '', BILLING_ADDRESS_PREFIX)),
            // CLASS
            $trns_class,
            // AMOUNT
            $order['total'],
            // DOCNUM
            $order['order_id'],
            // MEMO
            $order_details,
            // ADDR1
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'firstname', '', BILLING_ADDRESS_PREFIX)),
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'lastname', '', BILLING_ADDRESS_PREFIX)),
            // ADDR2
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'address', '', BILLING_ADDRESS_PREFIX)),
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'address_2', '', BILLING_ADDRESS_PREFIX)),
            // ADDR3
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'city', '', BILLING_ADDRESS_PREFIX)),
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'state', '', BILLING_ADDRESS_PREFIX)),
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'zipcode', '', BILLING_ADDRESS_PREFIX)),
            // ADDR4
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'country_descr', '', BILLING_ADDRESS_PREFIX)),
            // PAID
            $order_paid,
            // SADDR1
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'firstname', '', SHIPPING_ADDRESS_PREFIX)),
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'lastname', '', SHIPPING_ADDRESS_PREFIX)),
            // SADDR2
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'address', '', SHIPPING_ADDRESS_PREFIX)),
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'address_2', '', SHIPPING_ADDRESS_PREFIX)),
            // SADDR3
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'city', '', SHIPPING_ADDRESS_PREFIX)),
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'state', '', SHIPPING_ADDRESS_PREFIX)),
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'zipcode', '', SHIPPING_ADDRESS_PREFIX)),
            // SADDR4
            fn_quickbooks_escape_field($location_manager->getLocationField($order, 'country_descr', '', SHIPPING_ADDRESS_PREFIX))
        );

        // PRODUCTS
        foreach ($order['products'] as $product) {
            $product_id = $product['cart_id'];

            $product_subtotal = $product['price'] * $product['amount'];
            $product_select_options = !empty($order_products[$product_id]['selected_options']) ? $order_products[$product_id]['selected_options'] : '';

            if ($order_products[$product_id]['product_code']) {
                $product_code = $order_products[$product_id]['product_code'];
            } else {
                $product_code = $order_products[$product_id]['product_id'];
            }
            // Check wether product is taxable
            $_taxable = 'N';
            if (!empty($product['tax_value'])) {
                 $_taxable = 'Y';
            } elseif (is_array($order['taxes'])) {
                foreach ($order['taxes'] as $tax_data) {
                    if (!empty($tax_data['applies']['items']['P'][$product_id])) {
                        $_taxable = 'Y';
                        break;
                    }
                }
            }

            $product_name = fn_quickbooks_escape_field($order_products[$product_id]['product']);
            $product_select_options = fn_quickbooks_escape_field($product_select_options);

            $export[] = sprintf($spl,
                // DATE
                $order_date,
                // ACCNT
                $accnt_product,
                // NAME
                fn_quickbooks_escape_field($location_manager->getLocationField($order, 'lastname', '', BILLING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'firstname', '', BILLING_ADDRESS_PREFIX)),
                // CLASS
                $trns_class,
                // AMOUNT
                -$product_subtotal,
                // DOCNUM
                $order['order_id'],
                // MEMO
                $product_name,  $product_select_options,
                // PRICE
                $product['price'],
                // QNTY
                -$product['amount'],
                // INVITEM
                $product_code,
                // TAXABLE
                $_taxable,
                // EXTRA
                ''
            );

        }

        fn_set_hook('quickbooks_export_order', $order, $order_products, $spl, $export);

        // *********  SHIPPING  **********
        if ($order['shipping_cost'] > 0) {
            $shipping_names = array();
            $_taxable = 'N';
            foreach ($order['shipping'] as $ship) {
                $shipping_names[] = $ship['shipping'];
                // Check wether shipping is taxable
                if (is_array($order['taxes'])) {
                    foreach ($order['taxes'] as $tax_data) {
                        if (!empty($tax_data['applies']['items']['S'][$ship['group_key']][$ship['shipping_id']])) {
                            $_taxable = 'Y';
                            break;
                        }
                    }
                }
            }

            $shipping_cost = fn_order_shipping_cost($order);
            $export[] = sprintf($spl,
                // DATE
                $order_date,
                // ACCNT
                $accnt_shipping,
                // NAME
                fn_quickbooks_escape_field($location_manager->getLocationField($order, 'lastname', '', BILLING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'firstname', '', BILLING_ADDRESS_PREFIX)),
                // CLASS
                $trns_class,
                // AMOUNT
                -$shipping_cost,
                // DOCNUM
                $order['order_id'],
                // MEMO
                fn_quickbooks_escape_field(implode(', ', $shipping_names)), '',
                // PRICE
                $shipping_cost,
                // QNTY
                -1,
                // INVITEM
                'SHIPPING',
                // TAXABLE
                $_taxable,
                // EXTRA
                '');
        }

        // *********  TAXES  **********
        foreach ($order['taxes'] as $tax_data) {
            // Inserted the empty line above the tax #101326561 - do not know what for yet
            if ($tax_data['price_includes_tax'] == 'N') {
                $export[] = sprintf($spl,
                    // DATE
                    $order_date,
                    // ACCNT
                    $accnt_tax,
                    // NAME
                    fn_quickbooks_escape_field($location_manager->getLocationField($order, 'lastname', '', BILLING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'firstname', '', BILLING_ADDRESS_PREFIX)),
                    // CLASS
                    $trns_class,
                    // AMOUNT
                    -$tax_data['tax_subtotal'],
                    // DOCNUM
                    $order['order_id'],
                    // MEMO
                    $tax_data['description'], '',
                    // PRICE
                    $tax_data['tax_subtotal'],
                    // QNTY
                    -1,
                    // INVITEM
                    'TAX',
                    // TAXABLE
                    'N',
                    // EXTRA
                    'AUTOSTAX'
                );
            }
        }

        // **********  DISCOUNT  **********
        if ($order['subtotal_discount'] > 0) {
            $export[] = sprintf($spl,
                // DATE
                $order_date,
                // ACCNT
                $accnt_discount,
                // NAME
                fn_quickbooks_escape_field($location_manager->getLocationField($order, 'lastname', '', BILLING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'firstname', '', BILLING_ADDRESS_PREFIX)),
                // CLASS
                $trns_class,
                // AMOUNT
                $order['subtotal_discount'],
                // DOCNUM
                $order['order_id'],
                // MEMO
                'DISCOUNT', '',
                // PRICE
                -$order['subtotal_discount']
                // QNTY
                -1,
                // INVITEM
                'DISCOUNT',
                // TAXABLE
                'N',
                // EXTRA
                ''
            );
        }

        // *********  SURCHARGE  **********}
        if ($order['payment_surcharge'] > 0) {
            $export[] = sprintf($spl,
                // DATE
                $order_date,
                // ACCNT
                $accnt_surcharge,
                // NAME
                fn_quickbooks_escape_field($location_manager->getLocationField($order, 'lastname', '', BILLING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'firstname', '', BILLING_ADDRESS_PREFIX)),
                // CLASS
                $trns_class,
                // AMOUNT
                -$order['payment_surcharge'],
                // DOCNUM
                $order['order_id'],
                // MEMO
                'Payment processor surcharge', '',
                // PRICE
                $order['payment_surcharge']
                // QNTY
                -1,
                // INVITEM
                'SURCHARGE',
                // TAXABLE
                'N',
                // EXTRA
                ''
            );
        }

        // ********** AUTO TAX  ************
        if (!$order['taxes']) {
            $export[] = sprintf($spl,
                // DATE
                $order_date,
                // ACCNT
                $accnt_tax,
                // NAME
                fn_quickbooks_escape_field($location_manager->getLocationField($order, 'lastname', '', BILLING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'firstname', '', BILLING_ADDRESS_PREFIX)),
                // CLASS
                $trns_class,
                // AMOUNT
                0,
                // DOCNUM
                $order['order_id'],
                // MEMO
                'TAX', '',
                // PRICE
                '',
                // QNTY
                '',
                // INVITEM
                '',
                // TAXABLE
                'N',
                // EXTRA
                'AUTOSTAX'
            );
        }

        $export[] = "ENDTRNS\t";
    }

    $export[] = '';

    return true;
}

function fn_quickbooks_export_payments($orders, &$export)
{
    $location_manager = Tygh::$app['location'];

    $exists_order_complete = false;
    $payments = array();
    $payments[] = "!TRNS\tTRNSTYPE\tDATE\tACCNT\tNAME\tAMOUNT\tPAYMETH\tDOCNUM";
    $payments[] = "!SPL\tTRNSTYPE\tDATE\tACCNT\tNAME\tAMOUNT\tDOCNUM";
    $payments[] = "!ENDTRNS\t";

    $trns = "TRNS\tPAYMENT\t%s\tUndeposited Funds\t\"%s, %s\"\t%01.2f\t%s\t%d";
    $spl = "SPL\tPAYMENT\t%s\tAccounts Receivable\t\"%s, %s\"\t%01.2f\t%d";

    foreach ($orders as $order) {

        if (in_array($order['status'], fn_get_order_paid_statuses())) {
            $order_date = fn_date_format($order['timestamp'], "%m/%d/%Y");
            $order['payment_method']['payment'] = isset($order['payment_method']['payment']) ? $order['payment_method']['payment'] : '';

            $payments[] = sprintf($trns,
                // DATE
                $order_date,
                // NAME
                fn_quickbooks_escape_field($location_manager->getLocationField($order, 'lastname', '', BILLING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'firstname', '', BILLING_ADDRESS_PREFIX)),
                // AMOUNT
                $order['total'],
                // PAYMETH
                $order['payment_method']['payment'],
                // DOCNUM
                $order['order_id']
            );
            $payments[] = sprintf($spl,
                // DATE
                $order_date,
                // NAME
                fn_quickbooks_escape_field($location_manager->getLocationField($order, 'lastname', '', BILLING_ADDRESS_PREFIX)), fn_quickbooks_escape_field($location_manager->getLocationField($order, 'firstname', '', BILLING_ADDRESS_PREFIX)),
                // AMOUNT
                -$order['total'],
                // DOCNUM
                $order['order_id']
            );
            $payments[] = "ENDTRNS\t";

            $exists_order_complete = true;
        }
    }

    if ($exists_order_complete) {
        $payments[] = '';
        $export = array_merge($export, $payments);
    }

    return true;
}

/**
 * Replaces double quote with two double quotes
 *
 * @param string $value Value to escape
 * @return string Escaped value
 */
function fn_quickbooks_escape_field($value)
{
    return str_replace('"', '""', $value);
}
