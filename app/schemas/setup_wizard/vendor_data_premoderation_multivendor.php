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
    'header'   => 'sw.vendor_data_premoderation',
    'settings' => [
        'products_prior_approval'         => [
            'default_value' => 'none',
            'value'         => 'all',
        ],
        'products_updates_approval'       => [
            'default_value' => 'none',
            'value'         => 'all',
            'configure'     => [
                'href' => 'addons.update&addon=vendor_data_premoderation&selected_section=settings&selected_sub_section=vendor_data_premoderation_products',
            ]
        ],
        'vendor_profile_updates_approval' => [
            'default_value' => 'none',
            'value'         => 'all',
        ],
    ],
];
