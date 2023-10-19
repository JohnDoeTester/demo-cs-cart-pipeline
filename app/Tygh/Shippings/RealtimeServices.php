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

namespace Tygh\Shippings;

use Tygh\Tygh;
use Tygh\Http;
use Tygh\Registry;

class RealtimeServices
{
    const SERVICE_NOT_CONFIGURED = 'shippings.service_not_configured';
    const SERVICE_NOT_FOUND = 'shippings.service_not_found';
    const CURRENCY_NOT_FOUND = 'shippings.currency_not_found';
    const SERVICE_NOT_ERROR = '';

    /**
     * Stack for registered shipping services
     *
     * @var array $_services_stack
     */
    private static $_services_stack = array();

    private static $request_data = array();

    private static $module = array();

    /**
     * Result rates
     *
     * @var array $_rates
     */
    private static $_rates = array();

    private static function _processErrorCode($code, $placeholders = array())
    {
        return __($code, $placeholders);
    }

    /**
     * Check if multireading is available on the server
     *
     * @return bool True if available, false otherwise
     */
    private static function _checkMultithreading()
    {
        if (function_exists('curl_multi_init') && Http::getCurlInfo() == '') {
            $allow_multithreading = true;
            curl_multi_init();
        } else {
            $allow_multithreading = false;
        }

        return $allow_multithreading;
    }

    /**
     * Adds shipping service data to stack for future calculations
     *
     * @param  int   $shipping_key  Shipping service array position
     * @param  array $shipping_info Shipping service data
     * @return bool  true if information was added to stack, false otherwise
     */
    public static function register($shipping_key, $shipping_info)
    {
        if (empty($shipping_info['service_params'])) {
            return self::_processErrorCode(self::SERVICE_NOT_CONFIGURED);
        }

        $module = fn_camelize($shipping_info['module']);
        $module = 'Tygh\\Shippings\\Services\\' . $module;

        if (class_exists($module)) {
            /** @var \Tygh\Shippings\IService $service */
            $service = new $module;

            if (isset($service->calculation_currency)) {
                $currencies = Registry::get('currencies');

                if (isset($currencies[$service->calculation_currency])) {
                    $shipping_info['package_info']['C'] = fn_format_price_by_currency($shipping_info['package_info']['C'], CART_PRIMARY_CURRENCY, $service->calculation_currency);
                } else {
                    return self::_processErrorCode(self::CURRENCY_NOT_FOUND, array('[currency]' => $service->calculation_currency));
                }
            }

            $service->prepareData($shipping_info);

            self::$_services_stack[$shipping_key] = $service;
            self::$module[$shipping_key] = $shipping_info['module'];
        } else {
            return self::_processErrorCode(self::SERVICE_NOT_FOUND);
        }

        return self::_processErrorCode(self::SERVICE_NOT_ERROR);
    }

    /**
     * Sends requests to real-time services and process responses.
     *
     * @return array Shipping method rates list
     */
    public static function getRates()
    {
        $_services = [
            'multi'  => [],
            'simple' => [],
        ];

        if (empty(self::$_services_stack)) {
            return [];
        }

        if (self::_checkMultithreading()) {
            foreach (self::$_services_stack as $shipping_key => $service_object) {
                $key = 'simple';
                if ($service_object->allowMultithreading()) {
                    $key = 'multi';
                }

                $_services[$key][$shipping_key] = $service_object;
            }

        } else {
            $_services['simple'] = self::$_services_stack;
        }

        if (!empty($_services['multi'])) {
            foreach ($_services['multi'] as $shipping_key => $service_object) {
                self::$request_data[$shipping_key] = $data = $service_object->getRequestData();

                $extra = [
                    'callback'          => ['\Tygh\Shippings\RealtimeServices::multithreadingCallback', $shipping_key],
                    'headers'           => !empty($data['headers']) ? $data['headers'] : [],
                    'timeout'           => !empty($data['timeout']) ? $data['timeout'] : null,
                    'execution_timeout' => !empty($data['execution_timeout']) ? $data['execution_timeout'] : null,
                ];
                if ($data['method'] == 'post') {
                    Http::mpost($data['url'], $data['data'], $extra);
                } else {
                    Http::mget($data['url'], $data['data'], $extra);
                }
            }

            Http::processMultiRequest();
        }

        if (!empty($_services['simple'])) {
            foreach ($_services['simple'] as $shipping_key => $service_object) {
                self::$request_data[$shipping_key] = $service_object->getRequestData();
                $response = $service_object->getSimpleRates();
                self::multithreadingCallback($response, $shipping_key);
            }
        }

        return self::$_rates;
    }

