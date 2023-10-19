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

use Tygh\Addons\StorefrontRestApi\ASraEntity;
use Tygh\Api\Response;

class SraOneTimePasswords extends ASraEntity
{
    /** @inheritDoc */
    public function index($id = '', $params = [])
    {
        return [
            'status' => Response::STATUS_FORBIDDEN,
        ];
    }

    /** @inheritDoc */
    public function create($params)
    {
        $email = $this->safeGet($params, 'email', null);
        if (!$email) {
            return [
                'status' => Response::STATUS_BAD_REQUEST,
                'data'   => [
                    'errors' => [
                        __(
                            'api_required_field',
                            [
                                '[field]' => 'email',
                            ]
                        ),
                    ],
                ],
            ];
        }

        $user_id = (int) fn_is_user_exists(0, ['email' => $email]);
        if (!$user_id) {
            return [
                'status' => Response::STATUS_NOT_FOUND,
            ];
        }

        fn_user_send_otp($user_id);

        return [
            'status' => Response::STATUS_CREATED,
        ];
    }

    /** @inheritDoc */
    public function update($id, $params)
    {
        return [
            'status' => Response::STATUS_FORBIDDEN,
        ];
    }

    /** @inheritDoc */
    public function delete($id)
    {
        return [
            'status' => Response::STATUS_FORBIDDEN,
        ];
    }

    /** @inheritDoc */
    public function privileges()
    {
        return [
            'index'  => false,
            'create' => true,
            'update' => false,
            'delete' => false,
        ];
    }

    /** @inheritDoc */
    public function privilegesCustomer()
    {
        return [
            'index'  => false,
            'create' => true,
            'update' => false,
            'delete' => false,
        ];
    }
}
