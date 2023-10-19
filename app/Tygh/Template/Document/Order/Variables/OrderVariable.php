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


namespace Tygh\Template\Document\Order\Variables;


use Tygh\Registry;
use Tygh\Template\Document\Order\Context;
use Tygh\Template\Document\Variables\GenericVariable;
use Tygh\Template\IActiveVariable;
use Tygh\Tools\Formatter;

/**
 * The class of the `order` variable; it allows access to order data in the document editor.
 *
 * @package Tygh\Template\Document\Order\Variables
 */
class OrderVariable extends GenericVariable implements IActiveVariable
{
    /** @var Formatter Instance of formatter. */
    protected $formatter;

    /** @var array Config data. */
    protected $config;

    /** @var Context Instance of context. */
    protected $context;

    /**
     * OrderVariable constructor.
     *
     * @param Context   $context    Instance of context.
     * @param array     $config     Config data.
     * @param Formatter $formatter  Instance of formatter.
     */
    public function __construct(Context $context, array $config, Formatter $formatter)
    {
        parent::__construct($context, $config);

        $order = $context->getOrder();

        $currency = $order->getCurrencyCode();

        $this->config = $config;
        $this->formatter = $formatter;
        $this->context = $context;
        $this->data = $order->data;

        $this->data['raw'] = array();
        $this->data['raw']['timestamp'] = $this->data['timestamp'];
        $this->data['raw']['notes'] = $this->data['notes'];
        $this->data['raw']['display_subtotal'] = $this->data['display_subtotal'];
        $this->data['raw']['discount'] = $this->data['discount'];
        $this->data['raw']['subtotal_discount'] = $this->data['subtotal_discount'];
        $this->data['raw']['payment_surcharge'] = $this->data['payment_surcharge'];
        $this->data['raw']['display_shipping_cost'] = $this->data['display_shipping_cost'];
        $this->data['raw']['total'] = $this->data['total'];

        $this->data['timestamp'] = $formatter->asDatetime($this->data['timestamp']);
        $this->data['notes'] = $formatter->asNText($this->data['notes']);
        $this->data['display_subtotal'] = $formatter->asPrice($this->data['display_subtotal'], $currency, true, true);
        $this->data['discount'] = $formatter->asPrice($this->data['discount'], $currency, true, true);
        $this->data['subtotal_discount'] = $formatter->asPrice($this->data['subtotal_discount'], $currency, true, true);
        $this->data['payment_surcharge'] = $formatter->asPrice($this->data['payment_surcharge'], $currency, true, true);
        $this->data['display_shipping_cost'] = $formatter->asPrice($this->data['display_shipping_cost'], $currency, true, true);
        $this->data['total'] = $formatter->asPrice($this->data['total'], $currency, true, true);
        $this->data['status'] = $order->getStatusData($context->getLangCode());
        $this->data['secondary_currency'] = $currency;

        $this->initCouponCode();
        $this->initTaxes();
        $this->initShippings();
        $this->initInvoiceHeaderText();
    }

    /**
     * Initialize coupon_code attribute.
     */
    protected function initCouponCode()
    {
        $this->data['coupon_code'] = '';

        if (!empty($this->data['coupons'])) {
            $coupon_code_separator = isset($this->config['coupon_code_separator']) ? $this->config['coupon_code_separator'] : '<br/>';
            $coupons = array();

            foreach ($this->data['coupons'] as $coupon_code => $coupon) {
                $coupons[] = $coupon_code;
            }
            $this->data['coupon_code'] = implode($coupon_code_separator, $coupons);
        }
    }

    /**
     * Initialize tax attributes.
     */
    protected function initTaxes()
    {
        $this->data['tax_exempt_text'] = '';
        $this->data['tax_name'] = '';
        $this->data['tax_total'] = '';

        if (!empty($this->data['taxes'])) {
            $tax_separator = isset($this->config['tax_separator']) ? $this->config['tax_separator'] : '<br/>';
            $tax_names = $tax_totals = array();

            foreach ($this->data['taxes'] as $tax) {
                $name = $tax['description'] . '&nbsp;';
                if ($tax['rate_type'] == 'F') {
                    $name .= $this->formatter->asPrice(abs($tax['rate_value']), $this->data['secondary_currency'], true, true);
                } else {
                    $name .= abs($tax['rate_value']) . '%';
                }
                if (
                    $tax['price_includes_tax'] == 'Y'
                    && (
                        Registry::get('settings.Appearance.cart_prices_w_taxes') != 'Y'
                        || Registry::get('settings.Checkout.tax_calculation') == 'subtotal'
                    )
                ) {
                    $name .= '&nbsp;' . __('included', array(), $this->context->getLangCode());
                }
                $tax_names[] = $name;
                $tax_totals[] = $this->formatter->asPrice($tax['tax_subtotal'], $this->data['secondary_currency'], true, true);
            }

            $this->data['tax_name'] = implode($tax_separator, $tax_names);
            $this->data['tax_total'] = implode($tax_separator, $tax_totals);
        }

        if (!empty($this->data['tax_exempt']) && $this->data['tax_exempt'] == 'Y') {
            $this->data['tax_exempt_text'] = __('tax_exempt', array(), $this->context->getLangCode());
        }
    }

