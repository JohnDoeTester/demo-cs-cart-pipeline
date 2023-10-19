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

namespace Tygh\Addons\PaypalCheckout\Payments;

use Exception;
use Tygh\Addons\PaypalCheckout\Api\ClientWrapper;
use Tygh\Addons\PaypalCheckout\Enum\CheckoutPaymentIntent;
use Tygh\Addons\PaypalCheckout\Enum\UserAction;
use Tygh\Addons\PaypalCheckout\Webhook\PaymentCapture;
use Tygh\Common\OperationResult;
use Tygh\Database\Connection;
use Tygh\Enum\OrderStatuses;
use Tygh\Http;
use Tygh\Registry;
use Tygh\Tools\Url;

class PaypalCheckout
{
    /** @var string */
    protected static $processor_script = 'paypal_checkout.php';

    /** @var string */
    protected static $payment_name = 'paypal_checkout';

    /** @var array<string, int|float|string|array> */
    protected $order_info = [];

    /**
     * @var array<string, string|int>
     *
     * @psalm-var array{
     *   access_token: string,
     *   client_id: string,
     *   expiry_time: int,
     *   mode: string,
     *   secret: string,
     *   payer_id: string,
     *   currency: string
     * }
     */
    protected $processor_params;

    /** @var int */
    protected $payment_id;

    /** @var \Tygh\Database\Connection */
    protected $db;

    /** @var \Tygh\Addons\PaypalCheckout\Api\ClientWrapper */
    protected $api;

    /** @var array<string, string> */
    protected $status_conversion_schema;

    /** @var string */
    protected $tax_calculation_method;

    /**
     * PaypalCheckout constructor.
     *
     * @param int                                           $payment_id               Payment method ID
     * @param array<string, string|int>                     $processor_params         Payment method configuration
     * @param array<string, string>                         $status_conversion_schema Order status conversion schema
     * @param \Tygh\Database\Connection                     $db                       Database connection instance
     * @param \Tygh\Addons\PaypalCheckout\Api\ClientWrapper $api                      Api client
     * @param string                                        $tax_calculation_method   Tax calculation method. Either unit_price or subtotal
     *
     * @psalm-param array{
     *   access_token: string,
     *   client_id: string,
     *   expiry_time: int,
     *   mode: string,
     *   secret: string,
     *   payer_id: string,
     *   currency: string
     * } $processor_params
     */
    public function __construct(
        $payment_id,
        array $processor_params,
        array $status_conversion_schema,
        Connection $db,
        ClientWrapper $api,
        $tax_calculation_method
    ) {
        $this->payment_id = $payment_id;

        $this->db = $db;
        $this->api = $api;

        $this->processor_params = $processor_params;
        $this->status_conversion_schema = $status_conversion_schema;

        $this->tax_calculation_method = $tax_calculation_method;
    }

    /**
     * Obtains PayPal Checkout based payment method processor parameters.
     *
     * @param int|null $payment_id If specified, processor parameters of the specified payment method will be returned.
     *                             Otherwise, first suitable method will be used.
     *
     * @return array<string, int|string>
     *
     * @psalm-return array{
     *   access_token: string,
     *   client_id: string,
     *   expiry_time: int,
     *   mode: string,
     *   secret: string,
     *   payer_id: string,
     *   currency: string,
     *   delay_disburse_of_payouts:string,
     *   created_at: int
     * }
     */
    public static function getProcessorParameters($payment_id = null)
    {
        $processor_parameters = [
            'payer_id'     => null,
            'client_id'    => null,
            'secret'       => null,
            'access_token' => null,
            'expiry_time'  => 0,
            'mode'         => 'test',
            'created_at'   => 0,
        ];

        if ($payment_id === null) {
            if ($processor = fn_get_processor_data_by_name(static::getScriptName())) {
                if ($payment = fn_get_payment_by_processor($processor['processor_id'])) {
                    $payment = reset($payment);
                    $payment_id = $payment['payment_id'];
                }
            }
        }

        if ($processor_data = fn_get_processor_data($payment_id)) {
            $processor_parameters = array_merge($processor_parameters, $processor_data['processor_params']);
        }

        return $processor_parameters;
    }

    /**
     * Gets payment processor script name.
     *
     * @return string
     */
    public static function getScriptName()
    {
        return static::$processor_script;
    }

    /**
     * Gets payment method name.
     *
     * @return string
     */
    public static function getPaymentName()
    {
        return static::$payment_name;
    }

