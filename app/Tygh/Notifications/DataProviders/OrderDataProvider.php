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

namespace Tygh\Notifications\DataProviders;


use Tygh\Enum\ProfileFieldLocations;
use Tygh\Enum\UserTypes;
use Tygh\Exceptions\DeveloperException;
use Tygh\Notifications\Data;
use Tygh\Registry;

class OrderDataProvider extends BaseDataProvider
{
    protected $order_statuses = [];

    protected $payment_methods = [];

    protected $shipments = null;

    protected $use_shipments = null;

    protected $tracking_numbers = null;

    protected $shipping_methods = null;

    protected $profile_fields = [];

    protected $order = [];

    protected $translated_orders = [];

    protected $currencies = [];

    protected $is_mve = false;

    public function __construct(array $data, array $currencies = null, $is_mve = null)
    {
        if (empty($data['order_info'])) {
            throw new DeveloperException('The order_info must be defined.');
        }

        if ($currencies === null) {
            $currencies = Registry::get('currencies');
        }

        if ($is_mve === null) {
            $is_mve = fn_allowed_for('MULTIVENDOR');
        }

        $this->order = $data['order_info'];
        $this->currencies = $currencies;
        $this->is_mve = $is_mve;

        parent::__construct($data);
    }

    public function get($receiver_type)
    {
        switch ($receiver_type) {
            case UserTypes::CUSTOMER:
                $data = $this->getForCustomer();
                break;
            case UserTypes::ADMIN:
                $data = $this->getForAdmin();
                break;
            case UserTypes::VENDOR:
                $data = $this->getForVendor();
                break;
            default:
                return parent::get($receiver_type);
        }

        return new Data(array_merge($this->data, $data));
    }

    protected function getForCustomer()
    {
        $lang_code = empty($this->order['lang_code'])
            ? CART_LANGUAGE
            : $this->order['lang_code'];

        return [
            'lang_code'                  => $lang_code,
            'shipments'                  => $this->getShipments(),
            'use_shipments'              => $this->getUseShipments(),
            'tracking_numbers'           => $this->getTrackingNumbers(),
            'shipping_methods'           => $this->getShippingMethods(),
            'order_status'               => $this->getOrderStatus($lang_code),
            'payment_method'             => $this->getPaymentMethod($lang_code),
            'status_settings'            => $this->getOrderStatusSettings($lang_code),
            'profile_fields'             => $this->getProfileFields($lang_code),
            'profields'                  => $this->getProfields($lang_code),
            'secondary_currency'         => $this->getSecondaryCurrency(),
            'take_surcharge_from_vendor' => $this->getTakeSurchargeFromVendor(),
            'template_code'              => $this->getTemplateCode()
        ];
    }

    protected function getForAdmin()
    {
        $lang_code = Registry::get('settings.Appearance.backend_default_language');

        return [
            'lang_code'          => $lang_code,
            'order_info'         => $this->translateOrderInfo($lang_code),
            'shipments'          => $this->getShipments(),
            'use_shipments'      => $this->getUseShipments(),
            'order_status'       => $this->getOrderStatus($lang_code),
            'payment_method'     => $this->getPaymentMethod($lang_code),
            'status_settings'    => $this->getOrderStatusSettings($lang_code),
            'profile_fields'     => $this->getProfileFields($lang_code),
            'secondary_currency' => $this->getSecondaryCurrency(),
            'template_code'      => $this->getTemplateCode()
        ];
    }

    protected function getForVendor()
    {
        $lang_code = fn_get_company_language($this->order['company_id']);

        return [
            'lang_code'          => $lang_code,
            'order_info'         => $this->translateOrderInfo($lang_code),
            'shipments'          => $this->getShipments(),
            'use_shipments'      => $this->getUseShipments(),
            'order_status'       => $this->getOrderStatus($lang_code),
            'payment_method'     => $this->getPaymentMethod($lang_code),
            'status_settings'    => $this->getOrderStatusSettings($lang_code),
            'profile_fields'     => $this->getProfileFields($lang_code),
            'secondary_currency' => $this->getSecondaryCurrency(),
            'template_code'      => $this->getTemplateCode()
        ];
    }

    protected function getOrderStatus($lang_code)
    {
        if (isset($this->order_statuses[$lang_code])) {
            return $this->order_statuses[$lang_code];
        }

        $order_statuses = fn_get_statuses(STATUSES_ORDER, [], true, false, $lang_code, $this->order['company_id']);

        return $this->order_statuses[$lang_code] = $order_statuses[$this->order['status']];
    }

