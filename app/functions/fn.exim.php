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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Bootstrap;
use Tygh\Enum\ImagePairTypes;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\OutOfStockActions;
use Tygh\Enum\SiteArea;
use Tygh\Enum\ProductAvailability;
use Tygh\Enum\YesNo;
use Tygh\Enum\ProductTracking;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Shippings\Shippings;
use Tygh\Storage;
use Tygh\Tools\Url;
use Tygh\Languages\Languages;

//
// Export data using pattern
// Parameters:
// @pattern - import/export pattern
// @export_fields - export defined fields only
// @options - export options
// FIXME: add export conditions

function fn_export($pattern, $export_fields, $options)
{
    if (empty($pattern) || empty($export_fields)) {
        return false;
    }

    // Languages
    if (!empty($options['lang_code'])) {
        $multi_lang = $options['lang_code'];
        $count_langs = count($multi_lang);
    } else {
        $multi_lang = array(DEFAULT_LANGUAGE);
        $count_langs = 1;
        $options['lang_code'] = $multi_lang;
    }

    $can_continue = true;

    if (!empty($pattern['export_pre_moderation'])) {
        $data_export_pre_moderation = array(
            'pattern' => &$pattern,
            'export_fields' => &$export_fields,
            'options' => &$options,
            'can_continue' => &$can_continue,
        );

        fn_exim_processing('export', $pattern['export_pre_moderation'], $options, $data_export_pre_moderation);
    }

    if (!$can_continue) {
        return false;
    }

    if (!empty($pattern['pre_processing'])) {
        $data_export_pre_processing = [
            'pattern' => &$pattern
        ];
        fn_exim_processing('export', $pattern['pre_processing'], $options, $data_export_pre_processing);
    }

    if (isset($options['fields_names'])) {
        if ($options['fields_names']) {
            $fields_names = $export_fields;
            $export_fields = array_keys($export_fields);
        }
    }

    $primary_key = array();
    $_primary_key = $pattern['key'];
    foreach ($_primary_key as $key) {
        $primary_key[$key] = $key;
    }
    array_walk($primary_key, 'fn_exim_attach_value_helper', $pattern['table'].'.');

    $table_fields = $primary_key;
    $processes = array();

    // Build list of fields that should be retrieved from the database
    fn_exim_export_build_retrieved_fields($processes, $table_fields, $pattern, $export_fields);

    if (empty($pattern['export_fields']['multilang'])) {
        $multi_lang = array(DEFAULT_LANGUAGE);
        $count_langs = 1;
        $options['lang_code'] = $multi_lang;
    }

    // Build the list of joins
    $joins = fn_exim_export_build_joins($pattern, $options, $primary_key, $multi_lang);

    // Add retrieve conditions
    $conditions = fn_exim_export_build_conditions($pattern, $options);

    if (!empty($pattern['pre_export_process'])) {
        $pre_export_process_data = array(
            'pattern' => &$pattern,
            'export_fields' => &$export_fields,
            'options' => &$options,
            'conditions' => &$conditions,
            'joins' => &$joins,
            'table_fields' => &$table_fields,
            'processes' => &$processes
        );
        fn_exim_processing('export', $pattern['pre_export_process'], $options, $pre_export_process_data);
    }

    $total = db_get_field("SELECT COUNT(*) FROM ?:" . $pattern['table'] . " as " . $pattern['table'] .' '. implode(' ', $joins) . (!empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : ''));

    fn_set_progress('parts', $total);
    fn_set_progress('step_scale', 1);

    $sorting = '';

    if (!empty($pattern['order_by'])) {
        $sorting = ' ORDER BY ' . $pattern['order_by'];
    }

    // Build main query
    $query = "SELECT " . implode(', ', $table_fields) . " FROM ?:" . $pattern['table'] . " as " . $pattern['table'] .' '. implode(' ', $joins) . (!empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '') . $sorting;

    /** @psalm-suppress UndefinedConstant */
    $step = $count_langs <= DB_LIMIT_SELECT_ROW
        ? fn_floor_to_step(DB_LIMIT_SELECT_ROW, $count_langs)
        : $count_langs;
    $iterator = 0; // start retrieving from
    $progress = 0;
    $data_exported = false;

    $main_lang = reset($multi_lang);
    $manual_multilang = true;

    $field_lang = '';

    foreach ($pattern['export_fields']['multilang'] as $key => $value) {
        if (array_search('languages', $value, true)) {
            if (!isset($value['linked']) || $value['linked'] === true) {
                $manual_multilang = false;
            }
            $field_lang = $key;

            break;
        }
    }

    if (empty($field_lang) || !in_array($field_lang, $export_fields)) {
        $multi_lang = array($main_lang);
        $count_langs = 1;
    }

    while ($data = db_get_array($query . " LIMIT $iterator, $step")) {
        $data_exported = true;

        if ($manual_multilang) {
            $data_lang = $data;
            $data = array();

            foreach ($data_lang as $data_key => $data_value) {
                $data[] = array_combine($multi_lang, array_fill(0, $count_langs, $data_value));
            }

        } else {

            $data_lang = array_chunk($data, $count_langs);
            $data = array();

            foreach ($data_lang as $data_key => $data_value) {
                // Sort
                foreach ($multi_lang as $lang_code) {
                    foreach ($data_value as $v) {
                        if (array_search($lang_code, $v, true)) {
                            $data[$data_key][$lang_code] = $v;
                        }
                    }
                }
            }
        }

        $result = array();
        foreach ($data as $k => $v) {
            $progress += $count_langs;
            fn_set_progress('echo', __('exporting_data') . ':&nbsp;<b>' . ($progress)  .'</b>');
            fn_exim_export_fill_fields($result[$k], $v, $processes, $pattern, $options);
        }

        if (!empty($pattern['export_processing'])) {
            $pre_export_process_data = array(
                'data' => $data,
                'result' => &$result,
                'export_fields' => &$export_fields,
                'multi_lang' => $multi_lang,
                'main_lang' => $main_lang,
                'pattern' => $pattern
            );
            fn_exim_processing('export', $pattern['export_processing'], $options, $pre_export_process_data);
        }

        $_result = array();

        foreach ($result as $k => $v) {
            foreach ($multi_lang as $lang_code) {
                $_data = array();
                foreach ($export_fields as $field) {
                    if (isset($fields_names[$field])) {
                        $_data[$fields_names[$field]] = $v[$lang_code][$field];
                    } else {
                        $_data[$field] = (isset($v[$lang_code][$field])) ? $v[$lang_code][$field] : '';
                    }
                }
                $_result[] = $_data;
            }
        }

        // Put data
        $enclosure = (isset($pattern['enclosure'])) ? $pattern['enclosure'] : '"';
        fn_echo(' .');

        if (isset($pattern['func_save_content_to_file']) && is_callable($pattern['func_save_content_to_file'])) {
            call_user_func($pattern['func_save_content_to_file'], $_result, $options, $enclosure);
        } else {
            fn_exim_put_csv($_result, $options, $enclosure);
        }

        $iterator += $step;
    }

    if (!empty($pattern['post_processing'])) {
        fn_set_progress('echo', __('processing'), false);

        if ($data_exported && file_exists(fn_get_files_dir_path() . $options['filename'])) {

            $data_exported = fn_exim_processing('export', $pattern['post_processing'], $options);
        }
    }

    return $data_exported;
}

//
// Process csv file using pattern
// Parameters:
// @pattern - import/export pattern
// @file - path to csv file on filesystem
// @options - processing options

