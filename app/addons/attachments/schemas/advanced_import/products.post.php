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

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$default_attachments_path = 'exim/backup/attachments/';
if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.simple_ultimate')) {
    $default_attachments_path = Registry::get('runtime.forced_company_id') . '/' . $default_attachments_path;
}

/** @var array<string, array> $schema */

$schema['export_fields']['Attachments'] = [
    'process_put' => [
        'fn_attachments_import_product_attachments',
        '#key',
        '#this',
        '@attachments_path',
        '@attachments_delimiter',
        '@remove_attachments',
        '@preset',
        '#row'
    ],
    'linked'      => true,
    'import_only' => true,
    'is_aggregatable' => true,
];

$schema['options']['remove_attachments'] = [
    'title'       => 'attachments.delete_attachments',
    'description' => 'attachments.delete_attachments_tooltip',
    'type'        => 'checkbox',
    'import_only' => true,
    'tab'         => 'settings',
    'section'     => 'additional',
    'position'    => 920,
];

$schema['options']['attachments_delimiter'] = [
    'title'                     => 'attachments.attachments_delimiter',
    'description'               => 'attachments.attachments_delimiter.description',
    'type'                      => 'input',
    'default_value'             => ',',
    'position' => 810,
];

$schema['options']['attachments_path'] = [
    'title'           => 'attachments.attachments_directory',
    'type'            => 'input',
    'option_template' => 'addons/advanced_import/views/import_presets/components/option_fileeditor_open.tpl',
    'description'     => 'advanced_import.text_popup_file_editor_notice_full_link',
    'description_params' => [
        '[target]'    => 'attachments_path',
        '[link_text]' => __('file_editor'),
    ],
    'default_value'   => $default_attachments_path,
    'position'        => 820,
];

$schema['post_processing']['send_errors_notification'] = [
    'function'        => 'fn_attachments_exim_send_errors_notification',
    'args'            => [],
    'import_only'     => true,
];

return $schema;