    public static function multithreadingCallback($result, $shipping_key)
    {
        $service = self::$_services_stack[$shipping_key];
        $rate = $service->processResponse($result);

        if (isset($service->calculation_currency) && $rate['cost'] !== false) {
            $rate['cost'] = fn_format_price_by_currency($rate['cost'], $service->calculation_currency, CART_PRIMARY_CURRENCY);
        }

        if (!isset($rate) || (isset($rate['cost']) && $rate['cost'] === false)) {
            self::sendShippingErrorMessage($shipping_key, $result);
        }

        $rate['pickup_info'] = [];
        if ($service instanceof IPickupService) {
            /** @var \Tygh\Shippings\IPickupService $service Shipping service */
            $rate['pickup_info'] = [
                'min_cost'                => $service->getPickupMinCost(),
                'pickup_points'           => $service->getPickupPoints(),
                'number_of_pickup_points' => $service->getPickupPointsQuantity(),
            ];
        }

        /**
         * This hook allows you to modify the processed data received from a realtime shipping service.
         *
         * @param array    $result       The result returned by the shipping service
         * @param integer  $shipping_key Shipping service array position
         * @param IService $service      The object of the shipping method, the rates of which have just been calculated
         * @param array    $rate         The result of the shipping rate calculation
         */
        fn_set_hook('realtime_services_process_response_post', $result, $shipping_key, $service, $rate);

        self::$_rates[] = [
            'price'          => $rate['cost'],
            'error'          => $rate['error'],
            'shipping_key'   => $shipping_key,
            'delivery_time'  => isset($rate['delivery_time']) ? $rate['delivery_time'] : false,
            'destination_id' => isset($rate['destination_id']) ? $rate['destination_id'] : false,
            'pickup_info'    => $rate['pickup_info'],
        ];
    }

    /**
     * Clears shipping services stack
     */
    public static function clearStack()
    {
        self::$_services_stack = array();
        self::$_rates = array();
    }

    public function __construct()
    {

    }

    /**
     * Sends a message to administrator about a shipping method error if the error occurred more than 3 time.
     *
     * @param integer $shipping_key Shipping service array position
     * @param mixed   $result       The result returned by the shipping service
     *
     * @return void
     */
    private static function sendShippingErrorMessage($shipping_key, $result)
    {
        $data = self::$request_data[$shipping_key];
        $shipping_info = Shippings::getCarrierInfo(self::$module[$shipping_key]);

        if (!is_array(($data))) {
            $data = (array) json_decode(json_encode($data), true);
        }

        if (!empty($data['url']) && isset($data['data'])) {

            // result may be of any type and must be converted to a string to be stored in the database
            if (is_resource($result)) {
                $logged_response = '';
            } elseif (is_string($result)) {
                $logged_response = $result;
            } else {
                $logged_response = json_encode($result, JSON_UNESCAPED_UNICODE);
            }

            fn_log_event(
                'requests',
                'shipping',
                [
                    'url'      => $data['url'],
                    'response' => $logged_response,
                    'data'     => var_export($data['data'], true),
                    'shipping' => self::$module[$shipping_key],
                ]
            );
        }

        $params = [
            'period'    => 'C',
            'time_from' => time() - SECONDS_IN_HOUR,
            'time_to'   => time(),
            'q_type'    => 'requests',
            'q_action'  => 'shipping'
        ];

        list($logs_data, $params) = fn_get_logs($params);

        $total_items = 0;
        foreach ($logs_data as $log) {
            if (isset($log['content']['shipping']) && $log['content']['shipping'] == self::$module[$shipping_key]) {
                $total_items++;
            }
        }

        if ($total_items >= Registry::ifGet('config.tweaks.request_errors_threshold', 30)) {
            $period_threshold = Registry::ifGet('config.tweaks.request_errors_threshold_period', SECONDS_IN_HOUR);
            $last_sent_timestamp = (int) fn_get_storage_data('send_shipping_error_message_timestamp_' . self::$module[$shipping_key], 0);
            if (($last_sent_timestamp + $period_threshold) < time()) {
                $log_message = __('request_error_information', ['[shipping]' => $shipping_info['name']]);
                Tygh::$app['event.dispatcher']->dispatch('system.realtime_shipping_error.a', ['log_message' => $log_message]);
                fn_set_storage_data('send_shipping_error_message_timestamp_' . self::$module[$shipping_key], (string) time());
            }
        }
    }
}
