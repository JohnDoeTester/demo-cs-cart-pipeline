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

use Tygh\Database\Connection;

class DatabaseStorage implements StorageInterface
{
    /**
     * @var \Tygh\Database\Connection
     */
    private $db;

    /**
     * @param \Tygh\Database\Connection $db Database connection instance
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /** @inheritdoc */
    public function setId($user_id, $external_user_id)
    {
        $this->db->query('UPDATE ?:users SET helpdesk_user_id = ?i WHERE user_id = ?i', $external_user_id, $user_id);
    }

    /** @inheritdoc */
    public function resetId($user_id)
    {
        $this->setId($user_id, 0);
    }

    /** @inheritdoc */
    public function getId($user_id)
    {
        $id = $this->db->query('SELECT helpdesk_user_id FROM ?:users WHERE user_id = ?i', $user_id);

        return $id
            ? (int) $id
            : null;
    }
}
