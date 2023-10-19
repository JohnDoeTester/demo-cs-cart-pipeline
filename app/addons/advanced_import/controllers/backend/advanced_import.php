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

use Tygh\Addons\AdvancedImport\Exceptions\DownloadException;
use Tygh\Addons\AdvancedImport\Exceptions\FileNotFoundException;
use Tygh\Addons\AdvancedImport\Exceptions\ReaderNotFoundException;
use Tygh\Enum\Addons\AdvancedImport\ImportStatuses;
use Tygh\Addons\AdvancedImport\ServiceProvider;
use Tygh\Enum\Addons\AdvancedImport\RelatedObjectTypes;
use Tygh\Enum\NotificationSeverity;
use Tygh\Exceptions\PermissionsException;
use Tygh\Registry;
use Tygh\Http;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */

/** @var \Tygh\Addons\AdvancedImport\Presets\Manager $presets_manager */
$presets_manager = ServiceProvider::getPresetManager();
/** @var \Tygh\Addons\AdvancedImport\Presets\Importer $presets_importer */
$presets_importer = Tygh::$app['addons.advanced_import.presets.importer'];
/** @var \Tygh\Addons\AdvancedImport\FileManager $file_manager */
$file_manager = Tygh::$app['addons.advanced_import.file_manager'];
$current_company = (int) fn_get_runtime_company_id();

