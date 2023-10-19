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

namespace Tygh\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Enum\ReceiverSearchMethods;
use Tygh\Notifications\EventDispatcher;
use Tygh\Notifications\Settings\Factory;
use Tygh\Notifications\Transports\Internal\InternalTransport;
use Tygh\Notifications\Transports\Internal\ReceiverFinderFactory as InternalReceiverFinderFactory;
use Tygh\Notifications\Transports\Internal\ReceiverFinders\EmailFinder as InternalEmailFinder;
use Tygh\Notifications\Transports\Internal\ReceiverFinders\OrderManagerFinder as InternalOrderManagerFinder;
use Tygh\Notifications\Transports\Internal\ReceiverFinders\UsergroupIdFinder as InternalUsergroupIdFinder;
use Tygh\Notifications\Transports\Internal\ReceiverFinders\UserIdFinder as InternalUserIdFinder;
use Tygh\Notifications\Transports\Internal\ReceiverFinders\VendorOwnerFinder as InternalVendorOwnerFinder;
use Tygh\Notifications\Transports\Internal\ReceiverFinders\VendorEmailFinder as InternalVendorEmailFinder;
use Tygh\Notifications\Transports\Mail\MailTransport;
use Tygh\Notifications\Transports\Mail\ReceiverFinderFactory as MailReceiverFinderFactory;
use Tygh\Notifications\Transports\Mail\ReceiverFinders\EmailFinder as MailEmailFinder;
use Tygh\Notifications\Transports\Mail\ReceiverFinders\OrderManagerFinder as MailOrderManagerFinder;
use Tygh\Notifications\Transports\Mail\ReceiverFinders\UsergroupIdFinder as MailUsergroupIdFinder;
use Tygh\Notifications\Transports\Mail\ReceiverFinders\UserIdFinder as MailUserIdFinder;
use Tygh\Notifications\Transports\Mail\ReceiverFinders\VendorEmailFinder;
use Tygh\Notifications\Transports\Mail\ReceiverFinders\VendorOwnerFinder as MailVendorOwnerFinder;
use Tygh\Notifications\Transports\TransportFactory;
use Tygh\Tygh;

class EventDispatcherProvider implements ServiceProviderInterface
{
    /**
     * Gets event groups schema.
     *
     * @return array
     */
    public static function getEventGroupsSchema()
    {
        return Tygh::$app['event.groups_schema'];
    }

    /**
     * Gets notification settings.
     *
     * @param boolean $with_receivers Whether to obtain readable receiver values
     *
     * @return array
     */
    public static function getNotificationSettings($with_receivers = false)
    {
        return $with_receivers
            ? Tygh::$app['event.notification_settings.with_receivers']
            : Tygh::$app['event.notification_settings'];
    }

    /**
     * @return \Tygh\Notifications\Transports\Internal\ReceiverFinderFactory
     */
    protected static function getInternalReceiverFinderFactory()
    {
        return Tygh::$app['event.transports.internal.receiver_finder_factory'];
    }

    /**
     * @return \Tygh\Notifications\Transports\Mail\ReceiverFinderFactory
     */
    protected static function getMailReceiverFinderFactory()
    {
        return Tygh::$app['event.transports.mail.receiver_finder_factory'];
    }

    /**
     * @param string $search_method
     *
     * @return callable
     */
    protected static function getReceiverSearchConditionResolver($search_method)
    {
        return Tygh::$app['event.receiver_search_condition_resolver.' . $search_method];
    }

    /**
     * Gets event schema.
     *
     * @return array
     */
    public static function getEventsSchema()
    {
        return Tygh::$app['event.events_schema'];
    }

    /**
     * @return EventDispatcher
     */
    public static function getEventDispatcher()
    {
        return Tygh::$app['event.dispatcher'];
    }

    /**
     * @return Factory
     */
    public static function getNotificationSettingsFactory()
    {
        return Tygh::$app['event.notification_settings.factory'];
    }

