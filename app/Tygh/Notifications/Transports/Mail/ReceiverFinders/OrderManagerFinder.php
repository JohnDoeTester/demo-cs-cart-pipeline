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

class OrderManagerFinder implements ReceiverFinderInterface
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
        $order_id = $this->getOrderId($message_schema->data);
        if (!$order_id) {
            return [];
        }

        $conditions = [
            'users.status'    => ObjectStatuses::ACTIVE,
            'orders.order_id' => $order_id,
        ];

        return $this->db->getColumn(
            'SELECT users.email'
            . ' FROM ?:users AS users'
            . ' INNER JOIN ?:orders AS orders ON orders.issuer_id = users.user_id'
            . ' WHERE ?w'
            . ' GROUP BY users.user_id',
            $conditions
        );
    }

    protected function getOrderId(array $data)
    {
        $order_id = null;
        if (isset($data['order_id'])) {
            $order_id = (int) $data['order_id'];
        } elseif (isset($data['order_info']['order_id'])) {
            $order_id = (int) $data['order_info']['order_id'];
        }

        return $order_id;
    }
}
