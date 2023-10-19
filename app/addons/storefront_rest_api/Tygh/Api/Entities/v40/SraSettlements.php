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

namespace Tygh\Api\Entities\v40;

use Tygh\Addons\StorefrontRestApi\ASraEntity;
use Tygh\Addons\StorefrontRestApi\Payments\IConfigurablePayment;
use Tygh\Api\Response;
use Tygh\Common\OperationResult;
use Tygh\Enum\Addons\StorefrontRestApi\PaymentTypes;
use Tygh\Tygh;

class SraSettlements extends ASraEntity
{
    /** @var array $payment_processors */
    private $payment_processors;

    /** @inheritdoc */
    public function __construct(array $auth = [], $area = '')
    {
        parent::__construct($auth, $area);

        $this->initPaymentProcessors();
    }

    /** @inheritdoc */
    public function index($id = '', $params = [])
    {
        $valid_params = true;

        $status = Response::STATUS_BAD_REQUEST;
        $data = [];

        if (!$id) {
            $data['message'] = __('api_need_id');
            $valid_params = false;
        }

        $payment_method = [];
        if ($valid_params) {
            $payment_method = fn_get_payment_method_data($id);
            if (!$this->isPaymentMethodApiReady($payment_method)) {
                $valid_params = false;
            }
        }

        $configuration_provider = null;
        if ($valid_params) {
            $configuration_provider = $this->getConfigurationProvider($payment_method);
            if (!$configuration_provider) {
                $valid_params = false;
            }
        }

        if ($valid_params) {
            $status = Response::STATUS_OK;
            $data = [
                'processor_params' => $configuration_provider ? $configuration_provider->getProcessorParameters($payment_method) : [],
            ];
        }

        return [
            'status' => $status,
            'data'   => $data,
        ];
    }

    /** @inheritdoc */
    public function create($params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = [];
        $valid_params = true;
        $order_info = [];

        $params = array_merge([
            'order_id'  => null,
            'repay'     => false,
        ], $params);

        if (!$params['order_id']) {
            $data['message'] = __('api_required_field', [
                '[field]' => 'order_id',
            ]);
            $valid_params = false;
        }

        if ($valid_params) {
            $order_info = fn_get_order_info($params['order_id']);
        }

        if (!$order_info) {
            return [
                'status' => Response::STATUS_NOT_FOUND,
                'data'   => [
                    'message' => __('object_not_found', [
                        '[object]' => __('order')
                    ])
                ],
            ];
        }

        if ($valid_params && !$this->checkOrderOwner($order_info)) {
            $status = Response::STATUS_FORBIDDEN;
            $valid_params = false;
        }

        $payment_method = [];
        if ($valid_params) {
            $payment_method = $this->getPaymentMethod($order_info);
            if (!$this->isPaymentMethodApiReady($payment_method)) {
                $valid_params = false;
            }
        }

        if ($valid_params) {
            $order_info = $this->setOrderRepay($order_info, $params['repay']);

            $payment_result = $this->performPayment($order_info, $this->auth, $params, $payment_method);

            if ($payment_result->isSuccess()) {
                $status = Response::STATUS_CREATED;
                $data['data'] = $payment_result->getData();
            } else {
                $data['errors'] = $payment_result->getErrors();
            }

            $data['messages'] = $payment_result->getMessages();
        }

        return [
            'status' => $status,
            'data'   => $data,
        ];
    }

