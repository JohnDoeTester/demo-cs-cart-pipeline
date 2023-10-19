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

return [
    'categories' => [
        'modes' => [
            'do_m_delete'           => ['POST'],
            'delete'                => ['POST'],
            'm_delete_confirmation' => ['POST']
        ],
    ],
    'countries' => [
        'modes' => [
            'update' => ['POST'],
            'delete' => ['POST'],
        ],
    ],
    'companies' => [
        'modes' => [
            'update'   => ['POST'],
            'delete'   => ['GET', 'POST'],
            'm_delete' => ['GET', 'POST'],
        ],
    ],
    'currencies' => [
        'modes' => [
            'update' => ['POST'],
            'delete' => ['POST'],
        ],
    ],
    'destinations' => [
        'modes' => [
            'update'              => ['POST'],
            'update_destinations' => ['POST'],
            'delete_destinations' => ['POST'],
        ],
    ],
    'localizations' => [
        'restrict' => ['POST']
    ],
    'datakeeper' => [
        'restrict' => ['POST']
    ],
    'exim' => [
        'restrict' => ['POST']
    ],
    'languages' => [
        'restrict' => ['POST'],
        'modes'    => [
            'delete_language' => ['GET'],
        ],
    ],
    'usergroups' => [
        'modes' => [
            'add'    => ['POST'],
            'delete' => ['POST'],
        ],
    ],
    'pages' => [
        'modes' => [
            'do_m_delete'           => ['POST'],
            'delete'                => ['POST'],
            'm_delete_confirmation' => ['POST'],
        ],
    ],
    'products' => [
        'modes' => [
            'do_m_delete'           => ['POST'],
            'delete'                => ['POST'],
            'm_delete_confirmation' => ['POST'],
        ],
    ],
    'profiles' => [
        'restrict' => ['POST']
    ],
    'payments' => [
        'restrict' => ['POST']
    ],
    'settings' => [
        'restrict' => ['POST']
    ],
    'addons' => [
        'restrict' => ['POST'],
        'modes'    => [
            'update_status' => ['GET', 'POST'],
            'install'       => ['GET', 'POST'],
            'uninstall'     => ['GET', 'POST'],
        ],
    ],
    'shippings' => [
        'modes' => [
            'delete_shippings' => ['POST'],
            'add_shippings'    => ['POST'],
            'test'             => ['GET'],
        ],
    ],
    'states' => [
        'modes' => [
            'update' => ['POST'],
            'delete' => ['POST'],
        ],
    ],

    'taxes' => [
        'modes' => [
            'do_m_delete'           => ['POST'],
            'delete'                => ['POST'],
            'm_delete_confirmation' => ['POST'],
        ],
    ],
    'file_editor' => [
        'restrict' => ['POST'],
        'modes'    => [
            'delete_file' => ['GET'],
            'rename_file' => ['GET'],
            'create_file' => ['GET'],
            'chmod'       => ['GET'],
            'get_file'    => ['GET'],
            'restore'     => ['GET']
        ],
    ],
    'tools' => [
        'modes' => [
            'phpinfo'       => ['POST', 'GET'],
            'update_status' => ['GET'],
        ],
    ],
    'block_manager' => [
        'restrict' => ['POST'],
        'modes'    => [
            'delete'        => ['GET'],
            'bulk_actions'  => ['GET'],
            'update_status' => ['GET'],
        ],
    ],
    'image' => [
        'modes' => [
            'delete_image'      => ['POST', 'GET'],
            'delete_image_pair' => ['POST', 'GET']
        ],
    ],
    'elf_connector' => [
        'restrict' => ['POST'],
    ],
    'themes' => [
        'modes' => [
            'upload'  => ['POST'],
            'clone'   => ['POST'],
            'styles'  => ['GET', 'POST'],
            'set'     => ['GET', 'POST'],
            'delete'  => ['GET', 'POST'],
            'install' => ['GET', 'POST'],
        ],
    ],
    'upgrade_center' => [
        'restrict' => ['POST'],
        'modes'    => [
            'get_upgrade' => ['GET', 'POST'],
            'run_backup'  => ['GET', 'POST'],
            'check'       => ['GET', 'POST'],
        ],
    ],
    'templates' => [
        'restrict' => ['POST'],
    ],
];
