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
use Tygh\Addons\ProductVariations\Product\FeaturePurposes;

/** @var array $schema */

$schema[FeaturePurposes::CREATE_CATALOG_ITEM] = [
    'position'   => 200,
    'styles_map' => [
        'dropdown_checkbox' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN,
            'filter_style'  => ProductFilterStyles::CHECKBOX,
            'feature_type'  => ProductFeatures::TEXT_SELECTBOX
        ],
        'dropdown_checkbox_images' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN_IMAGES,
            'filter_style'  => ProductFilterStyles::CHECKBOX,
            'feature_type'  => ProductFeatures::TEXT_SELECTBOX
        ],
        'dropdown_checkbox_labels' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN_LABELS,
            'filter_style'  => ProductFilterStyles::CHECKBOX,
            'feature_type'  => ProductFeatures::TEXT_SELECTBOX
        ],
        'dropdown_slider' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN,
            'filter_style'  => ProductFilterStyles::SLIDER,
            'feature_type'  => ProductFeatures::NUMBER_SELECTBOX
        ],
        'dropdown_slider_labels' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN_LABELS,
            'filter_style'  => ProductFilterStyles::SLIDER,
            'feature_type'  => ProductFeatures::NUMBER_SELECTBOX
        ],
        'dropdown_color' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN,
            'filter_style'  => ProductFilterStyles::COLOR,
            'feature_type'  => ProductFeatures::TEXT_SELECTBOX,
        ],
        'dropdown_color_images' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN_IMAGES,
            'filter_style'  => ProductFilterStyles::COLOR,
            'feature_type'  => ProductFeatures::TEXT_SELECTBOX,
        ],
        'dropdown_color_labels' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN_LABELS,
            'filter_style'  => ProductFilterStyles::COLOR,
            'feature_type'  => ProductFeatures::TEXT_SELECTBOX,
        ],
    ],
];

$schema[FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM] = [
    'position'   => 300,
    'styles_map' => [
        'dropdown_checkbox' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN,
            'filter_style'  => ProductFilterStyles::CHECKBOX,
            'feature_type'  => ProductFeatures::TEXT_SELECTBOX
        ],
        'dropdown_checkbox_images' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN_IMAGES,
            'filter_style'  => ProductFilterStyles::CHECKBOX,
            'feature_type'  => ProductFeatures::TEXT_SELECTBOX
        ],
        'dropdown_checkbox_labels' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN_LABELS,
            'filter_style'  => ProductFilterStyles::CHECKBOX,
            'feature_type'  => ProductFeatures::TEXT_SELECTBOX
        ],
        'dropdown_slider' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN,
            'filter_style'  => ProductFilterStyles::SLIDER,
            'feature_type'  => ProductFeatures::NUMBER_SELECTBOX
        ],
        'dropdown_slider_labels' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN_LABELS,
            'filter_style'  => ProductFilterStyles::SLIDER,
            'feature_type'  => ProductFeatures::NUMBER_SELECTBOX
        ],
        'dropdown_color' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN,
            'filter_style'  => ProductFilterStyles::COLOR,
            'feature_type'  => ProductFeatures::TEXT_SELECTBOX,
        ],
        'dropdown_color_images' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN_IMAGES,
            'filter_style'  => ProductFilterStyles::COLOR,
            'feature_type'  => ProductFeatures::TEXT_SELECTBOX,
        ],
        'dropdown_color_labels' => [
            'feature_style' => ProductFeatureStyles::DROP_DOWN_LABELS,
            'filter_style'  => ProductFilterStyles::COLOR,
            'feature_type'  => ProductFeatures::TEXT_SELECTBOX,
        ],
    ],
];

return $schema;
