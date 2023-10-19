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

use Tygh\Addons\AdvancedImport\Presets\Manager as PresetsManager;
use Tygh\Addons\AdvancedImport\ServiceProvider;
use Tygh\Enum\Addons\AdvancedImport\ImportStrategies;
use Tygh\Enum\ObjectStatuses;
use Tygh\Tools\SecurityHelper;
use Tygh\Registry;

/**
 * Obtains list of product features for fields mapping.
 *
 * @param PresetsManager        $presets_manager Presets manager instance
 * @param array<string, string> $schema          Relations schema
 *
 * @return array<string, string|bool>
 */
function fn_advanced_import_get_product_features_list(PresetsManager $presets_manager, array $schema)
{
    [$features,] = fn_get_product_features(
        [
            'plain'         => true,
            'exclude_group' => true,
            'statuses'      => [
                ObjectStatuses::ACTIVE,
                ObjectStatuses::HIDDEN
            ]
        ],
        0,
        $presets_manager->getLangCode()
    );

    foreach ($features as &$feature) {
        $feature['show_description'] = true;
        $feature['show_name'] = false;
        $feature['description'] = $feature['internal_name'];
    }
    unset($feature);

    return $features;
}

/**
 * Aggregates product features values into a single field when importing a product.
 *
 * @param array $item            Imported product item
 * @param array $aggregated_data Aggregated features values
 *
 * @return array Array containing feature IDs as array keys and feature values as values
 */
function fn_advanced_import_aggregate_features(array $item, array $aggregated_data)
{
    foreach ($aggregated_data['values'] as $key => $value) {
        unset($aggregated_data['values'][$key]);

        if (
            !is_string($value)
            || !fn_string_not_empty($value)
        ) {
            continue;
        }

        [, $key] = explode('_', $key);
        $aggregated_data['values'][$key] = $value;
    }

    return $aggregated_data['values'];
}

/**
 * Updates product features in the database when importing a product.
 *
 * @param int    $product_id         Product ID
 * @param array  $features_list      Features list from ::fn_advanced_import_aggregate_features()
 * @param string $variants_delimiter Feature variants delimiter
 */
function fn_advanced_import_set_product_features($product_id, $features_list, $variants_delimiter = '///')
{
    if (!$features_list || !is_array($features_list)) {
        return;
    }

    static $features_cache = array();

    /** @var \Tygh\Addons\AdvancedImport\FeaturesMapper $features_mapper */
    $features_mapper = Tygh::$app['addons.advanced_import.features_mapper'];

    $main_lang = $features_mapper->getMainLanguageCode($features_list);

    $features_list = $features_mapper->remap($features_list, $variants_delimiter);

    if ($missing_features = array_diff(array_keys($features_list), array_keys($features_cache))) {
        $features_cache += db_get_hash_array(
            'SELECT feature_id, company_id, feature_type AS type FROM ?:product_features WHERE feature_id IN (?n)',
            'feature_id',
            $missing_features
        );
    }

    foreach ($features_list as $feature_id => &$feature) {
        $feature = array_merge($feature, $features_cache[$feature_id]);
    }
    unset($feature);

    if ($features_list) {
        return fn_exim_save_product_features_values($product_id, $features_list, $main_lang, false);
    }

    return [];
}

/**
 * Updates product images when importing a product.
 *
 * @param int          $product_id        Product ID
 * @param array|string $images            Images from import file
 * @param string       $images_path       Default dir to search files on server
 * @param string       $images_delimiter  Images delimiter
 * @param string       $remove_images     Whether to remove additional images
 * @param array        $preset            Import preset data
 */