    protected function getOrderStatusSettings($lang_code)
    {
        $order_status = $this->getOrderStatus($lang_code);

        return $order_status['params'];
    }

    protected function getPaymentMethod($lang_code)
    {
        if (isset($this->payment_methods[$lang_code])) {
            return $this->payment_methods[$lang_code];
        }

        $payment_id = !empty($this->order['payment_method']['payment_id'])
            ? $this->order['payment_method']['payment_id']
            : 0;

        $this->payment_methods[$lang_code] = [];

        if ($payment_id) {
            $this->payment_methods[$lang_code] = fn_get_payment_data($payment_id, $this->order['order_id'], $lang_code);
        }

        return $this->payment_methods[$lang_code];
    }

    protected function getShipments()
    {
        if ($this->shipments !== null) {
            return $this->shipments;
        }

        list($shipments) = fn_get_shipments_info(['order_id' => $this->order['order_id'], 'advanced_info' => true]);

        return $this->shipments = $shipments;
    }

    protected function getUseShipments()
    {
        if ($this->use_shipments !== null) {
            return $this->use_shipments;
        }
        $shipments = $this->getShipments();
        return $this->use_shipments = !fn_one_full_shipped($shipments);
    }

    protected function getTrackingNumbers()
    {
        if ($this->tracking_numbers !== null) {
            return $this->tracking_numbers;
        }

        $tracking_numbers = [];

        $shipments = $this->getShipments();
        $use_shipments = $this->getUseShipments();

        if (!empty($this->order['shipping'])) {
            foreach ($this->order['shipping'] as $shipping) {
                if (!isset($shipping['group_key'])) {
                    continue;
                }
                if (!$use_shipments && !empty($shipments[$shipping['group_key']]['tracking_number'])) {
                    $tracking_numbers[] = $shipments[$shipping['group_key']]['tracking_number'];
                }
            }
        }

        return $this->tracking_numbers = implode(', ', $tracking_numbers);
    }

    protected function getShippingMethods()
    {
        if ($this->shipping_methods !== null) {
            return $this->shipping_methods;
        }

        $shipping_methods = [];

        if (!empty($this->order['shipping'])) {
            foreach ($this->order['shipping'] as $shipping) {
                if (empty($shipping['shipping'])) {
                    continue;
                }
                $shipping_methods[] = $shipping['shipping'];
            }
        }

        return $this->shipping_methods = implode(', ', $shipping_methods);
    }

    protected function getProfileFields($lang_code)
    {
        if (isset($this->profile_fields[$lang_code])) {
            return $this->profile_fields[$lang_code];
        }


        return $this->profile_fields[$lang_code] = fn_get_profile_fields(ProfileFieldLocations::EXTRA_FIELDS, [], $lang_code);
    }

    protected function getProfields($lang_code)
    {
        $profile_fields = $this->getProfileFields($lang_code);

        $profields = [];
        foreach ($profile_fields as $section => $fields) {
            $profields[$section] = fn_fields_from_multi_level($fields, 'field_name', 'field_id');
        }

        return $profields;
    }

    protected function getSecondaryCurrency()
    {
        $secondary_currency = '';

        if (!empty($this->order['secondary_currency']) && isset($this->currencies[$this->order['secondary_currency']])) {
            $secondary_currency = $this->order['secondary_currency'];
        }

        return $secondary_currency;
    }

    protected function getTakeSurchargeFromVendor()
    {
        $take_surcharge_from_vendor = $this->is_mve
            ? fn_take_payment_surcharge_from_vendor($this->order['products'])
            : false;

        return $take_surcharge_from_vendor;
    }

    protected function getTemplateCode()
    {
        $template_code = 'order_notification.' . strtolower($this->order['status']);

        return $template_code;
    }

    protected function translateOrderInfo($lang_code)
    {
        if (isset($this->translated_orders[$lang_code])) {
            return $this->translated_orders[$lang_code];
        }

        $this->translated_orders[$lang_code] = $this->order;

        fn_add_user_data_descriptions($this->translated_orders[$lang_code], $lang_code);
        fn_translate_products($this->translated_orders[$lang_code]['products'], '', $lang_code, true);

        return $this->translated_orders[$lang_code];
    }
}
