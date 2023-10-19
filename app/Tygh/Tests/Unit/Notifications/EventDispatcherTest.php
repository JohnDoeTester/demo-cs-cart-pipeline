<?php

namespace Tygh\Tests\Unit\Notifications;

use Tygh\Enum\UserTypes;
use Tygh\Notifications\DataValue;
use Tygh\Notifications\EventDispatcher;
use Tygh\Notifications\Transports\Mail\MailMessageSchema;
use Tygh\Notifications\Transports\Mail\MailTransport;
use Tygh\Notifications\Transports\TransportFactory;
use Tygh\Tests\Unit\ATestCase;

class EventDispatcherTest extends ATestCase
{
    public function testMultipleDispatchOneEvent()
    {
        $transport = $this->getMockBuilder(MailTransport::class)
            ->disableOriginalConstructor()
            ->setMethods(['process'])
            ->getMock();

        $callback = function ($email, $company_id) {
            return function (MailMessageSchema $schema) use ($email, $company_id) {
                return $schema->to === $email && $schema->company_id === $company_id;
            };
        };

        $transport->expects($this->exactly(3))->method('process')->withConsecutive(
            [$this->callback($callback('address1@example.com', 1))],
            [$this->callback($callback('address2@example.com', 2))],
            [$this->callback($callback('address3@example.com', 3))]
        );

        $transport_factory = $this->getMockBuilder(TransportFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $transport_factory->method('create')->willReturn($transport);

        $event_dispatcher = new EventDispatcher(
            $this->getEventsSchema(),
            $this->getNotificationSettings(),
            $transport_factory
        );

        $event_dispatcher->dispatch('order.shipment_updated', [
            'order_info' => [
                'email'      => 'address1@example.com',
                'company_id' => 1,
                'lang_code'  => 'en',
            ]
        ]);

        $event_dispatcher->dispatch('order.shipment_updated', [
            'order_info' => [
                'email'      => 'address2@example.com',
                'company_id' => 2,
                'lang_code'  => 'ru',
            ]
        ]);

        $event_dispatcher->dispatch('order.shipment_updated', [
            'order_info' => [
                'email'      => 'address3@example.com',
                'company_id' => 3,
                'lang_code'  => 'de',
            ]
        ]);
    }

    protected function getEventsSchema()
    {
        return [
            'order.shipment_updated' => [
                'group'     => 'order',
                'name'      => [
                    'template' => 'event.order.shipment_updated.name',
                    'params'   => [],
                ],
                'receivers' => [
                    UserTypes::CUSTOMER => [
                        MailTransport::getId() => MailMessageSchema::create([
                            'area'            => 'C',
                            'from'            => 'company_orders_department',
                            'to'              => DataValue::create('order_info.email'),
                            'template_code'   => 'shipment_products',
                            'legacy_template' => 'shipments/shipment_products.tpl',
                            'company_id'      => DataValue::create('order_info.company_id'),
                            'language_code'   => DataValue::create('order_info.lang_code', 'en')
                        ]),
                    ],
                ],
            ],
        ];
    }

    protected function getNotificationSettings()
    {
        return [
            'order.shipment_updated' => [
                'receivers' => [
                    UserTypes::CUSTOMER => [
                        'mail' => true
                    ]
                ]
            ]
        ];
    }
}