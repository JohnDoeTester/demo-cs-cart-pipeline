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

use Tygh\Addons\AdvancedImport\Readers\Xml;
use Tygh\Enum\Addons\AdvancedImport\ImportStrategies;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

include_once Registry::get('config.dir.addons') . 'advanced_import/schemas/advanced_import/products.functions.php';

$default_files_path = 'exim/backup/downloads/';
if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.simple_ultimate')) {
    $default_files_path = Registry::get('runtime.forced_company_id') . '/' . $default_files_path;
}

$schema = array(
    'import_process_data' => array(
        'filter_out_null_values' => array(
            'function'    => 'fn_advanced_import_filter_out_null_values',
            'args'        => array('$object'),
            'import_only' => true,
        ),
        'test_import' => array(
            'function'    => 'fn_advanced_import_test_import',
            'args'        => array(
                '$pattern',
                '$options',
                '$processed_data',
                '$skip_record',
                '$stop_import',
            ),
            'import_only' => true,
        ),
    ),
    'export_fields' => array(
        'Advanced Import: Features' => array(
            'process_put'   => array('fn_advanced_import_set_product_features', '#key', '#this', '@features_delimiter'),
            'linked'        => false,
            'multilang'     => true,
            'import_only'   => true,
            'hidden'        => true,
            'return_result' => true,
            'return_field'  => 'product_features',
        ),
        'Images' => [
            'process_put' => [
                'fn_advanced_import_set_product_images',
                '#key',
                '#this',
                '@images_path',
                '@images_delimiter',
                '@remove_images',
                '@preset'
            ],
            'linked'      => false,
            'import_only' => true,
            'is_aggregatable' => true,
        ],
        'Detailed image' => array(
            'process_put' => array(
                'fn_advanced_import_import_detailed_image',
                '@images_path',
                '%Thumbnail%',
                '#this',
                '0',
                'M',
                '#key',
                'product',
                '@preset'
            ),
        ),
    ),
    'options'             => array(
        'images_delimiter' => array(
            'title'                     => 'advanced_import.images_delimiter',
            'description'               => 'advanced_import.images_delimiter.description',
            'type'                      => 'input',
            'default_value'             => '///',
            'option_data_post_modifier' => function ($option, $preset) {
                if (isset($preset['file']) && isset($preset['file_extension'])) {
                    $ext = $preset['file_extension'];
                    if (isset($preset['options']['fields_delimiter']) && $ext === 'xml') {
                        $option['selected_value'] = $preset['options']['fields_delimiter'];
                    }
                }

                return $option;
            },
        ),
        'fields_delimiter' => [
            'title'                     => 'advanced_import.fields_delimiter',
            'description'               => 'advanced_import.fields_delimiter.description',
            'type'                      => 'input',
            'default_value'             => ',',
        ],
        'target_node'      => array(
            'title'                     => 'advanced_import.target_node',
            'description'               => 'advanced_import.target_node.description',
            'type'                      => 'input',
            'default_value'             => implode(Xml::PATH_DELIMITER, array('yml_catalog', 'shop', 'offers', 'offer')),
            'position'                  => 5,
            'hidden'                    => true,
            'option_data_post_modifier' => function ($option, $preset) {
                if (isset($preset['file']) && isset($preset['file_extension'])) {
                    $ext = $preset['file_extension'];

                    if ($ext !== 'xml') {
                        $option['control_group_meta'] = 'hidden';
                    }
                }

                return $option;
            },
        ),
        'images_path'      => array(
            'option_data_post_modifier' => function ($option, $preset) {
                $option['display_value'] = '';

                $company_id = !empty($preset['company_id']) ? (int) $preset['company_id'] : (int) fn_get_runtime_company_id();

                $companies_image_directories = fn_advanced_import_get_companies_import_images_directory();

                $option['companies_image_directories'] = $companies_image_directories;

                if (isset($companies_image_directories[$company_id])) {
                    $option['input_prefix'] = $companies_image_directories[$company_id]['relative_path'];

                    if (!empty($option['selected_value'])) {
                        $option['display_value'] = str_replace(
                            $companies_image_directories[$company_id]['exim_path'],
                            '',
                            $option['selected_value']
                        );
                    }
                }

                return $option;
            },
        ),
        'remove_images'    => array(
            'title'       => 'advanced_import.delete_additional_images',
            'description' => 'advanced_import.delete_additional_images_tooltip',
            'type'        => 'checkbox',
            'import_only' => true,
            'tab'         => 'settings',
            'section'     => 'additional',
            'position'    => 910,
        ),
        'test_import'      => array(
            'title'         => 'advanced_import.test_import',
            'description'   => 'advanced_import.test_import_tooltip',
            'type'          => 'checkbox',
            'import_only'   => true,
            'tab'           => 'settings',
            'section'       => 'general',
            'position'      => 780,
            'sampling_size' => 5,
        ),
        'import_strategy'  => array(
            'title'                     => 'advanced_import.import_strategy',
            'description'               => 'advanced_import.import_strategy_tooltip',
            'type'                      => 'select',
            'import_only'               => true,
            'tab'                       => 'settings',
            'section'                   => 'general',
            'position'                  => 790,
            'variants'                  => array(
                ImportStrategies::IMPORT_ALL      => 'advanced_import.import_all',
                ImportStrategies::UPDATE_EXISTING => 'update_existing_products_only',
                ImportStrategies::CREATE_NEW      => 'advanced_import.create_new_products_only',
            ),
            'default_value'             => ImportStrategies::IMPORT_ALL,
            'option_data_post_modifier' => 'fn_advanced_import_set_import_strategy_option_value',
        ),
        'files_path' => [
            'title' => 'downloadable_product_files_directory',
            'description' => 'text_files_directory',
            'type' => 'input',
            'default_value' => $default_files_path,
            'option_template' => 'addons/advanced_import/views/import_presets/components/option_fileeditor_open.tpl',
            'notes' => __('advanced_import.text_popup_file_editor_notice_full_link', [
                '[target]'    => 'files_path',
                '[link_text]' => __('file_editor'),
            ]),
            'position' => 700,
        ],
    ),
);