function fn_advanced_import_set_product_images($product_id, $images, $images_path, $images_delimiter, $remove_images, $preset)
{
    if (
        fn_allowed_for('ULTIMATE')
        && Registry::get('runtime.simple_ultimate')
    ) {
        $images_path = Registry::get('runtime.forced_company_id') . '/' . $images_path;
    }

    if (is_string($images) && !fn_string_not_empty($images)
        || is_array($images) && !$images
    ) {
        return;
    }

    if (is_string($images)) {
        $images = explode($images_delimiter, $images);
    }

    foreach ($images as $i => $image) {
        $type = $i === 0 ? 'M' : 'A';

        $image = trim($image);
        if (!$image) {
            continue;
        }

        $options = array(
            'remove_images'     => $remove_images,
            'images_company_id' => $preset['company_id'],
        );
        
        fn_exim_import_images(
            $images_path,
            false,
            $image,
            $i * 10,
            $type,
            $product_id,
            'product',
            $options
        );
    }
}

/**
 * Hook handler: stores import results in the runtime to store them as the import result.
 *
 * @param array $pattern        Import/export pattern
 * @param array $import_data    Imported data
 * @param array $options        Import options
 * @param bool  $result         Import result
 * @param array $processed_data Import results
 */
function fn_advanced_import_import_post($pattern, $import_data, $options, $result, $processed_data)
{
    $processed_data = array_merge(
        Registry::ifGet('runtime.advanced_import.result', array()),
        $processed_data
    );

    Registry::set('runtime.advanced_import.result', $processed_data, true);
}

/**
 * Hook handler: stores notifications in the runtime to store them as the import result.
 *
 * @param string $type          Notification type
 *                              (E - error, W - warning, N - notice, O - order error on checkout, I - information)
 * @param string $title         Notification title
 * @param string $message       Notification message
 * @param string $message_state S - notification will be displayed unless it's closed, K - only once,
 *                              I - will be closed by timer
 * @param mixed  $extra         Extra data to save with notification
 * @param bool   $init_message  $title and $message will be processed by __ function if true
 */
function fn_advanced_import_set_notification_pre($type, $title, $message, $message_state, $extra, $init_message)
{
    if (AREA !== 'A' || $type !== 'E' || !Registry::get('runtime.advanced_import.in_progress')) {
        return;
    }

    $messages_list = Registry::ifGet('runtime.advanced_import.result.msg', array());

    $messages_list[] = $message;

    $messages_list = array_unique($messages_list);

    Registry::set('runtime.advanced_import.result.msg', $messages_list, true);
}

/**
 * Hook handler: removes company presets upon its removal.
 *
 * @param int  $company_id Company ID
 * @param bool $result     Whether company was removed
 */
function fn_advanced_import_delete_company($company_id, $result)
{
    if ($result) {
        /** @var PresetsManager $presets_manager */
        $presets_manager = ServiceProvider::getPresetManager();

        [$presets_list,] = $presets_manager->find(
            false,
            ['ip.company_id' => $company_id],
            false,
            ['ip.preset_id' => 'preset_id', 'ip.company_id' => 'company_id']
        );

        foreach ($presets_list as $preset) {
            $presets_manager->delete($preset['preset_id']);
        }
    }
}

/**
 * Wrapper for $presets_manager->find for the LastView functionality
 *
 * @param  array $params Params passed to the find method. Can be all standard search & sorting params.
 *                       E.g.
 *                       [
 *                          'items_per_page' => 10,
 *                          'page'=> 15,
 *                          'object_type' => 'products',
 *                          'preset_id' => 75,
 *                          'sort_by' => 'status',
 *                          'sort_order' => 'asc',
 *                       ]
 *                       See \Tygh\Addons\AdvancedImport\Presets\Manager::find() for reference.
 *
 * @return array        Array with two values: presets list and search parameters for templates
 */