    /**
     * Performs payment.
     *
     * @param array<string, int|float|string|array> $order_info Order to pay for.
     *
     * @psalm-param array{
     *   order_id: int
     * } $order_info
     *
     * @return array<string, string> Payment processor response
     *
     * @psalm-return array{'paypal_checkout.order_id': string, order_status: string, reason_text: string}
     */
    public function charge(array $order_info)
    {
        $pp_response = [
            'order_status'             => OrderStatuses::INCOMPLETED,
            'reason_text'              => '',
            'paypal_checkout.order_id' => '',
        ];

        $this->order_info = $order_info;

        $orders_queue = $this->getOrdersToCharge($order_info);

        try {
            $order_specification = [
                'intent'              => CheckoutPaymentIntent::CAPTURE,
                'purchase_units'      => [],
                'application_context' => [
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action'         => UserAction::PAY_NOW_ACTION,
                    'return_url'          => $this->getNotifyUrl('return', ['order_id' => $order_info['order_id']]),
                    'cancel_url'          => $this->getNotifyUrl('cancel', ['order_id' => $order_info['order_id']]),
                ],
            ];

            foreach (array_keys($orders_queue) as $suborder_id) {
                $suborder_info = fn_get_order_info($suborder_id);
                if (!$suborder_info) {
                    continue;
                }

                $order_specification['purchase_units'][] = $this->buildPurchaseUnit($suborder_info);
            }

            /** @psalm-suppress InvalidArgument */
            $response = $this->api->request('/v2/checkout/orders', $order_specification);

            /** @psalm-var array{id: string, links: array} $response */
            $pp_response['paypal_checkout.order_id'] = $response['id'];
            fn_update_order_payment_info($this->order_info['order_id'], $pp_response);

            $pp_response['links'] = $response['links'];
        } catch (Exception $e) {
            $pp_response['order_status'] = OrderStatuses::FAILED;
            $pp_response['reason_text'] = $e->getMessage();
        }

        return $pp_response;
    }

    /**
     * Performs actual payment.
     *
     * @param string $order_id Order ID in PayPal
     *
     * @return \Tygh\Common\OperationResult
     */
    public function capture($order_id)
    {
        $result = new OperationResult();

        try {
            $this->api->request('/v2/checkout/orders/' . $order_id . '/capture', '');
            $result->setSuccess(true);
        } catch (Exception $e) {
            $result->setSuccess(false);
            $result->addError((string) $e->getCode(), $e->getMessage());
        }

        return $result;
    }

    /**
     * Fetches order data from PayPal.
     *
     * @param string $order_id Order ID in PayPal.
     *
     * @return array<string, int|float|string>|null Order data or null on error
     */
    public function getOrder($order_id)
    {
        $order = null;

        try {
            /** @var array<string, int|float|string> $response */
            $response = $this->api->request(
                '/v2/checkout/orders/' . $order_id,
                '',
                [],
                Http::GET
            );
            $order = $response;
        } catch (Exception $e) {
        }

        return $order;
    }

    /**
     * Gets orders that should be paid.
     *
     * @param array<string, int> $order Parent order info
     *
     * @psalm-param {
     *   order_id: int,
     *   company_id: int,
     *   status: string
     * } $order
     *
     * @return array<int, int> Order queue
     *
     * @psalm-suppress InvalidReturnType
     */
    protected function getOrdersToCharge(array $order)
    {
        if ($order['status'] === OrderStatuses::PARENT) {
            $queue = $this->db->getSingleHash(
                'SELECT order_id, company_id FROM ?:orders WHERE parent_order_id = ?i',
                ['order_id', 'company_id'],
                $order['order_id']
            );
        } else {
            $queue = [
                $order['order_id'] => $order['company_id'],
            ];
        }

        /** @psalm-suppress InvalidReturnStatement */
        return $queue;
    }

    /**
     * Formats payment amount by currency.
     *
     * @param float $amount Payment amount
     *
     * @return array<string, float|string> Monetary amount definition
     *
     * @psalm-return array{
     *   value: float,
     *   currency_code: string,
     * }
     */
    protected function formatAmount($amount)
    {
        if (CART_PRIMARY_CURRENCY !== $this->processor_params['currency']) {
            $amount = fn_format_price_by_currency($amount, CART_PRIMARY_CURRENCY, $this->processor_params['currency']);
        }
        $currency_data = Registry::get('currencies.' . $this->processor_params['currency']);

        /** @var float $amount_formatted */
        $amount_formatted = fn_format_rate_value(
            $amount,
            'F',
            $currency_data['decimals'],
            '.',
            ''
        );

        return [
            'value'         => $amount_formatted,
            'currency_code' => $this->processor_params['currency'],
        ];
    }

