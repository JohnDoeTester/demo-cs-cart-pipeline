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
use Tygh\Registry;

class Statuses extends AEntity
{
    public function index($id = '', $params = [])
    {
        if (fn_allowed_for('MULTIVENDOR:ULTIMATE') && !empty($this->auth['storefront_id'])) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => []
            ];
        }

        $lang_code = $this->getLanguageCode($params);

        $type = (!empty($params['type'])) ? $params['type'] : STATUSES_ORDER;

        if (!empty($id)) {
            $data = fn_get_status_by_id($id, $lang_code);

            if (empty($data)) {
                $status = Response::STATUS_NOT_FOUND;
            } else {
                $status = Response::STATUS_OK;
            }
        } else {
            $items_per_page = $this->safeGet($params, 'items_per_page', Registry::get('settings.Appearance.admin_elements_per_page'));
            $page = $this->safeGet($params, 'page', 1);

            $data = fn_get_statuses($type, [], false, false, $lang_code);
            $data = array_values($data);
            $total_items_count = count($data);

            if ($items_per_page) {
                $data = array_slice($data, ($page - 1) * $items_per_page, $items_per_page);
            }

            $data = [
                'statuses' => $data,
                'params'   => [
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
        $valid_params = true;

        if (fn_allowed_for('MULTIVENDOR:ULTIMATE') && !empty($this->auth['storefront_id'])) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => $data
            ];
        }

        if (empty($params['type'])) {
            $params['type'] = STATUSES_ORDER;
        }

        if (empty($params['description'])) {
            $data['message'] = __('api_required_field', [
                '[field]' => 'description'
            ]);
            $valid_params = false;
        }

        if ($valid_params == true) {
            unset($params['status_id']);
            unset($params['status']);
            $status_name = fn_update_status('', $params, $params['type']);
            $status_data = fn_get_status_data($status_name, $params['type']);

            if ($status_data) {
                $status = Response::STATUS_CREATED;
                $data = [
                    'status_id' => $status_data['status_id']
                ];
            }
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

        if (fn_allowed_for('MULTIVENDOR:ULTIMATE') && !empty($this->auth['storefront_id'])) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => $data
            ];
        }

        unset($params['status_id']);

        $lang_code = $this->getLanguageCode($params);
        $status_data = fn_get_status_by_id($id, $lang_code);

        if (empty($status_data)) {
            $status = Response::STATUS_NOT_FOUND;
        } else {

            $params['status'] = $status_data['status'];
            $status_name = fn_update_status($status_data['status'], $params, $status_data['type']);

            if ($status_name) {
                $status = Response::STATUS_OK;
                $data = [
                    'status_id' => $id
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
        $status = Response::STATUS_BAD_REQUEST;

        if (fn_allowed_for('MULTIVENDOR:ULTIMATE') && !empty($this->auth['storefront_id'])) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => $data
            ];
        }

        $status_data = fn_get_status_by_id($id, DEFAULT_LANGUAGE);

        if (empty($status_data)) {
            $status = Response::STATUS_NOT_FOUND;

        } else {
            if (fn_delete_status($status_data['status'], $status_data['type'])) {
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
            'create' => 'manage_order_statuses',
            'update' => 'manage_order_statuses',
            'delete' => 'manage_order_statuses',
            'index'  => 'manage_order_statuses'
        ];
    }
}