function fn_get_import_presets(array $params)
{
    $preset_manager = ServiceProvider::getPresetManager();

    $limit = array();
    if (isset($params['items_per_page']) && is_numeric($params['items_per_page'])) {
        $limit['items_per_page'] = $params['items_per_page'];
    }
    if (!empty($params['page'])) {
        $limit['page'] = $params['page'];
    }
    if (empty($limit)) {
        $limit = false;
    }

    $condition = [];
    if (!empty($params['object_type'])) {
        $condition = array('ip.object_type' => $params['object_type']);
    }
    if (!empty($params['preset_id'])) {
        $condition = array('ip.preset_id' => $params['preset_id']);
    }
    if (!empty($params['only_vendors_presets'])) {
        $condition[] = ['ip.company_id', '<>', 0];
        if (isset($params['company_id'])) {
            unset($params['company_id']);
        }
    }
    if (isset($params['company_id'])) {
        $condition['ip.company_id'] = $params['company_id'];
    }

    $sorting = [];
    $sorting['sort_by'] = !empty($params['sort_by']) ? $params['sort_by'] : '';

    if (!empty($params['sort_order'])) {
        $sorting['sort_order'] = $params['sort_order'];
    }
    [$presets, $search] = $preset_manager->find(
        $limit,
        $condition,
        null,
        ['ip.*', 'ipd.*', 'ipst.last_launch', 'ipst.last_status', 'ipst.last_result', 'ipst.file', 'ipst.file_type'],
        $sorting
    );

    return [$presets, $search];
}

/**
 * Wrapper for $preset_manager->getName for the LastView functionality
 *
 * @param  int $preset_id
 *
 * @return bool|string
 */
function fn_get_import_preset_name($preset_id)
{
    $result = false;

    if (!$preset_id
        || !isset(Tygh::$app['addons.advanced_import.presets.manager'])
    ) {
        return $result;
    }

    $preset_manager = ServiceProvider::getPresetManager();
    $result = $preset_manager->getName($preset_id);

    return $result ? $result : false;
}

/**
 * Fetches array of paths to import image directory
 *
 * @param integer $company_id Company id
 * @param string  $path       User specified path
 *
 * @return array
 */
function fn_advanced_import_get_import_images_directory($company_id, $path = '')
{
    if ($path) {
        $path = fn_advanced_import_filter_user_path($path);
    }

    $files_dir = Registry::get('config.dir.files');

    $result = array(
        'absolute_path' => sprintf('%s%s/%s%s', $files_dir, $company_id, ADVANCED_IMPORT_PRIVATE_IMAGES_RELATIVE_PATH, $path),
        'relative_path' => sprintf('%s%s/%s%s', ltrim(fn_get_rel_dir($files_dir), '/'), $company_id, ADVANCED_IMPORT_PRIVATE_IMAGES_RELATIVE_PATH, $path),
        'exim_path' => sprintf('%s%s', ADVANCED_IMPORT_PRIVATE_IMAGES_RELATIVE_PATH, $path),
        'filemanager_path' => sprintf('%s%s', ADVANCED_IMPORT_PRIVATE_IMAGES_RELATIVE_PATH, $path),
    );

    if (!Registry::get('runtime.company_id')) {
        if ($company_id) {
            $result['filemanager_path'] = sprintf('%s/%s', $company_id, $result['filemanager_path']);
        } else {
            $result['absolute_path'] = sprintf('%s%s%s', $files_dir, ADVANCED_IMPORT_PRIVATE_IMAGES_RELATIVE_PATH, $path);
            $result['relative_path'] = sprintf('%s%s%s', ltrim(fn_get_rel_dir($files_dir), '/'), ADVANCED_IMPORT_PRIVATE_IMAGES_RELATIVE_PATH, $path);
        }
    }

    return $result;
}

/**
 * Sanitizes user specified path
 *
 * @param string $path User specified path
 *
 * @return string
 */
function fn_advanced_import_filter_user_path($path)
{
    $parts = explode('/', $path);

    foreach ($parts as $key => &$item) {
        $item = SecurityHelper::sanitizeFileName(trim($item, '.'));

        if (!$item) {
            unset($parts[$key]);
        }
    }
    unset($item);

    return implode('/', $parts);
}

/**
 * Fetches array of paths to images directory for each existing company
 *
 * @param string $path user specified path
 *
 * @return array
 */
function fn_advanced_import_get_companies_import_images_directory($path = '')
{
    $result = array();
    $company_ids = fn_get_all_companies_ids();
    if (fn_allowed_for('MULTIVENDOR')) {
        $company_ids[] = '0';
    }

    foreach ($company_ids as $company_id) {
        $result[$company_id] = fn_advanced_import_get_import_images_directory((int) $company_id, $path);
    }

    return $result;
}

