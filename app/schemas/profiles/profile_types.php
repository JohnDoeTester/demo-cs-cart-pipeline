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
 ***************************************************************************/

use Tygh\Enum\ProfileFieldAreas;
use Tygh\Enum\ProfileFieldSections;
use Tygh\Enum\ProfileTypes;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Describes the available profile types
 *
 * Syntax:
 * 'TYPE_CODE' => [
 *      'name' => 'field_name',
 *      'allowed_sections' => array(),
 *      'allowed_areas'  => array(),
 * ]
 *
 * name - specified will be substitute to a language variable (e.g. __('profile_types_section_%field_name%')). The
 * resulting variable will be shown in the sidebar section. allowed_sections - list of sections to show for active
 * profile type, only that in the list will be shown. allowed_areas - list of allowed locations to show fields that
 * belong to certain profile type on areas like checkout of profile
 */
$schema = [
    ProfileTypes::CODE_USER => [
        'name'             => ProfileTypes::USER,
        'allowed_sections' => [ // defines sections that allowed to be shown for particular profile type
            ProfileFieldSections::CONTACT_INFORMATION,
            ProfileFieldSections::BILLING_AND_SHIPPING_ADDRESS,
        ],
        'allowed_areas'    => [
            ProfileFieldAreas::PROFILE,
        ],
    ],
];

return $schema;
