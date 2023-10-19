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

use Tygh\Addons\AdvancedImport\Exceptions\DownloadException;
use Tygh\Addons\AdvancedImport\Exceptions\FileNotFoundException;
use Tygh\Addons\AdvancedImport\Exceptions\ReaderNotFoundException;
use Tygh\Enum\Addons\AdvancedImport\PresetFileTypes;
use Tygh\Addons\AdvancedImport\ServiceProvider;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\YesNo;
use Tygh\Exceptions\PermissionsException;
use Tygh\Registry;
use Tygh\Tools\Url;

/** @var string $mode */
/** @var string $action */

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
    if ($mode === 'upload') {
        $file_types = !empty($_REQUEST['type_' . $file_manager::UPLOADED_FILE_NAME]) ? $_REQUEST['type_' . $file_manager::UPLOADED_FILE_NAME] : [];
        $files = !empty($_REQUEST['file_' . $file_manager::UPLOADED_FILE_NAME]) ? $_REQUEST['file_' . $file_manager::UPLOADED_FILE_NAME] : [];
        $preset_id = isset($_REQUEST['preset_id']) ? (int) $_REQUEST['preset_id'] : 0;

        $preset = [
            'preset_id' => $preset_id,
            'file_type' => isset($file_types[$preset_id]) ? $file_types[$preset_id] : PresetFileTypes::LOCAL,
            'file'      => isset($files[$preset_id]) ? $files[$preset_id] : ''
        ];

        if ($preset['preset_id']) {
            $old_preset = $presets_manager->findById($preset['preset_id']);
        } else {
            $old_preset = null;
        }

        if (empty($old_preset)) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $preset['company_id'] = $old_preset['company_id'];
        $file = $file_manager->uploadPresetFile($preset);

        if ($file) {
            $file = reset($file);

            if ($preset['file_type'] === PresetFileTypes::LOCAL) {
                $preset['file'] = $file['name'];
            }
            $preset['file_extension'] = fn_advanced_import_get_file_extension_by_mimetype($file['name'], $file['type']);
        } else {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_exim_no_file_uploaded'));
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        if (empty($preset['file_extension']) && !empty($file)) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('text_not_allowed_to_upload_file_extension', [
                '[ext]' => fn_get_file_ext($file['name'])
            ]));

            exit;
        }

        if ($current_company !== $preset['company_id'] && $preset['file_extension'] !== $old_preset['file_extension']) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('advanced_import.file_extension_was_not_supported_by_owner', [
                '[ext]'  => $old_preset['file_extension'],
                '[name]' => $preset['file'],
            ]));
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        if ($preset['file_type'] === PresetFileTypes::LOCAL) {
            $preset['file'] = $file_manager->moveUpload($file['name'], $file['path'], $current_company);
        }
        if ($action === 'detailed') {
            $redirect_url = Url::buildUrn(['import_presets', 'update'], [
                'preset_id' => $preset['preset_id'],
            ]);
        }

        $presets_manager->update($preset['preset_id'], $preset);

        if (empty($redirect_url)) {
            $redirect_url = Url::buildUrn(['import_presets', 'manage'], [
                'object_type' => $old_preset['object_type'],
            ]);
        }

        Tygh::$app['ajax']->assign('force_redirection', fn_url($redirect_url));

        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if ($mode === 'update') {
        $redirect_url = !empty($_REQUEST['preset_id'])
            ? 'import_presets.update?preset_id=' . $_REQUEST['preset_id']
            : 'import_presets.add?object_type=' . $_REQUEST['object_type'];

        if (
            empty($_REQUEST['file']) && (
                empty($_REQUEST['file_' . $file_manager::UPLOADED_FILE_NAME])
                || empty(reset($_REQUEST['file_' . $file_manager::UPLOADED_FILE_NAME]))
                || empty($_REQUEST['type_' . $file_manager::UPLOADED_FILE_NAME])
                || empty(reset($_REQUEST['type_' . $file_manager::UPLOADED_FILE_NAME]))
            )
        ) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_exim_no_file_uploaded'));

            return [CONTROLLER_STATUS_OK, $redirect_url];
        }

        fn_trusted_vars('fields');
        $preset = array_merge([
            'preset_id'      => 0,
            'file_type'      => PresetFileTypes::LOCAL,
            'file'           => '',
        ], $_REQUEST);

        $file = $file_manager->uploadPresetFile($preset);

        if ($file) {
            $file = reset($file);
            $preset['file_extension'] = fn_advanced_import_get_file_extension_by_mimetype($file['name'], $file['type']);
            unset($preset['type_upload'], $preset['file_upload']);
        }

        if (empty($preset['file_extension']) && !empty($file['name'])) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('text_not_allowed_to_upload_file_extension', [
                '[ext]' => fn_get_file_ext($file['name'])
            ]));

            return [CONTROLLER_STATUS_OK, $redirect_url];
        }

        if (isset($preset['options']['images_path'])) {
            $images_directories = fn_advanced_import_get_import_images_directory($preset['company_id'], $preset['options']['images_path']);
            $preset['options']['images_path'] = $images_directories['exim_path'];
        }

        if ($file && $preset['file_type'] === PresetFileTypes::LOCAL) {
            // rename temporary file for a preset if exists
            $preset['file'] = $file_manager->moveUpload($file['name'], $file['path'], $current_company);
        }

        if ($preset['preset_id']) {
            $presets_manager->update($preset['preset_id'], $preset);
        } else {
            $preset['preset_id'] = $presets_manager->add($preset);
        }

        $folder_name = $file_manager::TEMP_ARCHIVE_FOLDER . $preset['preset_id'];
        if (isset($images_directories)) {
            $temp_images_path = fn_advanced_import_get_temporary_image_directory_path($preset, $folder_name, $images_directories['relative_path']);
        } else {
            $temp_images_path = fn_advanced_import_get_temporary_image_directory_path($preset, $folder_name, '');
        }

        if (isset($preset['uploaded_archives'])) {
            $uploaded_archives = $preset['uploaded_archives'];

            if (
                file_exists($temp_images_path)
                && $preset['archive_path'] === $temp_images_path
            ) {
                $uploaded_files = fn_get_dir_contents($temp_images_path, false, true);
                foreach ($uploaded_files as $file) {
                    if (!in_array($file, $uploaded_archives)) {
                        fn_rm($temp_images_path . '/' . $file);
                    }
                }
            }
        }

        if (fn_advanced_import_check_vendor_in_common_preset($preset['company_id'])) {
            $images_directories = fn_advanced_import_get_import_images_directory(Registry::get('runtime.company_id'), $_REQUEST['options']['images_path']);
            $temp_images_path = fn_advanced_import_get_temporary_image_directory_path($preset, $folder_name, $images_directories['relative_path']);
        }
        fn_mkdir($temp_images_path);

        $archives = $file_manager->uploadArchiveImagesFile($preset);
        fn_advanced_import_copy_archives($temp_images_path, $archives);
        $redirect_url = 'import_presets.update?preset_id=' . $preset['preset_id'];

        if ($action === 'import') {
            if (fn_advanced_import_check_vendor_in_common_preset($preset['company_id'])) {
                Tygh::$app['session']['advanced_import_path_archives'] = rtrim($preset['options']['images_path'], '/');
            }
            $redirect_url .= '&start_import=1';
        } elseif (empty($_REQUEST['preset_id'])) {
            $redirect_url .= '&step_map_fields=true';
        }

        return [CONTROLLER_STATUS_OK, $redirect_url];
    }

    if ($mode === 'm_delete') {
        $_REQUEST = array_merge(
            [
                'preset_ids'   => [],
                'object_type'  => 'products',
                'redirect_url' => 'import_presets.manage',
            ],
            $_REQUEST
        );

        foreach ($_REQUEST['preset_ids'] as $preset_id) {
            $presets_manager->delete($preset_id);
        }
        Tygh::$app['ajax']->assign('force_redirection', fn_url($_REQUEST['redirect_url']));
        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if ($mode === 'delete') {
        $_REQUEST = array_merge(
            [
                'preset_id' => 0,
                'object_type' => 'products',
            ],
            $_REQUEST
        );

        $presets_manager->delete($_REQUEST['preset_id']);

        return [CONTROLLER_STATUS_OK, 'import_presets.manage?object_type=' . $_REQUEST['object_type']];
    }

    if ($mode === 'validate_modifier') {
        $params = array_merge([
            'modifier' => '',
            'value'    => '',
            'notify'   => YesNo::YES,
        ], $_REQUEST);

        $presets_importer->applyModifier($params['value'], $params['modifier'], []);

        Tygh::$app['ajax']->assign('is_valid', !fn_notification_exists('type', 'E'));

        if ($params['notify'] === YesNo::NO) {
            fn_get_notifications();
        }

        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if ($mode === 'remove_upload') {
        $preset = $presets_manager->findById($_REQUEST['preset_id']);
        $file_manager->removeFile($preset['file'], $current_company);
        $preset['file'] = $preset['file_type'] = '';
        $presets_manager->updateState($preset);
        Tygh::$app['ajax']->assign('force_redirection', fn_url('import_presets.manage?object_type=' . $_REQUEST['object_type']));
        return [CONTROLLER_STATUS_OK];
    }
}

if (
    $mode === 'update'
    || $mode === 'add'
    || $mode === 'get_fields'
) {
    foreach (array_keys(fn_get_short_companies()) as $company_id) {
        $file_manager->initFilesDirectories($company_id);
    }
}

if ($mode === 'manage') {
    $params = array_merge([
        'page'              => 1,
        'object_type'       => 'products',
        'items_per_page'    => Registry::get('settings.Appearance.admin_elements_per_page')
    ], $_REQUEST);

    if (fn_allowed_for('MULTIVENDOR')) {
        [$common_presets, $search] = fn_get_import_presets(array_merge($params, [
            'company_id'     => 0,
            'items_per_page' => 0  // Gets all common presets
        ]));
        $common_presets = array_map(static function ($common_preset) use ($file_manager, $current_company) {
            if ($common_preset['file_type'] === 'server') {
                $common_preset['file_path'] = $file_manager->getFilePath($common_preset['file'], $current_company);
            }
            return $common_preset;
        }, $common_presets);
        if ($common_presets) {
            [$modifiers_presense,] = $presets_manager->find(
                false,
                [
                    ['modifier', '<>', ''],
                    'ipf.preset_id' => array_keys($common_presets),
                ],
                [
                    [
                        'table' => ['?:import_preset_fields' => 'ipf'],
                        'condition' => ['ip.preset_id = ipf.preset_id'],
                    ],
                    [
                        'table' => ['?:import_preset_descriptions' => 'ipd'],
                        'condition' => ['ip.preset_id = ipd.preset_id'],
                    ],
                ],
                [
                    'COUNT(ipf.field_id)' => 'has_modifiers',
                ]
            );
            $common_presets = fn_array_merge($common_presets, $modifiers_presense);
        }
        Tygh::$app['view']->assign(['common_presets' => $common_presets]);
    }

    if ($current_company) {
        $params['company_id'] = $current_company;
        [$presets, $search] = fn_get_import_presets($params);
    } else {
        $params['only_vendors_presets'] = true;
        [$presets, $search] = fn_get_import_presets($params);
        if (!empty($common_presets)) {
            $presets = array_diff_key($presets, $common_presets);
        }
    }

    if (
        empty($common_presets)
        && empty($presets)
        && $params['page'] === 1
    ) {
        return [CONTROLLER_STATUS_REDIRECT, 'import_presets.add?object_type=products'];
    }

    if ($presets) {
        [$modifiers_presense,] = $presets_manager->find(
            false,
            [
                ['modifier', '<>', ''],
                'ipf.preset_id' => array_keys($presets),
            ],
            [
                [
                    'table'     => ['?:import_preset_fields' => 'ipf'],
                    'condition' => ['ip.preset_id = ipf.preset_id'],
                ],
                [
                    'table'     => ['?:import_preset_descriptions' => 'ipd'],
                    'condition' => ['ip.preset_id = ipd.preset_id'],
                ],
            ],
            [
                'COUNT(ipf.field_id)' => 'has_modifiers',
            ]
        );

        $presets = fn_array_merge($presets, $modifiers_presense);

        foreach ($presets as &$preset) {
            if ($preset['file_type'] === PresetFileTypes::SERVER) {
                $preset['file_path'] = $file_manager->getFilePath($preset['file'], $current_company);
            }
        }
        unset($preset);
    }

    Tygh::$app['view']->assign([
        'presets'           => $presets,
        'search'            => $search,
        'company_id'        => $current_company,
        'object_type'       => $params['object_type'],
    ]);
}

if ($mode === 'add') {
    $preset = array_merge([
        'object_type' => 'products',
    ], $_REQUEST);

    $pattern = $presets_manager->getPattern($preset['object_type']);
    $preset = $presets_manager->mergePattern($preset, $pattern);

    Registry::set('navigation.tabs', [
        'general' => [
            'title' => __('file'),
            'js'    => true,
        ],
        'fields'  => [
            'title'        => __('advanced_import.fields_mapping'),
            'href'         => 'import_presets.get_fields',
            'ajax'         => true,
            'ajax_onclick' => true,
        ],
        'options' => [
            'title' => __('settings'),
            'js'    => true,
        ],
    ]);

    Tygh::$app['view']->assign([
        'pattern'     => $pattern,
        'preset'      => $preset,
        'is_mve'      => fn_allowed_for('MULTIVENDOR'),
        'allowed_ext' => fn_advanced_import_get_allowed_extensions(),
    ]);
}

if ($mode === 'update') {
    if (empty($_REQUEST['preset_id'])) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    [$presets] = fn_get_import_presets([
        'preset_id' => $_REQUEST['preset_id'],
    ]);

    if (!$presets) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $preset = reset($presets);
    if ($current_company && $preset['company_id'] && $preset['company_id'] !== $current_company) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }
    $pattern = $presets_manager->getPattern($preset['object_type']);
    $preset = $presets_manager->mergePattern($preset, $pattern);

    Registry::set('navigation.tabs', [
        'general' => [
            'title' => __('file'),
            'js'    => true,
        ],
        'fields'  => [
            'title'        => __('advanced_import.fields_mapping'),
            'href'         => 'import_presets.get_fields?preset_id=' . $_REQUEST['preset_id'],
            'ajax'         => true,
            'ajax_onclick' => true,
        ],
        'options' => [
            'title' => __('settings'),
            'js'    => true,
        ],
    ]);


    if ($preset['company_id'] === $current_company) {
        $allowed_ext = fn_advanced_import_get_allowed_extensions();
    } else {
        $allowed_ext = [$preset['file_extension']];
    }

    if ($current_company && $preset['company_id'] !== $current_company && !isset($_REQUEST['start_import'])) {
        Tygh::$app['view']->assign(['view_only' => true]);
    }

    if (isset($preset['options'])) {
        $path = rtrim(
            $preset['options']['images_path']['input_prefix'] . $preset['options']['images_path']['display_value'],
            '/'
        ) . '/' . $file_manager::TEMP_ARCHIVE_FOLDER . $preset['preset_id'];
        $archive_images = fn_advanced_import_get_archives_list($path);
    }

    Tygh::$app['view']->assign([
        'pattern'          => $pattern,
        'preset'           => $preset,
        'start_import'     => !empty($_REQUEST['start_import']) ? $_REQUEST['start_import'] : false,
        'disable_picker'   => (bool) $current_company,
        'allowed_ext'      => $allowed_ext,
        'is_mve'           => fn_allowed_for('MULTIVENDOR'),
        'archive_images'   => isset($archive_images) ? $archive_images : [],
        'archive_path'     => isset($path) ? $path : '',
        'step_map_fields'  => !empty($_REQUEST['step_map_fields']) ? true : false
    ]);
}