/**
 * Decides whether to skip updating existing or creating new products when importing products.
 *
 * @param array<array-key, int>                                                $primary_object_id Primary object defintion
 * @param array<array-key, string|array<string, string|int>>                   $options           Export options
 * @param bool                                                                 $skip_record       Skip record flag
 * @param array<array-key, int|array<array-key, array<array<array-key, int>>>> $processed_data    Import stats
 *
 * @psalm-param array{
 *  E: int,
 *  N: int,
 *  S: int,
 *  C: int
 * } $processed_data
 */
function fn_advanced_import_skip_updating_or_creating_new_products(
    $primary_object_id,
    $options,
    &$skip_record,
    &$processed_data
) {
    $skip_creating =
        !empty($options['skip_creating_new_products'])
        && $options['skip_creating_new_products'] == 'Y'
        ||
        !empty($options['import_strategy'])
        && $options['import_strategy'] == ImportStrategies::UPDATE_EXISTING;

    $skip_updating =
        !empty($options['import_strategy'])
        && $options['import_strategy'] == ImportStrategies::CREATE_NEW;

    if ($primary_object_id && $skip_updating
        || !$primary_object_id && $skip_creating
    ) {
        $skip_record = true;
        $processed_data['S']++;
    }
}

/**
 * Decides whether to stop products import when test amount of products is imported.
 *
 * @param array $pattern
 * @param array $options
 * @param array $processed_data
 * @param bool  $skip_record
 * @param bool  $stop_import
 */
function fn_advanced_import_test_import(
    $pattern,
    $options,
    $processed_data,
    &$skip_record,
    &$stop_import
) {
    // created and updated
    if (isset($processed_data['by_types'])) {
        $affected_products = 0;
        foreach ($processed_data['by_types'] as $type => $type_processed_data) {
            $affected_products += $type_processed_data['N'] + $type_processed_data['E'];
        }
    } else {
        $affected_products = $processed_data['N'] + $processed_data['E'];
    }

    if (!empty($options['test_import'])
        && $options['test_import'] == 'Y'
        && $affected_products >= $pattern['options']['test_import']['sampling_size']
    ) {
        $skip_record = true;
        $stop_import = true;
    }
}

/**
 * Converts legacy `skip_creating_new_products` option value into the new `import_strategy` one.
 *
 * @param array $option_definition Preset option
 * @param array $preset            Preset definition
 *
 * @return array Preset option with converted value
 */
function fn_advanced_import_set_import_strategy_option_value(array $option_definition, array $preset)
{
    if ($option_definition['selected_value'] !== null) {
        return $option_definition;
    }

    if (isset($preset['options']['skip_creating_new_products']['selected_value'])
        && $preset['options']['skip_creating_new_products']['selected_value'] == 'Y'
    ) {
        $option_definition['selected_value'] = ImportStrategies::UPDATE_EXISTING;
    } else {
        $option_definition['selected_value'] = ImportStrategies::IMPORT_ALL;
    }

    return $option_definition;
}

/**
 * Converts `Import strategy` to `Upgrade only existing products` option when saving a preset.
 *
 * @param string $option_id Option name
 * @param array $option_data Option description
 * @param array $pattern_options Pattern options schema
 * @param array $preset_options Options
 * @param string|null $initial_value Initial option value
 *
 * @return string `Upgrade only existing products` option value to save
 */
function fn_advanced_import_convert_import_strategy_to_set_skip_creating_new_products_option(
    $option_id,
    $option_data,
    $pattern_options,
    $preset_options,
    $initial_value
) {
    if (isset($preset_options['import_strategy'])
        && $preset_options['import_strategy'] == ImportStrategies::UPDATE_EXISTING
    ) {
        return 'Y';
    }

    if ($initial_value !== null) {
        return $initial_value;
    }

    return 'N';
}

/**
 * Gets file extension by mime type or name
 *
 * @param string $file_name File Name
 * @param string $file_type File Mime Type
 *
 * @return null|string File extension
 */
