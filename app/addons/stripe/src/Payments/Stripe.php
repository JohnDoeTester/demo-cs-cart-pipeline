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

namespace Tygh\Addons\Stripe\Payments;

use Exception;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe as StripeSetup;
use Tygh\Addons\Stripe\Logger;
use Tygh\Addons\Stripe\PriceFormatter;
use Tygh\Common\OperationResult;
use Tygh\Database\Connection;
use Tygh\Enum\OrderStatuses;

/**
 * Class Stripe implements Stipe payment method.
 *
 * @package Tygh\Payments\Addons\Stripe
 */
class Stripe
{
    /** @var string */
    const API_VERSION = '2019-05-16';

    /** @var string */
    const PAYMENT_INTENT_STATUS_SUCCEDED = 'succeeded';

    /** @var string */
    const PAYMENT_INTENT_STATUS_REQUIRES_ACTION = 'requires_action';

    /** @var string */
    const PAYMENT_INTENT_STATUS_REQUIRES_CONFIRMATION = 'requires_confirmation';

    /** @var string $processor_script */
    protected static $processor_script = 'stripe.php';

    /** @var string $addon_name */
    protected static $addon_name = 'stripe';

    /** @var array $processor_params */
    protected $processor_params = [];

    /** @var int $payment_id */
    protected $payment_id;

    /** @var Connection $db */
    protected $db;

    /** @var  PriceFormatter $formatter */
    protected $price_formatter;

    /**
     * Stripe constructor.
     *
     * @param int                                $payment_id       Payment method ID
     * @param \Tygh\Database\Connection          $db               Database connection
     * @param \Tygh\Addons\Stripe\PriceFormatter $price_formatter  Formatter
     * @param array<array-key, string>|null      $processor_params Payment processor configuration.
     *                                                             When set to null, will be obtained from the database
     */
    public function __construct($payment_id, Connection $db, PriceFormatter $price_formatter, $processor_params = null)
    {
        $this->payment_id = $payment_id;
        $this->db = $db;
        $this->price_formatter = $price_formatter;

        if ($processor_params === null) {
            $this->processor_params = static::getProcessorParameters($payment_id);
        } else {
            $this->processor_params = $processor_params;
        }

        StripeSetup::setApiKey($this->processor_params['secret_key']);
        StripeSetup::setApiVersion(self::API_VERSION);
    }