    /**
     * Initialize shippings attributes.
     */
    protected function initShippings()
    {
        $shippings = $this->context->getOrder()->getShippings();
        $shipments = $this->context->getOrder()->getShipments();

        if (!empty($shippings)) {
            $shipping_methods = array();
            $tracking_numbers = array();

            foreach ($shippings as $shipping) {
                if (!empty($shipping['shipping'])) {
                    $shipping_methods[] = $shipping['shipping'];
                }
            }

            foreach ($shipments as $shipment) {
                if (!empty($shipment['tracking_number'])) {
                    $tracking_number = $shipment['tracking_number'];

                    if (!empty($shipment['carrier_info']['tracking_url'])) {
                        $tracking_number = sprintf('<a href="%s">%s</a>',
                            $shipment['carrier_info']['tracking_url'],
                            $shipment['tracking_number']
                        );
                    }

                    $tracking_numbers[] = $tracking_number;
                }
            }

            $this->data['shippings_method'] = implode(', ', $shipping_methods);
            $this->data['tracking_number'] = implode(', ', $tracking_numbers);
        }
    }

    /**
     * Initialize header for invoice.
     */
    protected function initInvoiceHeaderText()
    {
        $status_data = $this->context->getOrder()->getStatusData($this->context->getLangCode());

        $this->data['invoice_header'] = __('invoice_heading', array(), $this->context->getLangCode());
        $this->data['invoice_id_text'] = __('order', array(), $this->context->getLangCode()) . '&nbsp;#' . $this->context->getOrder()->getId();

        if (!empty($status_data['params']['appearance_type'])) {
            if ($status_data['params']['appearance_type'] == 'O') {
                $this->data['invoice_header'] = __('order_details', array(), $this->context->getLangCode());
            } elseif (!empty($this->data['doc_ids'][$status_data['params']['appearance_type']])) {
                $doc_id = $this->data['doc_ids'][$status_data['params']['appearance_type']];
                if ($status_data['params']['appearance_type'] == 'I') {
                    $this->data['invoice_id_text'] = __('invoice', array(), $this->context->getLangCode()) . ' #' . $doc_id . '<br/>' . $this->data['invoice_id_text'];
                } elseif ($status_data['params']['appearance_type'] == 'C') {
                    $this->data['invoice_header'] = __('credit_memo', array(), $this->context->getLangCode());
                    $this->data['invoice_id_text'] = __('credit_memo', array(), $this->context->getLangCode()) . ' #' . $doc_id . '<br/>' . $this->data['invoice_id_text'];
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function attributes()
    {
        return array(
            'order_id', 'company_id', 'issuer_id', 'user_id', 'tracking_number', 'shippings_method',
            'total', 'subtotal', 'discount', 'subtotal_discount', 'payment_surcharge',
            'display_subtotal', 'shipping_cost', 'timestamp', 'notes', 'details', 'company', 'payment_id',
            'tax_exempt', 'tax_name', 'tax_total', 'lang_code', 'ip_address', 'tax_exempt_text', 'tax_name', 'tax_total',
            'tax_subtotal', 'display_shipping_cost', 'secondary_currency', 'coupon_code', 'invoice_header', 'invoice_id_text',
            'products' => array(
                '[0..N]' => array(
                    'item_id', 'product_id', 'product_code', 'price', 'amount', 'product', 'product_status',
                    'discount', 'base_price', 'original_price', 'cart_id', 'tax_value', 'subtotal', 'display_subtotal'
                )
            ),
            'taxes' => array(
                '[0..N]' => array(
                    'rate_type', 'rate_value', 'price_includes_tax', 'regnumber', 'tax_subtotal', 'description'
                )
            ),
            'shipping' => array(
                '[0..N]' => array(
                    'shipping_id', 'shipping', 'delivery_time', 'rate_calculation', 'destination',
                    'min_weight', 'max_weight', 'service_code', 'module', 'rate', 'group_name'
                )
            ),
            'product_groups' => array(
                '[0..N]' => array(
                    'name', 'company_id',
                    'products' => array(
                        '[0..N]' => array(
                            'item_id', 'product_id', 'product_code', 'price', 'amount', 'product', 'product_status',
                            'discount', 'base_price', 'original_price', 'cart_id', 'tax_value', 'subtotal', 'display_subtotal'
                        )
                    ),
                    'package_info' => array(
                        'shipping_freight',
                        'origination' => array(
                            'name', 'address', 'city', 'country', 'state', 'zipcode', 'phone', 'fax'
                        )
                    ),
                    'free_shipping'
                )
            ),
            'status' => array(
                'description', 'type', 'status', 'status_id', 'params' => array(
                    'allow_return', 'repay', 'notify', 'inventory', 'color', 'appearance_type'
                )
            ),
            'raw' => array(
                'timestamp', 'notes', 'display_subtotal', 'discount', 'subtotal_discount', 'payment_surcharge',
                'display_shipping_cost', 'total'
            ),
        );
    }
}