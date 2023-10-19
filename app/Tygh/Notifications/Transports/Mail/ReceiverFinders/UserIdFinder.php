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

class UserIdFinder implements ReceiverFinderInterface
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
        $conditions = [
            'users.status'  => ObjectStatuses::ACTIVE,
            'users.user_id' => (int) $criterion,
        ];

        if ($message_schema->to_company_id !== null) {
            $conditions['users.company_id'] = [0, $message_schema->to_company_id];
        }

        if (fn_allowed_for('MULTIVENDOR') && !empty($message_schema->to_storefront_id)) {
            $conditions['users.storefront_id'] = [0, $message_schema->to_storefront_id];
        }

        return $this->db->getColumn(
            'SELECT users.email'
            . ' FROM ?:users AS users'
            . ' WHERE ?w',
            $conditions
        );
    }
}
