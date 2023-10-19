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

use Tygh\Api\Entities\Pages;

/**
 * Class SraPages
 *
 * @package Tygh\Api\Entities
 */
class SraPages extends Pages
{
    protected $icon_size_small = [500, 500];

    protected $icon_size_big = [1000, 1000];

    /**
     * @inheritdoc
     */
    public function index($id = 0, $params = [])
    {
        if ($this->area === 'C') {
            $params['status'] = ['A', 'H'];
        }

        $params['get_image'] = $this->safeGet($params, 'get_image', true);

        $result = parent::index($id, $params);

        $params['icon_sizes'] = $this->safeGet($params, 'icon_sizes', [
            'main_pair'   => [$this->icon_size_big, $this->icon_size_small],
            'image_pairs' => [$this->icon_size_small],
        ]);

        $pages = [];
        if ($id && !empty($result['data'])) {
            $pages = [$result['data']['page_id'] => $result['data']];
        } elseif (!empty($result['data']['pages'])) {
            $pages = $result['data']['pages'];
        }

        $pages = fn_storefront_rest_api_set_pages_icons($pages, $params['icon_sizes']);

        if ($id) {
            $result['data'] = reset($pages);
        } else {
            $result['data']['pages'] = $pages;
        }

        return $result;
    }

    /** @inheritdoc */
    public function privilegesCustomer()
    {
        return [
            'index' => true
        ];
    }
}