function fn_advanced_import_get_file_extension_by_mimetype($file_name, $file_type)
{
    $mime_types_list = fn_get_ext_mime_types('mime');
    $path_info = fn_pathinfo($file_name);
    $ext = strtolower($path_info['extension']);

    if (!in_array($ext, $mime_types_list)) {
        $ext = isset($mime_types_list[$file_type]) ? $mime_types_list[$file_type] : null;
    }

    return $ext;
}

/**
 * Creates import path, if not exist and returns import path
 *
 * @param int    $company_id Company identifier
 * @param string $path       Path
 * @param string $path_id    Path identifier
 *
 * @return string
 */
function fn_advanced_import_get_import_path($company_id, $path, $path_id)
{
    $path = fn_advanced_import_filter_user_path($path);

    if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.simple_ultimate')) {
        $company_id = 0;
    }

    if ($path_id === 'images_path') {
        $companies_image_directories = fn_advanced_import_get_companies_import_images_directory();

        if ($company_id === 0) {
            $company_id = fn_get_default_company_id();
        }

        $path = sprintf('%s%s', $companies_image_directories[$company_id]['exim_path'], $path);
    }

    $absolute_path = fn_normalize_path(fn_get_files_dir_path($company_id) . $path);

    if (!file_exists($absolute_path)) {
        fn_mkdir($absolute_path);
    }

    $path = str_replace(Registry::get('config.dir.files'), '', $absolute_path);

    return $path;
}

/**
 * Adds support for YML files.
 *
 * @param string                $key   Get ext list with the mime linked, or mime with the ext linked
 * @param array<string, string> $types List of the file extension mappings to the file type
 *
 * @return void
 */
function fn_advanced_import_get_ext_mime_types($key, array &$types)
{
    $mime_types = Tygh::$app['addons.advanced_import.mime_types'];

    // phpcs:ignore
    if (!empty($mime_types)) {
        $types += $mime_types;
    }
}

/**
 * Returns a list of allowed file extensions
 *
 * @return array<string> Allowed file extensions
 */
function fn_advanced_import_get_allowed_extensions()
{
    $allowed_extensions = Tygh::$app['addons.advanced_import.allowed_extensions'];
    $file_extensions = [];

    foreach ($allowed_extensions as $extension) {
        $file_extensions = array_merge($file_extensions, $extension);
    }

    return $file_extensions;
}

/**
 * Replaces the separator in the preset example
 *
 * @param string                                          $process_function Process function name
 * @param array<string, string>                           $data             Preset example data
 * @param string                                          $field            Field name
 * @param array<string, string|int|array<string, string>> $preset           Preset data
 * @param array<string, string|array>                     $pattern_data     Pattern data
 * @param array<string, string|array>                     $pattern          Pattern definition
 *
 * @psalm-suppress PossiblyInvalidArrayOffset
 *
 * @return string Allowed file extensions
 */
function fn_advanced_import_replace_example_delimiter($process_function, array $data, $field, $preset, $pattern_data, $pattern)
{
    if ($process_function === 'fn_exim_export_price') {
        $args = fn_exim_get_values($pattern_data['process_get'], $pattern, $preset['options'], ['field' => $field], $data, '');
        $data[$field] = call_user_func_array($process_function, $args);
    } elseif ($process_function === 'fn_exim_get_product_categories') {
        $category = '';
        $category_path = explode('///', $data[$field]);
        foreach ($category_path as $value) {
            $category .= $value . $preset['options']['category_delimiter'];
        }
        $data[$field] = rtrim($category, $preset['options']['category_delimiter']);
    }

    return $data[$field];
}

/**
 * Sorts archives by upload date
 *
 * @param array<int, string> $archives  Archives
 * @param string             $path      Archives path
 * @param bool               $is_import Sorted for decompress
 *
 * @return array<int, string> Sorted archives by upload date
 */
function fn_advanced_import_sorts_uploaded_archives(array $archives, $path, $is_import = false)
{
    $sorted_archives = [];
    foreach ($archives as $archive_name) {
        $full_path = $path . '/' . $archive_name;
        $archive = ($is_import) ? $full_path : $archive_name;
        $sorted_archives[filectime($full_path)] = $archive;
    }
    ksort($sorted_archives);

    return $sorted_archives;
}

