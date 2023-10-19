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

use Tygh\Enum\UserTypes;
use Tygh\Registry;
use Tygh\Api\Response;
use Tygh\Addons\StorefrontRestApi\ASraEntity;

class SraAccountRemovalRequests extends ASraEntity
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
        $result = false;
        $status = Response::STATUS_BAD_REQUEST;

        if (UserTypes::isCustomer($this->auth['user_type'])) {
            $comment = isset($params['comment']) ? $params['comment'] : '';
            $user_info = fn_get_user_short_info($this->auth['user_id']);
            $result = fn_send_anonymization_request_email($user_info, $comment);
        }

        if ($result) {
            $status = Response::STATUS_OK;
            $message = __('user_action_request_success');
            $this->logoutUser($params);
        } else {
            $message = __(
                'user_action_request_fail',
                [
                    '[email]' => Registry::get('settings.Company.company_users_department')
                ]
            );
        }

        return [
            'status' => $status,
            'data' => [
                'message' => $message,
            ]
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

    /**
     * Deletes ekey
     *
     * @param array<string, string> $params Request params
     *
     * @return void
     */
    private function logoutUser(array $params)
    {
        if (empty($params['ekey'])) {
            return;
        }
        fn_delete_ekey($params['ekey']);
    }
}
