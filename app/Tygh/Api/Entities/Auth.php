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

class Auth extends AEntity
{
    /**
     * @var bool $are_users_shared
     */
    protected $are_users_shared = false;

    /**
     * @var bool $is_company_id_forced
     */
    protected $is_company_id_forced = false;

    /** @inheritdoc */
    public function __construct(array $auth = array(), $area = '')
    {
        parent::__construct($auth, $area);

        $this->are_users_shared = Registry::ifGet('settings.Stores.share_users', 'N') === 'Y';
        $this->is_company_id_forced = (bool) Registry::get('runtime.simple_ultimate');
    }

    /** @inheritdoc */
    public function index($id = '', $params = array())
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
            'data'   => array(),
        );
    }

    /** @inheritdoc */
    public function create($params)
    {
        $status = Response::STATUS_BAD_REQUEST;
        $data = array();

        $email = $this->safeGet($params, 'email', null);
        $user_info = array();

        if ($email) {
            $status = Response::STATUS_NOT_FOUND;

            $user_info = $this->getUserShortInfo($email);
        }

        if ($user_info) {

            $company_id = $this->getUserCompanyId($user_info);

            $notify = $this->safeGet($params, 'notify', false);
            $result = fn_recover_password_generate_key($email, $notify, $company_id, $this->area);
            if ($result) {

                $status = Response::STATUS_CREATED;

                if ($notify) {
                    $data = array(
                        'message' => __('text_password_recovery_instructions_sent'),
                    );
                } else {
                    $link = 'auth.ekey_login?ekey=' . $result['key'] . '&company_id=' . $company_id;

                    if ($redirect_url = $this->safeGet($params, 'redirect_url', '')) {
                        $link .= '&redirect_url=' . urlencode($redirect_url);
                    }

                    $data = array(
                        'key'  => $result['key'],
                        'link' => fn_url($link, $result['user_type']),
                    );
                }
            }
        }

        return array(
            'status' => $status,
            'data'   => $data,
        );
    }

    /** @inheritdoc */
    public function update($id, $params)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
            'data'   => array(),
        );
    }

    /** @inheritdoc */
    public function delete($id)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
            'data'   => array(),
        );
    }

    /** @inheritdoc */
    public function privileges()
    {
        return array(
            'index'  => false,
            'create' => 'manage_users',
            'update' => false,
            'delete' => false,
        );
    }

    /**
     * Obtains basic info to determine user-to-company relation.
     * Generally used to determine admin's company ID
     *
     * @param string $email User email
     *
     * @return array Basic relation data: user type, root flag and company ID
     */
    protected function getUserShortInfo($email)
    {
        $user = db_get_row(
            'SELECT user_type, is_root, company_id'
            . ' FROM ?:users'
            . ' WHERE email = ?s',
            $email
        );

        if ($user) {
            $user['company_id'] = is_numeric($user['company_id'])
                ? (int) $user['company_id']
                : $user['company_id'];
        }

        return $user;
    }

    /**
     * Provides company ID from the runtime.
     *
     * @return int|null Company ID
     */
    protected function getCompanyId()
    {
        if ($this->is_company_id_forced) {
            $company_id = Registry::get('runtime.forced_company_id');
        } else {
            $company_id = Registry::get('runtime.company_id');
        }

        return $company_id;
    }

    /**
     * Provides company ID for login key.
     * @see fn_recover_password_generate_key()
     *
     * @param array $user_info User to authenticate
     *
     * @return int|string Company ID
     */
    protected function getUserCompanyId(array $user_info)
    {
        $runtime_company_id = $this->getCompanyId();

        $company_id = '';
        if ($user_info['user_type'] === 'A') {
            if ($runtime_company_id && !$this->is_company_id_forced) {
                $company_id = $runtime_company_id;
            } else {
                $company_id = $user_info['company_id'];
            }
        } elseif (!$this->are_users_shared) {
            if ($runtime_company_id) {
                $company_id = $runtime_company_id;
            } else {
                $company_id = $user_info['company_id'];
            }
        }

        return $company_id;
    }

}