function fn_exim_get_csv($pattern, $file, $options)
{
    $max_line_size = 65536; // 64 Кб
    $result = array();

    $options = array_merge(array(
        'delimiter'     => 'C',
        'lang_code'     => CART_LANGUAGE,
        'validate_schema'   => true,
        'count'         => null,
        'import_schema' => null,
    ), $options);

    if ($options['delimiter'] == 'C') {
        $delimiter = ',';
    } elseif ($options['delimiter'] == 'T') {
        $delimiter = "\t";
    } else {
        $delimiter = ';';
    }

    if (!empty($file) && file_exists($file)) {

        $encoding = fn_detect_encoding($file, 'F', $options['lang_code']);

        if (!empty($encoding)) {
             $file = fn_convert_encoding($encoding, 'UTF-8', $file, 'F');
        } else {
            fn_set_notification('W', __('warning'), __('text_exim_utf8_file_format'));
        }

        $f = false;
        if ($file !== false) {
            $f = fopen($file, 'rb');
        }

        if ($f) {
            $f = fn_exim_clear_bom_symbols_if_exists($f);

            // Read import schema from file even when import schema is explicitly specified to skip the first line
            $import_schema = fgetcsv($f, $max_line_size, $delimiter);
            if ($options['import_schema']) {
                $import_schema = $options['import_schema'];
            }

            if (empty($import_schema)) {
                fn_set_notification('E', __('error'), __('error_exim_cant_read_file'));

                return false;
            }

            // Check if we selected correct delimiter
            // If line was read without delimition, array size will be == 1.
            if ($options['validate_schema'] && sizeof($import_schema) == 1) {

                // we could export one column if it is correct, otherwise show error
                if (!in_array($import_schema[0], array_keys($pattern['export_fields']))) {

                    fn_set_notification('E', __('error'), __('error_exim_incorrent_delimiter'));

                    return false;
                }
            }

            // Analyze schema - check for required fields
            if ($options['validate_schema'] && !fn_exim_analyze_schema($import_schema, $pattern)) {
                return false;
            }

            // Collect data
            $schema_size = sizeof($import_schema);
            $skipped_lines = array();
            $line_it = 1;
            while (($data = fn_fgetcsv($f, $max_line_size, $delimiter)) !== false) {

                $line_it ++;
                if (fn_is_empty($data)) {
                    continue;
                }

                if (sizeof($data) != $schema_size) {
                    $skipped_lines[] = $line_it;
                    continue;
                }

                if ($import_schema) {
                    $result[] = array_combine($import_schema, Bootstrap::stripSlashes($data));
                } else {
                    $result[] = Bootstrap::stripSlashes($data);
                }

                if ($options['count'] !== null && count($result) == $options['count']) {
                    break;
                }
            }

            if (!empty($skipped_lines)) {
                fn_set_notification('W', __('warning'), __('error_exim_incorrect_lines', array(
                    '[lines]' => implode(', ', $skipped_lines)
                )));
            }

            return $result;
        } else {
            fn_set_notification('E', __('error'), __('error_exim_cant_open_file'));

            return false;
        }
    } else {
        fn_set_notification('E', __('error'), __('error_exim_file_doesnt_exist'));

        return false;
    }
}

function fn_exim_export_fill_fields(&$result, $data, $processes, $pattern, $options)
{
    $multi_lang = array_keys($data);
    $main_lang = reset($multi_lang);

    // Filter
    $export_fields_all =  array_merge($pattern['export_fields']['main'], $pattern['export_fields']['multilang']);
    $result[$main_lang] = fn_array_key_intersect($data[$main_lang], $export_fields_all);
    foreach (array_diff($multi_lang, array($main_lang)) as $lang_code) {
        $result[$lang_code] = fn_array_key_intersect($data[$lang_code], $pattern['export_fields']['multilang']);
    }

    foreach ($processes['main'] as $field => $process_data) {
        $vars = array(
            'key' => array($data[$main_lang][reset($pattern['key'])]),
            'field' => $field,
            'lang_code' => $main_lang
        );

        $args = fn_exim_get_values($process_data['args'], $pattern, $options, $vars, $data[$main_lang], '');

        if (!empty($process_data['function'])) {
            $result[$main_lang][$field] = call_user_func_array($process_data['function'], $args);
        } else {
            $result[$main_lang][$field] = array_shift($args);
        }
    }

    foreach ($processes['multilang'] as $field => $process_data) {

        foreach ($multi_lang as $lang_code) {
            $vars = array(
                'key' => array($data[$lang_code][reset($pattern['key'])]),
                'field' => $field,
                'lang_code' => $lang_code,
            );

            $args = fn_exim_get_values($process_data['args'], $pattern, $options, $vars, $data[$lang_code], '');
            //$args[$index_lang] = $lang_code;

            if (!empty($process_data['function'])) {
                $result[$lang_code][$field] = call_user_func_array($process_data['function'], $args);
            } else {
                $result[$lang_code][$field] = array_shift($args);
            }

        }
    }
}

//
// Put data to csv file
// Parameters:
// @data - export data
// @options - options

function fn_exim_put_csv(&$data, &$options, $enclosure)
{
    static $output_started = false;

    $eol = "\n";

    if ($options['delimiter'] == 'C') {
        $delimiter = ',';
    } elseif ($options['delimiter'] == 'T') {
        $delimiter = "\t";
    } else {
        $delimiter = ';';
    }

    fn_mkdir(fn_get_files_dir_path());

    foreach ($data as $k => $v) {
        foreach ($v as $name => $value) {
            $data[$k][$name] = $enclosure . str_replace(array("\r","\n","\t",$enclosure), array('','','',$enclosure.$enclosure), $value) . $enclosure;
        }
        // If a line in a csv file ends with 3 or more double quotes (i.e. """), the mime content type is often
        // determined incorrectly, e.g. by using finfo_file or mime_content_type php functions.
        // To get round it, add an extra space to lines like this:
        if (substr($data[$k][$name], -3) == '"""') {
            $data[$k][$name] .= ' ';
        }
    }

    if ($output_started == false || isset($options['force_header'])) {
        Tygh::$app['view']->assign('fields', array_keys($data[0]));
    } else {
        Tygh::$app['view']->clearAssign('fields');
    }

    Tygh::$app['view']->assign('export_data', $data);
    Tygh::$app['view']->assign('delimiter', $delimiter);
    Tygh::$app['view']->assign('eol', $eol);

    $csv = Tygh::$app['view']->fetch('views/exim/components/export_csv.tpl');
    $fd = fopen(fn_get_files_dir_path() . $options['filename'], ($output_started && !isset($options['force_header'])) ? 'ab' : 'wb');
    if ($fd) {
        fwrite($fd, $csv, strlen($csv));
        fclose($fd);
        @chmod(fn_get_files_dir_path() . $options['filename'], DEFAULT_FILE_PERMISSIONS);
    }

    if ($output_started == false) {
        $output_started = true;
    }

    unset($options['force_header']);

    return true;
}

// --------- ExIm core functions ------------------

/**
 * Imports data using pattern.
 *
 * @param array $pattern     Imported items specification
 * @param array $import_data Imported items
 * @param array $options     Import options
 *
 * @return bool Whether import was successful
 */
