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

namespace Tygh\Addons\DirectPayments\Cart;

use Tygh\Registry;
use Tygh\Web\Session;

/**
 * Class Service
 *
 * @package Tygh\Cart
 */
class Service
{
    const SESSION_LEGACY_CART_FIELD = 'cart';
    const SESSION_CART_FIELD = '__cart';
    const SESSION_CURRENT_VENDOR_FIELD = '__cart_current_vendor_id';
    const SESSION_IS_SEPARATE_CHECKOUT_FIELD = '__cart_is_separate_checkout';
    const DEFAULT_VENDOR_ID = 0;

    /** @var bool flag separate checkout */
    protected $is_separate_checkout = true;

    /** @var Session */
    protected $session;

    /** @var int */
    protected $current_vendor_id = 0;

    /** @var array */
    protected $exists_vendors = array();

    /**
     * Cart service constructor.
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
        $vendor_id = self::DEFAULT_VENDOR_ID;
        $isset_cart = !empty($_SESSION[self::SESSION_CART_FIELD]) || !empty($_SESSION[self::SESSION_LEGACY_CART_FIELD]);
        $session_is_separate_checkout = isset($_SESSION[self::SESSION_IS_SEPARATE_CHECKOUT_FIELD]) ? $_SESSION[self::SESSION_IS_SEPARATE_CHECKOUT_FIELD] : null;

        if (!$session_is_separate_checkout) {
            if (!empty($_SESSION['auth']['user_id']) && $isset_cart) {
                fn_delete_user_cart($_SESSION['auth']['user_id']);
            }

            unset($_SESSION[self::SESSION_CART_FIELD], $_SESSION[self::SESSION_LEGACY_CART_FIELD]);
            $_SESSION[self::SESSION_IS_SEPARATE_CHECKOUT_FIELD] = true;
        }

        if (!isset($_SESSION[self::SESSION_CART_FIELD])) {
            $_SESSION[self::SESSION_CART_FIELD] = array();
        }

        $this->initCarts();

        if (
            isset($_SESSION[self::SESSION_CURRENT_VENDOR_FIELD])
            && $this->isVendorExist($_SESSION[self::SESSION_CURRENT_VENDOR_FIELD])
        ) {
            $vendor_id = (int) $_SESSION[self::SESSION_CURRENT_VENDOR_FIELD];
        }

        $this->setCurrentVendorId($vendor_id);
    }

    /**
     * Get cart
     *
     * @param null|int $vendor_id
     *
     * @return array
     */
    public function &getCart($vendor_id = null)
    {
        if ($vendor_id === null || !$this->isVendorExist($vendor_id)) {
            $vendor_id = $this->current_vendor_id;
        }

        $this->initCart($vendor_id);

        $this->setRuntimeVendorId($vendor_id);

        return $_SESSION[self::SESSION_CART_FIELD][$vendor_id];
    }

    /**
     * Init cart
     *
     * @param int $vendor_id
     */
    protected function initCart($vendor_id)
    {
        $vendor_id = (int) $vendor_id;

        if (!isset($_SESSION[self::SESSION_CART_FIELD][$vendor_id])) {
            $_SESSION[self::SESSION_CART_FIELD][$vendor_id] = array();

            fn_clear_cart($_SESSION[self::SESSION_CART_FIELD][$vendor_id]);
        }

        $_SESSION[self::SESSION_CART_FIELD][$vendor_id]['vendor_id'] = $vendor_id;

        if (empty($_SESSION[self::SESSION_CART_FIELD][$vendor_id]['user_data'])
            && isset($_SESSION[self::SESSION_CART_FIELD][self::DEFAULT_VENDOR_ID]['user_data'])
        ) {
            $_SESSION[self::SESSION_CART_FIELD][$vendor_id]['user_data'] =
                $_SESSION[self::SESSION_CART_FIELD][self::DEFAULT_VENDOR_ID]['user_data'];
        }
    }

    /**
     * Init carts
     */
    protected function initCarts()
    {
        $vendor_ids = array_keys($_SESSION[self::SESSION_CART_FIELD]);

        foreach ($vendor_ids as $vendor_id) {
            if ($this->isVendorExist($vendor_id)) {
                $this->initCart($vendor_id);
            } else {
                unset($_SESSION[self::SESSION_CART_FIELD][$vendor_id]);
            }
        }
    }

    /**
     * Get carts
     *
     * @return array
     */
    public function &getCarts()
    {
        $this->initCarts();

        return $_SESSION[self::SESSION_CART_FIELD];
    }

