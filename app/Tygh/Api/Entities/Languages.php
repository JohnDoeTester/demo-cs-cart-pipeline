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
use Tygh\Languages\Languages as LLanguages;

class Languages extends AEntity
{
    public function index($id = 0, $params = [])
    {
        $status = Response::STATUS_OK;
        $data = LLanguages::getAll();

        if ($data && $id) {
            foreach ($data as $lang_data) {
                if ($lang_data['lang_id'] == $id) {
                    $data = $lang_data;
                    break;
                } else {
                    $data = [];
                }
            }
        } elseif ($data && $lang_code = $this->safeGet($params, 'lang_code', '')) {
            if (!empty($data[$lang_code])) {
                $data = $data[$lang_code];
            } else {
                $status = Response::STATUS_NOT_FOUND;
                $data = [];
            }
        } else {
            $items_per_page = $this->safeGet($params, 'items_per_page', Registry::get('settings.Appearance.admin_elements_per_page'));
            $page = $this->safeGet($params, 'page', 1);
            $total_items_count = count($data);

            if ($items_per_page) {
                $data = array_slice($data, ($page - 1) * $items_per_page, $items_per_page);
            }

            $data = [
                'languages' => $data,
                'params'    => [
                    'items_per_page' => $items_per_page,
                    'page'           => $page,
                    'total_items'    => $total_items_count,
                ],
            ];
        }

        if (!$data) {
            $status = Response::STATUS_NOT_FOUND;
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

        if (fn_allowed_for('MULTIVENDOR:ULTIMATE') && !empty($this->auth['storefront_id'])) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => $data
            ];
        }

        unset($params['lang_id']);
        $lang_id = LLanguages::update($params, 0);

        if ($lang_id) {
            $status = Response::STATUS_CREATED;
            $data = [
                'lang_id' => $lang_id,
            ];
        }

        return [
            'status' => $status,
            'data'   => $data
        ];
    }

    public function update($id, $params)
    {
        $data = [];
        $status = Response::STATUS_BAD_REQUEST;

        if (fn_allowed_for('MULTIVENDOR:ULTIMATE') && !empty($this->auth['storefront_id'])) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => $data
            ];
        }

        unset($params['lang_id']);

        $lang_id = LLanguages::update($params, $id);

        if ($lang_id) {
            $status = Response::STATUS_OK;
            $data = [
                'lang_id' => $lang_id
            ];
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

        if (LLanguages::deleteLanguages(array($id))) {
            $status = Response::STATUS_NO_CONTENT;
        } elseif (!fn_notification_exists('extra', 'language_is_default')) {
            $status = Response::STATUS_NOT_FOUND;
        }

        return [
            'status' => $status,
            'data'   => $data
        ];
    }

    public function privileges()
    {
        return [
            'create' => 'manage_languages',
            'update' => 'manage_languages',
            'delete' => 'manage_languages',
            'index'  => 'view_languages'
        ];
    }

    public function childEntities()
    {
        return [
            'langvars'
        ];
    }
}
