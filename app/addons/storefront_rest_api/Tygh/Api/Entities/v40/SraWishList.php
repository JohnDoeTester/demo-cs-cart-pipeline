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

use Tygh\Addons\StorefrontRestApi\ASraEntity;
use Tygh\Api\Response;

class SraWishList extends ASraEntity
{
    /**
     * @var int[] Default icon sizes
     */
    protected $icon_size_small = [500, 500];

    /**
     * @var int[] Default detailed image sizes
     */
    protected $icon_size_big = [1000, 1000];

    /**
     * @var string Session user type
     */
    protected $user_type = 'R';

    /**
     * @var string Wish list cart type
     */
    protected $cart_type = 'W';

    /** @inheritdoc */
    public function index($id = '', $params = [])
    {
        $lang_code = $this->getLanguageCode($params);

        $wishlist = [];
        fn_extract_cart_content($wishlist, $this->auth['user_id'], $this->cart_type, $this->user_type, $lang_code);
        $products = empty($wishlist['products'])
            ? []
            : $wishlist['products'];

        $params['icon_sizes'] = $this->safeGet(
            $params,
            'icon_sizes',
            [
                'main_pair'   => [$this->icon_size_big, $this->icon_size_small],
                'image_pairs' => [$this->icon_size_small],
            ]
        );

        list($products,) = fn_wishlist_gather_product_data($products, [], $this->auth, $lang_code);

        $products = fn_storefront_rest_api_format_products_prices(
            $products,
            $this->getCurrencyCode($params)
        );

        $products = $this->normalizeAmount($products);

        $products = fn_storefront_rest_api_set_products_icons($products, $params['icon_sizes']);

        return [
            'status' => Response::STATUS_OK,
            'data'   => [
                'products' => $products,
            ],
        ];
    }

    /** @inheritdoc */
    public function create($params)
    {
        $products = $this->safeGet($params, 'products', []);
        if (!$products) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data'   => [
                    'errors' => [
                        __(
                            'api_required_field',
                            [
                                '[field]' => 'products',
                            ]
                        ),
                    ],
                ],
            ];
        }

        $lang_code = $this->getLanguageCode($params);

        $wishlist = [];
        $existing_wishlist_ids = [];
        fn_extract_cart_content($wishlist, $this->auth['user_id'], $this->cart_type, $this->user_type, $lang_code);
        if (isset($wishlist['products'])) {
            $existing_wishlist_ids = array_keys($wishlist['products']);
        }

        $wishlist_ids = fn_add_product_to_wishlist($products, $wishlist, $this->auth);
        if ($wishlist_ids === false) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
            ];
        }

        if ($wishlist_ids === array_intersect($wishlist_ids, $existing_wishlist_ids)) {
            return [
                'status' => Response::STATUS_CONFLICT,
                'data'   => [
                    'errors' => [
                        __('product_in_wishlist'),
                    ],
                ],
            ];
        }

        fn_save_cart_content($wishlist, $this->auth['user_id'], $this->cart_type, $this->user_type);

        return [
            'status' => Response::STATUS_CREATED,
            'data'   => [
                'cart_ids' => $wishlist_ids,
            ],
        ];
    }

    /** @inheritdoc */
    public function update($id, $params)
    {
        return [
            'status' => Response::STATUS_FORBIDDEN,
        ];
    }

    /** @inheritdoc */
    public function delete($id = 0)
    {
        $wishlist = [];
        fn_extract_cart_content($wishlist, $this->auth['user_id'], $this->cart_type, $this->user_type);
        if (empty($wishlist['products'])) {
            return [
                'status' => Response::STATUS_NO_CONTENT,
            ];
        }

        if ($id) {
            $wishlist_ids = [$id];
        } else {
            $wishlist_ids = array_keys($wishlist['products']);
        }
        foreach ($wishlist_ids as $wishlist_id) {
            fn_delete_wishlist_product($wishlist, $wishlist_id);
        }
        fn_save_cart_content($wishlist, $this->auth['user_id'], $this->cart_type, $this->user_type);

        return [
            'status' => Response::STATUS_NO_CONTENT,
        ];
    }

    /** @inheritdoc */
    public function privilegesCustomer()
    {
        return [
            'index'  => $this->auth['is_token_auth'],
            'create' => $this->auth['is_token_auth'],
            'update' => false,
            'delete' => $this->auth['is_token_auth'],
        ];
    }

    /**
     * Normalizes product amount for API response.
     *
     * FIXME: `amount` holds total product amount.
     * FIXME: API clients should use the `display_amount` property to display amount of product in the wish list.
     *
     * @param array $products Wishlist products
     *
     * @return array
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    private function normalizeAmount(array $products)
    {
        array_walk($products, static function (&$product) {
            $product['amount'] = $product['display_amount'] = 1;
        });

        return $products;
    }
}