    /**
     * Builds the `purchase_unit` field for the order creation API request.
     *
     * @param array<string, int|float|string> $order Order info to get purchase unit for
     *
     * @psalm-param array{
     *   total: float,
     *   subtotal: float,
     *   tax_subtotal: float,
     *   order_id: int,
     *   company_id: int,
     *   use_gift_certificates?: bool,
     *   subtotal_discount: float,
     *   points_info?: array{
     *     in_use: int,
     *   },
     *   products: array{
     *     array{
     *       subtotal: float,
     *       amount: int,
     *       product_code: string,
     *       product: string,
     *     },
     *   },
     *   gift_certificates?: array{
     *     array{
     *       extra: array{
     *         exclude_from_calculate: bool,
     *       },
     *       amount: float,
     *       gift_cert_code: string,
     *     },
     *   },
     *   payment_surcharge?: float,
     *   s_firstname: string,
     *   b_firstname: string,
     *   firstname: string,
     *   s_lastname: string,
     *   b_lastname: string,
     *   lastname: string,
     *   s_address: string,
     *   s_country: string,
     *   s_address_2?: string,
     *   s_city?: string,
     *   s_zipcode?: string,
     *   s_state?: string,
     *   shipping: array{
     *     array{
     *       shipping: string,
     *     },
     *   },
     * } $order
     *
     * @return array<string, string>
     *
     * @psalm-return array{
     *   reference_id: int,
     *   amount: array{
     *     value: float,
     *     currency_code: string,
     *     breakdown: array{
     *       item_total: array{
     *         value: float,
     *         currency_code: string,
     *       },
     *       shipping: array{
     *         value: float,
     *         currency_code: string,
     *       },
     *       tax_total?: array{
     *         value: float,
     *         currency_code: string,
     *       },
     *     },
     *   },
     *   description: string,
     *   custom_id: int,
     *   invoice_id: string,
     *   items: array,
     *   payee?: array{
     *     merchant_id: string,
     *   },
     *   payment_instruction?: array{
     *     platform_fees: array{
     *       array{
     *         amount: array{
     *           value: float,
     *           currency_code: string,
     *         },
     *       },
     *     },
     *     disbursement_mode: string,
     *   },
     *   shipping?: array{
     *     name: array{
     *       full_name: string,
     *     },
     *     address: array{
     *       address_line_1: string,
     *       country_code: string,
     *     },
     *   },
     * }
     */
    protected function buildPurchaseUnit(array $order)
    {
        $reference_id = $order['order_id'];

        $amount = $this->buildPurchaseUnitAmount($order);

        $description = $this->getCompanyDescription($order['company_id']);

        $items = $this->buildItems($order);

        $unit = [
            'reference_id'        => $reference_id,
            'amount'              => $amount,
            'description'         => '#' . $reference_id . ' ' . $description,
            'custom_id'           => $reference_id,
            'invoice_id'          => $reference_id . ':' . TIME,
            'items'               => $items,
        ];

        if ($this->getShippingMethod($order)) {
            $unit['shipping'] = [
                'name'    => $this->buildShippingName($order),
                'address' => $this->buildShippingAddress($order),
            ];
        }

        return $unit;
    }

    /**
     * Provides URL to payment notification for an order.
     *
     * @param string                    $method Notification type
     * @param array<string, string|int> $params Query parameters
     *
     * @return string
     */
    protected function getNotifyUrl($method, array $params = [])
    {
        $params = array_merge(
            [
                'payment' => static::getPaymentName(),
            ],
            $params
        );

        $urn = Url::buildUrn(['payment_notification', $method], $params);

        return fn_url($urn);
    }

    /**
     * Converts capture status into order status.
     * TODO: Add status conversion as in PayPal
     *
     * @param string $status Capture status
     *
     * @return string|null
     */
    public function getOrderStatusByCaptureStatus($status)
    {
        if (isset($this->status_conversion_schema[$status])) {
            return $this->status_conversion_schema[$status];
        }

        return null;
    }

