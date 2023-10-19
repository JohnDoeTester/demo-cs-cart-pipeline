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

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
$schema = [
    'images'      => [
        'icon'                   => [
            'name'         => 'm_app_icon',
            'type'         => 'm_app_icon',
            'image_params' => [
                'name' => 'icon',
            ],
        ],
        'notification_icon'      => [
            'name'         => 'm_app_notification_icon',
            'type'         => 'm_app_notification_icon',
            'image_params' => [
                'name' => 'notification_icon',
            ],
        ],
        'store_logo'             => [
            'name'         => 'm_app_store_logo',
            'type'         => 'm_app_store_logo',
            'image_params' => [
                'skip_resize' => true, // skip resizing (this image goes as link)
                'name'        => 'store_logo',
                'resolution'  => [
                    'width'  => 1024,
                    'height' => 500,
                ]
            ],
        ],
        'splash_screen_portrait' => [
            'name'         => 'm_app_splash_portrait',
            'type'         => 'm_app_splash_portrait',
            'image_params' => [
                'name'        => 'splash_screen', // corresponds to index inside the "image_sizes" array
            ],
        ],
    ],
    'image_sizes' => [
        'android' => [ // android specific resizing settings
            'icon'          => [
                'file_name'   => 'ic_launcher',
                'paths'       => [
                    [
                        'original_path' => 'android/app/src/main/res/mipmap',
                        'path'          => 'android/app/src/main/res/mipmap-%resolution_code%',
                        'variables'     => ['resolution_code'],
                    ],
                ],
                'resolutions' => [
                    'hdpi'    => [
                        'width'  => 72,
                        'height' => 72,
                    ],
                    'mdpi'    => [
                        'width'  => 48,
                        'height' => 48,
                    ],
                    'xhdpi'   => [
                        'width'  => 96,
                        'height' => 96,
                    ],
                    'xxhdpi'  => [
                        'width'  => 144,
                        'height' => 144,
                    ],
                    'xxxhdpi' => [
                        'width'  => 192,
                        'height' => 192,
                    ],
                ],
            ],
            'notification_icon' => [
                'file_name'   => 'ic_notification',
                'paths'       => [
                    [
                        'original_path' => 'android/app/src/main/res/mipmap',
                        'path'          => 'android/app/src/main/res/mipmap-%resolution_code%',
                        'variables'     => ['resolution_code'],
                    ],
                ],
                'resolutions' => [
                    'hdpi'    => [
                        'width'  => 72,
                        'height' => 72,
                    ],
                    'mdpi'    => [
                        'width'  => 48,
                        'height' => 48,
                    ],
                    'xhdpi'   => [
                        'width'  => 96,
                        'height' => 96,
                    ],
                    'xxhdpi'  => [
                        'width'  => 144,
                        'height' => 144,
                    ],
                    'xxxhdpi' => [
                        'width'  => 192,
                        'height' => 192,
                    ],
                ],
            ],
            'splash_screen' => [
                'file_name'   => 'ic_splash',
                'paths'       => [
                    'path' => [
                        'original_path' => 'android/app/src/main/res/mipmap', // the path where original image (without resizing) goes
                        'path'          => 'android/app/src/main/res/mipmap-%resolution_code%', // the path where the resized image goes, the %placefolder% should be replaced with
                        'variables'     => ['resolution_code'], // the variables to replace inside the path
                    ],
                ],
                'resolutions' => [
                    'hdpi'     => [
                        'width'  => 480,
                        'height' => 800,
                    ],
                    'mdpi'     => [
                        'width'  => 320,
                        'height' => 480,
                    ],
                    'xhdpi'    => [
                        'width'  => 720,
                        'height' => 1280,
                    ],
                    'xxhdpi'   => [
                        'width'  => 960,
                        'height' => 1600,
                    ],
                    'xxxhdpi'  => [
                        'width'  => 1280,
                        'height' => 1920,
                    ],
                ],
            ],
        ],
        'ios'     => [
            'icon'          => [
                'name'        => [
                    'file_name' => 'Icon-App-%width%x%height%@%scale%x',
                    'variables' => ['width', 'height', 'scale'],
                ],
                'path'        => 'ios/csnative/Images.xcassets/AppIcon.appiconset',
                'resolutions' => [
                    [
                        'width'  => 20,
                        'height' => 20,
                        'scales' => [
                            1 => ['ipad'],
                            2 => ['ipad', 'iphone'],
                            3 => ['iphone'],
                        ],
                    ],
                    [
                        'width'  => 29,
                        'height' => 29,
                        'scales' => [
                            1 => ['ipad', 'iphone'],
                            2 => ['ipad', 'iphone'],
                            3 => ['iphone'],
                        ],
                    ],
                    [
                        'width'  => 40,
                        'height' => 40,
                        'scales' => [
                            1 => ['ipad', 'iphone'],
                            2 => ['ipad', 'iphone'],
                            3 => ['iphone'],
                        ],
                    ],
                    [
                        'width'  => 50,
                        'height' => 50,
                        'scales' => [
                            1 => ['ipad'],
                            2 => ['ipad'],
                        ],
                    ],
                    [
                        'width'  => 57,
                        'height' => 57,
                        'scales' => [
                            1 => ['iphone'],
                            2 => ['iphone'],
                        ],
                    ],
                    [
                        'width'  => 60,
                        'height' => 60,
                        'scales' => [
                            2 => ['iphone'],
                            3 => ['iphone'],
                        ],
                    ],
                    [
                        'width'  => 72,
                        'height' => 72,
                        'scales' => [
                            1 => ['ipad'],
                            2 => ['ipad'],
                        ],
                    ],
                    [
                        'width'  => 76,
                        'height' => 76,
                        'scales' => [
                            1 => ['iphone'],
                            2 => ['ipad'],
                        ],
                    ],
                    [
                        'width'  => 83.5,
                        'height' => 83.5,
                        'scales' => [
                            2 => ['ipad'],
                        ],
                    ],
                    [
                        'width'  => 1024,
                        'height' => 1024,
                        'scales' => [
                            1 => ['ipad'],
                        ],
                    ],
                ],
            ],
            'splash_screen' => [
                'path'        => 'ios/csnative/Images.xcassets/LaunchScreen.imageset',
                'resolutions' => [
                    [
                        'width'  => 926,
                        'height' => 926,
                        'name'   => 'portrait_926x926@1x',
                    ],
                    [
                        'width'  => 1852,
                        'height' => 1852,
                        'name'   => 'portrait_1852x1852@2x',
                    ],
                    [
                        'width'  => 2778,
                        'height' => 2778,
                        'name'   => 'portrait_2778x2778@3x',
                    ],
                ],
            ],
        ],
    ],
];

return $schema;
