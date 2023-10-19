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

use Tygh\Enum\OrderStatuses;
use Tygh\Enum\YesNo;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */
if (defined('PAYMENT_NOTIFICATION')) {
    if ($mode === 'result') {
        if (fn_check_payment_script('ideal_basic.php', $_REQUEST['order_id'])) {
            $order_info = fn_get_order_info($_REQUEST['order_id'], true);
            if ($order_info['status'] === OrderStatuses::INCOMPLETED) {
                fn_change_order_status(
                    $_REQUEST['order_id'],
                    OrderStatuses::OPEN,
                    '',
                    fn_get_notification_rules([], true)
                );
            }
        }
        fn_order_placement_routines('route', $_REQUEST['order_id']);
    } elseif ($mode === 'cancel') {
        if (fn_check_payment_script('ideal_basic.php', $_REQUEST['order_id'])) {
            $pp_response = [];
            $pp_response['order_status'] = 'N';
            $pp_response['reason_text'] = __('text_transaction_cancelled');
            fn_finish_payment($_REQUEST['order_id'], $pp_response);
        }
        fn_order_placement_routines('route', $_REQUEST['order_id'], false);
    } else {
        $xml_response = !isset($GLOBALS['HTTP_RAW_POST_DATA'])
            ? file_get_contents('php://input')
            : $GLOBALS['HTTP_RAW_POST_DATA'];

        if (!empty($xml_response)) {
            preg_match('/<transactionID>(.*)<\/transactionID>/', $xml_response, $transaction);
            preg_match('/<purchaseID>(.*)<\/purchaseID>/', $xml_response, $purchase);
            preg_match('/<status>(.*)<\/status>/', $xml_response, $status);
            preg_match('/<createDateTimeStamp>(.*)<\/createDateTimeStamp>/', $xml_response, $date);

            $order_id = (strpos($purchase[1], '_'))
                ? substr($purchase[1], 0, strpos($purchase[1], '_'))
                : $purchase[1];
            $pp_response = [];

            if ($status[1] === 'Success') {
                $pp_response['order_status'] = OrderStatuses::PAID;
            } elseif ($status[1] === 'Open') {
                $pp_response['order_status'] = OrderStatuses::OPEN;
            } elseif ($status[1] === 'Cancelled') {
                $pp_response['order_status'] = OrderStatuses::CANCELED;
            } else {
                $pp_response['order_status'] = OrderStatuses::FAILED;
            }

            $pp_response['reason_text'] = 'Status code: ' . $status[1];

            $dat = $date[1];
            $time = $dat[0] . $dat[1] . $dat[2] . $dat[3] . '-' . $dat[4] . $dat[5] . '-' . $dat[6] . $dat[7] . ' ' . $dat[8] . $dat[9] . ':' . $dat[10] . $dat[11] . ':' . $dat[12] . $dat[13];

            $pp_response['reason_text'] .= ' (TimeStamp: ' . $time . ')';

            $pp_response['transaction_id'] = $transaction[1];
            if (fn_check_payment_script('ideal_basic.php', $order_id)) {
                fn_finish_payment($order_id, $pp_response); // Force customer notification
            }
        }
    }
} else {
    /** @var array $processor_data */
    /** @var int $order_id */
    /** @var array $order_info $valid_until */
    $valid_until = date('Y-m-d\TH:i:s', time() + 3600 + date('Z'));
    $valid_until = $valid_until . '.000Z';
    $pp_merch = $processor_data['processor_params']['merchant_id'];
    $pp_secret = $processor_data['processor_params']['merchant_key'];
    $pp_test = ($processor_data['processor_params']['test'] === 'TRUE')
        ? 'https://idealtest.secure-ing.com/ideal/mpiPayInitIng.do'
        : 'https://ideal.secure-ing.com/ideal/mpiPayInitIng.do';
    $pp_lang = $processor_data['processor_params']['language'];
    $order_total = $order_info['total'] * 100;
    $_order_id = ($order_info['repaid'])
        ? ($order_id . '_' . $order_info['repaid'])
        : $order_id;

    /*$shastring = "$key" . "$merchantID" . "$subID" . "$amount" . "$orderNumber" .
    "$paymentType" . "$validUntil" .
    "$itemNumber1" . "$itemDescription1" . $product1number . $product1price .
    "$itemNumber2" . "$itemDescription2" . $product2number . $product2price .
    "$itemNumber3" . "$itemDescription3" . $product3number . $product3price .
    "$itemNumber4" . "$itemDescription4" . $product4number . $product4price;

    concatString = merchantKey + merchantID + subID + amount + purchaseID + paymentType + validUntil + itemNumber1 + itemDescription1 + itemQuantity1
    + itemPrice1 (+ itemNumber2 + itemDescription2 + itemQuantity2 + itemPrice2 + itemNumber3 + item...)*/
    $pre_sha = '';
    // Products
    if (!empty($order_info['products'])) {
        foreach ($order_info['products'] as $v) {
            $_name = str_replace('"', '', str_replace("'", '', $v['product']));
            $pre_sha .= $v['product_id'] .
                $_name .
                $v['amount'] .
                ($v['subtotal'] / $v['amount'] * 100);
        }
    }
    // Gift Certificates
    if (!empty($order_info['gift_certificates'])) {
        foreach ($order_info['gift_certificates'] as $v) {
            $v['amount'] = (!empty($v['extra']['exclude_from_calculate']))
                ? 0
                : $v['amount'];
            $pre_sha .= $v['gift_cert_id'] .
                $v['gift_cert_code'] .
                '1' .
                ($v['amount'] * 100);
        }
    }
    // Discounts
    $discount = $order_info['subtotal_discount'];
    if ($discount > 0) {
        $pre_sha .= 'DI' .
            __('discount') .
            '1' .
            ($discount * 100 * -1);
    }

    if (!empty($order_info['use_gift_certificates'])) {
        foreach ($order_info['use_gift_certificates'] as $gc_code => $gc_data) {
            $pre_sha .= $gc_data['gift_cert_id'] .
                $gc_code .
                '1' .
                ($gc_data['amount'] * 100 * -1);
        }
    }

    // Taxes
    if (!empty($order_info['taxes'])) {
        foreach ($order_info['taxes'] as $tax_id => $tax_data) {
            if (YesNo::isTrue($tax_data['price_includes_tax'])) {
                continue;
            }

            $pre_sha .= $tax_id .
                __('tax') .
                '1' .
                ($tax_data['tax_subtotal'] * 100);
        }
    }

    // Shipping
    $shipping = $order_info['shipping_cost'];
    if ($shipping > 0) {
        $pre_sha .= 'SH' .
            __('shipping') .
            '1' .
            ($shipping * 100);
    }

    // Surcharge
    if (!empty($order_info['payment_surcharge'])) {
        $pre_sha .= 'PS' .
            __('payment_surcharge') .
            '1' .
            ($order_info['payment_surcharge'] * 100);
    }

    $shastring = $pp_secret . $pp_merch . '0' . $order_total . $_order_id . 'ideal' . $valid_until . $pre_sha;
    $shastring = str_replace(' ', '', $shastring);
    $shastring = str_replace("\t", '', $shastring);
    $shastring = str_replace("\n", '', $shastring);
    $shastring = str_replace('&amp;', '&', $shastring);
    $shastring = str_replace('&lt;', '<', $shastring);
    $shastring = str_replace('&gt;', '>', $shastring);
    $shastring = str_replace('&quot;', '"', $shastring);

    $shasign = sha1($shastring);

    $counter = 1;

    $return_url = fn_url("payment_notification.result?payment=ideal_basic&order_id=$order_id", AREA, 'current');
    $cancel_url = fn_url("payment_notification.cancel?payment=ideal_basic&order_id=$order_id", AREA, 'current');
    $post_data = [
        'merchantID' => $pp_merch,
        'subID' => '0',
        'amount' => $order_total,
        'purchaseID' => $_order_id,
        'language' => $pp_lang,
        'currency' => 'EUR',
        'description' => 'iDEAL Basic purchase',
        'hash' => $shasign,
        'paymentType' => 'ideal',
        'validUntil' => $valid_until,
        'urlCancel' => $cancel_url,
        'urlSuccess' => $return_url,
        'urlError' => $return_url,
    ];

    // Products
    if (!empty($order_info['products'])) {
        foreach ($order_info['products'] as $v) {
            $item_price = $v['subtotal'] / $v['amount'] * 100;
            $_name = str_replace('"', '', str_replace("'", '', $v['product']));

            $post_data['itemNumber' . $counter] = $v['product_id'];
            $post_data['itemDescription' . $counter] = $_name;
            $post_data['itemQuantity' . $counter] = $v['amount'];
            $post_data['itemPrice' . $counter] = $item_price;
            $counter++;
        }
    }
    // Gift Certificates
    if (!empty($order_info['gift_certificates'])) {
        foreach ($order_info['gift_certificates'] as $v) {
            $item_price = (!empty($v['extra']['exclude_from_calculate']))
                ? 0
                : ($v['amount'] * 100);

            $post_data['itemNumber' . $counter] = $v['gift_cert_id'];
            $post_data['itemDescription' . $counter] = $v['gift_cert_code'];
            $post_data['itemQuantity' . $counter] = 1;
            $post_data['itemPrice' . $counter] = $item_price;
            $counter++;
        }
    }

    // Discount
    if ($order_info['subtotal_discount'] > 0) {
        $discounts = $order_info['subtotal_discount'] * 100 * (-1);

        $post_data['itemNumber' . $counter] = 'DI';
        $post_data['itemDescription' . $counter] = __('discount');
        $post_data['itemQuantity' . $counter] = 1;
        $post_data['itemPrice' . $counter] = $discounts;
        $counter++;
    }

    if (!empty($order_info['use_gift_certificates'])) {
        foreach ($order_info['use_gift_certificates'] as $gc_code => $gc_data) {
            $item_price = fn_format_price($gc_data['amount']) * 100 * (-1);

            $post_data['itemNumber' . $counter] = $gc_data['gift_cert_id'];
            $post_data['itemDescription' . $counter] = $gc_code;
            $post_data['itemQuantity' . $counter] = 1;
            $post_data['itemPrice' . $counter] = $item_price;
            $counter++;
        }
    }

    // Taxes
    if (!empty($order_info['taxes'])) {
        $msg = __('tax');
        foreach ($order_info['taxes'] as $tax_id => $tax_data) {
            if (YesNo::isTrue($tax_data['price_includes_tax'])) {
                continue;
            }

            $item_price = fn_format_price($tax_data['tax_subtotal']) * 100;

            $post_data['itemNumber' . $counter] = $tax_id;
            $post_data['itemDescription' . $counter] = $msg;
            $post_data['itemQuantity' . $counter] = 1;
            $post_data['itemPrice' . $counter] = $item_price;
            $counter++;
        }
    }

    // Shipping
    if ($order_info['shipping_cost'] > 0) {
        $shipping_price = $order_info['shipping_cost'] * 100;

        $post_data['itemNumber' . $counter] = 'SH';
        $post_data['itemDescription' . $counter] = __('shipping');
        $post_data['itemQuantity' . $counter] = 1;
        $post_data['itemPrice' . $counter] = $shipping_price;
        $counter++;
    }

    // Surcharge
    if (!empty($order_info['payment_surcharge'])) {
        $surcharge = $order_info['payment_surcharge'] * 100;

        $post_data['itemNumber' . $counter] = 'PS';
        $post_data['itemDescription' . $counter] = __('payment_surcharge');
        $post_data['itemQuantity' . $counter] = 1;
        $post_data['itemPrice' . $counter] = $surcharge;
    }

    fn_create_payment_form($pp_test, $post_data, 'iDeal', false);
}