function fn_import($pattern, $import_data, $options)
{
    if (empty($pattern) || empty($import_data)) {
        return false;
    }

    $processed_data = array (
        'E' => 0, // existent
        'N' => 0, // new
        'S' => 0, // skipped
        'C' => 0  // vendors
    );

    $alt_keys = array();
    $primary_fields = array();
    $table_groups = array();
    $default_groups = array();
    $add_fields = array();
    $primary_object_ids = array();
    $required_fields = array();
    $alt_fields = array();

    if (!empty($pattern['pre_processing'])) {
        $data_pre_processing = array(
            'import_data' => &$import_data,
            'pattern' => &$pattern,
            'processed_data' => &$processed_data,
        );
        fn_exim_processing('import', $pattern['pre_processing'], $options, $data_pre_processing);
    }

    if (!empty($pattern['references'])) {
        $table_groups =  $pattern['references'];
    }

    if (!fn_exim_import_parse_languages($pattern, $import_data, $options)) {
        fn_set_notification('E', __('error'), __('error_exim_invalid_count_langs'));

        return false;
    }

    // Get keys to detect primary record
    foreach ($pattern['export_fields'] as $field => $data) {

        $_db_field = (empty($data['db_field']) ? $field : $data['db_field']);

        // Collect fields with default values
        if (isset($data['default'])) {
            if (is_array($data['default'])) {
                $default_groups[$_db_field] = call_user_func_array(array_shift($data['default']), $data['default']);
            } else {
                $default_groups[$_db_field] = $data['default'];
            }
        }

        // Get alt keys for primary table
        if (!empty($data['alt_key'])) {
            $alt_keys[$field] = $_db_field;
        }

        if (!empty($data['alt_field'])) {
            $alt_fields[$_db_field] = $data['alt_field'];
        }

        if (!empty($data['required']) && $data['required'] = true) {
            $required_fields[] = $_db_field;
        }

        if (!isset($data['linked']) || $data['linked'] == true) {
            // Get fields for primary table
            if (empty($data['table']) || $data['table'] == $pattern['table']) {
                $primary_fields[$field] = $_db_field;
            }

            // Group fields by tables
            if (!empty($data['table'])) {
                $table_groups[$data['table']]['fields'][$_db_field] = true;
            }
        }

        // Create set with fields that must be added to data import if they are not exist
        // %'s are for compatibility with %% field type in "process_put" key
        if (!empty($data['use_put_from'])) {
            $_f = str_replace('%', '', $data['use_put_from']);
            $_f = !empty($pattern['export_fields'][$_f]['db_field']) ? $pattern['export_fields'][$_f]['db_field'] : $_f;
            $add_fields[$_f][] = $_db_field;
        }
    }

    // Generate processing groups
    $processing_groups = fn_exim_import_build_groups('process_put', $pattern['export_fields']);

    // Generate converting groups
    $converting_groups = fn_exim_import_build_groups('convert_put', $pattern['export_fields']);

    //Generate pre inserting groups
    $pre_inserting_groups = fn_exim_import_build_groups('pre_insert', $pattern['export_fields']);

    //Generate post inserting groups
    $post_inserting_groups = fn_exim_import_build_groups('post_insert', $pattern['export_fields']);

    fn_set_progress('parts', sizeof($import_data));

    $data = $import_data ? reset($import_data) : array();
    $multi_lang = $data ? array_keys($data) : array();
    $main_lang = reset($multi_lang);

    foreach ($import_data as $k => $v) {

        //If the required field is empty skip this record
        foreach ($required_fields as $field) {
            if (!empty($v[$main_lang][$field]) || $v[$main_lang][$field] === 0 || $v[$main_lang][$field] === '0') {
                continue;
            }
            if (empty($alt_fields[$field]) || empty($v[$main_lang][$alt_fields[$field]])) {
                $processed_data['S']++;
                continue 2;
            }
        }

        $_alt_keys = array();
        $object_exists = true;

        // Check if converting groups exist and convert fields if it is so
        fn_exim_import_prepare_groups($v[$main_lang], $converting_groups, $options);

        foreach ($alt_keys as $import_field => $real_field) {
            if (!isset($v[$main_lang][$real_field])) {
                continue;
            }
            if (!empty($v[$main_lang][$real_field])) {
                $_alt_keys[$real_field] = $v[$main_lang][$real_field];
            } elseif (!empty($alt_fields[$real_field])) {
                $_alt_keys[$alt_fields[$real_field]] = $v[$main_lang][$alt_fields[$real_field]];
            }

        }

        foreach ($primary_fields as $import_field => $real_field) {
            if (!isset($v[$main_lang][$real_field])) {
                continue;
            }
            $_primary_fields[$real_field] = $v[$main_lang][$real_field];
        }

        $skip_get_primary_object_id = false;

        if (!empty($pattern['import_get_primary_object_id'])) {
            $data_import_get_primary_object_id = array(
                'pattern' => &$pattern,
                'alt_keys' => &$_alt_keys,
                'object' => &$v[$main_lang],
                'skip_get_primary_object_id' => &$skip_get_primary_object_id,
            );

            fn_exim_processing('import', $pattern['import_get_primary_object_id'], $options, $data_import_get_primary_object_id);
        }

        if ($skip_get_primary_object_id) {
            $primary_object_id = array();
        } else {
            $where = array();
            foreach ($_alt_keys as $field => $value) {
                if (is_array($value)) {
                    $where[] = db_quote("?p IN (?a)", $field, $value);
                } else {
                    $where[] = db_quote("?p = ?s", $field, $value);
                }

            }
            $where = implode(' AND ', $where);

            $primary_object_id = db_get_row('SELECT ' . implode(', ', $pattern['key']) . ' FROM ?:' . $pattern['table'] . ' WHERE ?p', $where);
        }

        $skip_record = $stop_import = false;

        if (!empty($pattern['import_process_data'])) {
            $data_import_process_data = array(
                'primary_object_id' => &$primary_object_id,
                'object'            => &$v[$main_lang],
                'pattern'           => &$pattern,
                'options'           => &$options,
                'processed_data'    => &$processed_data,
                'processing_groups' => &$processing_groups,
                'skip_record'       => &$skip_record,
                'stop_import'       => &$stop_import,
                'data'              => &$v,
            );

            fn_exim_processing('import', $pattern['import_process_data'], $options, $data_import_process_data);
        }

        fn_exim_import_prepare_groups($v[$main_lang], $pre_inserting_groups, $options, $skip_record, $stop_import);

        if ($stop_import) {
            break;
        }

        if ($skip_record) {
            continue;
        }

        $primary_object_ids[$k] = $primary_object_id;
        if (!(isset($pattern['import_skip_db_processing']) && $pattern['import_skip_db_processing'])) {

            fn_set_progress('echo', __('importing_data'));
            if (empty($primary_object_id)) {

                // If scheme is used for update objects only, skip this record
                if (!empty($pattern['update_only'])) {
                    $_a = array();
                    foreach ($alt_keys as $_d => $_v) {
                        if (!isset($v[$main_lang][$_v])) {
                            continue;
                        }
                        $_a[] = $_d . ' = ' . $v[$main_lang][$_v];
                    }
                    fn_set_progress('echo', __('object_does_not_exist') . ' (' . implode(', ', $_a) . ')...', false);

                    $processed_data['S']++;
                    continue;
                }

                $object_exists = false;
                $processed_data['N']++;

                // For new objects - fill the default values
                if (!empty($default_groups)) {
                    foreach ($default_groups as $field => $value) {
                        foreach ($v as $lang => $import_field) {
                            if (empty($import_field[$field])) {
                                $v[$lang][$field] = $value;
                            }
                        }
                    }
                }
            } else {
                $processed_data['E']++;
            }

            if ($object_exists == true) {
                fn_set_progress('echo', __('updating') . ' ' . $pattern['name'] . ' <b>' . implode(',', $primary_object_id) . '</b>. ', false);
                db_query('UPDATE ?:' . $pattern['table'] . ' SET ?u WHERE ?w', $v[$main_lang], $primary_object_id);
            } else {
                $o_id = db_query('INSERT INTO ?:' . $pattern['table'] . ' ?e', $v[$main_lang]);

                reset($pattern['key']);
                foreach ($pattern['key'] as $key) {
                    if (isset($v[$main_lang][$key])) {
                        $primary_object_id[$key] = $v[$main_lang][$key];
                    } else {
                        $primary_object_id[$key] = $o_id;
                    }
                }

                fn_set_progress('echo', __('creating') . ' ' . $pattern['name'] . ' <b>' . implode(',', $primary_object_id) . '</b>. ', false);
                $primary_object_ids[$k] = $primary_object_id;
            }
        }

        $skip_db_processing_record = false;

        fn_exim_import_prepare_groups($v[$main_lang], $post_inserting_groups, $options, $skip_db_processing_record);

        if (!empty($pattern['import_after_process_data'])) {
            $data_import_after_process_data = array(
                'primary_object_id' => &$primary_object_id,
                'object' => &$v[$main_lang],
                'pattern' => &$pattern,
                'options' => &$options,
                'processed_data' => &$processed_data,
                'processing_groups' => &$processing_groups,
                'skip_db_processing_record' => &$skip_db_processing_record,
                'object_exists' => &$object_exists,
            );

            fn_exim_processing('import', $pattern['import_after_process_data'], $options, $data_import_after_process_data);
        }

        if ($skip_db_processing_record) {
            continue;
        }

        if (!(isset($pattern['import_skip_db_processing']) && $pattern['import_skip_db_processing'])) {
            // Update referenced tables
            fn_set_progress('echo', __('updating_links') . '... ', false);

            foreach ($table_groups as $table => $tdata) {
                if (isset($tdata['import_skip_db_processing']) && $tdata['import_skip_db_processing']) {
                    continue;
                }

                foreach ($v as $value_data) {
                    $_data = array();

                    // First, build condition
                    $where_insert = array();

                    // If alternative key is defined, use it
                    if (!empty($tdata['alt_key'])) {

                        foreach ($tdata['alt_key'] as $akey) {
                            if (strval($akey) == '#key') {
                                $where_insert = fn_array_merge($where_insert, $primary_object_id);
                            } elseif (strpos($akey, '@') !== false) {
                                $_opt = str_replace('@', '', $akey);
                                $where_insert[$akey] = $options[$_opt];
                            } else {
                                $where_insert[$akey] = $value_data[$akey];
                            }
                        }
                    // Otherwise - link by reference fields
                    } else {
                        $vars = array('key' => $primary_object_id);
                        if (!empty($value_data['lang_code'])) {
                            $vars['lang_code'] = $value_data['lang_code'];
                        }
                        $where_insert = fn_exim_get_values($tdata['reference_fields'], array(), $options, $vars, $value_data, '');
                    }

                    // Now, build update fields array
                    if (!empty($tdata['fields'])) {
                        foreach ($tdata['fields'] as $import_field => $set) {
                            if (!isset($value_data[$import_field])) {
                                continue;
                            }
                            $_data[$import_field] = $value_data[$import_field];
                        }
                    }

                    // Check if object exists
                    $is_exists = db_get_field("SELECT COUNT(*) FROM ?:$table WHERE ?w", $where_insert);
                    if ($is_exists == true && !empty($_data)) {
                        db_query("UPDATE ?:$table SET ?u WHERE ?w", $_data, $where_insert);
                    } elseif (empty($is_exists)) { // if reference does not exist, we should insert it anyway to avoid inconsistency
                        $_data = fn_array_merge($_data, $where_insert);

                        if (substr($table, -13) == '_descriptions' && isset($_data['lang_code'])) {
                            // add description for all cart languages when adding object data
                            foreach (Languages::getAll() as $_data['lang_code'] => $lang_v) {
                                db_query("REPLACE INTO ?:$table ?e", $_data);
                            }

                        } else {
                            db_query("INSERT INTO ?:$table ?e", $_data);
                        }
                    }

                    //
                    if (empty($_data['lang_code'])) {
                        break;
                    }
                }
            }
        }

        if (!empty($processing_groups)) {

            foreach ($processing_groups as $group) {

                $args = array();
                $use_this_group = true;
                $_refs = array();

                foreach ($group['args'] as $ak => $av) {

                    foreach ($v as $lang_code => $value) {
                        if ($av == '#key') {
                            $args[$ak] = (sizeof($primary_object_id) >= 1) ? reset($primary_object_id) : $primary_object_id;

                        } elseif ($av == '#keys') {
                            $args[$ak] = is_array($primary_object_id) ? $primary_object_id : (array) $primary_object_id;

                        } elseif ($av == '#new') {
                            $args[$ak] = !$object_exists;

                        } elseif ($av == '#lang_code') {
                            $args[$ak] = $lang_code;

                        } elseif ($av == '#row') {
                            $args[$ak] = $value;

                        } elseif ($av == '#this') {
                            // If we do not have this field in the import data, do not apply the function
                            $this_id = $group['this_field'];

                            if (!isset($value[$this_id])) {
                                $is_empty_data = true;

                                if (!empty($add_fields[$this_id])) {
                                    foreach ($add_fields[$this_id] as $from_field) {
                                        if (isset($value[$from_field])) {
                                            $is_empty_data = false;
                                        }
                                    }
                                }

                                if ($is_empty_data) {
                                    $use_this_group = false;
                                    break;
                                }
                            }

                            $this_multilang = false;

                            if (!empty($pattern['export_fields'][$this_id]['multilang'])) {
                                $this_multilang = true;
                            } else {
                                foreach ($pattern['export_fields'] as $field) {
                                    if (!empty($field['multilang']) && !empty($field['db_field']) && $field['db_field'] == $this_id) {
                                        $this_multilang = true;
                                        break;
                                    }
                                }
                            }

                            if ($this_multilang) {
                                $args[$ak][$lang_code] = $value[$group['this_field']];
                            } else {
                                $args[$ak] = $value[$group['this_field']];
                                break;
                            }

                        } elseif ($av == '#counter') {
                            $args[$ak] = &$processed_data;

                        } elseif (strpos($av, '%') !== false) {
                            $_ref = str_replace('%', '', $av);
                            $arg_multilang = !empty($pattern['export_fields'][$_ref]['multilang']);
                            $_ref = !empty($pattern['export_fields'][$_ref]['db_field']) ? $pattern['export_fields'][$_ref]['db_field'] : $_ref; // FIXME!!! Move to code, which builds processing_groups

                            if ($arg_multilang) {
                                $args[$ak][$lang_code] = isset($value[$_ref]) ? $value[$_ref] : '';
                            } elseif ($lang_code == $main_lang) {
                                $args[$ak] = isset($value[$_ref]) ? $value[$_ref] : '';
                            }

                            $_refs[$lang_code][] = $_ref;

                        } elseif (strpos($av, '@') !== false) {
                            $_opt = str_replace('@', '', $av);
                            $args[$ak] = $options[$_opt];

                        } else {
                            $args[$ak] = $av;
                        }

                        if (empty($group['multilang'])) {
                            break;
                        }
                    }

                }

                if ($use_this_group == false) {
                    continue;
                }

                $result = call_user_func_array($group['function'], $args); // FIXME - add checking for returned value

                if ($group['return_result'] == true) {
                    foreach (array_keys($v) as $lang) {
                        $v[$lang][$group['return_field']] = $result;
                        $import_data[$k][$lang][$group['return_field']] = $result;
                    }
                }
            }
        }
    }

    $final_import_notification = __('text_exim_data_imported', array(
        '[new]' => $processed_data['N'],
        '[exist]' => $processed_data['E'],
        '[skipped]' => $processed_data['S'],
        '[total]' => $processed_data['E'] + $processed_data['N'] + $processed_data['S']
    ));

    if (!empty($pattern['post_processing'])) {
        $data_post_processing = [
            'primary_object_ids'        => &$primary_object_ids,
            'import_data'               => &$import_data,
            'processed_data'            => &$processed_data,
            'final_import_notification' => &$final_import_notification,
            'pattern'                   => &$pattern
        ];

        fn_exim_processing('import', $pattern['post_processing'], $options, $data_post_processing);
    }

    if (!empty($processed_data['C'])) {
        fn_set_notification(NotificationSeverity::WARNING, __('important'), __('import_new_vendor', [$processed_data['C']]));
    }

    $notification_type = ($processed_data['S']) ? NotificationSeverity::WARNING : NotificationSeverity::NOTICE;
    fn_set_notification($notification_type, __('important'), $final_import_notification, '', 'exim_import_final_notification');

    $result = true;

    /**
     * Executes after objects were imported into a store.
     *
     * @param array $pattern        Imported items specification
     * @param array $import_data    Imported items
     * @param array $options        Import options
     * @param bool  $result         Import result
     * @param array $processed_data Information about objects affected by import
     */
    fn_set_hook('import_post', $pattern, $import_data, $options, $result, $processed_data);

    return $result;
}

