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

use Tygh\Registry;
use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Tygh;

class Shippings extends AEntity
{

    public function index($id = 0, $params = [])
    {
        $lang_code = $this->getLanguageCode($params);

        if (!empty($id)) {
            $data = fn_get_shipping_info($id, $lang_code);

            if (empty($data)) {
                $status = Response::STATUS_NOT_FOUND;
            } else {
                $status = Response::STATUS_OK;
            }

        } else {
            $items_per_page = $this->safeGet($params, 'items_per_page', Registry::get('settings.Appearance.admin_elements_per_page'));
            $page = $this->safeGet($params, 'page', 1);

            $company_id = fn_get_runtime_company_id() ?: null;
            $storefront_id = null;
            $auth = Tygh::$app['session']['auth'];
            if (
                fn_allowed_for('MULTIVENDOR:ULTIMATE')
                && !empty($auth['storefront_id'])
            ) {
                $storefront_id = $auth['storefront_id'];
            }

            $data = fn_get_available_shippings($company_id, false, $storefront_id);
            $total_items_count = count($data);

            if ($items_per_page) {
                $data = array_slice($data, ($page - 1) * $items_per_page, $items_per_page);
            }

            $data = [
                'shippings' => $data,
                'params'    => [
                    'items_per_page' => $items_per_page,
                    'page'           => $page,
                    'total_items'    => $total_items_count,
                ],
            ];
            $status = Response::STATUS_OK;
        }

        return [
            'status' => $status,
            'data'   => $data
        ];
    }

    public function create($params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = [];

        $this->correctCompanyID($params);

        unset($params['shipping_id']);

        if (!empty($params['shipping'])) {

            $shipping_id = fn_update_shipping($params, 0);

            if ($shipping_id) {
                $status = Response::STATUS_CREATED;
                $data = [
                    'shipping_id' => $shipping_id,
                ];
            }
        } else {
            $data['message'] = __('api_required_field', [
                '[field]' => 'shipping'
            ]);
        }

        return [
            'status' => $status,
            'data'   => $data
        ];
    }

    public function update($id, $params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = [];

        $lang_code = $this->getLanguageCode($params);

        $this->correctCompanyID($params);
        unset($params['shipping_id']);

        if (fn_check_company_id('shippings', 'shipping_id', $id)) {
            $shipping_id = fn_update_shipping($params, $id, $lang_code);

            if ($shipping_id) {
                $status = Response::STATUS_OK;
                $data = [
                    'shipping_id' => $shipping_id
                ];
            }
        }

        return [
            'status' => $status,
            'data'   => $data
        ];
    }

    public function delete($id)
    {
        $data = [];
        $status = Response::STATUS_NOT_FOUND;

        if (fn_check_company_id('shippings', 'shipping_id', $id)) {
            if (fn_delete_shipping($id)) {
                $status = Response::STATUS_NO_CONTENT;
            }
        }

        return [
            'status' => $status,
            'data'   => $data
        ];
    }

    public function privileges()
    {
        return [
            'create' => 'manage_shipping',
            'update' => 'manage_shipping',
            'delete' => 'manage_shipping',
            'index'  => 'view_shipping'
        ];
    }

    public function correctCompanyID(&$params)
    {
        if (fn_allowed_for('ULTIMATE')) {
            if (empty($params['company_id'])) {
                $params['company_id'] = fn_get_default_company_id();
            }
        } elseif (fn_allowed_for('MULTIVENDOR')) {
            $runtime_company_id = Registry::get('runtime.company_id');

            // Root admin can set any company ID to the object
            // Vendor admin can't handle company ID
            if ($runtime_company_id != 0 || !isset($params['company_id'])) {
                $params['company_id'] = $runtime_company_id;
            }
        }
    }
}
