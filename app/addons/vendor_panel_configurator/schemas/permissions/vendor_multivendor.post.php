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

use Tygh\Enum\SiteArea;

defined('BOOTSTRAP') or die('Access denied');

if (!SiteArea::isStorefront(AREA)) {
    /** @var array $schema */
    $schema['controllers']['theme_editor'] = [
        'modes' => [
            'view'    => [
                'permissions' => true
            ],
            'get_css' => [
                'permissions' => true
            ],
            'save'    => [
                'permissions' => true
            ]
        ]
    ];
}

return $schema;
