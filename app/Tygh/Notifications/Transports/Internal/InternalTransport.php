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

namespace Tygh\Notifications\Transports\Internal;

use Tygh\Exceptions\DeveloperException;
use Tygh\Notifications\Transports\BaseMessageSchema;
use Tygh\Notifications\Transports\ITransport;

/**
 * Class InternalTransport implements a transport that creates notifications in the Notifications center
 * based on an event message.
 *
 * @package Tygh\Events\Transports
 */
class InternalTransport implements ITransport
{
    /**
     * @var \Tygh\NotificationsCenter\NotificationsCenter
     */
    protected $notifications_center;

    /**
     * @var \Tygh\Database\Connection
     */
    protected $db;

    /**
     * @var \Tygh\NotificationsCenter\IFactory
     */
    protected $factory;

    /**
     * @var \Tygh\Notifications\Transports\Internal\ReceiverFinderFactory
     */
    protected $receiver_finder_factory;

    public function __construct(
        $notifications_center,
        ReceiverFinderFactory $receiver_finder_factory
    ) {
        $this->notifications_center = $notifications_center;
        $this->receiver_finder_factory = $receiver_finder_factory;
    }

    public static function getId()
    {
        return 'internal';
    }

    /**
     * @inheritDoc
     */
    public function process(BaseMessageSchema $schema, array $receiver_search_conditions)
    {
        if (!$schema instanceof InternalMessageSchema) {
            throw new DeveloperException('Input data should be instance of InternalMessageSchema');
        }

        $receivers = $this->getReceivers($receiver_search_conditions, $schema);

        foreach ($receivers as $user_id => $area) {
            $notificaion_data = array_filter([
                'user_id'       => $user_id,
                'title'         => $schema->title,
                'message'       => $schema->message,
                'severity'      => $schema->severity,
                'section'       => $schema->section,
                'tag'           => $schema->tag,
                'area'          => $area,
                'action_url'    => $schema->action_url,
                'is_read'       => $schema->is_read,
                'timestamp'     => $schema->timestamp,
                'language_code' => $schema->language_code,
                'template_code' => $schema->template_code,
                'data'          => $schema->data,
            ]);

            $this->notifications_center->add($notificaion_data);
        }

        return true;
    }

    /**
     * Gets message receivers.
     *
     * @param \Tygh\Notifications\Receivers\SearchCondition[]               $receiver_search_conditions Receiver search conditions.
     * @param \Tygh\Notifications\Transports\Internal\InternalMessageSchema $schema                     Internal message schema
     *
     * @return array<int, string>
     */
    protected function getReceivers(array $receiver_search_conditions, InternalMessageSchema $schema)
    {
        $users = [];

        foreach ($receiver_search_conditions as $condition) {
            $finder = $this->receiver_finder_factory->get($condition->getMethod());
            $users += $finder->find($condition->getCriterion(), $schema);
        }

        return $users;
    }
}