//
// Analyze import schema and convert fields using pattern
// Parameters:
// @schema - import schema
// @pattern - import/export pattern

function fn_exim_analyze_schema(&$schema, $pattern)
{
    $failed_fields = array();
    $schema_match = false;
    array_walk($schema, 'fn_trim_helper');

    foreach ($pattern['export_fields'] as $field => $data) {

        if (!empty($data['required']) && $data['required'] == true && !in_array($field, $schema)) {
            if (empty($data['db_field']) || $data['db_field'] != 'lang_code') {
                $failed_fields[] = $field;
            }
        }

        if (in_array($field, $schema)) {
            $schema_match = true;
        }

        // Replace fields aliases with database representation
        if (!empty($data['db_field'])) {
            $key = array_search($field, $schema);
            if ($key !== false) {
                $schema[$key] = $data['db_field'];
            }
        }
    }

    if (!empty($failed_fields)) {
        fn_set_notification('E', __('error'), __('error_exim_pattern_required_fields', array(
            '[fields]' => implode(', ', $failed_fields)
        )));

        return false;
    }

    if ($schema_match == false) {
        fn_set_notification('E', __('error'), __('error_exim_pattern_dont_match'));

        return false;
    }

    return true;
}

//
// Helper function: attaches prefix to value
//
function fn_exim_attach_value_helper(&$value, $key, $attachment)
{
    $value = $attachment . $value;

    return true;
}

// -------------- ExIm utility functions ---------------------

/**
 * Export image (moves to selected directory on filesystem)
 *
 * @param int $image_id ID of the image
 * @param string $object object to export image for (product, category, etc...)
 * @param string $backup_path path to export image
 * @return string path to the exported image
 */
function fn_exim_export_image($image_id, $object, $backup_path = '', $include_alt = true)
{
    if (empty($backup_path)) {
        $backup_path = 'exim/backup/images/' . $object . '/';
    }

    $backup_path = rtrim(fn_normalize_path($backup_path), '/');
    $files_dir_path = (fn_allowed_for('ULTIMATE') && Registry::get('runtime.simple_ultimate'))
        ? fn_get_files_dir_path(0)
        : fn_get_files_dir_path();
    $images_path = $files_dir_path . $backup_path;

    // if backup dir does not exist then try to create it
    fn_mkdir($images_path);

    $image_data = db_get_row("SELECT image_id, image_path FROM ?:images WHERE image_id = ?i", $image_id);
    if (empty($image_data) || empty($image_data['image_path'])) {
        return '';
    }

    if ($include_alt) {
        $alt_data = db_get_hash_single_array("SELECT lang_code, description FROM ?:common_descriptions WHERE ?:common_descriptions.object_id = ?i AND ?:common_descriptions.object_holder = 'images'", array('lang_code', 'description'), $image_id);
        $alt_text = '#{';
        if (!empty($alt_data)) {
            foreach ($alt_data as $lang_code => $text) {
                $alt_text .= '[' . $lang_code . ']:' . $text . ';';
            }
        }
        $alt_text .= '}';
    }

    $path = $images_path . '/' . fn_basename($image_data['image_path']);

    Storage::instance('images')->export($object . '/' . floor($image_id / MAX_FILES_IN_DIR) . '/' . $image_data['image_path'], $path);

    return ($backup_path . '/' . fn_basename($image_data['image_path'])) . (!empty($alt_data) && $include_alt ? $alt_text : '');
}

