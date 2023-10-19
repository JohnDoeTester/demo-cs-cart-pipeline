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

use Tygh\Api\Entities\AuthTokens;
use Tygh\Api\Response;

/**
 * Class SraAuthTokens
 *
 * @package Tygh\Api\Entities
 */
class SraAuthTokens extends AuthTokens
{
    /** @inheritdoc */
    public function create($params)
    {
        $ekey = $this->safeGet($params, 'ekey', null);
        $one_time_password = $this->safeGet($params, 'one_time_password', null);
        $email = $this->safeGet($params, 'email', null);

        if (!$ekey && !$one_time_password) {
            return parent::create($params);
        }

        $status = Response::STATUS_NOT_FOUND;
        $data = [];

        $user_id = null;
        if ($ekey) {
            $user_id = $this->getUserIdByEkey($ekey);
        } elseif ($one_time_password && $email) {
            $user_id = $this->getUserIdByOneTimePassword($email, $one_time_password);
        }

        if ($user_id) {
            list($token, $expiry_time) = fn_get_user_auth_token($user_id);

            $status = Response::STATUS_CREATED;
            $data = [
                'token' => $token,
                'ttl'   => $expiry_time - TIME,
            ];
        }

        return [
            'status' => $status,
            'data'   => $data,
        ];
    }

    /**
     * Gets user ID by their ekey.
     *
     * @param string $ekey Ekey specified in the API request
     *
     * @return int|null User ID when OTP is correct, null otherwise
     */
    private function getUserIdByEkey($ekey)
    {
        $user_id = fn_get_object_by_ekey($ekey, 'U');
        if ($user_id) {
            return (int) $user_id;
        }

        return null;
    }

    /**
     * Gets user ID by their email and one-time password.
     *
     * @param string $email             E-mail address
     * @param string $one_time_password One-time password specified in the API request
     *
     * @return int|null User ID when OTP is correct, null otherwise
     */
    private function getUserIdByOneTimePassword($email, $one_time_password)
    {
        $user_id = (int) fn_is_user_exists(0, ['email' => $email]);
        if (!$user_id) {
            return null;
        }

        if (fn_user_verify_otp($user_id, $one_time_password)) {
            fn_user_delete_otp($user_id);

            return $user_id;
        }

        return null;
    }
}
