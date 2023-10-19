<?php

namespace Tygh\Tests\Unit\NotificationsCenter;

use Tygh\NotificationsCenter\Factory;
use Tygh\NotificationsCenter\Notification;
use Tygh\NotificationsCenter\NotificationsCenter;
use Tygh\NotificationsCenter\Repository;
use Tygh\Tests\Unit\ATestCase;
use Tygh\Tools\Formatter;

function __($val)
{
    return $val;
}

class NotificationsCenterTest extends ATestCase
{
    /**
     * @var NotificationsCenter
     */
    protected $nc;

    public $runTestInSeparateProcess = true;

    public $backupGlobals = false;

    public $preserveGlobalState = false;

    public function setUp(): void
    {
        define('CART_SECONDARY_CURRENCY', 'US');

        $schema = [
            'all'      => [
                'section'      => 'all',
                'section_name' => 'all',
                'tags'         => [],
            ],
            '+section' => [
                'section'      => '+section',
                'section_name' => '+section',
                'tags'         => [
                    '+tag'  => [
                        'tag'      => '+tag',
                        'tag_name' => '+tag',
                    ],
                    'noop'  => [
                        'tag'      => 'noop',
                        'tag_name' => 'noop',
                    ],
                    'other' => [
                        'tag'      => 'other',
                        'tag_name' => 'other',
                    ],
                ],
            ],
            'other'    => [
                'section'      => 'other',
                'section_name' => 'other',
                'tags'         => [
                    'other' => [
                        'tag'      => 'other',
                        'tag_name' => 'other',
                    ],
                ],
            ],
        ];

        $this->nc = new NotificationsCenter(
            0,
            'A',
            $this->createMock(Repository::class),
            $this->createMock(Factory::class),
            $this->createMock(Formatter::class),
            $schema,
            10,
            '\Tygh\Tests\Unit\NotificationsCenter\__'
        );
    }

    /**
     * @dataProvider dpGroupNotificationsBySection
     */
    public function testGroupNotificationsBySection($notifications, $expected)
    {
        $actual = $this->nc->groupNotificationsBySection($notifications);

        $this->assertEquals($expected, $actual);
    }