/**
 * Imports image pair.
 *
 * @param string     $prefix            Path prefix
 * @param string     $image_file        Thumbnail path or filename
 * @param string     $detailed_file     Detailed image path or filename
 * @param string     $position          Image position
 * @param string     $type              Pair type
 * @param int        $object_id         ID of object to attach images to
 * @param string     $object            Name of object to attach images to
 * @param array|null $import_options    Import options
 *
 * @return array|bool True if images were imported
 */
function fn_exim_import_images($prefix, $image_file, $detailed_file, $position, $type, $object_id, $object, $import_options = null)
{
    static $updated_products = array();
    $perform_import = true;

    /**
     * Allows to change image import params before import
     *
     * @param string $prefix         Path prefix
     * @param string $image_file     Thumbnail path or filename
     * @param string $detailed_file  Detailed image path or filename
     * @param string $position       Image position
     * @param string $type           Pair type
     * @param int    $object_id      ID of object to attach images to
     * @param string $object         Name of object to attach images to
     * @param array  $import_options Import options
     * @param bool   $perform_import Whether to import images
     */
    fn_set_hook('exim_import_images_pre', $prefix, $image_file, $detailed_file, $position, $type, $object_id, $object, $import_options, $perform_import);

    if (!$perform_import) {
        return false;
    }

    if (!empty($object_id)) {
        // Process multilang requests
        if (!is_array($object_id)) {
            $object_id = array($object_id);
        }

        if ($import_options === null && isset($_REQUEST['import_options'])) {
            $import_options = $_REQUEST['import_options'];
        }

        foreach ($object_id as $_id) {
            if (empty($updated_products[$_id]) && !empty($import_options['remove_images']) && $import_options['remove_images'] == 'Y') {
                $updated_products[$_id] = true;

                fn_delete_image_pairs($_id, $object, 'A');
            }

            $_REQUEST["server_import_image_icon"] = '';
            $_REQUEST["type_import_image_icon"] = '';

            // Get image alternative text if exists
            if (!empty($image_file) && strpos($image_file, '#') !== false) {
                list ($image_file, $image_alt) = explode('#', $image_file);
            }

            if (!empty($detailed_file) && strpos($detailed_file, '#') !== false) {
                list ($detailed_file, $detailed_alt) = explode('#', $detailed_file);
            }

            if (!empty($image_alt)) {
                preg_match_all('/\[([A-Za-z]+?)\]:(.*?);/', $image_alt, $matches);
                if (!empty($matches[1]) && !empty($matches[2])) {
                    $image_alt = array_combine(array_values($matches[1]), array_values($matches[2]));
                }
            }

            if (!empty($detailed_alt)) {
                preg_match_all('/\[([A-Za-z]+?)\]:(.*?);/', $detailed_alt, $matches);
                if (!empty($matches[1]) && !empty($matches[2])) {
                    $detailed_alt = array_combine(array_values($matches[1]), array_values($matches[2]));
                }
            }
            $type_image_detailed = (strpos($detailed_file, '://') === false) ? 'server' : 'url';
            $type_image_icon = (strpos($image_file, '://') === false) ? 'server' : 'url';

            $_REQUEST["type_import_image_icon"] = array($type_image_icon);
            $_REQUEST["type_import_image_detailed"] = array($type_image_detailed);

            $company_id = !empty($import_options['images_company_id']) ? (int) $import_options['images_company_id'] : null;
            if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.simple_ultimate')) {
                $company_id = 0;
            }

            $files_dir_path = (fn_allowed_for('ULTIMATE') && Registry::get('runtime.simple_ultimate'))
                ? fn_get_files_dir_path(0)
                : fn_get_files_dir_path();

            $image_file = fn_find_file($prefix, $image_file, $company_id);

            if ($image_file !== false) {
                if ($type_image_icon == 'url') {
                    $_REQUEST['file_import_image_icon'] = [$image_file];

                } elseif (strpos($image_file, Registry::get('config.dir.root')) === 0) {
                    $_REQUEST['file_import_image_icon'] = [str_ireplace($files_dir_path, '', $image_file)];

                } else {
                    fn_set_notification('E', __('error'), __('error_images_need_located_root_dir'));
                    $_REQUEST['file_import_image_detailed'] = [];
                }
            } else {
                $_REQUEST['file_import_image_icon'] = [];
            }

            $detailed_file = fn_find_file($prefix, $detailed_file, $company_id);

            if ($detailed_file !== false) {
                if ($type_image_detailed == 'url') {
                    $_REQUEST['file_import_image_detailed'] = [$detailed_file];

                } elseif (strpos($detailed_file, Registry::get('config.dir.root')) === 0) {
                    $_REQUEST['file_import_image_detailed'] = [str_ireplace($files_dir_path, '', $detailed_file)];

                } else {
                    fn_set_notification('E', __('error'), __('error_images_need_located_root_dir'));
                    $_REQUEST['file_import_image_detailed'] = [];
                }

            } else {
                $_REQUEST['file_import_image_detailed'] = [];
            }

            $_REQUEST['import_image_data'] = [
                [
                    'type'         => $type,
                    'image_alt'    => empty($image_alt) ? '' : $image_alt,
                    'detailed_alt' => empty($detailed_alt) ? '' : $detailed_alt,
                    'position'     => empty($position) ? 0 : $position,
                ]
            ];

            $result = fn_attach_image_pairs('import', $object, $_id);
        }

        if (!$result) {
            fn_set_notification('W', __('warning'), __('error_exim_get_images_for_products'));
        }
        return $result;
    }

    return false;
}

//
// Get absolute url to the image
// Parameters:
// @image_id - Id of image
// @object_type - type of image object

function fn_exim_get_image_url($product_id, $object_type, $pair_type, $get_icon, $get_detailed, $lang_code)
{
    $image_pair = fn_get_image_pairs($product_id, $object_type, $pair_type, true, true, $lang_code);

    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];
    $url = $storefront->url;

    $image_data = fn_image_to_display($image_pair,
        Registry::get('settings.Thumbnails.product_details_thumbnail_width'),
        Registry::get('settings.Thumbnails.product_details_thumbnail_height'),
        $url
    );

    if (!empty($image_data['image_path'])) {
        $url = new Url($image_data['image_path']);
        $url->setProtocol(fn_get_storefront_protocol());

        return $url->build($url->getIsEncoded());
    }

    return '';
}

/**
 * @param int    $product_id Product ID
 * @param string $lang_code  Language code
 *
 * @return string Product images URLs separated with '///'
 */
function fn_exim_get_all_images_url($product_id, $lang_code)
{
    $image_pairs = [];
    $image_pairs[] = fn_get_image_pairs($product_id, 'product', ImagePairTypes::MAIN, false, true, $lang_code);
    $image_pairs = array_merge(
        $image_pairs,
        fn_get_image_pairs($product_id, 'product', ImagePairTypes::ADDITIONAL, false, true, $lang_code)
    );
    $protocol = fn_get_storefront_protocol();

    return array_reduce(
        $image_pairs,
        static function ($images_urls, $image_pair) use ($protocol) {
            if (!empty($image_pair['detailed'][$protocol . '_image_path'])) {
                if ($images_urls) {
                    return $images_urls . '///' . $image_pair['detailed'][$protocol . '_image_path'];
                }
                return $image_pair['detailed'][$protocol . '_image_path'];
            }
            return $images_urls;
        }
    );
}

//
// Get absolute url to the detailed image
// Parameters:
// @image_id - Id of image
// @object_type - type of image object

function fn_exim_get_detailed_image_url($product_id, $object_type, $pair_type, $lang_code)
{
    $image_pair = fn_get_image_pairs($product_id, $object_type, $pair_type, false, true, $lang_code);
    $protocol = fn_get_storefront_protocol();

    return !empty($image_pair['detailed'][$protocol . '_image_path']) ? $image_pair['detailed'][$protocol . '_image_path'] : '';
}

