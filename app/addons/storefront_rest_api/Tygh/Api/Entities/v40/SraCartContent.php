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
use Tygh\Api\Response;
use Tygh\Common\OperationResult;
use Tygh\Registry;

class SraCartContent extends ASraEntity
{
    const PRODUCT_ACTION_ADD = 'add';
    const PRODUCT_ACTION_UPDATE = 'update';

    protected $icon_size_small = [500, 500];

    protected $icon_size_big = [1000, 1000];

    /**
     * @var array $carts Shopping carts content
     */
    protected $carts = [];

    /**
     * @var string $cart_type Regular shopping cart type
     */
    protected $cart_type = 'C';

    /**
     * Gets cart content.
     *
     * @param int    $cart_service_id Cart identifier
     * @param string $lang_code       Two-letter language code
     *
     * @return array
     */
    protected function get($cart_service_id = 0, $lang_code = DEFAULT_LANGUAGE)
    {
        $cart_service_id = (int) $cart_service_id;
        if ($cart_service_id === 0) {
            $cart_service_ids = fn_storefront_rest_api_get_cart_service_ids($this->auth);
            $cart_service_id = reset($cart_service_ids);
        }

        if (!isset($this->carts[$cart_service_id])) {
            $cart = fn_storefront_rest_api_get_empty_cart($cart_service_id, $this->auth);
            fn_extract_cart_content($cart, $this->auth['user_id'], $this->cart_type, 'R', $lang_code);
            $this->carts[$cart_service_id] = $cart;
        }

        return $this->carts[$cart_service_id];
    }

    /**
     * Calculates cart content with promotions, taxes and shipping.
     *
     * @param array<string, int|string|array> $cart      Cart data
     * @param array<string, int|string|array> $params    Calculation parameters
     * @param string                          $lang_code Two-letter language code
     *
     * @return array<string, int|string|array>
     */
    protected function calculate(array $cart, array $params = [], $lang_code = DEFAULT_LANGUAGE)
    {
        $cart = $this->setUserInfo($cart, fn_get_user_info($this->auth['user_id']));

        $params = array_merge([
            'calculate_shipping' => 'S', // skip shipping calculation
            'coupon_codes'       => [],
            'shipping_ids'       => [],
        ], $params);

        if ($params['shipping_ids']) {
            $cart['chosen_shipping'] = $params['shipping_ids'];
            if ($params['calculate_shipping'] === 'S') {
                $params['calculate_shipping'] = 'E';
            }
        }

        if ($params['coupon_codes']) {
            $do_recalc = false;
            foreach ($params['coupon_codes'] as $code) {
                if ($do_recalc) {
                    fn_calculate_cart_content($cart, $this->auth, 'S', false, 'S', true, $lang_code);
                }
                $cart['pending_coupon'] = $code;
                $do_recalc = true;
            }
        }

        if ($params['calculate_shipping'] !== 'S') {
            $cart['calculate_shipping'] = true;
        }

        list($products,) = fn_calculate_cart_content(
            $cart,
            $this->auth,
            $params['calculate_shipping'],
            true,
            'F',
            true,
            $lang_code
        );
        $cart['products'] = $this->mergeProductsDataIntoCart($cart['products'], $products);
        $cart['products'] = $this->getDetailedOptions($cart['products'], $products, $lang_code);
        $cart['products'] = $this->getTaxedPrices($cart['products'], $this->auth);

        $cart['default_location'] = $this->getDefaultLocation($lang_code);

        // add payment methods
        $cart['payments'] = $this->getPayments($cart, $lang_code);

        // remove sensitive and redundant information
        $cart = fn_storefront_rest_api_strip_service_data($cart);

        return $cart;
    }