    /**
     * Builds purchase items for an order.
     *
     * @param array<string, array<string, string>> $order Order info
     *
     * @psalm-param array{
     *   total: float,
     *   subtotal: float,
     *   tax_subtotal: float,
     *   order_id: int,
     *   company_id: int,
     *   use_gift_certificates?: bool,
     *   subtotal_discount: float,
     *   points_info?: array{
     *     in_use: int,
     *   },
     *   products: array{
     *     array{
     *       subtotal: float,
     *       amount: int,
     *       product_code: string,
     *       product: string,
     *     },
     *   },
     *   gift_certificates?: array{
     *     array{
     *       extra: array{
     *         exclude_from_calculate: bool,
     *       },
     *       amount: float,
     *       gift_cert_code: string,
     *     },
     *   },
     *   payment_surcharge?: float,
     * } $order
     *
     * @return array<array<string, string>>
     *
     * @psalm-return non-empty-list<
     *   array{
     *     name: string,
     *     unit_amount: array{
     *       value: float,
     *       currency_code: string,
     *     },
     *     quantity: int,
     *     sku?: string
     *   }
     * >
     */
    protected function buildItems(array $order)
    {
        $items = [];

        if (
            empty($order['use_gift_certificates'])
            && !$order['subtotal_discount']
            && empty($order['points_info']['in_use'])
        ) {
            foreach ($order['products'] as $product) {
                $items[] = $this->buildProductItem($product);
            }

            if (!empty($order['gift_certificates'])) {
                foreach ($order['gift_certificates'] as $certificate) {
                    $items[] = $this->buildGiftCertificateItem($certificate);
                }
            }
        } else {
            $items[] = [
                'name'        => __('total_product_cost'),
                'unit_amount' => $this->formatAmount($this->getOrderSubtotal($order)),
                'quantity'    => 1,
            ];
        }

        return $items;
    }

    /**
     * Gets product item for an API request.
     *
     * @param array<string, int|float|string> $product Product data
     *
     * @psalm-param array{
     *   subtotal: float,
     *   amount: int,
     *   product_code: string,
     *   product: string,
     * } $product
     *
     * @return array<string, int|float|string>
     *
     * @psalm-return array{
     *   name: string,
     *   unit_amount: array{
     *     value: float,
     *     currency_code: string,
     *   },
     *   quantity: int,
     *   sku?: string,
     * }
     */
    protected function buildProductItem(array $product)
    {
        $price = ($product['subtotal'] - fn_external_discounts($product)) / $product['amount'];

        $item = [
            'name'        => $this->getProductItemName($product['product']),
            'unit_amount' => $this->formatAmount($price),
            'quantity'    => (int) $product['amount'],
        ];

        if (!empty($product['product_code'])) {
            $item['sku'] = $this->getProductItemSku($product['product_code']);
        }

        return $item;
    }

    /**
     * Builds gift certificate item for an API request.
     *
     * @param array<string, int|float|string> $certificate Gift certificate data
     *
     * @psalm-param array{
     *   extra: array{
     *     exclude_from_calculate: bool,
     *   },
     *   amount: float,
     *   gift_cert_code: string
     * } $certificate
     *
     * @return array<string, int|float|string>
     *
     * @psalm-return array{
     *   name: string,
     *   unit_amount: array{
     *     value: float,
     *     currency_code: string,
     *   },
     *   quantity: int,
     * }
     */
    protected function buildGiftCertificateItem(array $certificate)
    {
        $price = empty($certificate['extra']['exclude_from_calculate'])
            ? $certificate['amount']
            : 0;

        return [
            'name'        => $certificate['gift_cert_code'],
            'unit_amount' => $this->formatAmount($price),
            'quantity'    => 1,
        ];
    }

    /**
     * Provides company name.
     *
     * @param int $company_id Company ID
     *
     * @return string Company name
     */
    protected function getCompanyDescription($company_id)
    {
        return fn_get_company_name($company_id, 'order');
    }