/**
 * Gets pattern definition by its name.
 *
 * @param string $pattern_id Pattern name
 * @param string $get_for    Export of import
 * @param array  $extra      Additional parameters passed by add-ons
 *
 * @return array|bool Pattern of false on failure
 */
function fn_exim_get_pattern_definition($pattern_id, $get_for = '', $extra = array())
{
    // First, check basic patterns
    $schema = fn_get_schema('exim', $pattern_id);

    if (empty($schema)) {
        fn_set_notification('E', __('error'), __('error_exim_pattern_not_found'));

        return false;
    }

    if ((!empty($schema['export_only']) && $get_for == 'import') || (!empty($schema['import_only']) && $get_for == 'export')) {
        return array();
    }

    $has_alt_keys = false;

    foreach ($schema['export_fields'] as $field_id => $field_data) {
        if (!empty($field_data['table'])) {
            // Table exists in export fields, but doesn't exist in references definition
            if (empty($schema['references'][$field_data['table']])) {
                fn_set_notification('E', __('error'), __('error_exim_pattern_definition_references'));

                return false;
            }
        }

        // Check if schema has alternative keys to import basic data
        if (!empty($field_data['alt_key'])) {
            $has_alt_keys = true;
        }

        if ((!empty($field_data['export_only']) && $get_for == 'import') || (!empty($field_data['import_only']) && $get_for == 'export')) {
            unset($schema['export_fields'][$field_id]);
        }

        /**
         * Executes when processing fields of an export/import pattern.
         *
         * @param string $pattern_id Pattern name
         * @param string $get_for    Export of import
         * @param array  $extra      Additional parameters passed by add-ons
         * @param string $field_id   Field name
         * @param array  $field_data Field properties
         * @param array  $schema     Pattern
         */
        fn_set_hook('exim_get_pattern_definition_export_fields', $pattern_id, $get_for, $extra, $field_id, $field_data, $schema);
    }

    if ($has_alt_keys == false) {
        fn_set_notification('E', __('error'), __('error_exim_pattern_definition_alt_keys'));

        return false;
    }

    if (isset($schema['options'])) {
        foreach ($schema['options'] as $option_id => &$option_data) {
            /**
             * Executes when processing options of an export/import pattern.
             *
             * @param string $pattern_id  Pattern name
             * @param string $get_for     Export of import
             * @param array  $extra       Additional parameters passed by add-ons
             * @param string $option_id   Option name
             * @param array  $option_data Option properties
             * @param array  $schema      Pattern
             */
            fn_set_hook('exim_get_pattern_definition_options', $pattern_id, $get_for, $extra, $option_id, $option_data, $schema);
        }
        unset($option_data);
    }

    return $schema;
}

/**
 * Gets all available patterns for the section
 *
 * @param string $section section to get patterns for
 * @param string $get_for get import or export patterns
 * @return array
 */
function fn_exim_get_patterns($section, $get_for)
{
    // Get core patterns
    $files = fn_get_dir_contents(Registry::get('config.dir.schemas') . 'exim', false, true, '.php');

    foreach (Registry::get('addons') as $addon_name => $addon_data) {
        if ($addon_data['status'] != 'A') {
            continue;
        }

        $schema_dir = Registry::get('config.dir.addons') . $addon_name . '/schemas/exim';
        if (is_dir($schema_dir)) {
            $_files = fn_get_dir_contents($schema_dir, false, true, '.php');
            foreach ($_files as $key => $filename) {
                if (strpos($filename, '.post.php') !== false) {
                    unset($_files[$key]);
                }
            }

            if (!empty($_files)) {
                $files = fn_array_merge($files, $_files, false);
            }
        }
    }

    $patterns = array();
    $sections = array();

    foreach ($files as $schema_file) {
        if (strpos($schema_file, '.functions.') !== false) { // skip functions schema definition
            continue;
        }

        $pattern_id = str_replace('.php', '', $schema_file);
        $pattern = fn_exim_get_pattern_definition($pattern_id, $get_for);

        if (empty($pattern) || !fn_exim_check_pattern_permissions($pattern, $get_for, Tygh::$app['session']['auth']['user_id'])) {
            continue;
        }

        $sections[$pattern['section']] = array (
            'title' => __($pattern['section']),
            'href' => 'exim.' . Registry::get('runtime.mode') . '?section=' . $pattern['section'],
        );
        if ($pattern['section'] == $section) {
            $patterns[$pattern_id] = $pattern;
        }
    }

    if (Registry::get('runtime.company_id')) {
        $schema = fn_get_permissions_schema('vendor');

        // Check if the selected section is available
        if (isset($schema[$get_for]['sections'][$section]['permission']) && !$schema[$get_for]['sections'][$section]['permission']) {
            return array('', '');
        }

        if (!empty($schema[$get_for]['sections'])) {
            foreach ($schema[$get_for]['sections'] as $section_id => $data) {
                if (isset($data['permission']) && !$data['permission']) {
                    unset($sections[$section_id]);
                }
            }
        }

        if (!empty($schema[$get_for]['patterns'])) {
            foreach ($schema[$get_for]['patterns'] as $pattern_id => $data) {
                if (isset($data['permission']) && !$data['permission']) {
                    unset($patterns[$pattern_id]);
                }
            }
        }
    }

    ksort($sections, SORT_STRING);
    uasort($patterns, 'fn_exim_sort_patterns');

    return array($sections, $patterns);
}

/**
 * Patterns sort function
 *
 * @param array $a scheme array
 * @param array $b scheme array
 * @return int
 */
function fn_exim_sort_patterns($a, $b)
{
    $s1 = isset($a['order']) ? $a['order'] : $a['pattern_id'];
    $s2 = isset($b['order']) ? $b['order'] : $b['pattern_id'];
    if ($s1 == $s2) {
        return 0;
    }

    return ($s1 < $s2) ? -1 : 1;
}

/**
 * Checks if admin has rights to use this pattern
 *
 * @param array  $pattern Pattern structure
 * @param string $get_for import|export
 * @param int    $user_id User ID
 *
 * @return bool true if user has privilege to use this pattern, false otherwise
 */
function fn_exim_check_pattern_permissions($pattern, $get_for, $user_id)
{
    $has_permissions = true;
    if (isset($pattern['permissions'])) {
        if (!empty($pattern['permissions']['edition'])) {
            $has_permissions = fn_allowed_for($pattern['permissions']['edition']);
        }
        if ($has_permissions && !empty($pattern['permissions'][$get_for])) {
            $privilege = $pattern['permissions'][$get_for];
            $has_permissions = fn_check_user_access($user_id, $privilege);
        }
    }
    return $has_permissions;
}

/**
 * Gets product url
 *
 * @param $product_id
 * @param string $lang_code
 * @return bool
 */
function fn_exim_get_product_url($product_id, $lang_code = '')
{
    if (fn_allowed_for('ULTIMATE')) {
        if (Registry::get('runtime.company_id')) {
            $company_id = Registry::get('runtime.company_id');
        } else {
            $company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);
        }

        $company_url = '&company_id=' . $company_id;
    } else {
        $company_url = '';
    }

    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];

    $url = fn_url('products.view?product_id=' . $product_id . $company_url . '&storefront_id=' . $storefront->storefront_id, SiteArea::STOREFRONT, fn_get_storefront_protocol(), $lang_code);

    fn_set_hook('exim_get_product_url', $url, $product_id, $options, $lang_code);

    return $url;
}

/**
 * Convert price to it representation with selected decimal separator
 *
 * @param float $price Price
 * @param string $decimals_separator
 * @return string Converted price
 */
function fn_exim_export_price($price, $decimals_separator)
{
    if ($decimals_separator == '.') {
        return $price;
    }

    return str_replace('.', $decimals_separator, $price);
}

/**
 * Convert price to float with dot decimal separator
 *
 * @param float $price Price
 * @param string $decimals_separator
 * @return string Converted price
 */
function fn_exim_import_price($price, $decimals_separator)
{
    if ($decimals_separator == '.') {
        return $price;
    }

    return str_replace($decimals_separator, '.', $price);
}

function fn_exim_processing($type_processing, $processing, $options, $vars = array())
{
    $result = true;

    foreach ($processing as $data) {
        if ((!empty($data['import_only']) && $type_processing == 'export') || (!empty($data['export_only']) && $type_processing == 'import')) {
            continue;
        }

        $args = fn_exim_get_values($data['args'], array(), $options, array(), $vars, '');
        $result = call_user_func_array($data['function'], $args) && $result;
    }

    return $result;
}