    /**
     * Gets payments list that doesn't contain any sensitive data (like config).
     *
     * @param array  $cart      Cart content
     * @param string $lang_code Two-letter language code
     *
     * @return array
     */
    protected function getPayments(array $cart, $lang_code = DEFAULT_LANGUAGE)
    {
        $payment_methods = fn_prepare_checkout_payment_methods($cart, $this->auth, $lang_code, false);

        return array_map(
            function ($payment) {
                $script = fn_get_processor_data($payment['payment_id']);

                return [
                    'payment'         => $payment['payment'],
                    'description'     => $payment['description'],
                    'instructions'    => $payment['instructions'],
                    'p_surcharge'     => $payment['p_surcharge'],
                    'a_surcharge'     => $payment['a_surcharge'],
                    'surcharge_title' => $payment['surcharge_title'],
                    'script'          => empty($script['processor_script'])
                        ? null
                        : $script['processor_script'],
                    'template'        => empty($payment['template'])
                        ? null
                        : $payment['template'],
                ];
            },
            $payment_methods
        );
    }

    /**
     * Saves cart content.
     *
     * @param array $cart
     *
     * @return bool
     */
    protected function save(array $cart)
    {
        $cart = $this->calculate($cart);

        return fn_save_cart_content($cart, $this->auth['user_id'], $this->cart_type);
    }

    /**
     * Adds product to a cart.
     *
     * @param array $cart
     * @param array $cart_products Products data to add/update.
     *                             Add:
     *                             product_id: [
     *                               'amount': product_amount,
     *                               'product_options': [
     *                                 option_id => option_value
     *                               ]
     *                             ]
     *                             Update:
     *                             cart_id: [
     *                               'amount': product_amount,
     *                               'product_options': [
     *                                 option_id => option_value
     *                               ]
     *                             ]
     * @param bool $is_update      Whether to update existing cart products or add the new one
     *
     * @return \Tygh\Common\OperationResult Status and added products cart IDs as pairs of [cart_id => product_id].
     *               Status is Response::STATUS_CREATED when products are added
     *               and Response::STATUS_CONFLICT when unable to add.
     */
    protected function addProducts(array $cart, array $cart_products, $is_update = false)
    {
        $operation_result = new OperationResult(false);
        $product_cart_ids = fn_add_product_to_cart($cart_products, $cart, $this->auth, $is_update);
        if ($product_cart_ids) {
            $operation_result->setSuccess(true);
            $operation_result->setData($product_cart_ids, 'cart_ids');
            $this->save($cart);

            foreach ($cart['products'] as $product) {
                if (
                    $product['amount'] > 0
                    && $product['amount'] < $cart_products[$product['product_id']]['amount']
                ) {
                    $operation_result->addMessage('cart_amount_corrected', __('text_cart_amount_corrected', [
                        '[product]' => $product['product'],
                        '[count]' => $product['amount'],
                    ]));
                }
            }
        }

        return $operation_result;
    }

    /**
     * Removes product from cart.
     *
     * @param int $cart_service_id
     * @param array $cart
     * @param int   $product_cart_id Product cart ID
     */
    protected function removeProduct($cart_service_id, array $cart, $product_cart_id)
    {
        fn_delete_cart_product($cart, $product_cart_id);

        if (fn_cart_is_empty($cart) == true) {
            $cart = fn_storefront_rest_api_get_empty_cart($cart_service_id, $this->auth);
        }

        $this->save($cart);
    }

