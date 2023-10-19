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

namespace Tygh\Payments\Addons\Pingpp;

use Exception;
use Pingpp\Pingpp as Sdk;
use Pingpp\WxpubOAuth;
use Pingpp\Charge;
use Tygh\Enum\OrderStatuses;
use Tygh\Payments\Addons\Pingpp\Channels\IQrPayment;
use Tygh\Payments\Addons\Pingpp\Channels\IWxPayment;
use Tygh\Registry;

class Pingpp
{
    /**
     * @var string $processor_script
     */
    protected static $processor_script = 'pingpp.php';

    /**
     * @var string $curreny
     */
    protected static $curreny = 'CNY';

    /**
     * @var string $payment_name
     */
    protected static $payment_name = 'pingpp';

    /**
     * @var int $subject_length
     */
    private static $subject_length = 32;

    /**
     * @var int $body_length
     */
    private static $body_length = 100;

    /**
     * @var string $channel_name
     */
    protected $channel_name;

    /**
     * @var array $order_info
     */
    protected $order_info = array();

    /**
     * @var array $processor_params
     */
    protected $processor_params = array();

    /**
     * @var int $payment_id
     */
    protected $payment_id;

    /**
     * @var string $current_location
     */
    protected $current_location;

    /**
     * @var \Tygh\Payments\Addons\Pingpp\Channels\IChannel $channel
     */
    protected $channel;

