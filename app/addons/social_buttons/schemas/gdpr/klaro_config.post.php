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

use Tygh\Enum\YesNo;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
if (YesNo::isTrue(Registry::get('addons.social_buttons.facebook_enable'))) {
    $schema['services']['facebook'] = [
        'purposes' => ['functional'],
        'name' => 'facebook',
        'translations' => [
            'zz' => [
                'title' => 'social_buttons.facebook_cookie_title',
                'description' => 'social_buttons.facebook_cookie_description'
            ],
        ],
    ];
}

if (YesNo::isTrue(Registry::get('addons.social_buttons.pinterest_enable'))) {
    $schema['services']['pinterest'] = [
        'purposes' => ['functional'],
        'name' => 'pinterest',
        'translations' => [
            'zz' => [
                'title' => 'social_buttons.pinterest_cookie_title',
                'description' => 'social_buttons.pinterest_cookie_description'
            ],
        ],
    ];
}

if (YesNo::isTrue(Registry::get('addons.social_buttons.twitter_enable'))) {
    $schema['services']['twitter'] = [
        'purposes' => ['functional'],
        'name' => 'twitter',
        'translations' => [
            'zz' => [
                'title' => 'social_buttons.twitter_cookie_title',
                'description' => 'social_buttons.twitter_cookie_description'
            ],
        ],
    ];
}

if (YesNo::isTrue(Registry::get('addons.social_buttons.vkontakte_enable'))) {
    $schema['services']['vkontakte'] = [
        'purposes' => ['functional'],
        'name' => 'vkontakte',
        'translations' => [
            'zz' => [
                'title' => 'social_buttons.vkontakte_cookie_title',
                'description' => 'social_buttons.vkontakte_cookie_description'
            ],
        ],
    ];
}

if (YesNo::isTrue(Registry::get('addons.social_buttons.yandex_enable'))) {
    $schema['services']['yandex'] = [
        'purposes' => ['functional'],
        'name' => 'yandex',
        'translations' => [
            'zz' => [
                'title' => 'social_buttons.yandex_cookie_title',
                'description' => 'social_buttons.yandex_cookie_description'
            ],
        ],
    ];
}

return $schema;
