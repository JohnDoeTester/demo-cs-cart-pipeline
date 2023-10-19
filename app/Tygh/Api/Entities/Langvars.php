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
use Tygh\Languages\Values as LanguageValues;
use Tygh\Registry;

class Langvars extends AEntity
{
    protected function getParentLanguageCode(array $params)
    {
        $result = $this->getLanguageCode($params, CART_LANGUAGE);

        if ($this->getParentName() == 'languages') {
            $parent_language = $this->getParentData();
            if (!empty($parent_language)) {
                $result = $parent_language['lang_code'];
            }
        }

        return $result;
    }

    public function index($id = '', $params = array())
    {
        $status = Response::STATUS_OK;

        if (fn_allowed_for('MULTIVENDOR:ULTIMATE') && !empty($this->auth['storefront_id'])) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => []
            ];
        }

        $lang_code = $this->getParentLanguageCode($params);

        if ($id) {
            $data = array(
                'lang_code' => $lang_code,
                'name' => $id,
                'value' => LanguageValues::getLangVar($id, $lang_code),
            );
        } else {
            $items_per_page = $this->safeGet($params, 'items_per_page', Registry::get('settings.Appearance.admin_elements_per_page'));

            if (!$lang_code) {
                $lang_code = DESCR_SL;
            }

            $params['lang_code'] = $lang_code;
            list($data['langvars'], $data['params']) = LanguageValues::getVariables($params, $items_per_page, $lang_code);
        }

        if (!$data) {
            $status = Response::STATUS_NOT_FOUND;
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function create($params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = array();
        $valid_params = true;

        if (fn_allowed_for('MULTIVENDOR:ULTIMATE') && !empty($this->auth['storefront_id'])) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => $data
            ];
        }

        if (empty($params['name'])) {
            $data['message'] = __('api_required_field', array(
                '[field]' => 'name'
            ));
            $valid_params = false;
        }

        if ($valid_params && $lang_code = $this->getParentLanguageCode($params)) {
            $res = LanguageValues::updateLangVar(array($params), $lang_code);

            if ($res) {
                $status = Response::STATUS_CREATED;
                $data = array(
                    'name' => $params['name'],
                );
            }
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function update($id, $params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = array();
        $params['name'] = $id;

        if (fn_allowed_for('MULTIVENDOR:ULTIMATE') && !empty($this->auth['storefront_id'])) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => $data
            ];
        }

        /**
         * Lang code is required.
         */
        if ($lang_code = $this->getParentLanguageCode($params)) {
            $res = LanguageValues::updateLangVar(array($params), $lang_code);

            if ($res) {
                $status = Response::STATUS_OK;
                $data = array(
                    'name' => $params['name'],
                );
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

        if (fn_allowed_for('MULTIVENDOR:ULTIMATE') && !empty($this->auth['storefront_id'])) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => $data
            ];
        }

        $res = LanguageValues::deleteVariables(array($id));

        if ($res) {
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
            'create' => 'manage_languages',
            'update' => 'manage_languages',
            'delete' => 'manage_languages',
            'index'  => 'view_languages'
        );
    }

    public function childEntities()
    {
        return array(
            'products'
        );
    }

    public function isValidIdentifier($id)
    {
        return preg_match('/^[0-9a-z_\-]+$/', $id);
    }
}
