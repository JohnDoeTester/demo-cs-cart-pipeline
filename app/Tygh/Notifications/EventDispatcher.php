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

namespace Tygh\Notifications;

use Tygh\Exceptions\DeveloperException;
use Tygh\Notifications\DataProviders\BaseDataProvider;
use Tygh\Notifications\DataProviders\IDataProvider;
use Tygh\Notifications\EventIdProviders\IProvider;
use Tygh\Notifications\Receivers\SearchCondition;
use Tygh\Notifications\Settings\Ruleset;
use Tygh\Notifications\Transports\ITransportFactory;
use Tygh\Notifications\Transports\BaseMessageSchema;

/**
 * Class EventDispatcher provides event dispatching functionality.
 *
 * @package Tygh\Events
 */
class EventDispatcher
{
    /**
     * Schema of events from ('notifications', 'events')
     *
     * @var array
     */
    protected $events_schema;

    /**
     * User notifications settings from DB
     *
     * @var array
     */
    protected $notification_settings;

    /**
     * @var \Tygh\Notifications\Transports\ITransportFactory
     */
    protected $transport_factory;

    protected $receiver_search_conditions_param = 'receiver_search_conditions';

    /**
     * @var array[]
     */
    protected $dispatched_events = [];

    public function __construct(
        array $events_schema,
        array $notification_settings,
        ITransportFactory $transport_factory
    ) {
        $this->events_schema = $events_schema;
        $this->notification_settings = $notification_settings;
        $this->transport_factory = $transport_factory;
    }

    /**
     * @param string    $event_id
     * @param array     $data
     * @param Ruleset   $ruleset
     * @param IProvider $id_provider
     *
     * @throws \Tygh\Exceptions\DeveloperException
     */
    public function dispatch($event_id, array $data, Ruleset $ruleset = null, IProvider $id_provider = null)
    {
        if (!isset($this->events_schema[$event_id])) {
            return;
        }

        $notification_settings = $this->extractEventNotificationSettings($event_id, $ruleset);
        $data_provider = $this->getDataProvider($event_id, $data);

        foreach ($this->events_schema[$event_id]['receivers'] as $receiver => $transports) {
            // TODO Do not retrieve data if all receiver notifications are disabled
            $data = $data_provider->get($receiver);
            foreach ($transports as $transport_id => $message_schema) {
                if (empty($notification_settings['receivers'][$receiver][$transport_id])) {
                    continue;
                }

                if ($id_provider) {
                    if ($this->isDispatched($id_provider->getId(), $transport_id, $receiver)) {
                        continue;
                    }
                    $this->saveDispatched($id_provider->getId(), $transport_id, $receiver);
                }
                if (!$message_schema instanceof BaseMessageSchema) {
                    throw new DeveloperException(
                        sprintf('Notification event for %s receiver and %s transport was not found',
                            $receiver,
                            $transport_id
                        )
                    );
                }

                $transport = $this->transport_factory->create($transport_id);

                $transport->process(
                    $message_schema->init($data),
                    $this->getReceiverSearchConditions($data, $notification_settings, $receiver)
                );
            }
        }
    }

    /**
     * Checks whether the unique event has been already processed by the transport for the receiver.
     *
     * @param string $id           Unique event ID
     * @param string $transport_id Transport ID
     * @param string $receiver     Receiver ID
     *
     * @return bool
     */
    protected function isDispatched($id, $transport_id, $receiver)
    {
        return isset($this->dispatched_events[$id][$transport_id][$receiver]);
    }

    /**
     * Marks the unique event being already processed.
     *
     * @param string $id           Unique event ID
     * @param string $transport_id Transport ID
     * @param string $receiver     Receiver ID
     */
    protected function saveDispatched($id, $transport_id, $receiver)
    {
        $this->dispatched_events[$id][$transport_id][$receiver] = true;
    }

    /**
     * Extracts notification settings from the passed event data.
     *
     * @param string  $event_id
     * @param Ruleset $ruleset
     *
     * @return array
     */
    protected function extractEventNotificationSettings($event_id, Ruleset $ruleset = null)
    {
        $event_notification_settings = isset($this->notification_settings[$event_id])
            ? $this->notification_settings[$event_id]
            : [];

        if ($ruleset) {
            $event_notification_settings['receivers'] = $ruleset->apply($event_notification_settings['receivers']);
        }

        return $event_notification_settings;
    }

    /**
     * @param string $event_id
     * @param array  $data
     *
     * @return IDataProvider
     */
    protected function getDataProvider($event_id, array $data)
    {
        if (isset($this->events_schema[$event_id]['data_provider']) && is_callable($this->events_schema[$event_id]['data_provider'])) {
            $data = call_user_func($this->events_schema[$event_id]['data_provider'], $data);

            if ($data instanceof IDataProvider) {
                return $data;
            }
        }

        return new BaseDataProvider($data);
    }

    protected function convertLegacyReceiverSearchConditionsParameters(array $data)
    {
        if (!isset($data['recipient_search_method'])) {
            return $data;
        }

        if (isset($data['recipient_search_critieria'])) {
            foreach ((array) $data['recipient_search_critieria'] as $criterion) {
                $data[$this->receiver_search_conditions_param][] = new SearchCondition($data['recipient_search_method'], $criterion);
            }
        }

        if (isset($data['recipient_search_criteria'])) {
            foreach ((array) $data['recipient_search_criteria'] as $criterion) {
                $data[$this->receiver_search_conditions_param][] = new SearchCondition($data['recipient_search_method'], $criterion);
            }
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return \Tygh\Notifications\Receivers\SearchCondition[]
     */
    protected function getReceiverSearchConditionsFromMessageData(array $data)
    {
        if (!isset($data[$this->receiver_search_conditions_param])) {
            $data[$this->receiver_search_conditions_param] = [];
        }

        $data = $this->convertLegacyReceiverSearchConditionsParameters($data);

        foreach ($data[$this->receiver_search_conditions_param] as &$condition) {
            if (!$condition instanceof SearchCondition) {
                $condition = SearchCondition::makeOne($condition);
            }
        }
        unset($condition);

        if ($data[$this->receiver_search_conditions_param]) {
            return $data[$this->receiver_search_conditions_param];
        }

        return [];
    }

    /**
     * @param array $notification_settings
     * @param string      $receiver
     *
     * @return \Tygh\Notifications\Receivers\SearchCondition[]
     */
    protected function getReceiverSearchConditionsFromSettings(array $notification_settings, $receiver)
    {
        return isset($notification_settings[$this->receiver_search_conditions_param][$receiver])
            ? $notification_settings[$this->receiver_search_conditions_param][$receiver]
            : [];
    }

    /**
     * @param \Tygh\Notifications\Data $data
     * @param array $notification_settings
     * @param string      $receiver
     *
     * @return \Tygh\Notifications\Receivers\SearchCondition[]
     */
    protected function getReceiverSearchConditions(Data $data, array $notification_settings, $receiver)
    {
        return $this->getReceiverSearchConditionsFromMessageData($data->toArray())
            ?: $this->getReceiverSearchConditionsFromSettings($notification_settings, $receiver);
    }
}