    public function dpGroupNotificationsBySection()
    {
        return [
            [
                [

                ],
                [

                ],
            ],
            [
                [
                    new Notification(0, 1, 'title', '+section +tag', 'E', '+section', '+tag', 'A', '', false, 0, true, true),
                    new Notification(0, 1, 'title', '+section -tag', 'E', '+section', '-tag', 'A', '', false, 0, true, true),
                    new Notification(0, 1, 'title', '-section -tag', 'E', '-section', '-tag', 'A', '', false, 0, true, true),
                ],
                [
                    'all'            => [
                        'section'       => 'all',
                        'section_name'  => 'all',
                        'tags'          => [
                            '+tag' => [
                                'tag'      => '+tag',
                                'tag_name' => '+tag',
                                'is_used'  => true,
                            ],
                            'other'  => [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                                'is_used'  => true,
                            ],
                        ],
                        'notifications' => [
                            ['user_id' => 1, 'title' => 'title', 'message' => '+section +tag', 'severity' => 'E', 'section' => '+section', 'tag' => '+tag', 'area' => 'A', 'action_url' => '', 'is_read' => 0, 'timestamp' => 0, 'datetime' => null, 'notification_id' => 0, 'pinned' => 1, 'remind' => 1],
                            ['user_id' => 1, 'title' => 'title', 'message' => '+section -tag', 'severity' => 'E', 'section' => '+section', 'tag' => 'other', 'area' => 'A', 'action_url' => '', 'is_read' => 0, 'timestamp' => 0, 'datetime' => null, 'notification_id' => 0, 'pinned' => 1, 'remind' => 1],
                            ['user_id' => 1, 'title' => 'title', 'message' => '-section -tag', 'severity' => 'E', 'section' => 'other', 'tag' => 'other', 'area' => 'A', 'action_url' => '', 'is_read' => 0, 'timestamp' => 0, 'datetime' => null, 'notification_id' => 0, 'pinned' => 1, 'remind' => 1],
                        ],
                    ],
                    '+section' => [
                        'section'       => '+section',
                        'section_name'  => '+section',
                        'tags'          => [
                            '+tag' => [
                                'tag'      => '+tag',
                                'tag_name' => '+tag',
                                'is_used'  => true,
                            ],
                            'noop'  => [
                                'tag'      => 'noop',
                                'tag_name' => 'noop',
                            ],
                            'other'  => [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                                'is_used'  => true,
                            ],
                        ],
                        'notifications' => [
                            ['user_id' => 1, 'title' => 'title', 'message' => '+section +tag', 'severity' => 'E', 'section' => '+section', 'tag' => '+tag', 'area' => 'A', 'action_url' => '', 'is_read' => 0, 'timestamp' => 0, 'datetime' => null, 'notification_id' => 0,  'pinned' => 1, 'remind' => 1],
                            ['user_id' => 1, 'title' => 'title', 'message' => '+section -tag', 'severity' => 'E', 'section' => '+section', 'tag' => 'other', 'area' => 'A', 'action_url' => '', 'is_read' => 0, 'timestamp' => 0, 'datetime' => null, 'notification_id' => 0,  'pinned' => 1, 'remind' => 1],
                        ],
                    ],
                    'other'          => [
                        'section'       => 'other',
                        'section_name'  => 'other',
                        'tags'          => [
                            'other' => [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                                'is_used'  => true,
                            ],
                        ],
                        'notifications' => [
                            ['user_id' => 1, 'title' => 'title', 'message' => '-section -tag', 'severity' => 'E', 'section' => 'other', 'tag' => 'other', 'area' => 'A', 'action_url' => '', 'is_read' => 0, 'timestamp' => 0, 'datetime' => null, 'notification_id' => 0, 'pinned' => 1, 'remind' => 1],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dpBuildTags
     */
    public function testBuildTags($sections, $expected)
    {
        $actual = $this->nc->buildTags($sections);

        $this->assertEquals($expected, $actual);
    }

    public function dpBuildTags() {
        return [
            [
                [
                    'all'            => [
                        'section'       => 'all',
                        'section_name'  => 'all',
                        'tags'          => [
                            '+tag' => [
                                'tag'      => '+tag',
                                'tag_name' => '+tag',
                                'is_used'  => true,
                            ],
                            'other'  => [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                                'is_used'  => true,
                            ],
                        ],
                        'notifications' => [],
                    ],
                    '+section' => [
                        'section'       => '+section',
                        'section_name'  => '+section',
                        'tags'          => [
                            '+tag' => [
                                'tag'      => '+tag',
                                'tag_name' => '+tag',
                                'is_used'  => true,
                            ],
                            'noop'  => [
                                'tag'      => 'noop',
                                'tag_name' => 'noop',
                            ],
                            'other'  => [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                                'is_used'  => true,
                            ],
                        ],
                        'notifications' => [],
                    ],
                    'other'          => [
                        'section'       => 'other',
                        'section_name'  => 'other',
                        'tags'          => [
                            'other' => [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                                'is_used'  => true,
                            ],
                        ],
                        'notifications' => [],
                    ],
                ],
                [
                    'all'            => [
                        'section'       => 'all',
                        'section_name'  => 'all',
                        'tags'          => [
                            [
                                'tag'      => '+tag',
                                'tag_name' => '+tag',
                            ],
                            [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                            ],
                        ],
                        'notifications' => [],
                    ],
                    '+section' => [
                        'section'       => '+section',
                        'section_name'  => '+section',
                        'tags'          => [
                            [
                                'tag'      => '+tag',
                                'tag_name' => '+tag',
                            ],
                            [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                            ],
                        ],
                        'notifications' => [],
                    ],
                    'other'          => [
                        'section'       => 'other',
                        'section_name'  => 'other',
                        'tags'          => [
                            [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                            ],
                        ],
                        'notifications' => [],
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider dpBuildUniqueSections
     */
    public function testBuildUniqueSections($sections, $expected)
    {
        $actual = $this->nc->buildUniqueSections($sections);

        $this->assertEquals($expected, $actual);
    }

    public function dpBuildUniqueSections() {
        return [
            [
                [
                    'all'            => [
                        'section'       => 'all',
                        'section_name'  => 'all',
                        'tags'          => [
                            [
                                'tag'      => '+tag',
                                'tag_name' => '+tag',
                            ],
                            [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                            ],
                        ],
                        'notifications' => [],
                    ],
                    '+section' => [
                        'section'       => '+section',
                        'section_name'  => '+section',
                        'tags'          => [
                            [
                                'tag'      => '+tag',
                                'tag_name' => '+tag',
                            ],
                            [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                            ],
                        ],
                        'notifications' => [],
                    ],
                    'other'          => [
                        'section'       => 'other',
                        'section_name'  => 'other',
                        'tags'          => [
                            [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                            ],
                        ],
                        'notifications' => [],
                    ],
                ],
                [
                    [
                        'section'       => 'all',
                        'section_name'  => 'all',
                        'tags'          => [
                            [
                                'tag'      => '+tag',
                                'tag_name' => '+tag',
                            ],
                            [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                            ],
                        ],
                        'notifications' => [],
                    ],
                    [
                        'section'       => '+section',
                        'section_name'  => '+section',
                        'tags'          => [
                            [
                                'tag'      => '+tag',
                                'tag_name' => '+tag',
                            ],
                            [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                            ],
                        ],
                        'notifications' => [],
                    ],
                    [
                        'section'       => 'other',
                        'section_name'  => 'other',
                        'tags'          => [
                            [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                            ],
                        ],
                        'notifications' => [],
                    ],
                ]
            ],
            [
                [
                    'all'            => [
                        'section'       => 'all',
                        'section_name'  => 'all',
                        'tags'          => [
                            [
                                'tag'      => '+tag',
                                'tag_name' => '+tag',
                            ],
                            [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                            ],
                        ],
                        'notifications' => [],
                    ],
                    '+section' => [
                        'section'       => '+section',
                        'section_name'  => '+section',
                        'tags'          => [
                            [
                                'tag'      => '+tag',
                                'tag_name' => '+tag',
                            ],
                            [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                            ],
                        ],
                        'notifications' => [],
                    ],
                ],
                [
                    [
                        'section'       => '+section',
                        'section_name'  => '+section',
                        'tags'          => [
                            [
                                'tag'      => '+tag',
                                'tag_name' => '+tag',
                            ],
                            [
                                'tag'      => 'other',
                                'tag_name' => 'other',
                            ],
                        ],
                        'notifications' => [],
                    ],
                ],
            ]
        ];
    }
}
