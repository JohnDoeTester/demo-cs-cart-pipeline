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

namespace Tygh\Notifications\Transports\Mail\ReceiverFinders;

use Tygh\Database\Connection;
use Tygh\Enum\ObjectStatuses;
use Tygh\Notifications\Transports\Mail\MailMessageSchema;

class VendorOwnerFinder implements ReceiverFinderInterface
{
    /**
     * @var \Tygh\Database\Connection
     */
    protected $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @inheritDoc
     */
    public function find($criterion, MailMessageSchema $message_schema)
    {
        $owner_id = $this->getOwnerId($message_schema);
        if (!$owner_id) {
            return [];
        }

        $conditions = [
            'users.status'  => ObjectStatuses::ACTIVE,
            'users.user_id' => $owner_id,
        ];

        return $this->db->getColumn(
            'SELECT users.email'
            . ' FROM ?:users AS users'
            . ' WHERE ?w',
            $conditions
        );
    }

    /**
     * Gets vendor owner user ID.
     *
     * @param \Tygh\Notifications\Transports\Mail\MailMessageSchema $schema Message schema
     *
     * @return int|null
     */
    protected function getOwnerId(MailMessageSchema $schema)
    {
        $company_id = null;
        if ($schema->to_company_id) {
            $company_id = $schema->to_company_id;
        }
        if (!$company_id) {
            return null;
        }

        return fn_get_company_admin_user_id($company_id);
    }
}