    /**
     * Pingpp constructor.
     *
     * @param int        $payment_id
     * @param array|null $processor_data
     */
    public function __construct($payment_id, $processor_data)
    {
        $this->payment_id = $payment_id;

        if (is_null($processor_data)) {
            $processor_data = fn_get_processor_data($payment_id);
        }

        $this->processor_params = $processor_data['processor_params'];

        $this->current_location = rtrim(Registry::get('config.current_location'), '/');
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
     * @param array $order_info
     *
     * @return array Payment channel response
     */
    public function charge(array $order_info)
    {
        $this->order_info = $order_info;

        Sdk::setApiKey($this->getParameter('api_key'));

        $result = array(
            'order_status'   => 'F',
            'reason_text'    => '',
            'transaction_id' => null,
        );

        try {

            $charge = $this->createCharge();

            $result['order_status'] = OrderStatuses::INCOMPLETED;
            $result['transaction_id'] = $charge['id'];

            if ($this->channel instanceof IQrPayment) {
                $result['qr_code_url'] = $this->channel->getQrCodeUrl($charge);
                $result['instructions'] = $this->channel->getInstructions();
            } elseif ($this->channel instanceof IWxPayment) {
                $result['wx_pay_request'] = $this->channel->getPayRequest($charge);
                $result['instructions'] = $this->channel->getInstructions();
            } else {
                $result['payment_form'] = $this->buildPaymentForm($charge);
            }
        } catch (Exception $e) {
            $result['reason_text'] = $e->getMessage();
        }

        $this->updateOrderInfo($result);

        return $result;
    }

    /**
     * Gets payment method paramter.
     *
     * @param string $key_path Dot-separated parameter path
     * @param mixed  $default  Value to return when paramter is missing
     *
     * @return mixed
     */
    public function getParameter($key_path, $default = null)
    {
        $key_path = explode('.', $key_path);

        $root = $this->processor_params;

        foreach ($key_path as $key) {
            if (isset($root[$key])) {
                $root = $root[$key];
            } else {
                $root = $default;
                break;
            }
        }

        return $root;
    }

    /**
     * Performs API request to create transaction.
     *
     * @return array Transaction data
     */
    protected function createCharge()
    {
        $charge = Charge::create(array(
            'subject'   => $this->getChargeSubject(),
            'body'      => $this->getOrderBody(),
            'amount'    => $this->formatAmount($this->order_info['total'], CART_PRIMARY_CURRENCY),
            'order_no'  => $this->getOrderNumber(),
            'currency'  => strtolower(static::getCurrencyCode()),
            'extra'     => $this->getExtra(),
            'channel'   => $this->channel_name,
            'client_ip' => $this->getClientIp(),
            'app'       => array('id' => $this->processor_params['app_id']),
        ));

        return json_decode($charge, true);
    }

    /**
     * Provides transaction subject.
     *
     * @return string
     */
    protected function getChargeSubject()
    {
        return fn_format_long_string(
            __("pingpp.order", array("[order_id]" => $this->getOrderId())),
            self::$subject_length
        );
    }

    /**
     * Provides order identifier.
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->order_info['order_id'];
    }

    /**
     * Provides order description.
     *
     * @return string
     */
    protected function getOrderBody()
    {
        return fn_format_long_string(
            __("pingpp.purchase_in", array(
                '[company]' => fn_get_company_name($this->order_info['company_id'], 'pingpp.online_store')
            )),
            self::$body_length
        );
    }

    /**
     * Formats payment amount by currency.
     *
     * @param float  $total         Payment amount
     * @param string $currency_from Secondary currency code
     *
     * @return float
     */
    protected function formatAmount($total, $currency_from)
    {
        if ($currency_from == static::getCurrencyCode()) {
            return $total;
        }

        return fn_format_price_by_currency($total, $currency_from, static::getCurrencyCode());
    }

    /**
     * Provides supported currency.
     *
     * @return string Currency code
     */
    public static function getCurrencyCode()
    {
        return static::$curreny;
    }

    /**
     * Provides order number for transaction.
     *
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->getParameter('order_prefix') . (
            $this->order_info['repaid']
                ? $this->order_info['order_id'] . '_' . $this->order_info['repaid']
                : $this->order_info['order_id']
            );
    }

    /**
     * Gets channel extra data for charge request.
     *
     * @return array
     */
    protected function getExtra()
    {
        $this->channel->setNotificationUrl($this->getPaymentNotificationUrl());
        $this->channel->setFailUrl($this->getPaymentNotificationUrl('fail'));
        $this->channel->setCancelUrl($this->getPaymentNotificationUrl('cancel'));
        $this->channel->setOrderNumber($this->getOrderNumber());

        return $this->channel->getExtra();
    }

    /**
     * Provides URL for payment notification controller.
     *
     * @param string      $mode     Payment notification mode
     * @param int|null    $order_id Order identifier
     * @param string|null $channel  Payment channel name
     *
     * @return string
     */
    protected function getPaymentNotificationUrl($mode = 'notify', $order_id = null, $channel = null)
    {
        $order_id = $order_id ?: $this->getOrderId();
        $channel = $channel ?: $this->channel_name;

        return sprintf('%s/pingpp_%s/%d/%s/',
            $this->current_location,
            $mode,
            $order_id,
            $channel
        );
    }

    /**
     * Provides client IP for charge request.
     *
     * @return string
     */
    protected function getClientIp()
    {
        $ip = fn_get_ip();

        return $ip['host'];
    }

    /**
     * Provides payment form data for payment channels that require it.
     *
     * @param array $charge Charge data from API.
     *
     * @return array
     */
    protected function buildPaymentForm(array $charge)
    {
        return array(
            'url'    => $this->getPaymentFormUrl($charge),
            'data'   => $this->getPaymentFormData($charge),
            'method' => $this->getPaymentFormMethod($charge),
        );
    }

    /**
     * Provides payment form submit URL.
     *
     * @param array $charge Charge data from API.
     *
     * @return string
     */
    protected function getPaymentFormUrl(array $charge)
    {
        if (!$charge['livemode']) {
            return 'http://sissi.pingxx.com/mock.php';
        }

        /** @var \Tygh\Payments\Addons\Pingpp\Channels\IFormPayment $form_channel */
        $form_channel = $this->channel;

        return $form_channel->getFormUrl($charge);
    }

    /**
     * Provides payment form data.
     *
     * @param array $charge Charge data from API.
     *
     * @return array
     */
    protected function getPaymentFormData(array $charge)
    {
        if (!$charge['livemode']) {
            return array(
                'ch_id'    => $charge['id'],
                'order_no' => $charge['order_no'],
                'channel'  => $this->channel_name,
                'scheme'   => 'http',
                'extra'    => json_encode($charge['extra']),
            );
        }

        /** @var \Tygh\Payments\Addons\Pingpp\Channels\IFormPayment $form_channel */
        $form_channel = $this->channel;

        return $form_channel->getFormData($charge);
    }

    /**
     * Provides payment form submit method.
     *
     * @param array $charge Charge data from API.
     *
     * @return string 'get' or 'post'
     */
    private function getPaymentFormMethod(array $charge)
    {
        if (!$charge['livemode']) {
            return 'get';
        }

        /** @var \Tygh\Payments\Addons\Pingpp\Channels\IFormPayment $form_channel */
        $form_channel = $this->channel;

        return $form_channel->getFormMethod($charge);
    }

    /**
     * Updates order payment information in the database.
     *
     * @param array $result Payment processor response
     */
    private function updateOrderInfo(array $result)
    {
        fn_mark_payment_started($this->getOrderId());

        fn_update_order_payment_info($this->getOrderId(), array(
            'order_status'   => $result['order_status'],
            'reason_text'    => $result['reason_text'],
            'transaction_id' => $result['transaction_id'],
        ));
    }

    /**
     * Sets extra data to channel.
     *
     * @param string $key   Extra key
     * @param mixed  $value Extra value
     */
    public function setExtra($key, $value)
    {
        $this->channel->setExtra($key, $value);
    }

    /**
     * Obtains WeChat openID identifier from OAuth code.
     *
     * @param string $code OAuth code
     *
     * @return string OpenID identifier
     */
    public function getWxOpenId($code)
    {
        // Errors are suppressed to prevent undefined index notices when open_id is missing in response
        @$open_id = WxpubOAuth::getOpenid(
            $this->getParameter('wx_app_id'),
            $this->getParameter('wx_app_secret'),
            $code
        );

        return $open_id;
    }

    /**
     * Obtains WeChat OAuth URL to obtain openID identifier.
     *
     * @return string URL
     */
    public function getWxOauthUrl($order_id)
    {
        return WxpubOAuth::createOauthUrlForCode(
            $this->getParameter('wx_app_id'),
            $this->getPaymentNotificationUrl('continue', $order_id)
        );
    }

    /**
     * Provides used payment channel name.
     *
     * @return string
     */
    public function getChannelName()
    {
        return $this->channel_name;
    }

    /**
     * Sets payment channel.
     *
     * @param string $channel_name
     */
    public function setChannel($channel_name)
    {
        $this->channel_name = $channel_name;

        $channel_class_name = "\\Tygh\\Payments\\Addons\\Pingpp\\Channels\\" . fn_camelize($channel_name);

        $this->channel = new $channel_class_name($this->getParameter("channels.{$channel_name}.settings", array()));
    }
}