    /**
     * Lists cart content.
     *
     * @param string $id
     * @param array  $params
     *
     * @return array
     */
    public function index($id = '', $params = [])
    {
        if ($user_relation_error = $this->getUserRelationError()) {
            return $user_relation_error;
        }

        $cart_service_id = (int) $id;

        $currency = $this->getCurrencyCode($params);

        $params['icon_sizes'] = $this->safeGet($params, 'icon_sizes', [
            'main_pair'   => [$this->icon_size_big, $this->icon_size_small],
            'image_pairs' => [$this->icon_size_small],
        ]);

        // normalize coupon codes
        if ($coupon_codes = $this->safeGet($params, 'coupon_codes', [])) {
            $params['coupon_codes'] = array_map(static function ($code) {
                return fn_strtolower(trim($code));
            }, array_unique((array) $coupon_codes));
        }

        // normalize shipping ids
        $params['shipping_ids'] = array_filter((array) $this->safeGet($params, 'shipping_ids', []), 'is_numeric');

        // normalize shipping calculation policy
        $calculate_shipping = $this->safeGet($params, 'calculate_shipping', 'S');
        $params['calculate_shipping'] = in_array($calculate_shipping, ['A', 'E', 'S']) ? $calculate_shipping : 'S';

        $lang_code = $this->getLanguageCode($params);

        $cart = $this->get($cart_service_id);

        $cart = $this->calculate($cart, $params, $lang_code);

        $data = fn_storefront_rest_api_format_order_prices($cart, $currency);

        $data['products'] = fn_storefront_rest_api_set_products_icons($data['products'], $params['icon_sizes']);
        foreach ($data['product_groups'] as &$product_group) {
            $product_group['products'] = fn_storefront_rest_api_set_products_icons(
                $product_group['products'],
                $params['icon_sizes']
            );
        }

        if ($this->safeGet($params, 'get_checkout_fields', false)) {
            $data['checkout_fields'] = fn_storefront_rest_api_get_checkout_fields(
                $cart,
                $this->auth,
                $lang_code
            );
        }

        return [
            'status' => Response::STATUS_OK,
            'data'   => $data,
        ];
    }

    /**
     * Entry point for cart management.
     *
     * @param array $params
     *
     * @return array
     */
    public function create($params)
    {
        if ($user_relation_error = $this->getUserRelationError()) {
            return $user_relation_error;
        }

        $status = Response::STATUS_BAD_REQUEST;
        $data = [];

        $cart_products = $this->safeGet($params, 'products', []);

        // add to cart
        if ($cart_products) {
            if (!$this->auth['user_id']) {
                return [
                    'status' => Response::STATUS_FORBIDDEN,
                    'data'   => [
                        'message' => __('storefront_rest_api.guests_cant_add_products_to_cart')
                    ]
                ];
            }

            $result = $this->updateProducts($cart_products, self::PRODUCT_ACTION_ADD);
            if ($result->isSuccess()) {
                $status = Response::STATUS_CREATED;
                $data['cart_ids'] = $result->getData('cart_ids');

                $messages = $result->getMessages();
                $data['message'] = reset($messages);
            } else {
                $data['message'] = $result->getFirstError();
                $status = Response::STATUS_CONFLICT;
            }
        } else {
            $data['message'] = __('api_required_field', [
                '[field]' => 'products',
            ]);
        }

        return [
            'status' => $status,
            'data'   => $data,
        ];
    }

    // update amount/options
    public function update($id = '', $params = [])
    {
        if ($user_relation_error = $this->getUserRelationError()) {
            return $user_relation_error;
        }

        $status = Response::STATUS_BAD_REQUEST;
        $data = [];
        $user_data = [];

        $can_edit = true;
        if ($id) {
            $cart_products = [$id => $params];
            if (!$params) {
                $can_edit = false;
                $data['message'] = __('api_need_params');
            }
        } else {
            $cart_products = (array) $this->safeGet($params, 'products', []);
            $user_data = (array) $this->safeGet($params, 'user_data', []);
            if (!$cart_products && !$user_data) {
                $can_edit = false;
                $data['message'] = __('api_required_fields', [
                    '[fields]' => 'products / user_data',
                ]);
            }
        }

        if ($can_edit && $cart_products) {
            $cart_products_groups = fn_storefront_rest_api_group_cart_products($cart_products);

            foreach ($cart_products_groups as &$group) {
                $cart_service_id = $group['cart_service_id'];
                $cart = $this->get($cart_service_id);

                foreach ($group['products'] as $product_cart_id => $product) {
                    if ($this->getCartServiceIdByProductCartId($product_cart_id) === null) {
                        $can_edit = false;
                        $status = Response::STATUS_NOT_FOUND;
                        break 2;
                    }

                    // remove products with zero amount
                    if (
                        isset($product['amount'])
                        && empty($product['amount'])
                        && !isset($cart['products'][$product_cart_id]['extra']['parent'])
                    ) {
                        $this->removeProduct($cart_service_id, $cart, $product_cart_id);
                        unset($cart_products[$product_cart_id]);
                        continue;
                    }

                    // update existing product data
                    $cart_products[$product_cart_id] = array_merge($cart['products'][$product_cart_id], $product);
                }
            }
            unset($group);
        }

        // update cart
        if ($can_edit) {
            if ($user_data && $this->updateUserData($user_data)) {
                $status = Response::STATUS_CREATED;
            }

            if ($cart_products) {
                $result = $this->updateProducts($cart_products, self::PRODUCT_ACTION_UPDATE);

                if ($result->isSuccess()) {
                    $data['cart_ids'] = $result->getData('cart_ids');
                    $status = Response::STATUS_CREATED;
                } else {
                    $data['message'] = $result->getFirstError();
                    $status = Response::STATUS_CONFLICT;
                }
            }
        }

        return [
            'status' => $status,
            'data'   => $data,
        ];
    }