    /**
     * Load cart in session
     *
     * @return void
     */
    public function loadSessionCart()
    {
        $_SESSION[self::SESSION_LEGACY_CART_FIELD] = &$this->getCart();
    }

    /**
     * Save carts
     *
     * @param int    $user_id
     * @param string $type
     * @param string $user_type
     */
    public function save($user_id, $type = 'C', $user_type = 'R')
    {
        foreach ($this->getCarts() as $vendor_id => &$cart) {
            fn_save_cart_content($cart, $user_id, $type, $user_type);
        }
    }

    /**
     * Load carts
     *
     * @param int    $user_id
     * @param string $type
     * @param string $user_type
     */
    public function load($user_id, $type = 'C', $user_type = 'R')
    {
        $stored_vendor_ids = db_get_fields(
            'SELECT company_id FROM ?:user_session_products '
            . 'WHERE user_id = ?i AND type = ?s AND user_type = ?s '
            . 'GROUP BY company_id ORDER BY timestamp ASC',
            $user_id, $type, $user_type
        );

        $carts = $this->getCarts();
        $extracted_carts    = [];
        $session_vendor_ids = [];
        $keep_vendor_ids    = [];

        foreach ($carts as $vendor_id => $cart) {
            $session_vendor_ids[] = $vendor_id;
            if (!empty($cart['user_data']['user_id'])) {
                continue;
            }
            // keep unauthorized user cart
            $keep_vendor_ids[] = $vendor_id;
            $extracted_carts[$vendor_id] = $cart;
        }

        // if cart was removed in other session, it must removed also in the current
        $remove_vendor_ids = array_diff($session_vendor_ids, $stored_vendor_ids);

        foreach ($remove_vendor_ids as $vendor_id) {
            // but when a cart was formed by unauthorized user, we must keep the cart
            if (in_array($vendor_id, $keep_vendor_ids)) {
                continue;
            }
            unset($_SESSION[self::SESSION_CART_FIELD][$vendor_id]);
        }

        // Extracts carts and keeps carts sorting
        foreach ($stored_vendor_ids as $vendor_id) {
            $cart = &$this->getCart($vendor_id);
            fn_extract_cart_content($cart, $user_id, $type, $user_type);
            $extracted_carts[$vendor_id] = $cart;
        }

        $_SESSION[self::SESSION_CART_FIELD] = $extracted_carts;
    }

    /**
     * Clear carts
     *
     * @param bool|false $complete
     * @param bool|false $clear_all
     */
    public function clear($complete = false, $clear_all = false)
    {
        foreach ($this->getCarts() as &$cart) {
            fn_clear_cart($cart, $complete, $clear_all);
        }
    }

    /**
     * Set current vendor id
     *
     * @param int $vendor_id
     *
     * @return bool
     */
    public function setCurrentVendorId($vendor_id)
    {
        if (!$this->isVendorExist($vendor_id)) {
            return false;
        }

        /** @psalm-suppress RedundantCastGivenDocblockType */
        $this->current_vendor_id = (int) $vendor_id;

        unset($_SESSION[self::SESSION_LEGACY_CART_FIELD]);
        $_SESSION[self::SESSION_CURRENT_VENDOR_FIELD] = $vendor_id;
        $_SESSION[self::SESSION_LEGACY_CART_FIELD] = &$this->getCart($vendor_id);

        $this->setRuntimeVendorId($vendor_id);

        return true;
    }

    /**
     * Get current vendor id
     *
     * @return int
     */
    public function getCurrentVendorId()
    {
        return $this->current_vendor_id;
    }

