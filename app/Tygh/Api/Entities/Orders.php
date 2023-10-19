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

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Tygh;

class Orders extends AEntity
{
    /** @var string[] Request parameters that will not trigger full order recalculation when updating an order */
    protected $status_update_parameters = [
        'status',
        'notify_user',
        'notify_department',
        'notify_vendor',
    ];


    public function index($id = 0, $params = array())
    {
        if (!empty($id)) {
            $status = Response::STATUS_NOT_FOUND;

            $data = $this->getOrderData($id);

            if ($data) {
                // check if order is owned by authenticated user
                if (!empty($this->auth['is_token_auth']) && $data['user_id'] != $this->auth['user_id']) {
                    $data = array();
                } else {
                    $status = Response::STATUS_OK;
                }
            }
        } else {
            $items_per_page = $this->safeGet($params, 'items_per_page', Registry::get('settings.Appearance.admin_elements_per_page'));

            // when interacting with an authorized customer, use his/her user ID instead of the passed one
            if (!empty($this->auth['is_token_auth'])) {
                $params['user_id'] = $this->auth['user_id'];
            }

            list($data, $params) =  fn_get_orders($params, $items_per_page);

            if ($this->safeGet($params, 'get_orders_data', false)) {
                foreach ($data as &$order_data) {
                    $order_data = $this->getOrderData($order_data['order_id']);
                }
            }

            $data = array(
                'orders' => $data,
                'params' => $params,
            );
            $status = Response::STATUS_OK;
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function create($params)
    {
        $shipping_ids = $data = array();
        $valid_params = true;
        $status = Response::STATUS_BAD_REQUEST;

        if (isset($params['shipping_ids'])) {
            $shipping_ids = (array) $params['shipping_ids'];
            unset($params['shipping_ids']);
        } elseif (isset($params['shipping_id'])) {
            $shipping_ids = (array) $params['shipping_id'];
        }

        if ($coupon_codes = $this->safeGet($params, 'coupon_codes', array())) {
            $coupon_codes = array_map(function($code) {
                return fn_strtolower(trim($code));
            }, array_unique((array)$coupon_codes));
        }

        fn_clear_cart($cart, true);

        // when interacting with an authorized customer, use his/her user ID instead of the passed one
        if (!empty($this->auth['is_token_auth'])) {
            $params['user_id'] = $this->auth['user_id'];
            unset($params['user_data']);
        } elseif (empty($this->auth['user_id'])) {
            $status = Response::STATUS_UNAUTHORIZED;
            $valid_params = false;
        }

        if (!empty($params['user_id']) && is_numeric($params['user_id'])) {
            $cart['user_data'] = fn_get_user_info($params['user_id']);
            if (empty($cart['user_data'])) {
                $status = Response::STATUS_BAD_REQUEST;
                $data['message'] = __('object_not_found', array('[object]' => __('user')));
                $valid_params = false;
            }
        } elseif (!empty($params['user_data']) && is_array($params['user_data'])) {
            $cart['user_data'] = $params['user_data'];
        } else {
            $data['message'] = __('api_required_field', array(
                '[field]' => 'user_id/user_data'
            ));
            $valid_params = false;
        }

        // merging request data with auth data is not safe when processing customer's requests
        if ($valid_params && empty($this->auth['is_token_auth'])) {
            $cart['user_data'] = array_merge($cart['user_data'], $params);
        }

        /**
         * Executes after get user data for create order through API request,
         * allows you to modify cart user data.
         *
         * @param array $params       Request parameters
         * @param int   $status       API response status
         * @param array $data         API response data
         * @param bool  $valid_params Is valid params flag
         * @param array $auth         API authentication details
         * @param array $cart         Cart data
         */
        fn_set_hook('api_orders_create_after_get_user_data', $params, $status, $data, $valid_params, $this->auth, $cart);

        if ($valid_params && empty($params['payment_id'])) {
            $data['message'] = __('api_required_field', array(
                '[field]' => 'payment_id'
            ));
            $valid_params = false;
        }

        if ($valid_params && empty($params['products'])) {
            $data['message'] = __('api_required_field', array(
                '[field]' => 'products'
            ));
            $valid_params = false;
        }

        if ($valid_params
            && isset($cart['user_data']['company_id'])
            && !($this->checkUserCompanyRelation($cart['user_data']['company_id']) || $this->areUsersShared())
        ) {
            $status = Response::STATUS_FORBIDDEN;
            $data['message'] = __('api_wrong_user_company_relation');
            $valid_params = false;
        }

        if ($valid_params && !empty($params['products'])) {
            foreach ($params['products'] as $product) {
                if (!fn_is_allowed_options($product, true)) {
                    $status = Response::STATUS_FORBIDDEN;
                    $data['message'] = __('product_disabled_options', ['[product]' => fn_get_product_name($product['product_id'])]);
                    $valid_params = false;
                    break;
                }
            }
        }

        if ($valid_params) {

            $cart['payment_id'] = $params['payment_id'];
            $cart['storefront_id'] = StorefrontProvider::getStorefront()->storefront_id;

            $customer_auth = fn_fill_auth($cart['user_data'], [], false, 'C');

            fn_add_product_to_cart($params['products'], $cart, $customer_auth);

            // group products - disable all calculations for speed
            fn_calculate_cart_content($cart, $customer_auth, 'S', false, 'S', false);

            /**
             * Executes after add product to cart for create order through API request,
             * allows you to modify cart data.
             *
             * @param array $params       Request parameters
             * @param int   $status       API response status
             * @param array $data         API response data
             * @param bool  $valid_params Is valid params flag
             * @param array $auth         API authentication details
             * @param array $cart         Cart data
             */
            fn_set_hook('api_orders_create_after_add_product_to_cart', $params, $status, $data, $valid_params, $this->auth, $cart);

            if ($cart['shipping_required'] && empty($shipping_ids)) {
                $data['message'] = __('api_required_field', [
                    '[field]' => 'shipping_id'
                ]);
                $valid_params = false;
            }

            if (!empty($cart['product_groups']) && !empty($shipping_ids)) {
                if (count($shipping_ids) === 1) { //back capability
                    $company_ids = array_column($cart['product_groups'], 'company_id');
                    $shipping_ids = array_fill_keys($company_ids, reset($shipping_ids));
                }

                foreach ($cart['product_groups'] as $key => $group) {
                    if (!isset($group['group_key'])) {
                        $cart['product_groups'][$key]['group_key'] = $group['company_id'];
                        $group['group_key'] = $group['company_id'];
                    }

                    if (isset($shipping_ids[$group['group_key']])) {
                        $cart['chosen_shipping'][$key] = $shipping_ids[$group['group_key']];
                    }
                }
            }

            if ($coupon_codes) {
                $do_recalc = false;
                foreach ($coupon_codes as $code) {
                    if ($do_recalc) {
                        fn_calculate_cart_content($cart, $customer_auth, 'S', false, 'S', true);
                    }
                    $cart['pending_coupon'] = $code;
                    $do_recalc = true;
                }
            }

            $cart['calculate_shipping'] = true;
            fn_calculate_cart_content($cart, $customer_auth);

            if (empty($cart['shipping_failed']) || empty($shipping_ids)) {
                fn_update_payment_surcharge($cart, $customer_auth);
                // FIXME: Backward compatibility
                $cart = $this->mergeOrderData($cart, $params);
            } else {
                $status = Response::STATUS_FORBIDDEN;
                $data['message'] = __('api_no_shipping_methods_available');
                $valid_params = false;
            }
        }

        if ($valid_params &&
            !fn_allow_place_order($cart, $this->auth)
        ) {
            $status = Response::STATUS_FORBIDDEN;
            $valid_params = false;

            if (!empty($cart['amount_failed'])) {
                /** @var \Tygh\Tools\Formatter $formatter */
                $formatter = Tygh::$app['formatter'];
                $min_amount = $formatter->asPrice(
                    $cart['min_order_amount'],
                    $this->safeGet($params, 'currency', CART_PRIMARY_CURRENCY)
                );

                $data['message'] = __('checkout.min_cart_subtotal_required', [
                    '[amount]' => $min_amount,
                ]);
            }
        }

        if ($valid_params) {
            $order_placement_action = $this->safeGet($params, 'action', 'save');

            /**
             * Executes before place order for create order through API request,
             * allows you to modify cart data.
             *
             * @param array $params                 Request parameters
             * @param int   $status                 API response status
             * @param array $data                   API response data
             * @param bool  $valid_params           Is valid params flag
             * @param array $cart                   Cart data
             * @param array $customer_auth          Array of user authentication data (e.g. uid, usergroup_ids, etc.)
             * @param array $order_placement_action Current action. Can be empty or "save"
             */
            fn_set_hook('api_orders_create_before_place_order', $params, $status, $data, $valid_params, $cart, $customer_auth, $order_placement_action);

            list($order_id, ) = fn_place_order(
                $cart,
                $customer_auth,
                $order_placement_action,
                empty($this->auth['is_token_auth']) ? $this->auth['user_id'] : null
            );

            if (!empty($order_id)) {
                $status = Response::STATUS_CREATED;
                $data = [
                    'order_id'   => $order_id,
                    'order_data' => $this->getOrderData($order_id)
                ];
                if (fn_allowed_for('MULTIVENDOR')) {
                    $data['suborder_ids'] = array_map('intval', db_get_fields(
                        'SELECT order_id'
                        . ' FROM ?:orders'
                        . ' WHERE parent_order_id = ?i',
                        $order_id
                    ));

                    $data['suborders_data'] = [];
                    foreach ($data['suborder_ids'] as $suborder_id) {
                        $data['suborders_data'][] = $this->getOrderData($suborder_id);
                    }
                }
            } else {
                $data['message'] = __('api_order_couldnt_be_created');
            }
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function update($id, $params)
    {
        fn_define('ORDER_MANAGEMENT', true);
        $shipping_ids = $data = array();
        $valid_params = true;
        $status = Response::STATUS_BAD_REQUEST;

        if (isset($params['shipping_ids'])) {
            $shipping_ids = (array) $params['shipping_ids'];
        } elseif (isset($params['shipping_id'])) {
            $shipping_ids = (array) $params['shipping_id'];
        }

        if ($valid_params) {

            // FIXME: Dirty hack to prevent full order recalculation when simply updating its status
            if ($this->isStatusUpdateRequest($params)
                && fn_check_permissions('orders', 'update_status', 'admin')
            ) {
                fn_change_order_status($id, $params['status'], '', fn_get_notification_rules($params, false));

                return [
                    'status' => Response::STATUS_OK,
                    'data'   => [
                        'order_id' => $id,
                    ],
                ];
            }

            fn_clear_cart($cart, true);
            $customer_auth = fn_fill_auth(array(), array(), false, 'C');
            $cart_status = md5(serialize($cart));

            // Order info was not found or customer does not have enought permissions
            if (fn_form_cart($id, $cart, $customer_auth) && $cart_status != md5(serialize($cart))) {
                fn_store_shipping_rates($id, $cart, $customer_auth);

                unset($params['product_groups'], $params['chosen_shipping'], $params['order_id']);
                $cart['calculate_shipping'] = true;

                if (empty($shipping_ids)) {
                    $shipping_ids = $cart['chosen_shipping'];
                }

                $cart['order_id'] = $id;

                if (!empty($params['products'])) {
                    $product = reset($params['products']);
                    if (isset($product['product_id'], $product['price'])) {
                        $cart['products'] = $params['products'];
                    } else {
                        $cart['products'] = array();
                        fn_add_product_to_cart($params['products'], $cart, $customer_auth);
                    }

                    unset($params['products']);
                }

                fn_calculate_cart_content($cart, $customer_auth);

                if (!empty($params['user_id'])) {
                    $cart['user_data'] = fn_get_user_info($params['user_id']);
                } elseif (!empty($params['user_data'])) {
                    $cart['user_data'] = $params['user_data'];
                }

                $cart['user_data'] = array_merge($cart['user_data'], $params);

                if (!empty($cart['product_groups']) && !empty($shipping_ids)) {
                    if (count($shipping_ids) == 1) { //back capability
                        $shipping_ids = array_fill_keys(array_keys($cart['product_groups']), reset($shipping_ids));
                    }

                    foreach ($cart['product_groups'] as $key => $group) {
                        foreach ($group['shippings'] as $shipping_id => $shipping) {
                            if (isset($shipping_ids[$key]) && $shipping_id == $shipping_ids[$key]) {
                                $cart['chosen_shipping'][$key] = $shipping_id;
                                break;
                            }
                        }
                    }
                }

                if (!empty($params['payment_id'])) {
                    if (!empty($params['payment_info'])) {
                        $cart['payment_info'] = $params['payment_info'];
                    } elseif ($params['payment_id'] != $cart['payment_id']) {
                        $cart['payment_info'] = array();
                    }

                    $cart['payment_id'] = $params['payment_id'];
                    unset($params['payment_id'], $params['payment_info']);
                }

                fn_calculate_cart_content($cart, $customer_auth);

                if (!empty($cart) && empty($cart['shipping_failed'])) {

                    fn_update_payment_surcharge($cart, $customer_auth);

                    $cart = $this->mergeOrderData($cart, $params); // backward compatibility

                    list($order_id, $order_status) = fn_update_order($cart, $id);

                    if ($order_id) {
                        if (!empty($params['status']) && fn_check_permissions('orders', 'update_status', 'admin')) {
                            fn_change_order_status($order_id, $params['status'], '', fn_get_notification_rules($params, false));
                        } elseif (!empty($order_status)) {
                            fn_change_order_status($order_id, $order_status, '', fn_get_notification_rules($params, false));
                        }

                        $status = Response::STATUS_OK;
                        $data = array(
                            'order_id' => $order_id
                        );
                    }
                }
            }
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function delete($id)
    {
        $data = array();
        $status = Response::STATUS_NOT_FOUND;

        if (fn_delete_order($id)) {
            $status = Response::STATUS_NO_CONTENT;
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function privileges()
    {
        return array(
            'create' => 'create_order',
            'update' => 'edit_order',
            'delete' => 'delete_orders',
            'index'  => 'view_orders'
        );
    }

    public function privilegesCustomer()
    {
        return array(
            'create' => $this->auth['is_token_auth'],
            'update' => false,
            'delete' => false,
            'index'  => $this->auth['is_token_auth']
        );
    }

    /**
     * Merges the order data with request params.
     * Needed for backward compatibility.
     *
     * @param array $cart   Cart data.
     * @param array $params Params.
     *
     * @return array
     */
    private function mergeOrderData($cart, $params)
    {
        //Unsafe fields, were processed separately.
        $unsafe_fields = array('products', 'user_data', 'product_groups', 'chosen_shipping');

        $params = array_diff_key($params, array_flip($unsafe_fields));
        $cart = array_merge($cart, $params);

        return $cart;
    }

    /**
     * Provides company identifier of a storefront.
     *
     * @return int Company ID
     */
    protected function getCompanyId()
    {
        if (!empty($this->parent['company_id'])) {
            $company_id = $this->parent['company_id'];
        } elseif (Registry::get('runtime.simple_ultimate')) {
            $company_id = Registry::get('runtime.forced_company_id');
        } else {
            $company_id = Registry::get('runtime.company_id');
        }

        return $company_id;
    }

    /**
     * Checks whether users are shared between storefronts.
     *
     * @return bool
     */
    protected function areUsersShared()
    {
        return fn_allowed_for('ULTIMATE') && Registry::get('settings.Stores.share_users') == 'Y';
    }

    /**
     * Checks whether user belongs to a company.
     *
     * @param int $company_id Company identifier of a user
     *
     * @return bool
     */
    protected function checkUserCompanyRelation($company_id)
    {
        return fn_allowed_for('MULTIVENDOR') || !$this->getCompanyId() || $company_id == $this->getCompanyId();
    }

    /**
     * Checks if an API request will lead to order status update only.
     *
     * @param array $params
     *
     * @return bool
     */
    protected function isStatusUpdateRequest(array $params)
    {
        $is_status_param_present = isset($params['status']);
        $are_update_params_present = false;

        foreach ($params as $param_name => $value) {
            if (!in_array($param_name, $this->status_update_parameters)) {
                $are_update_params_present = true;
                break;
            }
        }

        return $is_status_param_present
            && !$are_update_params_present;
    }

    /**
     * Gets order data
     *
     * @param int $order_id Order identifier
     *
     * @return array
     */
    protected function getOrderData($order_id)
    {
        $order_data = fn_get_order_info($order_id, false, false);

        //The processor_params removed by security reason.
        unset($order_data['payment_method']['processor_params']);

        return $order_data;
    }
}
