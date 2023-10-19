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

/**
 * Class SraRecoverPassword
 *
 * @package Tygh\Api\Entities\v40
 */
class SraRecoverPassword extends ASraEntity
{
    /** @inheritdoc */
    public function index($id = '', $params = [])
    {
        $status = Response::STATUS_BAD_REQUEST;

        if ($id) {
            $status = Response::STATUS_NOT_FOUND;
            $ekey = fn_get_ekeys([
                'ekey'        => $id,
                'object_type' => 'U'
            ]);

            if ($ekey) {
                $status = Response::STATUS_OK;
            }
        }

        return [
            'status' => $status
        ];
    }

    /** @inheritdoc */
    public function create($params)
    {
        $status = Response::STATUS_BAD_REQUEST;

        $email = $this->safeGet($params, 'email', null);

        if ($email && fn_recover_password_generate_key($email)) {
            $status = Response::STATUS_OK;
        }

        return [
            'status' => $status
        ];
    }

    /** @inheritdoc */
    public function update($id, $params)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED
        ];
    }

    /** @inheritdoc */
    public function delete($id)
    {
        $status = Response::STATUS_BAD_REQUEST;

        if ($id) {
            fn_delete_ekey($id, 'U');
            $status = Response::STATUS_NO_CONTENT;
        }

        return [
            'status' => $status
        ];
    }

    /** @inheritdoc */
    public function privilegesCustomer()
    {
        return [
            'index'  => true,
            'create' => true,
            'update' => false,
            'delete' => true
        ];
    }

    /**
     * @inheritDoc
     */
    public function isValidIdentifier($id)
    {
        return is_string($id);
    }
}