    /**
     * Builds purchase unit shipping address for an order.
     *
     * @param array<string, int|float|string> $order Order info
     *
     * @psalm-param array{
     *   total: float,
     *   order_id: int,
     *   company_id: int,
     *   use_gift_certificates?: bool,
     *   subtotal_discount: float,
     *   points_info?: array{
     *     in_use: int,
     *   },
     *   products: array{
     *     array{
     *       subtotal: float,
     *       amount: int,
     *       product_code?: string,
     *     },
     *   },
     *   gift_certificates?: array{
     *     array{
     *       extra: array{
     *         exclude_from_calculate: bool,
     *       },
     *       amount: float,
     *       gift_cert_code: string,
     *     },
     *   },
     *   payment_surcharge?: float,
     *   s_firstname: string,
     *   b_firstname: string,
     *   firstname: string,
     *   s_lastname: string,
     *   b_lastname: string,
     *   lastname: string,
     *   s_address: string,
     *   s_country: string,
     *   s_address_2?: string,
     *   s_city?: string,
     *   s_zipcode?: string,
     *   s_state?: string,
     * } $order
     *
     * @return array<string, string>
     */
    protected function buildShippingAddress(array $order)
    {
        $address = [
            'address_line_1' => $order['s_address'],
            'country_code'   => $order['s_country'],
        ];

        if (!empty($order['s_address_2'])) {
            $address['address_line_2'] = $order['s_address_2'];
        }
        if (!empty($order['s_city'])) {
            $address['admin_area_2'] = $order['s_city'];
        }
        if (!empty($order['s_zipcode'])) {
            $address['postal_code'] = $order['s_zipcode'];
        }
        if (!empty($order['s_state'])) {
            $address['admin_area_1'] = $order['s_state'];
        }

        return $address;
    }

    /**
     * Builds order amount for an API request.
     *
     * @param array<string, int|float|string> $order Order data
     *
     * @psalm-param array{
     *   total: float,
     *   order_id: int,
     *   company_id: int,
     *   subtotal: float,
     *   subtotal_discount: float,
     *   tax_subtotal: float,
     * } $order
     *
     * @return array<string, array<string, array<string, string>>>
     *
     * @psalm-return array{
     *   value: float,
     *   currency_code: string,
     *   breakdown: array{
     *     item_total: array{
     *       value: float,
     *       currency_code: string
     *     },
     *     shipping: array{
     *       value: float,
     *       currency_code: string
     *     },
     *     tax_total?: array{
     *       value: float,
     *       currency_code: string
     *     },
     *   }
     * }
     */
    protected function buildPurchaseUnitAmount(array $order)
    {
        /** @var array{value: float, currency_code: string} $amount */
        $amount = $this->formatAmount($order['total']);
        /** @var array{value: float, currency_code: string} $shipping */
        $shipping = $this->formatAmount($this->getOrderShippingCost($order));
        /** @var array{value: float, currency_code: string} $item_total */
        $item_total = $this->formatAmount($this->getOrderSubtotal($order));
        /** @var array{value: float, currency_code: string} $handling */
        $handling = $this->formatAmount($this->getOrderPaymentSurcharge($order));

        $amount['breakdown'] = [
            'item_total' => $item_total,
            'shipping'   => $shipping,
            'handling'   => $handling
        ];

        if ($this->tax_calculation_method === 'subtotal') {
            /** @var array{value: float, currency_code: string} $tax_total */
            $tax_total = $this->formatAmount($this->getOrderTaxTotal($order));
            $amount['breakdown']['tax_total'] = $tax_total;
        }

        if (!empty($order['use_gift_certificates'])) {
            /** @var array{value: float, currency_code: string} $discount */
            $discount = $this->formatAmount($this->getOrderGiftCertificatesDiscount($order));
            $amount['breakdown']['discount'] = $discount;
        }

        return $amount;
    }

    /**
     * Gets order subtotal.
     *
     * @param array<string, string> $order Order data
     *
     * @psalm-param array{
     *   total: float,
     *   subtotal: float,
     *   subtotal_discount: float,
     *   tax_subtotal: float,
     * } $order
     *
     * @return float
     */
    protected function getOrderSubtotal(array $order)
    {
        return $order['subtotal'] - $order['subtotal_discount'];
    }

    /**
     * Gets order payment surcharge.
     *
     * @param array<string, string|float> $order Order data
     *
     * @return float
     */
    protected function getOrderPaymentSurcharge(array $order)
    {
        return (float) $order['payment_surcharge'];
    }

    /**
     * Gets order discount from gift certificate.
     *
     * @param array<string, string|float|array<string, string|int>> $order Order data
     *
     * @psalm-param array{
     *   company_id: int,
     *   order_id: int,
     *   subtotal: float,
     *   subtotal_discount: float,
     *   tax_subtotal: float,
     *   total: float,
     *   use_gift_certificates: array{
     *       array{
     *          gift_cert_id: string,
     *          amount: string,
     *          cost: string,
     *          log_id: int
     *      },
     *   },
     * } $order
     *
     * @return float
     */
    protected function getOrderGiftCertificatesDiscount(array $order)
    {
        return (float) array_reduce($order['use_gift_certificates'], static function ($total_cost, $gift_certificate) {
            return $total_cost + $gift_certificate['cost'];
        }, 0);
    }