    /**
     * Deletes a product from a cart or cleans up the whole cart.
     *
     * @param int $id Product cart ID
     *
     * @return array
     */
    public function delete($id = 0)
    {
        if ($user_relation_error = $this->getUserRelationError()) {
            return $user_relation_error;
        }

        if ($id) {
            $status = Response::STATUS_NOT_FOUND;
            $product_cart_id = $id;

            $cart_service_ids = fn_storefront_rest_api_get_cart_service_ids($this->auth);

            foreach ($cart_service_ids as $cart_service_id) {
                $cart = $this->get($cart_service_id);

                if (isset($cart['products'][$product_cart_id])) {
                    $this->removeProduct($cart_service_id, $cart, $product_cart_id);
                    $status = Response::STATUS_NO_CONTENT;
                    break;
                }
            }
        } else {
            $this->clearCarts();
            $status = Response::STATUS_NO_CONTENT;
        }

        return [
            'status' => $status,
            'data'   => [],
        ];
    }

    /** @inheritDoc */
    public function privilegesCustomer()
    {
        return [
            'index'  => $this->auth['is_token_auth'],
            'create' => true,
            'update' => $this->auth['is_token_auth'],
            'delete' => $this->auth['is_token_auth'],
        ];
    }

    /** @inheritDoc */
    public function privileges()
    {
        return [
            'index'  => true,
            'create' => true,
            'update' => true,
            'delete' => true,
        ];
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
        } else {
            $company_id = parent::getCompanyId();
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
     * @return array API response data to return if user doesn't belong to a company or an empty array if belongs
     */
    protected function getUserRelationError($company_id = null)
    {
        if ($company_id == null && isset($this->auth['company_id'])) {
            $company_id = $this->auth['company_id'];
        }

        if (fn_allowed_for('ULTIMATE')
            && $company_id
            && $company_id != $this->getCompanyId()
            && !$this->areUsersShared()
        ) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => [
                    'message' => __('api_wrong_user_company_relation'),
                ],
            ];
        }

