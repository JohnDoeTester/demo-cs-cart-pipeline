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

namespace Tygh\Helpdesk\AuthStorage;

use Tygh\Registry;
use Tygh\Web\Session;

class RuntimeStorage implements StorageInterface
{
    /**
     * @var \Tygh\Web\Session
     */
    protected $session;

    /**
     * @param \Tygh\Web\Session $session Session instance
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /** @inheritdoc */
    public function setId($user_id, $external_user_id)
    {
        if ((int) $this->session['auth']['user_id'] === $user_id) {
            $this->session['auth']['helpdesk_user_id'] = $external_user_id;
        }

        if ((int) Registry::get('user_info.user_id') === $user_id) {
            Registry::set('user_info.helpdesk_user_id', $external_user_id);
        }

        return;
    }

    /** @inheritdoc */
    public function resetId($user_id)
    {
        $this->setId($user_id, 0);
    }

    /** @inheritdoc */
    public function getId($user_id)
    {
        if (
            ((int) $this->session['auth']['user_id'] === $user_id)
            && isset($this->session['auth']['helpdesk_user_id'])
        ) {
            return (int) $this->session['auth']['helpdesk_user_id'];
        }

        return null;
    }
}
