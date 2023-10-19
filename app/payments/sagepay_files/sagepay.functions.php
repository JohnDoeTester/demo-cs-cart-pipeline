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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Enum\YesNo;

function addPKCS5Padding($input)
{
    $blockSize = 16;
    $padd = "";

    // Pad input to an even block size boundary.
    $length = $blockSize - (strlen($input) % $blockSize);
    for ($i = 1; $i <= $length; $i++) {
        $padd .= chr($length);
    }

    return $input . $padd;
}

function encryptAes($string, $key)
{
    // AES encryption, CBC blocking with PKCS5 padding then HEX encoding.
    // Add PKCS5 padding to the text to be encypted.
    $string = addPKCS5Padding($string);

    // Perform encryption with PHP's openssl module.
    $encrypted = openssl_encrypt($string, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $key);

    // Perform hex encoding and return.
    return "@" . strtoupper(bin2hex($encrypted));
}

function decryptAes($strIn, $password)
{
    // Remove the first char which is @ to flag this is AES encrypted and HEX decoding.
    $hex = substr($strIn, 1);

    // Throw exception if string is malformed
    if (!preg_match('/^[0-9a-fA-F]+$/', $hex)) {
        //invalid key
        return false;
    }
    $strIn = pack('H*', $hex);

    // Perform decryption with PHP's openssl module.
    $decrypted = openssl_decrypt($strIn, 'AES-128-CBC', $password, OPENSSL_RAW_DATA, $password);

    /* Check result for printable characters */
    if (preg_match('/[[:^print:]]/', $decrypted)) {
        return false;
    }

    return $decrypted;
}

/**
 * Formats string to be used in Basket request field.
 *
 * @param array $order_info Order info
 * @param string $primary_currency Cart's primary currency
 * @param string $pp_currency Payment processor currency
 *
 * @return string
 */
