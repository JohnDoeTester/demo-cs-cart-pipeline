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

use Tygh\Enum\ProductFeatures;
use Tygh\Enum\ProductFilterStyles;
use Tygh\Enum\ProductFeatureStyles;


/**
 * Describes the available product features purposes
 *
 * Syntax:
 * 'purpose_code' => [
 *      'is_core'  => true,
 *      'is_default' => true,
 *      'position'   => 100,
 *      'styles_map' => []
 * ]
 *
 * is_core - determines if the purpose belongs to the core; affects the logic of determining the purpose by the type of the feature.
 * position - determines the position of the purpose in the feature management dialogue.
 * is_default - specifies that the purpose must be selected by default for new features.
 * styles_map - describes what feature_style and filter_style are available for the feature_type.
 */
$schema = [
    'organize_catalog' => [
        'is_core'    => true,
        'position'   => 400,
        'styles_map' => [
            'default' => [
                'feature_style' => ProductFeatureStyles::BRAND,
                'filter_style'  => ProductFilterStyles::CHECKBOX,
                'feature_type'  => ProductFeatures::EXTENDED
            ],
        ],
    ],
    'find_products' => [
        'is_core'    => true,
        'position'   => 100,
        'is_default' => true,
        'styles_map' => [
            'text_checkbox' => [
                'feature_style' => ProductFeatureStyles::TEXT,
                'filter_style'  => ProductFilterStyles::CHECKBOX,
                'feature_type'  => ProductFeatures::TEXT_SELECTBOX
            ],
            'checkbox_checkbox' => [
                'feature_style' => ProductFeatureStyles::CHECKBOX,
                'filter_style'  => ProductFilterStyles::CHECKBOX,
                'feature_type'  => ProductFeatures::SINGLE_CHECKBOX
            ],
            'multiple_checkbox_checkbox' => [
                'feature_style' => ProductFeatureStyles::MULTIPLE_CHECKBOX,
                'filter_style'  => ProductFilterStyles::CHECKBOX,
                'feature_type'  => ProductFeatures::MULTIPLE_CHECKBOX
            ],
            'text_date' => [
                'feature_style' => ProductFeatureStyles::TEXT,
                'filter_style'  => ProductFilterStyles::DATE,
                'feature_type'  => ProductFeatures::DATE
            ],
            'text_slider' => [
                'feature_style' => ProductFeatureStyles::TEXT,
                'filter_style'  => ProductFilterStyles::SLIDER,
                'feature_type'  => ProductFeatures::NUMBER_SELECTBOX
            ],
            'simple_number_slider' => [
                'feature_style' => ProductFeatureStyles::NUMBER,
                'filter_style'  => ProductFilterStyles::SLIDER,
                'feature_type'  => ProductFeatures::NUMBER_FIELD
            ],
            'text_color' => [
                'feature_style' => ProductFeatureStyles::TEXT,
                'filter_style'  => ProductFilterStyles::COLOR,
                'feature_type'  => ProductFeatures::TEXT_SELECTBOX,
            ],
        ],
    ],
    'describe_product' => [
        'is_core'    => true,
        'position'   => 500,
        'styles_map' => [
            'default' => [
                'feature_style' => 'text',
                'filter_style'  => null,
                'feature_type'  => ProductFeatures::TEXT_FIELD
            ],
        ],
    ]
];

return $schema;