function fn_exim_export_build_retrieved_fields(&$processes, &$table_fields, &$pattern, $export_fields)
{
    $_pattern['export_fields']['main'] = $_pattern['export_fields']['multilang'] = array();
    $processes['main'] = $processes['multilang'] = array();

    foreach ($pattern['export_fields'] as $field => $data) {
        if (!in_array($field, $export_fields)) {
            continue;
        }

        // These fields are not exported, but are required to calculate the result of other fields.
        if (isset($data['table_fields'])) {
            foreach ($data['table_fields'] as $field_alias => $table_field) {
                $table_fields[] = "{$table_field} as {$field_alias}";
            }
        }

        if (isset($data['references'])) {
            foreach ($data['references'] as $ref_table => $ref_data) {
                $pattern['references'][$ref_table] = $ref_data;

                if (empty($ref_data['use_storefront_condition'])) {
                    continue;
                }

                $company_id = fn_get_runtime_company_id();
                $repository = StorefrontProvider::getRepository();

                /** @var \Tygh\Storefront\Storefront $storefront */
                $storefront = $repository->findByCompanyId($company_id, true);

                if ($storefront) {
                    $storefront_id = $storefront->storefront_id;
                } else {
                    $storefront_id = isset($ref_data['use_storefront_condition']['default']) ? $ref_data['use_storefront_condition']['default'] : null;
                }

                if ($storefront_id === null) {
                    continue;
                }

                $pattern['references'][$ref_table]['reference_fields']['storefront_id'] = $storefront_id;
            }
        }

        // Do no link this field
        if (isset($data['linked']) && $data['linked'] == false) {
            // do something?
        }
        // Primary object table
        elseif (empty($data['table']) || $data['table'] == $pattern['table']) {
            $table_fields[] = $pattern['table'] . '.' . (!empty($data['db_field']) ? $data['db_field'] . ' as "' .$field. '"' : $field);
        // Linked object tables
        } else {
            $table_fields[] = $data['table'] . '.' . (!empty($data['db_field']) ? $data['db_field'] . ' as "' .$field. '"' : $field);
        }

        $type_data = (array_key_exists('multilang', $data)) ? 'multilang' : 'main';

        $_pattern['export_fields'][$type_data][$field] = $data;

        if (!empty($data['process_get'])) {
            $processes[$type_data][$field]['function'] = array_shift($data['process_get']);
            $processes[$type_data][$field]['args'] = $data['process_get'];
        }

    }

    $pattern['export_fields'] = $_pattern['export_fields'];

    return true;
}

function fn_exim_export_build_joins($pattern, $options, $primary_key, $langs)
{
    $joins = array();

    if (!empty($pattern['references'])) {
        foreach ($pattern['references'] as $table => $data) {
            $ref = array();
            $vars = array(
                'key' => $primary_key,
                'lang_code' => $langs,
            );
            $values = fn_exim_get_values($data['reference_fields'], $pattern, $options, $vars);

            foreach ($data['reference_fields'] as $k => $v) {
                $_val = $values[$k];

                if (is_array($_val)) {
                    $ref[] = "$table.$k IN (" . implode(", ", $_val) . ")";
                } else {
                    $ref[] = "$table.$k = $_val";
                }
            }

            $joins[] = $data['join_type'] . ' JOIN ?:' . $table . " as $table ON " . implode(' AND ', $ref);
        }
    }

    return $joins;
}

function fn_exim_export_build_conditions($pattern, $options)
{
    $conditions = array();

    if (!empty($pattern['condition'])) {
        $_cond = array();

        if (!empty($pattern['condition']['conditions'])) {
            $values = fn_exim_get_values($pattern['condition']['conditions'], $pattern, $options);
            foreach ($pattern['condition']['conditions'] as $field => $value) {

                $_val = $values[$field];

                if (strpos($field, '&') !== false) {
                    $_field = substr($field, 1);
                } else {
                    $_field = $pattern['table'] . '.' .$field;
                }

                if (is_array($_val)) {
                    $_val = implode(",", $_val);
                    $_cond[] = "$_field IN ($_val)";

                } else {
                    $_cond[] = "$_field = $_val";
                }
            }
        }

        if (!empty($pattern['condition']['use_company_condition'])) {
            $company_condition = fn_get_company_condition($pattern['table'] . '.company_id', false);
            if (!empty($company_condition)) {
                $_cond[] = $company_condition;
            }
        }

        if (!empty($_cond)) {
            $conditions[] = implode(' AND ', $_cond);
        }
    }

    return $conditions;
}

function fn_exim_quote(&$value, $quote = "'")
{
    if (is_string($value)) {
        $value = $quote . $value . $quote;
    } elseif (is_array($value) && !empty($value)) {
        foreach ($value as $k => &$v) {
            fn_exim_quote($v, $quote);
        }
    }
}

function fn_exim_get_values($values, $pattern, $options, $vars = array(), $data = array(), $quote = "'")
{
    $val = array();

    foreach ($values as $field => $value) {

        if (is_array($value)) {
            $val[$field] = $value;
            fn_exim_quote($val[$field]);
        } else {
            $operator = substr($value, 0, 1);

            if ($operator === '@') {
                $opt = str_replace('@', '', $value);
                $val[$field] = isset($options[$opt]) ? $options[$opt] : '';

                isset($options[$opt]) && fn_exim_quote($val[$field], $quote);

            } elseif ($value === '#this') {
                if (!empty($vars['field'])) {
                    $val[$field] = $data[$vars['field']];
                } else {
                    $val[$field] = $data[$field];
                }
                fn_exim_quote($val[$field], $quote);

            } elseif ($value === '#key') {
                $val[$field] = (sizeof($vars['key']) == 1)
                    ? reset($vars['key'])
                    : (isset($vars['key'][$field]) ? $vars['key'][$field] : $vars['key']);

            } elseif ($operator === '&') {
                $val[$field] = $pattern['table'] . '.' . substr($value, 1);

            } elseif ($value === '#field') {
                if (!empty($vars['field'])) {
                    $val[$field] = $vars['field'];
                } else {
                    $val[$field] = $field;
                }

            } elseif ($value === '#lang_code') {
                $val[$field] = empty($vars['lang_code']) ? '' : $vars['lang_code'];
                empty($vars['lang_code']) || fn_exim_quote($val[$field], $quote);

            } elseif ($value === '#row') {
                $val[$field] = $data;

            } elseif ($operator === '#') {
                $val[$field] = substr($value, 1);

            } elseif ($operator === '$') {
                $opt = str_replace('$', '', $value);
                if (isset($data[$opt])) {
                    fn_exim_quote($data[$opt], $quote);
                    $val[$field] = &$data[$opt];
                } else {
                    $val[$field] = '';
                }
            } elseif ($operator === '%') {
                $opt = str_replace('%', '', $value);
                if (isset($data['object'][$opt])) {
                    $val[$field] = $data['object'][$opt];
                } else {
                    $val[$field] = '';
                }
            } else {
                $val[$field] = $value;
                fn_exim_quote($val[$field], $quote);
            }
        }
    }

    return $val;
}

function fn_exim_import_build_groups($type_group, $export_fields)
{
    $groups = [];

    if (empty($type_group)) {
        return $groups;
    }

    foreach ($export_fields as $field => $data) {
        if (empty($data[$type_group])) {
            continue;
        }

        $db_field = empty($data['db_field']) ? $field : $data['db_field'];
        $return_result = empty($data['return_result']) ? false : $data['return_result'];
        $return_field = empty($data['return_field']) ? $db_field : $data['return_field'];

        $args = $data[$type_group];
        $function = array_shift($args);

        $groups[] = [
            'function'      => $function,
            'this_field'    => $db_field,
            'args'          => $args,
            'table'         => empty($data['table']) ? '' : $data['table'],
            'multilang'     => !empty($data['multilang']),
            'return_result' => $return_result,
            'return_field'  => $return_result ? $return_field : false
        ];
    }

    return $groups;
}

function fn_exim_import_prepare_groups(&$data, $groups, $options, $skip_record = false, $stop_import = false)
{
    if (!empty($groups)) {
        foreach ($groups as $group) {
            if (!isset($data[$group['this_field']])) {
                continue;
            }
            $vars = array(
                'lang_code' => !empty($data['lang_code']) ? $data['lang_code'] : '',
                'field' => $group['this_field']
            );

            $params = fn_exim_get_values($group['args'], array(), $options, $vars, $data, '');
            $params[] = & $skip_record;
            $params[] = & $stop_import;

            $data[$group['this_field']] = call_user_func_array($group['function'], $params);
        }
    }

    return true;
}