    /**
     * Check is empty
     *
     * @param bool|true $check_excluded
     *
     * @return bool
     */
    public function isEmpty($check_excluded = true)
    {
        foreach ($this->getCarts() as &$cart) {
            if (!fn_cart_is_empty($cart, $check_excluded)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set user data
     *
     * @param array $user_data
     */
    public function setUserData($user_data)
    {
        foreach ($this->getCarts() as &$cart) {
            $cart['user_data'] = $user_data;
        }
    }

    /**
     * Checks user data
     *
     * @return bool
     */
    public function checkUserData()
    {
        foreach ($this->getCarts() as $cart) {
            if (empty($cart['user_data'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get group products by vendor
     *
     * @param array $products_data
     *
     * @return array
     */
    public function getGroupProducts(array $products_data)
    {
        $product_ids = $result = array();

        foreach ($products_data as $key => &$item) {
            $product_id = isset($item['product_id']) ? (int) $item['product_id'] : (int) $key;
            $product_ids[$product_id] = $product_id;
            $item['product_id'] = $product_id;
        }

        unset($item);

        if ($product_ids) {
            $product_vendor_ids = $this->getVendorIdsByProductIds($product_ids);
            krsort($products_data);

            foreach ($products_data as $key => &$item) {
                if ($key === 'custom_files') {
                    $option_data = explode('_', reset($item));
                    $data_id = empty($option_data[0]) ? 0 : $option_data[0];
                    $product_id = $products_data[$data_id]['product_id'] ?? 0;
                } elseif (empty($item['product_id'])) {
                    continue;
                } else {
                    $product_id = $item['product_id'];
                }
                $vendor_id = $product_vendor_ids[$product_id];

                /**
                 * Executes after the company ID of a product is detected when spliting products in the cart by vendors,
                 * allows to modify the detected product company ID.
                 *
                 * @param array $products_data Products in the cart
                 * @param int   $key           Cart ID
                 * @param array $item          Product data
                 * @param int   $vendor_id     Detected company ID
                 */
                fn_set_hook(
                    'direct_payments_cart_service_get_group_products_get_company_id',
                    $products_data,
                    $key,
                    $item,
                    $vendor_id
                );

                $result[$vendor_id][$key] = $item;
            }
            unset($item);
        }

        return $result;
    }

    /**
     * Get cart by product_id
     *
     * @param int $product_id
     *
     * @return array
     */
    public function &getCartByProductId($product_id)
    {
        $vendor_ids = $this->getVendorIdsByProductIds($product_id);

        return $this->getCart($vendor_ids[$product_id]);
    }

    /**
     * Get cart by order_id
     *
     * @param int $order_id
     *
     * @return array
     */
    public function &getCartByOrderId($order_id)
    {
        $vendor_id = $this->getVendorIdByOrderId($order_id);

        return $this->getCart($vendor_id);
    }

    /**
     * Get vendor_id by order_id
     *
     * @param int $order_id
     *
     * @return int
     */
    public function getVendorIdByOrderId($order_id)
    {
        $vendor_id = (int) db_get_field('SELECT company_id FROM ?:orders WHERE order_id = ?i', $order_id);

        return $vendor_id;
    }

    /**
     * Gets IDs of vendors that sell products in customer's cart.
     *
     * @param int $user_id
     *
     * @return int[]
     */
    public function getVendorIdsByUserId($user_id)
    {
        $vendor_ids = db_get_fields(
            'SELECT company_id'
            . ' FROM ?:user_session_products'
            . ' WHERE user_id = ?i'
            . ' AND type = ?s'
            . ' AND user_type = ?s',
            $user_id,
            'C',
            'R'
        );

        $vendor_ids = array_map('intval', $vendor_ids);

        return $vendor_ids;
    }

    /**
     * Get vendor ids by product ids
     *
     * @param array|int $product_ids
     *
     * @return array
     */
    protected function getVendorIdsByProductIds($product_ids)
    {
        $product_ids = (array) $product_ids;

        $result = db_get_hash_single_array(
            "SELECT product_id, company_id FROM ?:products WHERE product_id IN (?n)",
            array('product_id', 'company_id'),
            $product_ids
        );

        foreach ($product_ids as $product_id) {
            if (!isset($result[$product_id])) {
                $result[$product_id] = self::DEFAULT_VENDOR_ID;
            }
        }

        return $result;
    }

    /**
     * Check exist vendor
     *
     * @param int $vendor_id
     *
     * @return bool
     */
    protected function isVendorExist($vendor_id)
    {
        if ($vendor_id === self::DEFAULT_VENDOR_ID) {
            return true;
        }

        if (!isset($this->exists_vendors[$vendor_id])) {
            if (db_get_row("SELECT company_id FROM ?:companies WHERE company_id = ?i", $vendor_id)) {
                $this->exists_vendors[$vendor_id] = true;
            } else {
                $this->exists_vendors[$vendor_id] = false;
            }
        }

        return $this->exists_vendors[$vendor_id];
    }

    /**
     * @param int $vendor_id
     */
    public function setRuntimeVendorId($vendor_id)
    {
        $prev_id = Registry::get('runtime.direct_payments.cart.vendor_id');
        if ($prev_id == $vendor_id) {
            return;
        }

        Registry::set('runtime.direct_payments.cart.prev_vendor_id', $prev_id, true);

        Registry::set('runtime.direct_payments.cart.vendor_id', $vendor_id, true);
    }
}
