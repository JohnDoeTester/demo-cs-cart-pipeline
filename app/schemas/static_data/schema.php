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
    'A' => [ // menu items
        'param' => 'url',
        'tooltip' => 'tts_link_text',
        'descr' => 'name',
        'add_title' => 'new_items',
        'add_button' => 'add_item',
        'edit_title' => 'editing_item',
        'mainbox_title' => 'menu_items',
        'additional_params' => [
            [
                'title' => 'open_in_new_window',
                'type' => 'checkbox',
                'default_value' => \Tygh\Enum\YesNo::NO,
                'name' => 'param_6'
            ],
            [
                'title' => 'activate_menu_tab_for',
                'tooltip' => 'tts_activate_menu_tab_for',
                'type' => 'input',
                'name' => 'param_2'
            ],
            [
                'title' => 'generate_submenu',
                'tooltip' => 'tts_generate_submenu',
                'type' => 'megabox', // :)
                'name' => 'param_3'
            ],
            [
                'title' => 'user_class',
                'tooltip' => 'tts_menu_user_class',
                'type' => 'input',
                'name' => 'class'
            ],
        ],
        'has_localization' => true,
        'multi_level' => true,
        'owner_object' => [
            'return_url' => 'menus.manage',
            'return_url_text' => 'menu',
            'key' => 'menu_id',
            'table' => 'menus',
            'param' => 'param_5',
            'default_value' => 0,
            'name_function' => 'fn_get_menu_name',
            'check_owner_function' => 'fn_check_menu_owner',
            'children' => [
                'key' => 'menu_id',
                'table' => 'menus_descriptions',
            ],
        ],
    ],
];