if ($mode === 'get_fields') {
    if (!defined('AJAX_REQUEST')) {
        if (!empty($_REQUEST['preset_id'])) {
            $redirect_url = sprintf('import_presets.update?preset_id=%s', $_REQUEST['preset_id']);
        } else {
            $redirect_url = 'import_presets.manage';
        }

        return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
    }

    $preset = array_merge([
        'file'           => '',
        'file_type'      => PresetFileTypes::LOCAL,
        'preset_id'      => 0,
        'object_type'    => 'products',
        'fields'         => [],
        'options'        => [],
        'company_id'     => null,
    ], $_REQUEST);

    $company_directory_id = null;
    if ($preset['preset_id']) {
        $preset = $presets_manager->findById((int) $preset['preset_id']);
        $preset['fields'] = $presets_manager->getFieldsMapping((int) $preset['preset_id']);
        if (isset($_REQUEST['file'])) {
            $preset['file'] = $_REQUEST['file'];
        }
        if (isset($_REQUEST['file_type'])) {
            $preset['file_type'] = $_REQUEST['file_type'];
        }
        if (isset($_REQUEST['options'])) {
            $preset['options'] = array_merge($preset['options'], $_REQUEST['options']);
        }
        if (isset($_REQUEST['company_id'])) {
            $preset['company_id'] = $_REQUEST['company_id'];
        }
        if (empty($preset['file']) && !empty($preset['fields'])) {
            $action = 'get_mapping';
        }
        $company_directory_id = $current_company;
    }

    $view_only = $current_company && $preset['company_id'] !== $current_company;
    $relations = $presets_manager->getRelations($preset['object_type']);
    if ($action === 'get_mapping') {
        Tygh::$app['view']->assign([
            'preset'                 => $preset,
            'fields'                 => array_keys($preset['fields']),
            'relations'              => $relations,
            'show_buttons_container' => false,
            'view_only'              => $view_only,
            'detailed_preset_page'   => true,
        ]);
    } elseif ($preset['file']) {
        $reader_factory = ServiceProvider::getReadersFactory($company_directory_id);

        /** @var Tygh\Addons\AdvancedImport\Readers\IReader $reader */
        try {
            $reader = $reader_factory->get($preset);

            $schema = $reader->getSchema();
            $schema->showNotifications();
            $fields = $schema->getData();

            $result = $reader->getContents(1, $fields);
            $result->showNotifications();

            if ($result->getData()) {
                $preview = $presets_importer->prepareImportItems(
                    $result->getData(),
                    $preset['fields'],
                    $preset['object_type']
                );
            }

            $pattern = $presets_manager->getPattern($preset['object_type']);
            $preset = $presets_manager->mergePattern($preset, $pattern);

            Tygh::$app['view']->assign([
                'preset'                 => $preset,
                'fields'                 => $fields,
                'preview'                => isset($preview) ? $preview : null,
                'relations'              => $relations,
                'show_buttons_container' => $action === 'import',
                'view_only'              => $view_only,
            ]);

            Tygh::$app['ajax']->assign('has_fields', !empty($fields));
            Tygh::$app['ajax']->assign('file_extension', $reader->getExtension());
        } catch (ReaderNotFoundException $e) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_exim_cant_read_file'));
            return [CONTROLLER_STATUS_NO_CONTENT];
        } catch (PermissionsException $e) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('advanced_import.cant_load_file_for_company'));
            return [CONTROLLER_STATUS_NO_CONTENT];
        } catch (FileNotFoundException $e) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('advanced_import.file_not_loaded'));
            return [CONTROLLER_STATUS_NO_CONTENT];
        } catch (DownloadException $e) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('advanced_import.cant_load_file'));
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }
}