$schema['options']['test_import']['description_params'] = array(
    $schema['options']['test_import']['sampling_size'],
);

if (isset($schema['export_fields'])) {
    $schema['export_fields']['Product code']['example_value'] = 'B0002OG6NY';
    $schema['export_fields']['Language']['example_value'] = 'en';
    $schema['export_fields']['Product id']['example_value'] = '130';
    $schema['export_fields']['Category']['example_value'] = 'Computers///Desktops';
    $schema['export_fields']['Secondary categories']['example_value'] = 'Computers///New products';
    $schema['export_fields']['List price']['example_value'] = '1750.00';
    $schema['export_fields']['Price']['example_value'] = '1600.00';
    $schema['export_fields']['Status']['example_value'] = 'A';
    $schema['export_fields']['Popularity']['example_value'] = '8';
    $schema['export_fields']['Quantity']['example_value'] = '50';
    $schema['export_fields']['Weight']['example_value'] = '20.25';
    $schema['export_fields']['Min quantity']['example_value'] = '1';
    $schema['export_fields']['Max quantity']['example_value'] = '10';
    $schema['export_fields']['Quantity step']['example_value'] = '1';
    $schema['export_fields']['List qty count']['example_value'] = '10';
    $schema['export_fields']['Shipping freight']['example_value'] = '2.00';
    $schema['export_fields']['Date added']['example_value'] = '25 Dec 2011 14:05:00';
    $schema['export_fields']['Downloadable']['example_value'] = 'Y';
    $schema['export_fields']['Files']['example_value'] = '/home/client/public_html/cscart-4.4.1/var/files/exim/backup/downloads/filename.pdf';
    $schema['export_fields']['Ship downloadable']['example_value'] = 'Y';
    $schema['export_fields']['Inventory tracking']['example_value'] = 'D';
    $schema['export_fields']['Out of stock actions']['example_value'] = 'B';
    $schema['export_fields']['Free shipping']['example_value'] = 'Y';
    $schema['export_fields']['Zero price action']['example_value'] = 'A';
    $schema['export_fields']['Thumbnail']['example_value'] = '/home/client/public_html/cscart/var/files/exim/backup/images/thumbnail_image.jpg';
    $schema['export_fields']['Detailed image']['example_value'] = '/home/client/public_html/cscart/var/files/exim/backup/images/detailed_image.jpg';
    $schema['export_fields']['Product name']['example_value'] = 'Adidas Men’s ClimaCool Short Sleeve Mock';
    $schema['export_fields']['Description']['example_value'] = 'ClimaCool is softer than cotton and resists pilling better than other natural and synthetic fibers. The shape and placement of ClimaCool fibers help move moisture quickly to the outer surface, where it evaporates away from the body.';
    $schema['export_fields']['Short description']['example_value'] = '100% circular rib Coolmax« Extreme 1x1 mini-rib solid pique mock with UV and anti-microbial finish.';
    $schema['export_fields']['Meta keywords']['example_value'] = 'adidas, climacool, clima cool, mock turtleneck, golf shirts, uv protection, sun';
    $schema['export_fields']['Meta description']['example_value'] = 'Adidas Men’s ClimaCool Short Sleeve Mock';
    $schema['export_fields']['Search words']['example_value'] = 'adidas, climacool, men';
    $schema['export_fields']['Page title']['example_value'] = 'Adidas Men’s ClimaCool Short Sleeve Mock';
    $schema['export_fields']['Promo text']['example_value'] = 'FREE US shipping over $100! Orders within next 2 days will be shipped on Monday';
    $schema['export_fields']['Taxes']['example_value'] = 'VAT, test';
    $schema['export_fields']['Features']['example_value'] = 'Color: S[Red]';
    $schema['export_fields']['Options']['example_value'] = '(CS-Cart) Your age: IG';
    $schema['export_fields']['Items in box']['example_value'] = 'min:1;max:5';
    $schema['export_fields']['Box size']['example_value'] = 'length:10;width:15;height:15';
    $schema['export_fields']['Usergroup IDs']['example_value'] = '0';
    $schema['export_fields']['Available since']['example_value'] = '25 Dec 2015 14:05:00';
    $schema['export_fields']['Product availability']['example_value'] = 'in stock';
    $schema['export_fields']['Options type']['example_value'] = 'S';
    $schema['export_fields']['Exceptions type']['example_value'] = 'F';
    $schema['export_fields']['Images']['example_value'] = 'exim/backup/images/additional_image.jpg';
    $schema['export_fields']['Vendor']['example_value'] = 'CS-Cart';
    $schema['export_fields']['Variation group code']['example_value'] = 'T-shirt_Need4Sports';
    $schema['export_fields']['Variation set as default']['example_value'] = 'Y';
}

return $schema;
