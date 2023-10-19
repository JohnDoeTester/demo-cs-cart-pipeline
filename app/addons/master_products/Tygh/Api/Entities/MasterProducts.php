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

use Tygh\Addons\MasterProducts\ServiceProvider;
use Tygh\Api\Response;
use Tygh\Enum\UserTypes;
use Tygh\Registry;

class MasterProducts extends Products
{
    const ENTITY_NAME = 'master_products';

    /**
     * @inheritDoc
     */
    public function index($id = 0, $params = [])
    {
        $status = Response::STATUS_OK;

        $lang_code = $this->getLanguageCode($params);
        $params['extend'][] = 'categories';

        $params['show_master_products_only'] = true;

        $vendor_id = $this->safeGet($params, 'company_id', null);
        if ($vendor_id) {
            Registry::set('runtime.vendor_id', $vendor_id);
        }

        if ($this->getParentName() === 'categories') {
            $parent_category = $this->getParentData();
            $params['cid'] = $parent_category['category_id'];
        }

        if (!empty($id)) {
            $data = fn_get_product_data($id, $this->auth, $lang_code, '', true, true, true, false, false, false, true);

            if (empty($data)) {
                $status = Response::STATUS_NOT_FOUND;
            } elseif ((int) $data['company_id'] !== 0) {
                $status = Response::STATUS_BAD_REQUEST;
                $data = __('master_products.api_not_master_product');
            } else {
                $data['selected_options'] = $this->safeGet($params, 'selected_options', []);
                $products = $this->getProductsAdditionalData([$data], $params);
                $data = reset($products);
            }
        } else {
            $items_per_page = $this->safeGet($params, 'items_per_page', Registry::get('settings.Appearance.admin_elements_per_page'));
            list($products, $search) = fn_get_products($params, $items_per_page, $lang_code);

            $products = $this->getProductsAdditionalData($products, $search);

            $data = [
                'products' => array_values($products),
                'params'   => $search,
            ];
        }

        return [
            'status' => $status,
            'data'   => $data,
        ];
    }

    /**
     * @inheritDoc
     */
    public function create($params)
    {
        if (UserTypes::isVendor($this->auth['user_type'])) {
            return [
                'status' => Response::STATUS_FORBIDDEN
            ];
        }

        $params['company_id'] = 0;

        return parent::create($params);
    }

    /**
     * @inheritDoc
     */
    public function update($id, $params)
    {
        $product_id_map = ServiceProvider::getProductIdMap();

        if (!$product_id_map->isMasterProduct($id)) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data' => __('master_products.api_not_master_product')
            ];
        }

        return parent::update($id, $params);
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        $product_id_map = ServiceProvider::getProductIdMap();

        if (!$product_id_map->isMasterProduct($id)) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data' => __('master_products.api_not_master_product')
            ];
        }

        return parent::delete($id);
    }

    /**
     * @inheritDoc
     */
    public function childEntities()
    {
        return [
            'master_product_offers',
        ];
    }
}