function fn_sagepay_get_basket($order_info, $primary_currency, $pp_currency)
{
    $basket_items = array();
    $basket_item_template = '[descr]:[quantity]:[cost_wo_tax]:[tax]:[cost_w_tax]:[total]';

    if (!empty($order_info['products'])) {
        foreach ($order_info['products'] as $v) {
            $total       = $v['subtotal'] - fn_external_discounts($v);
            $tax_value   = 0;
            if (!empty($order_info['taxes'])) {
                $tax_value = fn_sagepay_get_tax_value_included_in_price($order_info, $v['item_id'], 'product');
            }
            $cost_wo_tax = ($total - $tax_value) / $v['amount'];
            $tax         = $tax_value / $v['amount'];
            $cost_w_tax  = $total / $v['amount'];
            $basket_items[] = strtr($basket_item_template,
                [
                    '[descr]'       => str_replace(':', ' ', $v['product']),
                    '[quantity]'    => (int) $v['amount'],
                    '[cost_wo_tax]' => fn_format_price_by_currency($cost_wo_tax, $primary_currency, $pp_currency),
                    '[tax]'         => fn_format_price_by_currency($tax,         $primary_currency, $pp_currency),
                    '[cost_w_tax]'  => fn_format_price_by_currency($cost_w_tax,  $primary_currency, $pp_currency),
                    '[total]'       => fn_format_price_by_currency($total,       $primary_currency, $pp_currency),
                ]
            );
        }
    }
    if (!empty($order_info['gift_certificates'])) {
        foreach ($order_info['gift_certificates'] as $v) {
            $v['amount'] = (!empty($v['extra']['exclude_from_calculate'])) ? 0 : $v['amount'];
            $basket_items[] = strtr($basket_item_template,
                [
                    '[descr]'       => str_replace(':', ' ', $v['gift_cert_code']),
                    '[quantity]'    => 1,
                    '[cost_wo_tax]' => fn_format_price_by_currency($v['amount'], $primary_currency, $pp_currency),
                    '[tax]'         => 0,
                    '[cost_w_tax]'  => fn_format_price_by_currency($v['amount'], $primary_currency, $pp_currency),
                    '[total]'       => fn_format_price_by_currency($v['amount'], $primary_currency, $pp_currency),
                ]
            );
        }
    }
    if (floatval($order_info['payment_surcharge'])) {
        $basket_items[] = strtr($basket_item_template,
            [
                '[descr]'       => str_replace(':', ' ', __('payment_surcharge')),
                '[quantity]'    => '---',
                '[cost_wo_tax]' => '---',
                '[tax]'         => '---',
                '[cost_w_tax]'  => '----',
                '[total]'       => fn_format_price_by_currency($order_info['payment_surcharge'], $primary_currency, $pp_currency),
            ]
        );
    }
    if (!empty($order_info['shipping_cost']) && (float) $order_info['shipping_cost']) {
        $cost_w_tax = $order_info['shipping_cost'];
        $tax_value   = 0;
        if (!empty($order_info['taxes'])) {
            $tax_value = fn_sagepay_get_tax_value_included_in_price($order_info, 0, 'shipping');
        }
        $cost_wo_tax = $cost_w_tax - $tax_value;
        $basket_items[] = strtr($basket_item_template,
            [
                '[descr]'       => str_replace(':', ' ', __('shipping_cost')),
                '[quantity]'    => '---',
                '[cost_wo_tax]' => fn_format_price_by_currency($cost_wo_tax, $primary_currency, $pp_currency),
                '[tax]'         => fn_format_price_by_currency($tax_value, $primary_currency, $pp_currency),
                '[cost_w_tax]'  => fn_format_price_by_currency($cost_w_tax,  $primary_currency, $pp_currency),
                '[total]'       => fn_format_price_by_currency($cost_w_tax,  $primary_currency, $pp_currency),
            ]
        );
    }

    if (floatval($order_info['subtotal_discount'])) {
        $basket_items[] = strtr($basket_item_template,
            [
                '[descr]'       => str_replace(':', ' ', __('order_discount')),
                '[quantity]'    => '---',
                '[cost_wo_tax]' => '---',
                '[tax]'         => '---',
                '[cost_w_tax]'  => '---',
                '[total]'       => -fn_format_price_by_currency($order_info['subtotal_discount'], $primary_currency, $pp_currency),
            ]
        );
    }

    if (!empty($order_info['taxes'])) {
        foreach ($order_info['taxes'] as $tax_id => $tax) {
            if (YesNo::isTrue($tax['price_includes_tax'])) {
                continue;
            }

            $basket_items[] = strtr(
                $basket_item_template,
                [
                    '[descr]'       => str_replace(':', ' ', $tax['description']),
                    '[quantity]'    => '---',
                    '[cost_wo_tax]' => '---',
                    '[tax]'         => '---',
                    '[cost_w_tax]'  => '---',
                    '[total]'       => fn_format_price_by_currency($tax['tax_subtotal'], $primary_currency, $pp_currency),
                ]
            );
        }
    }

    return sprintf('%d:%s',
        count($basket_items),
        implode(':', $basket_items)
    );
}

//phpcs:ignore
function fn_sagepay_get_tax_value_included_in_price($order_info, $object_id, $object_type)
{
    if (!$order_info['taxes'] || !is_array($order_info['taxes']) || !$object_type) {
        return 0;
    }

    $tax_value = 0;

    foreach ($order_info['taxes'] as $tax) {
        if (YesNo::isFalse($tax['price_includes_tax']) || empty($tax['applies'])) {
            continue;
        }

        foreach ($tax['applies'] as $id => $value) {
            //phpcs:ignore
            if (
                $object_type === 'product'
                && $id === 'P_' . $object_id
                || $object_type === 'shipping'
                && strpos($id, 'S_') !== false
            ) {
                $tax_value += $value;
            }
        }
    }

    return $tax_value;
}