    /**
     * Obtains Stripe Connect based payment method processor parameters.
     *
     * @param int|null $payment_id If specified, processor parameters of the specified payment method will be returned.
     *                             Otherwise, first suitable method will be used.
     *
     * @return array
     */
    public static function getProcessorParameters($payment_id = null)
    {
        if ($payment_id === null) {
            if ($processor = fn_get_processor_data_by_name(static::getScriptName())) {
                if ($payment = fn_get_payment_by_processor($processor['processor_id'])) {
                    $payment = reset($payment);
                    $payment_id = $payment['payment_id'];
                }
            }
        }

        if ($processor_data = fn_get_processor_data($payment_id)) {
            return $processor_data['processor_params'];
        }

        return [
            'publishable_key' => null,
            'secret_key'      => null,
            'currency'        => null,
        ];
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
     * Gets add-on name.
     *
     * @return string
     */
    public static function getAddonName()
    {
        return static::$addon_name;
    }

    /**
     * Performs payment.
     *
     * @param array $order_info Order to pay for.
     *
     * @return array Payment processor response
     */
    public function charge(array $order_info)
    {
        $pp_response = [
            'order_status'             => OrderStatuses::INCOMPLETED,
            'reason_text'              => '',
            'stripe.payment_intent_id' => '',
        ];

        try {
            $payment_intent = PaymentIntent::retrieve($order_info['payment_info']['stripe.payment_intent_id']);
            if ($payment_intent->status === self::PAYMENT_INTENT_STATUS_REQUIRES_CONFIRMATION) {
                $payment_intent->confirm();
            }

            /** @var \Stripe\Charge $charge */
            foreach ($payment_intent->charges->all() as $charge) {
                $charge->metadata['order_id'] = $order_info['order_id'];
                $charge->save();
                break;
            }

            fn_update_order_payment_info($order_info['order_id'], [
                'order_status'   => OrderStatuses::PAID,
                'transaction_id' => $payment_intent->id,
            ]);

            $pp_response['order_status'] = OrderStatuses::PAID;
        } catch (Exception $e) {
            $pp_response['order_status'] = OrderStatuses::FAILED;
            $pp_response['reason_text'] = $e->getMessage();
        }

        return $pp_response;
    }

    /**
     * Formats payment amount by currency.
     *
     * @param float $amount Payment amount
     *
     * @return int Order amount <b>in cents</b>
     */
    public function formatAmount($amount)
    {
        return $this->price_formatter->asCents($amount, $this->processor_params['currency']);
    }

    /**
     * Refunds charge.
     *
     * @param array $order_info Refunded order info
     * @param float $amount     Refunded amount
     *
     * @return string
     *
     * @throws \Stripe\Exception\ApiErrorException Stripe exception.
     */
    public function refund(array $order_info, $amount)
    {
        $payment_intent = PaymentIntent::retrieve($order_info['payment_info']['transaction_id']);
        /** @var \Stripe\Charge[] $charges */
        $charges = $payment_intent->charges->all();
        $charge = reset($charges);

        $refund = Refund::create([
            'charge' => $charge->id,
            'amount' => $this->formatAmount($amount),
            'reason' => 'requested_by_customer',
        ]);

        return $refund->id;
    }

    /**
     * Gets payment intent confirmation details.
     *
     * @param string   $payment_intent_payment_method_id Payment method ID
     * @param float    $total                            Payment total
     * @param int|null $order_id                         Order ID
     *
     * @return \Tygh\Common\OperationResult
     *
     * @throws \Stripe\Exception\ApiErrorException Stripe exception.
     */
    public function getPaymentConfirmationDetails($payment_intent_payment_method_id, $total, $order_id = null)
    {
        $result = new OperationResult(false);

        $intent = PaymentIntent::create([
            'payment_method'      => $payment_intent_payment_method_id,
            'amount'              => $this->formatAmount($total),
            'currency'            => $this->processor_params['currency'],
            'confirmation_method' => 'manual',
            'confirm'             => true,
            'metadata'            => [
                'order_id' => $order_id,
            ],
        ]);

        $is_success = in_array($intent->status, [
            self::PAYMENT_INTENT_STATUS_REQUIRES_ACTION,
            self::PAYMENT_INTENT_STATUS_SUCCEDED,
        ]);
        $result->setSuccess($is_success);

        $result->setData($intent->id, 'payment_intent_id');

        if ($intent->status === self::PAYMENT_INTENT_STATUS_REQUIRES_ACTION) {
            $result->setData(true, 'requires_confirmation');
            $result->setData($intent->client_secret, 'client_secret');
        }

        return $result;
    }

    /**
     * Creates Checkout Session for Stripe Connect.
     *
     * @param array<array-key, string|int|float|array<array-key, string|int|float|array<array-key, string|int|float|array<array-key, string|int|float>>>> $order_info Order information
     *
     * @return Session|void
     */
    public function createCheckoutSession(array $order_info)
    {
        $order_id = $order_info['order_id'];
        $metadata = [
            'payment_type' => 'stripe',
            'order_id'     => $order_id
        ];

        $customer_fullname = __('customer');
        if (
            !empty($order_info['firstname'])
            || !empty($order_info['lastname'])
        ) {
            $customer_fullname = $order_info['firstname'] . ' ' . $order_info['lastname'];
        }

        $orders = $this->getSessionLineItems($order_info);

        try {
            $session_params = [
                'line_items'  => $orders,
                'locale'      => $this->getSessionLocale(),
                'submit_type' => 'pay',
                'mode'        => 'payment',
                'success_url' => fn_url('payment_notification.success?payment=stripe&order_id=' . $order_id),
                'cancel_url'  => fn_url('payment_notification.cancel?payment=stripe&order_id=' . $order_id),
                'payment_intent_data' => [
                    'metadata' => $metadata,
                    'shipping' => [
                        'name' => $customer_fullname,
                        'address' => [
                            'line1' => $order_info['s_address'],
                            'line2' => $order_info['s_address_2'],
                            'city' => $order_info['s_city'],
                            'state' => $order_info['s_state'],
                            'country' => $order_info['s_country'],
                            'postal_code' => $order_info['s_zipcode'],
                        ],
                    ],
                    'description' => __('order') . ' #' . $order_info['order_id']
                ],
                'customer_email' => $order_info['email'],
            ];

            return Session::create($session_params);
        } catch (Exception $e) {
            Logger::logException($e);
        }
    }

    /**
     * Creates orders list for Checkout Session.
     *
     * @param array<array-key, string|int|float|array<array-key, string|int|float|array<array-key, string|int|float|array<array-key, string|int|float>>>> $order_info Order information
     *
     * @return array<array-key, array<array-key, string|int|array<array-key, string|int|array<array-key, string|int>>>>
     */
    protected function getSessionLineItems(array $order_info)
    {
        return
            [
                [
                    'price_data' =>
                        [
                            'currency' => $this->processor_params['currency'],
                            'product_data' =>
                                [
                                    'name' => __('order') . ' #' . $order_info['order_id'],
                                ],
                            'unit_amount' => $this->formatAmount((float) $order_info['total']),
                        ],
                    'quantity' => 1,
                ]
            ];
    }

    /**
     * Select locale language for Checkout Session.
     *
     * @return string
     */
    protected function getSessionLocale()
    {
        $locale = CART_LANGUAGE;

        if ($locale === 'no') {
            $locale = 'nb';
        }

        if (!in_array($locale, ['bg', 'hr', 'cs', 'da', 'nl', 'en', 'et', 'fi', 'fil', 'fr', 'de', 'el', 'hu', 'id', 'it', 'ja', 'ko', 'lv', 'lt', 'ms', 'mt', 'nb', 'pl', 'pt', 'ro', 'ru', 'zh', 'sk', 'sl', 'es', 'sv', 'th', 'tr', 'vi'], true)) {
            $locale = 'en';
        }

        return $locale;
    }
}
