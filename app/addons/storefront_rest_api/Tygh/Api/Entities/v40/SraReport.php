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

use Exception;
use Tygh\Api\Response;
use Tygh\Addons\StorefrontRestApi\ASraEntity;

class SraReport extends ASraEntity
{

    /** @inheritdoc */
    public function index($id = '', $params = [])
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /**
     * @inheritDoc
     */
    public function create($params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = [];
        $report_object_id = $this->safeGet($params, 'report_object_id', null);
        $report_type = $this->safeGet($params, 'report_type', null);
        $message = $this->safeGet($params, 'message', null);

        if (!$message) {
            $data['message'] = __('api_required_field', [
                '[field]' => 'message'
            ]);

            return [
                'status' => $status,
                'data' => $data
            ];
        }

        try {
            fn_mobile_app_add_notification_about_report(
                $report_object_id,
                $report_type,
                $message,
                $this->auth
            );

            $status = Response::STATUS_OK;
        } catch (Exception $e) {
            $data['message'] = $e->getMessage();
        }

        return [
            'status' => $status,
            'data' => $data
        ];
    }

    /**
     * @inheritDoc
     */
    public function update($id, $params)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /** @inheritdoc */
    public function privilegesCustomer()
    {
        return [
            'index'  => false,
            'create' => $this->auth['is_token_auth'],
            'update' => false,
            'delete' => false,
        ];
    }
}