        return [];
    }

    /**
     * Gathers additional information for products' options.
     *
     * @param array  $cart_products $cart['products'] from calculated cart
     * @param array  $products      Products returned from \fn_calculate_cart_content()
     * @param string $lang_code     Two-letter language code
     *
     * @return array $cart['products'] with options attached
     */
    protected function getDetailedOptions(array $cart_products, array $products, $lang_code)
    {
        fn_gather_additional_products_data($products, [
            'get_options' => true,
        ], $lang_code);

        foreach ($products as $cart_id => $product_data) {
            if (empty($product_data['product_options'])) {
                continue;
            }

            foreach ($product_data['product_options'] as $option_data) {
                if (!isset($cart_products[$cart_id]['product_options_detailed'])) {
                    $cart_products[$cart_id]['product_options_detailed'] = [];
                }

                $cart_products[$cart_id]['product_options_detailed'][$option_data['option_id']] = $option_data;
            }
        }

        return $cart_products;
    }

    /**
     * Updates customer's profile.
     *
     * @param array $user_data User data
     *
     * @return int User identifier
     */
    private function updateUserData(array $user_data)
    {
        unset($user_data['profile_id'], $user_data['user_id']);

        list(,$profile_id) = fn_update_user($this->auth['user_id'], $user_data, $this->auth, false, false);

        return $profile_id ? $profile_id : false;
    }

    /**
     * Provides default location from the store settings.
     *
     * @param string $lang_code Two-letter language code
     *
     * @return array Default address
     */
    protected function getDefaultLocation($lang_code = DEFAULT_LANGUAGE)
    {
        $general_settings = Registry::get('settings.General');

        $default_location = [];
        foreach ($general_settings as $field => $value) {
            if (strpos($field, 'default_') === 0) {
                $default_location[str_replace('default_', '', $field)] = $value;
            }
        }

        if ($default_location['country']) {
            $default_location['country_descr'] = fn_get_country_name(
                $default_location['country'],
                $lang_code
            );
        }

        if ($default_location['state']) {
            $default_location['state_descr'] = fn_get_state_name(
                $default_location['state'],
                $default_location['country'],
                $lang_code
            );
            if (!$default_location['state_descr']) {
                $default_location['state_descr'] = $default_location['state'];
            }
        }

        return $default_location;
    }

    /**
     * Merges product properties into the products stored in the cart.
     *
     * @param array $cart_products
     * @param array $products
     *
     * @return array
     */
    protected function mergeProductsDataIntoCart(array $cart_products, array $products)
    {
        foreach ($products as $cart_id => $product) {
            if (isset($cart_products[$cart_id])) {
                /**
                 * @todo: FIXME: Options in products and cart products are stored differently and processed separately.
                 * @see \Tygh\Api\Entities\v40\SraCartContent::getDetailedOptions
                 */
                unset($product['product_options']);
                $cart_products[$cart_id] = fn_array_merge($cart_products[$cart_id], $product);
            }
        }

        return $cart_products;
    }

    protected function getTaxedPrices(array $products, array $auth)
    {
        array_walk(
            $products,
            function (&$product) use ($auth) {
                fn_get_taxed_and_clean_prices($product, $auth);
            }
        );

        return $products;
    }

    protected function setUserInfo(array $cart, array $user_info)
    {
        $cart['user_data'] = $user_info;

        return $cart;
    }

    protected function getCartServiceIdByProductCartId($product_cart_id)
    {
        foreach ($this->carts as $cart_service_id => $cart) {
            if (isset($cart['products'][$product_cart_id])) {
                return $cart_service_id;
            }
        }

        return null;
    }

    protected function clearCarts()
    {
        $cart_service_ids = fn_storefront_rest_api_get_cart_service_ids($this->auth);

        foreach ($cart_service_ids as $cart_service_id) {
            $cart = fn_storefront_rest_api_get_empty_cart($cart_service_id, $this->auth);
            $this->save($cart);
        }

        unset($cart);
    }

    /**
     * @param array $cart_products
     * @param string $action
     *
     * @return \Tygh\Common\OperationResult
     */
    protected function updateProducts(array $cart_products, $action = self::PRODUCT_ACTION_ADD)
    {
        $product_cart_ids = [];

        $operation_result = new OperationResult(true);

        $cart_products = fn_storefront_rest_api_group_cart_products($cart_products);
        foreach ($cart_products as $group) {
            $cart = $this->get($group['cart_service_id']);
            $group_result = $this->addProducts($cart, $group['products'], $action === self::PRODUCT_ACTION_UPDATE);
            if (!$group_result->isSuccess()) {
                $operation_result->setSuccess(false);
                $operation_result->setErrors($group_result->getErrors());
                break;
            }
            $operation_result->setMessages($group_result->getMessages());

            $product_cart_ids = fn_array_merge($product_cart_ids, $group_result->getData('cart_ids'));
        }

        $operation_result->setData($product_cart_ids, 'cart_ids');

        return $operation_result;
    }
}