if (version_compare(PHP_VERSION, '8.1', '<')) {
    ini_set('auto_detect_line_endings', true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_REQUEST = array_merge([
        'preset_id' => 0,
        'fields'    => [],
    ], $_REQUEST);

    $redirect_url = 'import_presets.manage';

    if ($mode === 'clone') {
        $cloning_preset_id = $_REQUEST['preset_id'];

        $presets_manager->clonePreset($cloning_preset_id, $current_company);

        if (defined('AJAX_REQUEST')) {
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            Tygh::$app['ajax']->assign('force_redirection', fn_url($redirect_url));
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }
    if ($mode === 'import') {
        $preset = $presets_manager->findById($_REQUEST['preset_id']);

        $archives_temp = rtrim($preset['options']['images_path'], '/') . '/' . $file_manager::TEMP_ARCHIVE_FOLDER . $preset['preset_id'];
        $archives_path = fn_get_files_dir_path($preset['company_id']) . $archives_temp;

        if (fn_advanced_import_check_vendor_in_common_preset($preset['company_id'])) {
            if (!empty(Tygh::$app['session']['advanced_import_path_archives'])) {
                $preset['options']['images_path'] = Tygh::$app['session']['advanced_import_path_archives'];
            }
            $archives_temp = rtrim($preset['options']['images_path'], '/') . '/' . $file_manager::TEMP_ARCHIVE_FOLDER . $preset['preset_id'];
            $archives_path = fn_get_files_dir_path(Registry::get('runtime.company_id')) . $archives_temp;
        }

        if (
            file_exists($archives_path)
            && $uploaded_files = fn_get_dir_contents($archives_path, false, true)
        ) {
            fn_advanced_import_decompress_archives($uploaded_files, $archives_path);
            $preset['options']['images_path'] = $archives_temp;
        }

        if (!empty($preset)) {
            Registry::set('runtime.advanced_import.in_progress', true, true);
            if ($current_company && $current_company !== $preset['company_id']) {
                unset($_REQUEST['fields']);
            }
            /** @var \Tygh\Addons\AdvancedImport\Readers\Factory $reader_factory */
            $reader_factory = ServiceProvider::getReadersFactory();
            $is_success = false;
            try {
                $reader = $reader_factory->get($preset);
                if (!empty($_REQUEST['fields'])) {
                    $fields_mapping = array_combine(
                        array_column($_REQUEST['fields'], 'name'),
                        $_REQUEST['fields']
                    );
                } else {
                    $fields_mapping = $presets_manager->getFieldsMapping($preset['preset_id']);
                }

                $pattern = $presets_manager->getPattern($preset['object_type']);
                $schema = $reader->getSchema();
                $schema->showNotifications();
                $schema = $schema->getData();

                $remapping_schema = $presets_importer->getEximSchema(
                    $schema,
                    $fields_mapping,
                    $pattern
                );

                if ($remapping_schema) {
                    $presets_importer->setPattern($pattern);
                    $result = $reader->getContents(null, $schema);
                    $result->showNotifications();

                    $import_items = $presets_importer->prepareImportItems(
                        $result->getData(),
                        $fields_mapping,
                        $preset['object_type'],
                        true,
                        $remapping_schema
                    );

                    $presets_manager->updateState([
                        'preset_id'      => $preset['preset_id'],
                        'last_launch'    => TIME,
                        'last_status'    => ImportStatuses::IN_PROGRESS,
                        'file'           => $preset['file'],
                        'file_type'      => $preset['file_type'],
                    ]);

                    $preset['options']['preset'] = $preset;
                    unset($preset['options']['preset']['options']);

                    // Sets execution timeout for files getting from remote server
                    Http::setDefaultTimeout(ADVANCED_IMPORT_HTTP_EXECUTION_TIMEOUT);

                    $company_id = $preset['company_id'];
                    if (fn_allowed_for('MULTIVENDOR') && !$preset['company_id'] && Registry::get('runtime.company_id')) {
                        $company_id = Registry::get('runtime.company_id');
                    }

                    $is_success = fn_execute_as_company(
                        static function () use ($pattern, $import_items, $preset) {
                            return fn_import($pattern, $import_items, $preset['options']);
                        },
                        $company_id
                    );
                }
            } catch (ReaderNotFoundException $e) {
                fn_set_notification('E', __('error'), __('error_exim_cant_read_file'));
            } catch (PermissionsException $e) {
                fn_set_notification('E', __('error'), __('advanced_import.cant_load_file_for_company'));
            } catch (FileNotFoundException $e) {
                fn_set_notification('E', __('error'), __('advanced_import.file_not_loaded'));
            } catch (DownloadException $e) {
                fn_set_notification('E', __('error'), __('advanced_import.cant_load_file'));
            }
            $presets_manager->updateState([
                'preset_id'   => $preset['preset_id'],
                'last_status' => $is_success
                    ? ImportStatuses::SUCCESS
                    : ImportStatuses::FAIL,
                'last_result' => Registry::get('runtime.advanced_import.result'),
            ]);

            unset(Tygh::$app['session']['advanced_import_path_archives']);
            if ($is_success) {
                fn_rm($archives_path);
            } else {
                $files = scandir($archives_path);
                foreach ($files as $file) {
                    if (
                        !fn_advanced_import_check_file_is_archive($file)
                        && !in_array($file, ['.', '..'])
                    ) {
                        fn_rm($archives_path . '/' . $file);
                    }
                }
            }

            Registry::set('runtime.advanced_import.in_progress', false, true);
            if (!empty($_REQUEST['return_url'])) {
                $redirect_url = $_REQUEST['return_url'];
            } else {
                $redirect_url = 'import_presets.manage?object_type=' . $preset['object_type'];
            }
        } else {
            fn_set_notification('E', __('error'), __('advanced_import.preset_not_found'));
        }
    }
    if ($mode === 'export_example') {
        $data = [];
        $empty_preset = true;
        $preset_id = $_REQUEST['preset_id'];
        $preset = $presets_manager->findById($preset_id);
        $preset['fields'] = $presets_manager->getFieldsMapping($preset_id);
        $relations = $presets_manager->getRelations($preset['object_type']);
        $pattern = fn_exim_get_pattern_definition('products', 'export');

        foreach ($preset['fields'] as $field_data) {
            if (empty($field_data['related_object'])) {
                continue;
            }

            $empty_preset = false;
            $field_type = $field_data['related_object_type'];
            if ($field_type === RelatedObjectTypes::FEATURE) {
                $field = $relations[$field_type]['fields'][$field_data['related_object']]['internal_name'];
                $data[0][$field] = '';
                continue;
            }

            $field = $field_data['related_object'];
            $data[0][$field] = isset($relations[$field_type]['fields'][$field]['example_value'])
                ? $relations[$field_type]['fields'][$field]['example_value']
                : '';
            $pattern_data = isset($pattern['export_fields'][$field])
                ? $pattern['export_fields'][$field]
                : [];

            if (!isset($pattern_data['process_get'])) {
                continue;
            }
            $process_function = array_shift($pattern_data['process_get']);
            $data[0][$field] = fn_advanced_import_replace_example_delimiter($process_function, $data[0], $field, $preset, $pattern_data, $pattern);
        }

        if ($empty_preset) {
            fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('advanced_import.cant_export_example'));
        } else {
            $preset['options']['filename'] = 'preset_' . $preset_id . '_example_' . fn_date_format(time(), '%d%m%Y') . '.csv';
            fn_exim_put_csv($data, $preset['options'], '"');
            $url = fn_url('exim.get_file?filename=' . rawurlencode($preset['options']['filename']));
            return [CONTROLLER_STATUS_OK, $url];
        }
    }

    if (defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('non_ajax_notifications', true);
        Tygh::$app['ajax']->assign('force_redirection', fn_url($redirect_url));
        return [CONTROLLER_STATUS_NO_CONTENT];
    }
    return [CONTROLLER_STATUS_OK, $redirect_url];
}

if ($mode === 'modifiers_list') {
    /** @var \Tygh\Addons\AdvancedImport\SchemasManager $schema_manager */
    $schema_manager = Tygh::$app['addons.advanced_import.schemas_manager'];
    $modifiers = $schema_manager->getModifiers();

    Tygh::$app['view']->assign([
        'modifiers' => $modifiers['operations'],
    ]);
}