/**
 * Checks whether the user is a vendor and the preset is common
 *
 * @param string $company_id Preset company ID
 *
 * @return bool The user is a vendor and the preset is common
 */
function fn_advanced_import_check_vendor_in_common_preset($company_id)
{
    return fn_allowed_for('MULTIVENDOR')
        && !empty(Registry::get('runtime.company_id'))
        && (int) $company_id === 0;
}

/**
 * Retrieves a list of archives uploaded earlier
 *
 * @param string $path Path to archives
 *
 * @return array<int, array<string, string>>|null The user is a vendor and the preset is common
 */
function fn_advanced_import_get_archives_list($path)
{
    $archive_images = [];

    if (
        file_exists($path)
        && $uploaded_files = fn_get_dir_contents($path, false, true)
    ) {
        $sort_uploaded_files = fn_advanced_import_sorts_uploaded_archives($uploaded_files, $path);
        foreach ($sort_uploaded_files as $file) {
            if (
                !in_array(
                    strtolower(pathinfo($file, PATHINFO_EXTENSION)),
                    Registry::get('config.allowed_pack_exts'),
                    true
                )
            ) {
                continue;
            }

            $archive_images[] = [
                'path'     => $path . '/' . $file,
                'name'     => $file,
                'file'     => str_replace(' ', '_', pathinfo($file, PATHINFO_FILENAME)),
                'type'     => 'local',
                'location' => 'advanced_import'
            ];
        }
    }

    return $archive_images;
}

/**
 * Checks whether the file is archive
 *
 * @param string $file Path to archives
 *
 * @return bool The file is archive
 */
function fn_advanced_import_check_file_is_archive($file)
{
    return in_array(
        strtolower(pathinfo($file, PATHINFO_EXTENSION)),
        Registry::get('config.allowed_pack_exts'),
        true
    );
}

/**
 * Decompress archives
 *
 * @param array<int, string> $archives Archives
 * @param string             $path     Archives path
 *
 * @return void
 */
function fn_advanced_import_decompress_archives(array $archives, $path)
{
    $uploaded_files = fn_advanced_import_sorts_uploaded_archives($archives, $path, true);

    foreach ($uploaded_files as $file_path) {
        if (!fn_advanced_import_check_file_is_archive($file_path)) {
            continue;
        }

        $uploader_folder = $path . '/' . pathinfo($file_path, PATHINFO_FILENAME);
        $result_decompress = fn_decompress_files($file_path, $path);
        fn_copy($uploader_folder, $path);

        if (!$result_decompress) {
            continue;
        }
        fn_rm($uploader_folder);
    }
}

/**
 * Retrieves temporary image directory path
 *
 * @param array<string, int|string|array<string, string>> $preset      Preset data
 * @param string                                          $folder_name Folder name
 * @param string                                          $directories Archives
 *
 * @psalm-suppress PossiblyInvalidArrayOffset
 *
 * @return string The path of temporary images
 */
function fn_advanced_import_get_temporary_image_directory_path(array $preset, $folder_name, $directories)
{
    if (!empty($directories)) {
        $temp_images_path = rtrim($directories, '/') . '/' . $folder_name;
    } else {
        $temp_images_path = fn_advanced_import_get_import_images_directory(
            (int) $preset['company_id'],
            rtrim($preset['options']['images_path'], '/') . '/' . $folder_name
        )['relative_path'];
    }

    return $temp_images_path;
}

/**
 * Copies archives to the specified directory
 *
 * @param string                                     $path     Path to copy
 * @param array<array-key, array<array-key, string>> $archives Archives list
 *
 * @psalm-suppress PossiblyInvalidScalarArgument
 *
 * @return void
 */
function fn_advanced_import_copy_archives($path, array $archives = [])
{
    if (empty($archives)) {
        return;
    }

    foreach ($archives as $archive) {
        fn_copy($archive['path'], $path . '/' . $archive['name']);
        sleep(1);
    }
}
