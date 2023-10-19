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

return [
    // Fetch directives
    'child-src' => [],
    'connect-src' => [],
    'default-src' => [],
    'font-src' => [],
    'frame-src' => [],
    'img-src' => [],
    'manifest-src' => [],
    'media-src' => [],
    'object-src' => [],
    'script-src' => [],
    'style-src' => [],
    // Document directives
    'base-uri' => [],
    'plugin-types' => [],
    'sandbox' => [],
    // Navigation directives
    'form-action' => [],
    'frame-ancestors' => [
        'self' => true,
        'allow' => []
    ],
    'navigate-to' => [],
    // Other
    'block-all-mixed-content' => [],
    'upgrade-insecure-requests' => [],
];