function fn_exim_import_parse_languages($pattern, &$import_data, $options)
{

    foreach ($pattern['export_fields'] as $field_name => $field) {
        if (!empty($field['type']) && $field['type'] == 'languages') {
            if (empty($field['db_field'])) {
                $field_lang = $field_name;
            } else {
                $field_lang = $field['db_field'];
            }
        }
    }

    // Languages
    $langs = array();

    // Get all lang from data
    foreach ($import_data as $k => $v) {
        if (!isset($v['lang_code']) || in_array($v['lang_code'], $langs)) {
            break;
        }
        $langs[] = $v['lang_code'];
    }

    if (empty($langs)) {
        foreach ($import_data as $key => $data) {
            $import_data[$key]['lang_code'] = DEFAULT_LANGUAGE;
        }

        $langs[] = DEFAULT_LANGUAGE;
    }

    $langs = array_intersect($langs, array_keys(Languages::getAll()));
    $count_langs = count($langs);

    $count_lang_data = array();
    foreach ($langs as $lang) {
        $count_lang_data[$lang] = 0;
    }

    $data = array();
    $result = true;
    if (isset($field_lang)) {
        foreach ($import_data as $v) {
            if (!empty($v[$field_lang]) && in_array($v[$field_lang], $langs)) {
                $data[] = $v;
                $count_lang_data[$v[$field_lang]]++;
            }
        }

        // Check
        $count_data = reset($count_lang_data);
        foreach ($langs as $lang) {
            if ($count_lang_data[$lang] != $count_data) {
                $result = false;
                break;
            }
        }

        if ($result) {
            // Chunk on languages
            $data_lang = $data ? array_chunk($data, $count_langs) : array();
            $data = array();

            foreach ($data_lang as $data_key => $data_value) {
                foreach ($data_value as $v) {
                    $data[$data_key][$v[$field_lang]] = $v;
                }
            }

            if (fn_allowed_for('ULTIMATE')) {
                foreach ($data as $data_key => $data_value) {
                    $data_main = array_shift($data_value);
                    if (empty($data_main['company'])) {
                        $data_main['company'] = Registry::get('runtime.company_data.company');
                    }
                    foreach ($data_value as $v) {
                        $data[$data_key][$v[$field_lang]]['company'] = $data_main['company'];
                    }
                }
            }

            $import_data = $data;
        }
    } else {
        $main_lang = reset($langs);
        foreach ($import_data as $data_key => $data_value) {
            $data[$data_key][$main_lang] = $data_value;
        }
        $import_data = $data;
    }

    return $result;
}

/**
 * Changes company_id in the table passed in the object_type parameter.
 *
 * If the company isn't found, a new company will be created.
 *
 * @param string             $object_type              Type of object ('currencies', 'pages', etc)
 * @param string             $object_key               Object key
 * @param int                $object_id                Product identifier
 * @param string             $company_name             Company name
 * @param array<string, int> $processed_data           Quantity of the loaded objects. Objects:
 *                                                     'E' - quantity existent products, 'N' - quantity new products,
 *                                                     'S' - quantity skipped products, 'C' - quantity vendors
 * @param bool               $check_runtime_company_id Whether to check runtime company ID or not
 *
 * @return int $company_id Company identifier.
 */

function fn_exim_set_company($object_type, $object_key, $object_id, $company_name, array &$processed_data = [], $check_runtime_company_id = true)
{
    if (empty($company_name) || empty($object_id) || empty($object_type)) {
        return false;
    }

    $company_name = trim($company_name);
    if (Registry::get('runtime.company_id') && $check_runtime_company_id) {
        $company_id = Registry::get('runtime.company_id');
    } else {
        $company_id = fn_get_company_id_by_name($company_name);

        if (!$company_id) {
            if (!empty($processed_data)) {
                $processed_data['C']++;
            }

            $company_data = [
                'company' => $company_name,
                'email' => '',
                'status' => 'A'
            ];

            if (fn_allowed_for('MULTIVENDOR')) {
                $shippings = Shippings::getShippingIdsAvailableForNewVendors(true);
                if (!empty($shippings)) {
                    $company_data['shippings'] = $shippings;
                } else {
                    $company_data['shippings']  = '';
                }
            }

            $company_id = fn_update_company($company_data, 0);
        }
    }

    db_query("UPDATE ?:$object_type SET company_id = ?s WHERE $object_key = ?i", $company_id, $object_id);

    return $company_id;
}

/**
 * Wrap value
 * @param string|string[] $value
 * @param string $enclosure
 * @param string $delimiter
 * @return string|string[]
 */
function fn_exim_wrap_value($value, $enclosure = '"', $delimiter = ',')
{
    $enclosure = trim($enclosure);
    $delimiter = trim($delimiter);

    if (is_array($value)) {
        foreach ($value as &$item) {
            $item = fn_exim_wrap_value($item, $enclosure, $delimiter);
        }
        unset($item);

        return $value;
    }

    if (is_string($value) && strpos($value, $delimiter) !== false) {
        $value = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $value) . $enclosure;
    }

    return $value;
}

/**
 * Adds current company_id as altkey if object doesn't
 * have store defined. Needed to determine primary object
 * correctly.
 *
 * @param array $pattern Pattern structure
 * @param array $alt_keys Keys that act as primary when adding/updating
 * @param array $object Object structure to export
 * @param boolean $skip_get_primary_object_id Flag to leave primary object empty
 */
function fn_exim_apply_company($pattern, &$alt_keys, &$object, &$skip_get_primary_object_id)
{
    if (empty($object['company'])){ //object doesn't have store name defined
        if (Registry::get('runtime.company_id') && empty($alt_keys['company_id'])){
            $alt_keys['company_id'] = Registry::get('runtime.company_id');
        }
    } else {
        $company_id = fn_get_company_id_by_name($object['company']);
        if ($company_id !== null) {
            $alt_keys['company_id'] = $company_id;
        }
    }
}

/**
 * Gets the translation for the variable.
 *
 * @param string $value The name of variable.
 * @param string $action Type of the action: import|export
 *
 * @return bool|string The string with the translation.
 */
function fn_exim_get_field_label($value, $action = '')
{
    $value = str_replace(array(':', '(', ')', '-'), '', $value);
    $value = strtolower(str_replace(' ', '_', $value));

    if (!empty($action) && fn_is_lang_var_exists('exim_' . $action . '_' . $value)) {
        $label = __('exim_' . $action . '_' . $value);
    } elseif (fn_is_lang_var_exists('exim_' . $value)) {
        $label = __('exim_' . $value);
    } else {
        $label = fn_is_lang_var_exists($value) ? __($value) : false;
    }

    return $label;
}

/**
 * Gets product availability depending on amount and inventory settings.
 *
 * @param array<string, string|null> $product     Product data
 * @param string                     $export_type Export type
 *
 * @return string
 */
function fn_exim_get_product_availability(array $product, $export_type)
{
    $availability = ProductAvailability::getAll();

    /**
     * Allows to override "Product availability" export field values.
     *
     * @param array{
     *  OUT_OF_STOCK: string,
     *  IN_STOCK: string,
     *  PRE_ORDER: string
     * } $availability Product availability
     * @param string $export_type Identifies the export
     */
    fn_set_hook('export_product_availability', $availability, $export_type);

    $amount = $product['availability_amount'];
    $allow_inventory_tracking = YesNo::toBool(Settings::getSettingValue('General.inventory_tracking', Registry::get('runtime.company_id')));
    $allow_negative_amount = YesNo::toBool(Settings::getSettingValue('General.allow_negative_amount', Registry::get('runtime.company_id')));

    if ($allow_inventory_tracking) {
        if ($allow_negative_amount || $product['tracking'] === ProductTracking::DO_NOT_TRACK) {
            return $amount > 0 ? $availability[ProductAvailability::KEY_IN_STOCK] : $availability[ProductAvailability::KEY_PRE_ORDER];
        }

        if ($amount > 0 && $amount > $product['min_quantity']) {
            return $availability[ProductAvailability::KEY_IN_STOCK];
        }

        if ($product['out_of_stock_action'] === OutOfStockActions::BUY_IN_ADVANCE) {
            return $availability[ProductAvailability::KEY_PRE_ORDER];
        }

        return $availability[ProductAvailability::KEY_OUT_OF_STOCK];
    }

    return $availability[ProductAvailability::KEY_PRE_ORDER];
}

/**
 * Clears BOM symbols from the beginning of the file if exists
 *
 * @param resource $file File resource
 *
 * @return resource
 */
function fn_exim_clear_bom_symbols_if_exists($file)
{
    $byte_order_mark = chr(239) . chr(187) . chr(191);
    $byte_count = 3;

    // Skip BOM if present
    if (fread($file, $byte_count) !== $byte_order_mark) {
        rewind($file); // Or rewind pointer to start of file
    }

    return $file;
}