    /**
     * Gets order shipping cost.
     *
     * @param array<string, int|float|string> $order Order data
     *
     * @psalm-param array{
     *   total: float,
     * } $order
     *
     * @return float
     */
    protected function getOrderShippingCost(array $order)
    {
        return fn_order_shipping_cost($order);
    }

    /**
     * Gets order shipping method.
     *
     * @param array<string, array<string, string>> $order Order data
     *
     * @psalm-param array{
     *   total: float,
     *   order_id: int,
     *   company_id: int,
     *   use_gift_certificates?: bool,
     *   subtotal_discount: float,
     *   points_info?: array{
     *     in_use: int,
     *   },
     *   products: array{
     *     array{
     *       subtotal: float,
     *       amount: int,
     *       product_code?: string,
     *     },
     *   },
     *   gift_certificates?: array{
     *     array{
     *       extra: array{
     *         exclude_from_calculate: bool,
     *       },
     *       amount: float,
     *       gift_cert_code: string,
     *     },
     *   },
     *   payment_surcharge?: float,
     *   s_firstname: string,
     *   b_firstname: string,
     *   firstname: string,
     *   s_lastname: string,
     *   b_lastname: string,
     *   lastname: string,
     *   s_address: string,
     *   s_country: string,
     *   s_address_2?: string,
     *   s_city?: string,
     *   s_zipcode?: string,
     *   s_state?: string,
     *   shipping: array{
     *     array{
     *       shipping: string,
     *     },
     *   },
     * } $order
     *
     * @return string
     */
    protected function getShippingMethod(array $order)
    {
        $shipping_method = '';

        if (!empty($order['shipping'])) {
            $shipping = reset($order['shipping']);
            $shipping_method = $shipping['shipping'];
        }

        return $shipping_method;
    }

    /**
     * Gets customer name for shipping information for an API request.
     *
     * @param array<string, string> $order Order data
     *
     * @psalm-param array{
     *   s_firstname: string,
     *   b_firstname: string,
     *   firstname: string,
     *   s_lastname: string,
     *   b_lastname: string,
     *   lastname: string,
     * } $order
     *
     * @return array<string, string>
     *
     * @psalm-return array{
     *   full_name: string
     * }
     */
    protected function buildShippingName(array $order)
    {
        $firstname = '';
        $lastname = '';
        foreach ([SHIPPING_ADDRESS_PREFIX . '_', BILLING_ADDRESS_PREFIX . '_', ''] as $prefix) {
            if (!empty($order["{$prefix}firstname"]) && $firstname === '') {
                $firstname = $order["{$prefix}firstname"];
            }
            if (!empty($order["{$prefix}lastname"]) && $lastname === '') {
                $lastname = $order["{$prefix}lastname"];
            }
            if ($firstname !== '' && $lastname !== '') {
                break;
            }
        }

        return [
            'full_name' => $firstname . ' ' . $lastname,
        ];
    }

    /**
     * Gets tax total from an order.
     *
     * @param array<string, float> $order Order info
     *
     * @psalm-param array{
     *   total: float,
     *   subtotal: float,
     *   subtotal_discount: float,
     *   tax_subtotal: float,
     * } $order
     *
     * @return float
     */
    protected function getOrderTaxTotal(array $order)
    {
        return $order['tax_subtotal'];
    }

    /**
     * Gets product name formatted for the API usage.
     *
     * @param string $product_name Raw product name
     *
     * @return string
     */
    private function getProductItemName($product_name)
    {
        return fn_substr(strip_tags($product_name), 0, 127);
    }

    /**
     * Gets product SKU formatted for the API usage.
     *
     * @param string $product_code Raw product code
     *
     * @return string
     */
    private function getProductItemSku($product_code)
    {
        return fn_substr($product_code, 0, 127);
    }

    /**
     * Gets capture from PayPal.
     *
     * @param string $capture_id Capture identifier
     *
     * @return PaymentCapture|null $capture Capture
     */
    public function getCapture($capture_id)
    {
        $capture = null;

        try {
            /** @var array $response */
            $response = $this->api->request(
                '/v2/payments/captures/' . $capture_id,
                '',
                [],
                Http::GET
            );

            $capture = new PaymentCapture(
                $response['id'],
                $response['status'],
                $response['custom_id'],
                $response['amount']['value']
            );
        } catch (Exception $e) {
        }

        return $capture;
    }
}