    /** @inheritdoc */
    public function update($id, $params)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
            'data'   => [],
        ];
    }

    /** @inheritdoc */
    public function delete($id)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
            'data'   => [],
        ];
    }

    /** @inheritdoc */
    public function privileges()
    {
        return [
            'index'  => true,
            'create' => true,
        ];
    }

    /** @inheritdoc */
    public function privilegesCustomer()
    {
        return [
            'index'  => $this->auth['is_token_auth'],
            'create' => $this->auth['is_token_auth'],
        ];
    }

    /**
     * Checks if the authenticated users is the one who placed the order.
     *
     * @param array $payment_method Payment method data
     *
     * @return null|\Tygh\Addons\StorefrontRestApi\Payments\IConfigurablePayment
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    protected function getConfigurationProvider(array $payment_method)
    {
        $configuration_provider = null;
        $processor_script = $this->getPaymentProcessor($payment_method['payment_id']);
        if (isset($processor_script)) {
            $payment = $this->payment_processors[$processor_script];
            $configuration_provider = new $payment['class']();
        }

        if (!$configuration_provider instanceof IConfigurablePayment) {
            return null;
        }

        return $configuration_provider;
    }

    /**
     * Checks if the authenticated users is the one who placed the order.
     *
     * @param array $order_info Order info from ::fn_get_order_info
     *
     * @return bool
     */
    protected function checkOrderOwner(array $order_info)
    {
        return isset($order_info['user_id']) && $order_info['user_id'] == $this->auth['user_id'];
    }

    /**
     * Gets payment information from order information.
     *
     * @param array $order_info Order info from ::fn_get_order_info
     *
     * @return array
     */
    protected function getPaymentMethod(array $order_info)
    {
        return empty($order_info['payment_method']) ? [] : $order_info['payment_method'];
    }

    /**
     * Checks if the payment method of the order can be used within Storefront REST API.
     * TODO: Add offline payment methods (CC, phone ordering) support.
     *
     * @param array $payment_method Payment information extracted from an order
     *
     * @return bool
     */
    protected function isPaymentMethodApiReady($payment_method)
    {
        $result = false;

        if (!empty($payment_method['payment_id'])) {
            if ($processor_script = $this->getPaymentProcessor($payment_method['payment_id'])) {
                $result = $this->isProcessorApiReady($processor_script);
            }
        }

        return $result;
    }

    /**
     * Checks if processor used by a payment method can be used to perform payment via Storefront REST API.
     *
     * @param string $processor_script Processor script name with extension
     *
     * @return bool
     */
    protected function isProcessorApiReady($processor_script)
    {
        return !empty($this->payment_processors[$processor_script]);
    }

    /**
     * Performs payment for an order using associated payment method.
     *
     * @param array $order_info     Order info from ::fn_get_order_info
     * @param array $auth           API authentication details
     * @param array $request        Request parameters passed in an API call
     * @param array $payment_method Payment method information extracted from $order_info
     *
     * @return \Tygh\Common\OperationResult Contains payment details, payment errors, additional messages
     */
    protected function performPayment(array $order_info, array $auth, array $request, array $payment_method)
    {
        $payment_result = new OperationResult();
        $payment_result->setSuccess(false);
        $payment_result->setErrors([__('text_transaction_cancelled')]);

        if (!empty($payment_method['payment_id'])) {
            if ($processor_script = $this->getPaymentProcessor($payment_method['payment_id'])) {

                $this->startPayment($order_info['order_id']);

                $payment = $this->payment_processors[$processor_script];

                if ($payment['type'] == PaymentTypes::REDIRECTION) {
                    /** @var \Tygh\Addons\StorefrontRestApi\Payments\IRedirectionPayment $details_provider */
                    $details_provider = new $payment['class'];
                    $details_provider
                        ->setOrderInfo($order_info)
                        ->setAuthInfo($auth)
                        ->setPaymentInfo($payment_method);
                    $payment_result = $details_provider->getDetails($request);
                } elseif ($payment['type'] == PaymentTypes::DIRECT) {
                    /** @var \Tygh\Addons\StorefrontRestApi\Payments\IDirectPayment $payment_processor */
                    $payment_processor = new $payment['class'];
                    $payment_processor
                        ->setOrderInfo($order_info)
                        ->setAuthInfo($auth)
                        ->setPaymentInfo($payment_method);
                    $payment_result = $payment_processor->pay($request);
                }
            }
        }

        return $payment_result;
    }

    /**
     * Gets processor script used by a payment.
     *
     * @param int $payment_id Payment method ID
     *
     * @return string|null Payment method processor or null if none
     */
    protected function getPaymentProcessor($payment_id)
    {
        $processor_data = fn_get_processor_data($payment_id);

        return empty($processor_data['processor_script']) ? null : $processor_data['processor_script'];
    }

    /**
     * Gets all payment processors that can be used to perform payment via Storefront REST API.
     *
     * @param array|null $payment_processors API-ready payment processors definition.
     *                                       When null, content on the `storefront_rest_api/payment_processors` schema
     *                                       will be used.
     *
     * @return array API-ready payment processors schema
     */
    protected function initPaymentProcessors($payment_processors = null)
    {
        if ($this->payment_processors === null) {
            $this->payment_processors = $payment_processors ?: fn_get_schema('storefront_rest_api',
                'payment_processors');
        }

        return $this->payment_processors;
    }

    /**
     * Stores information indicating that a payment was started in the database.
     *
     * @param int $order_id Order ID
     *
     * @return array Stored payment data
     */
    protected function startPayment($order_id)
    {
        return fn_mark_payment_started($order_id);
    }

    /**
     * Increases order repay count in the database if necessary.
     *
     * @param array $order_info Order into to repay from ::fn_get_order_info
     * @param bool  $is_repaid  Wheter order should be repaid
     *
     * @return array Order info with repay count increased
     */
    protected function setOrderRepay(array $order_info, $is_repaid = false)
    {
        if (!$is_repaid) {
            return $order_info;
        }

        $order_info['repaid']++;

        Tygh::$app['db']->query(
            'UPDATE ?:orders SET repaid = ?i WHERE order_id = ?i',
            $order_info['repaid'],
            $order_info['order_id']
        );

        return $order_info;
    }
}