    /** @inheritdoc */
    public function register(Container $app)
    {
        $app['event.events_schema'] = function (Container $app) {
            $events_schema = fn_get_schema('notifications', 'events');

            $receiver_search_conditions = fn_get_notification_receiver_search_conditions($app['event.receivers_schema']);

            foreach ($events_schema as $event_id => &$event) {
                $group_id = $event['group'];
                $event['receiver_search_conditions'] = [];
                if (isset($receiver_search_conditions['events'][$event_id])) {
                    $event['receiver_search_conditions'] = $receiver_search_conditions['events'][$event_id];
                } elseif (isset($receiver_search_conditions['groups'][$group_id])) {
                    $event['receiver_search_conditions'] = $receiver_search_conditions['groups'][$group_id];
                }
            }
            unset($event);

            return $events_schema;
        };

        $app['event.notification_settings'] = function (Container $app) {
            $notification_settings = static::getEventsSchema();

            $stored_notification_settings = fn_get_notification_settings();

            foreach ($notification_settings as $event_id => &$event) {
                foreach ($event['receivers'] as $receiver_id => &$transports) {
                    foreach ($transports as $transport_id => &$callback) {
                        $callback = isset($stored_notification_settings[$event_id][$receiver_id][$transport_id])
                            ? $stored_notification_settings[$event_id][$receiver_id][$transport_id]
                            : true;
                    }
                }
            }

            return $notification_settings;
        };

        $app['event.notification_settings.with_receivers'] = function (Container $app) {
            $notification_settings = static::getNotificationSettings();

            foreach ($notification_settings as $event_id => &$event) {
                foreach ($event['receiver_search_conditions'] as $receiver_type => $conditions) {
                    /** @var \Tygh\Notifications\Receivers\SearchCondition $condition */
                    foreach ($conditions as $id => $condition) {
                        $resolver = static::getReceiverSearchConditionResolver($condition->getMethod());
                        $value = $resolver($condition->getCriterion());
                        if ($value === null) {
                            unset($event['receiver_search_conditions'][$receiver_type][$id]);
                            continue;
                        }
                        $event['receiver_search_conditions_readable'][$receiver_type][$id] = $value;
                    }
                }
            }

            return $notification_settings;
        };

        $app['event.transports_schema'] = function (Container $app) {
            $schema = static::getEventsSchema();
            $transports = [];
            foreach ($schema as $event) {
                foreach ($event['receivers'] as $list_of_transports) {
                    $transports = array_merge($transports, array_keys($list_of_transports));
                }
            }
            return array_unique($transports);
        };

        $app['event.groups_schema'] = function (Container $app) {
            $events_schema = static::getEventsSchema();
            $groups_schema = fn_get_schema('notifications', 'groups');

            $combined_schema = [];
            foreach ($events_schema as $event) {
                $combined_schema[$event['group']] = $groups_schema['__default'];
            }

            $combined_schema = array_merge($combined_schema, $groups_schema);
            $combined_schema = array_filter($combined_schema);

            return $combined_schema;
        };

        $app['event.dispatcher'] = function (Container $app) {
            $dispatcher = new EventDispatcher(
                static::getEventsSchema(),
                static::getNotificationSettings(),
                $app['event.transport_factory']
            );

            return $dispatcher;
        };

        $app['event.transport_factory'] = function (Container $app) {
            $factory = new TransportFactory($app);

            return $factory;
        };

        $app['event.transports.mail'] = function (Container $app) {
            return new MailTransport(
                $app['mailer'],
                static::getMailReceiverFinderFactory(),
                fn_get_runtime_company_id()
            );
        };

        $app['event.transports.internal'] = function (Container $app) {
            return new InternalTransport(
                $app['notifications_center'],
                static::getInternalReceiverFinderFactory()
            );
        };

        $app['event.receivers_schema'] = function (Container $app) {
            $receivers_schema = array_keys(fn_get_notification_rules(false, true));

            return $receivers_schema;
        };

        $app['event.notification_settings.factory'] = function (Container $app) {
            return new Factory($app['event.receivers_schema'], $app['event.transports_schema']);
        };

        $app['event.receiver_search_condition_resolver.' . ReceiverSearchMethods::USER_ID] = function (Container $app) {
            return function($condition) {
                static $cache = [];
                if (!array_key_exists($condition, $cache)) {
                    $user_info = fn_get_user_info($condition);
                    if ($user_info) {
                        $email = fn_get_user_email($condition, $user_info);
                        $name = fn_get_user_name($condition, $user_info) ?: $email;

                        $cache[$condition] = [
                            'user' => $name,
                            'user_id' => $condition,
                            'email' => $email,
                        ];
                    } else {
                        $cache[$condition] = null;
                    }
                }

                return $cache[$condition];
            };
        };

        $app['event.receiver_search_condition_resolver.' . ReceiverSearchMethods::USERGROUP_ID] = function (Container $app) {
            return function($condition) use ($app) {
                static $cache = [];
                if (!array_key_exists($condition, $cache)) {
                    list(, $params) = fn_get_users(['usergroup_id' => $condition], $app['session']['auth'], 1);
                    $cache[$condition] = [
                        'usergroup' => fn_get_usergroup_name($condition),
                        'usergroup_id' => $condition,
                        'users_count' => (int) $params['total_items'],
                    ];
                }

                return $cache[$condition];
            };
        };

        $app['event.receiver_search_condition_resolver.' . ReceiverSearchMethods::EMAIL] = function (Container $app) {
            return function($condition) {
                return ['email' => $condition];
            };
        };

        $app['event.receiver_search_condition_resolver.' . ReceiverSearchMethods::ORDER_MANAGER] = function (Container $app) {
            return function() {
                return [
                    'usergroup'    => __('order_manager'),
                    'usergroup_id' => ReceiverSearchMethods::ORDER_MANAGER,
                    'users_count'  => null,
                ];
            };
        };

        $app['event.receiver_search_condition_resolver.' . ReceiverSearchMethods::VENDOR_OWNER] = function (Container $app) {
            return function () {
                return [
                    'usergroup'    => __('vendor_owner'),
                    'usergroup_id' => ReceiverSearchMethods::VENDOR_OWNER,
                    'users_count'  => count(fn_get_all_companies_ids()),
                ];
            };
        };

        $app['event.receiver_search_condition_resolver.' . ReceiverSearchMethods::VENDOR_EMAIL] = static function (Container $app) {
            return static function ($condition) {
                static $cache = [];
                if (!array_key_exists($condition, $cache)) {
                    $profile_field = fn_get_profile_field($condition);
                    if ($profile_field) {
                        $cache[$condition] = [
                            'field' => $profile_field['description'],
                            'field_id' => $condition,
                        ];
                    } else {
                        $cache[$condition] = null;
                    }
                }

                return $cache[$condition];
            };
        };

        $app['event.transports.internal.receiver_finder_factory'] = function (Container $app) {
            return new InternalReceiverFinderFactory($app);
        };

        $app['event.transports.internal.receiver_finders.' . ReceiverSearchMethods::USER_ID] = function (Container $app) {
            return new InternalUserIdFinder($app['db']);
        };

        $app['event.transports.internal.receiver_finders.' . ReceiverSearchMethods::USERGROUP_ID] = function (Container $app) {
            return new InternalUsergroupIdFinder($app['db']);
        };

        $app['event.transports.internal.receiver_finders.' . ReceiverSearchMethods::EMAIL] = function (Container $app) {
            return new InternalEmailFinder($app['db']);
        };

        $app['event.transports.internal.receiver_finders.' . ReceiverSearchMethods::ORDER_MANAGER] = function (Container $app) {
            return new InternalOrderManagerFinder($app['db']);
        };

        $app['event.transports.internal.receiver_finders.' . ReceiverSearchMethods::VENDOR_OWNER] = function (Container $app) {
            return new InternalVendorOwnerFinder($app['db']);
        };

        $app['event.transports.internal.receiver_finders.' . ReceiverSearchMethods::VENDOR_EMAIL] = static function (Container $app) {
            return new InternalVendorEmailFinder($app['db']);
        };

        $app['event.transports.mail.receiver_finder_factory'] = function (Container $app) {
            return new MailReceiverFinderFactory($app);
        };

        $app['event.transports.mail.receiver_finders.' . ReceiverSearchMethods::USER_ID] = function (Container $app) {
            return new MailUserIdFinder($app['db']);
        };

        $app['event.transports.mail.receiver_finders.' . ReceiverSearchMethods::USERGROUP_ID] = function (Container $app) {
            return new MailUsergroupIdFinder($app['db']);
        };

        $app['event.transports.mail.receiver_finders.' . ReceiverSearchMethods::EMAIL] = function (Container $app) {
            return new MailEmailFinder();
        };

        $app['event.transports.mail.receiver_finders.' . ReceiverSearchMethods::ORDER_MANAGER] = function (Container $app) {
            return new MailOrderManagerFinder($app['db']);
        };

        $app['event.transports.mail.receiver_finders.' . ReceiverSearchMethods::VENDOR_OWNER] = function (Container $app) {
            return new MailVendorOwnerFinder($app['db']);
        };

        $app['event.transports.mail.receiver_finders.' . ReceiverSearchMethods::VENDOR_EMAIL] = static function (Container $app) {
            return new VendorEmailFinder($app['db']);
        };
    }
}
