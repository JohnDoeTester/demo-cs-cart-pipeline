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

use Tygh\Tools\SecurityHelper;

/** @var array $schema */
$schema = [
    'product'         => [
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => [
            'shortname'           => SecurityHelper::ACTION_REMOVE_HTML,
            'meta_description'    => SecurityHelper::ACTION_REMOVE_HTML,
            'meta_keywords'       => SecurityHelper::ACTION_REMOVE_HTML,
            'search_words'        => SecurityHelper::ACTION_REMOVE_HTML,
            'page_title'          => SecurityHelper::ACTION_REMOVE_HTML,
            'age_warning_message' => SecurityHelper::ACTION_REMOVE_HTML,
            'product_code'        => SecurityHelper::ACTION_REMOVE_HTML,
            'short_description'   => SecurityHelper::ACTION_SANITIZE_HTML,
            'full_description'    => SecurityHelper::ACTION_SANITIZE_HTML,
            'promo_text'          => SecurityHelper::ACTION_SANITIZE_HTML,
            'product'             => SecurityHelper::ACTION_SANITIZE_HTML,
        ]
    ],
    'category'        => [
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => [
            'meta_keywords'       => SecurityHelper::ACTION_REMOVE_HTML,
            'meta_description'    => SecurityHelper::ACTION_REMOVE_HTML,
            'page_title'          => SecurityHelper::ACTION_REMOVE_HTML,
            'age_warning_message' => SecurityHelper::ACTION_REMOVE_HTML,
            'description'         => SecurityHelper::ACTION_SANITIZE_HTML,
            'category'            => SecurityHelper::ACTION_SANITIZE_HTML,
        ]
    ],
    'company'         => [
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => [
            'company'             => SecurityHelper::ACTION_REMOVE_HTML,
            'company_description' => SecurityHelper::ACTION_SANITIZE_HTML,
            'email'               => SecurityHelper::ACTION_REMOVE_HTML,
            'address'             => SecurityHelper::ACTION_REMOVE_HTML,
            'city'                => SecurityHelper::ACTION_REMOVE_HTML,
            'zipcode'             => SecurityHelper::ACTION_REMOVE_HTML,
            'url'                 => SecurityHelper::ACTION_SANITIZE_HTML,
            'tax_number'          => SecurityHelper::ACTION_REMOVE_HTML,
        ]
    ],
    'page'            => [
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => [
            'page'             => SecurityHelper::ACTION_REMOVE_HTML,
            'description'      => SecurityHelper::ACTION_SANITIZE_HTML,
            'page_title'       => SecurityHelper::ACTION_REMOVE_HTML,
            'meta_description' => SecurityHelper::ACTION_REMOVE_HTML,
            'meta_keywords'    => SecurityHelper::ACTION_REMOVE_HTML,
        ]
    ],
    'product_option'  => [
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => [
            'internal_option_name' => SecurityHelper::ACTION_REMOVE_HTML,
            'option_name'          => SecurityHelper::ACTION_REMOVE_HTML,
            'description'          => SecurityHelper::ACTION_SANITIZE_HTML,
            'comment'              => SecurityHelper::ACTION_REMOVE_HTML,
            'incorrect_message'    => SecurityHelper::ACTION_REMOVE_HTML,
        ]
    ],
    'product_option_variant' => [
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => [
            'variant_name' => SecurityHelper::ACTION_REMOVE_HTML,
        ]
    ],
    'promotion'       => [
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => [
            'name'                 => SecurityHelper::ACTION_REMOVE_HTML,
            'short_description'    => SecurityHelper::ACTION_SANITIZE_HTML,
            'detailed_description' => SecurityHelper::ACTION_SANITIZE_HTML,
        ]
    ],
    'product_feature' => [
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => [
            'internal_name'    => SecurityHelper::ACTION_REMOVE_HTML,
            'description'      => SecurityHelper::ACTION_REMOVE_HTML,
            'full_description' => SecurityHelper::ACTION_SANITIZE_HTML,
            'feature_code'     => SecurityHelper::ACTION_REMOVE_HTML,
        ]
    ],
    'product_feature_variant' => [
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => [
            'variant'          => SecurityHelper::ACTION_REMOVE_HTML,
            'description'      => SecurityHelper::ACTION_SANITIZE_HTML,
            'page_title'       => SecurityHelper::ACTION_REMOVE_HTML,
            'url'              => SecurityHelper::ACTION_REMOVE_HTML,
            'meta_description' => SecurityHelper::ACTION_SANITIZE_HTML,
            'meta_keywords'    => SecurityHelper::ACTION_REMOVE_HTML,
        ]
    ],
    'block'           => [
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => [
            'name' => SecurityHelper::ACTION_SANITIZE_HTML,
        ]
    ],
    'shipping'        => [
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => [
            'shipping'    => SecurityHelper::ACTION_REMOVE_HTML,
            'description' => SecurityHelper::ACTION_SANITIZE_HTML,
        ]
    ],
    'status'          => [
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => [
            'description'  => SecurityHelper::ACTION_REMOVE_HTML,
            'email_subj'   => SecurityHelper::ACTION_REMOVE_HTML,
            'email_header' => SecurityHelper::ACTION_SANITIZE_HTML,
        ]
    ],
    'payment'         => [
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => [
            'payment'         => SecurityHelper::ACTION_REMOVE_HTML,
            'description'     => SecurityHelper::ACTION_REMOVE_HTML,
            'instructions'    => SecurityHelper::ACTION_SANITIZE_HTML,
            'surcharge_title' => SecurityHelper::ACTION_REMOVE_HTML,
        ]
    ],
];

return $schema;