if ($mode === 'get_file') {
    [$presets] = fn_get_import_presets([
        'preset_id' => $_REQUEST['preset_id'],
    ]);

    if (!$presets) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $preset = reset($presets);

    if (empty($preset['file'])) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    if ($preset['file'] && $preset['file_type'] === PresetFileTypes::URL) {
        fn_redirect($preset['file'], true);
    }

    $file_path = $file_manager->getFilePath($preset['file'], $current_company);

    if ($file_path) {
        fn_get_file($file_path);
    }
}

if ($mode === 'file_manager') {
    if (
        !isset($_REQUEST['path'])
        || !isset($_REQUEST['company_id'])
        || !isset($_REQUEST['option_id'])
    ) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $selected_company_id = (int) Registry::get('runtime.company_id');
    $company_id = !empty($_REQUEST['company_id']) ? (int) $_REQUEST['company_id'] : fn_get_runtime_company_id();

    if ($selected_company_id && $company_id !== $selected_company_id) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $path = fn_advanced_import_get_import_path($company_id, $_REQUEST['path'], $_REQUEST['option_id']);

    return [
        CONTROLLER_STATUS_REDIRECT,
        sprintf('file_editor.manage?in_popup=1&path=%s&container_id=%s', $path, md5(TIME))
    ];
}

if ($mode === 'get_archive') {
    [$presets] = fn_get_import_presets([
        'preset_id' => $_REQUEST['preset_id'],
    ]);

    if (!$presets) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $preset = reset($presets);

    if (empty($_REQUEST['file'])) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    if ($_REQUEST['file'] && $_REQUEST['file_type'] === PresetFileTypes::URL) {
        fn_redirect($_REQUEST['file'], true);
    }

    $archives_temp = rtrim($preset['options']['images_path'], '/') . '/' . $file_manager::TEMP_ARCHIVE_FOLDER . $preset['preset_id'];
    $company_id = !empty($preset['company_id']) ? (int) $preset['company_id'] : fn_get_runtime_company_id();
    $file_path = fn_get_files_dir_path($company_id) . $archives_temp  . '/' . $_REQUEST['file'];

    if ($file_path) {
        fn_get_file($file_path);
    }
}
