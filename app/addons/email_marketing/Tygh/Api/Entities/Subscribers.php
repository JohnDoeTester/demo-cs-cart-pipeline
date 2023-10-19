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

class Subscribers extends AEntity
{

    public function index($id = 0, $params = array())
    {
        if (empty($id)) {
            list($subscribers, $search) = fn_em_get_subscribers($params, Registry::get('settings.Appearance.admin_elements_per_page'));
            $data['subscribers'] = $subscribers;
            $data['params'] = $search;

        } else {
            list($data, $search) = fn_em_get_subscribers(array(
                'subscriber_id' => $id
            ), Registry::get('settings.Appearance.admin_elements_per_page'));

            if (!empty($data)) {
                $data = array_pop($data);
            }
        }

        if (empty($data)) {
            $status = Response::STATUS_NOT_FOUND;
        } else {
            $status = Response::STATUS_OK;
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
        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);

        unset($params['subscriber_id']);

        $this->correctCompanyID($params);

        $subscriber_id = fn_em_update_subscriber($params, 0, true, $lang_code, $params['company_id']);

        if ($subscriber_id) {
            $status = Response::STATUS_CREATED;
            $data = array(
                'subscriber_id' => $subscriber_id,
            );
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
        unset($params['subscriber_id']);

        $lang_code = $this->safeGet($params, 'lang_code', DEFAULT_LANGUAGE);
        $subscriber_id = fn_em_update_subscriber($params, $id, true, $lang_code);

        if ($subscriber_id) {
            $status = Response::STATUS_OK;
            $data = array(
                'subscriber_id' => $subscriber_id
            );
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function delete($id)
    {
        $data = array();
        $status = Response::STATUS_BAD_REQUEST;

        $subscriber_data = fn_em_get_subscriber_data('', $id);
        if (!empty($subscriber_data)) {
            if (fn_em_delete_subscribers($subscriber_data['subscriber_id'])) {
                $status = Response::STATUS_NO_CONTENT;
            }
        } else {
            $status = Response::STATUS_NOT_FOUND;
        }

        return array(
            'status' => $status,
            'data' => $data
        );
    }

    public function privileges()
    {
        return array(
            'create' => 'manage_email_marketing',
            'update' => 'manage_email_marketing',
            'delete' => 'manage_email_marketing',
            'index'  => 'view_email_marketing'
        );
    }

    /**
     * Determines company_id for the array of subscriber's parameters to assign
     * the subscriber to the correct storefront or vendor.
     *
     * @param array $params The array of subscriber's parameters.
     *
     * @return void
     */
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
