<?php

return [
    'section1' => [
        'group1' => [
            'action_manage' => [
                [
                    'privilege'   => 'p1',
                    'section_id'  => 'section1',
                    'group_id'    => 'group1',
                    'is_view'     => 'N',
                    'action_type' => 'action_manage',
                    'description' => 'privileges.p1',
                ],
            ],
            'action_view'   => [
                [
                    'privilege'   => 'p2',
                    'section_id'  => 'section1',
                    'group_id'    => 'group1',
                    'is_view'     => 'Y',
                    'action_type' => 'action_view',
                    'description' => 'privileges.p2',
                ],
            ],

        ],
        'group2' => [
            'action_manage' => [
                [
                    'privilege'   => 'p4',
                    'section_id'  => 'section1',
                    'group_id'    => 'group2',
                    'is_view'     => 'N',
                    'action_type' => 'action_manage',
                    'description' => 'privileges.p4',
                ],
            ],
            'action_view'   => [
                [
                    'privilege'   => 'p3',
                    'section_id'  => 'section1',
                    'group_id'    => 'group2',
                    'is_view'     => 'Y',
                    'action_type' => 'action_view',
                    'description' => 'privileges.p3',
                ],
            ],
        ],
        ''       => [
            'action_manage' => [
                [
                    'privilege'   => 'p5',
                    'section_id'  => 'section1',
                    'group_id'    => '',
                    'is_view'     => 'N',
                    'action_type' => 'action_manage',
                    'description' => 'privileges.p5',
                ],
                [
                    'privilege'   => 'p6',
                    'section_id'  => 'section1',
                    'group_id'    => '',
                    'is_view'     => 'N',
                    'action_type' => 'action_manage',
                    'description' => 'privileges.p6',
                ],
            ],
        ],
    ],
];
