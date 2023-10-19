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

use Tygh\Addons\VendorPanelConfigurator\Enum\ColorSchemas;

defined('BOOTSTRAP') or die('Access denied');

return [
    ColorSchemas::LIGHT => [
        'element_color' => '#073763',
        'sidebar_color' => '#eef1f3',
    ],
    ColorSchemas::PURPLE => [
        'element_color' => '#674ea7',
        'sidebar_color' => '#f3edf7',
    ],
    ColorSchemas::LEMONGRASS => [
        'element_color' => '#38761d',
        'sidebar_color' => '#eaf5d6',
    ],
    ColorSchemas::SWEETY => [
        'element_color' => '#660000',
        'sidebar_color' => '#ffefee',
    ],
    ColorSchemas::SAND => [
        'element_color' => '#7f6000',
        'sidebar_color' => '#fdf1e0',
    ],
    ColorSchemas::CUSTOM  => [],
];
