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

use Tygh\Exceptions\DatabaseException;
use Tygh\Exceptions\InputException;
use Tygh\Exceptions\NativeBackupperException;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
 */

/**
 * Execute query and format result as associative array with column names as keys
 *
 * @param string $query unparsed query
 * @param mixed ... unlimited number of variables for placeholders
 * @return array structured data
 */
function db_get_array()
{
    return call_user_func_array(array(Tygh::$app['db'], 'getArray'), func_get_args());
}

/**
 * Execute query and format result as associative array with column names as keys and index as defined field
 *
 * @param string $query unparsed query
 * @param string $field field for array index
 * @param mixed ... unlimited number of variables for placeholders
 * @return array structured data
 */
function db_get_hash_array()
{
    return call_user_func_array(array(Tygh::$app['db'], 'getHash'), func_get_args());
}

/**
 * Execute query and format result as associative array with column names as keys and then return first element of this array
 *
 * @param string $query unparsed query
 * @param mixed ... unlimited number of variables for placeholders
 * @return array structured data
 */
function db_get_row()
{
    return call_user_func_array(array(Tygh::$app['db'], 'getRow'), func_get_args());
}

/**
 * Execute query and returns first field from the result
 *
 * @param string $query unparsed query
 * @param mixed ... unlimited number of variables for placeholders
 *
 * @return string
 */
function db_get_field()
{
    return call_user_func_array(array(Tygh::$app['db'], 'getField'), func_get_args());
}

/**
 * Execute query and format result as set of first column from all rows
 *
 * @param string $query unparsed query
 * @param mixed ... unlimited number of variables for placeholders
 * @return array structured data
 */
function db_get_fields()
{
    return call_user_func_array(array(Tygh::$app['db'], 'getColumn'), func_get_args());
}

/**
 * Execute query and format result as one of: field => array(field_2 => value), field => array(field_2 => row_data), field => array([n] => row_data)
 *
 * @param string $query unparsed query
 * @param array $params array with 3 elements (field, field_2, value)
 * @param mixed ... unlimited number of variables for placeholders
 * @return array structured data
 */
function db_get_hash_multi_array()
{
    return call_user_func_array(array(Tygh::$app['db'], 'getMultiHash'), func_get_args());
}

/**
 * Execute query and format result as key => value array
 *
 * @param string $query unparsed query
 * @param array $params array with 2 elements (key, value)
 * @param mixed ... unlimited number of variables for placeholders
 * @return array structured data
 */
function db_get_hash_single_array()
{
    return call_user_func_array(array(Tygh::$app['db'], 'getSingleHash'), func_get_args());
}

/**
 * Prepare data and execute REPLACE INTO query to DB
 * If one of $data element is null function unsets it before querry
 *
 * @param string $table       Name of table that condition generated. Must be in SQL notation without placeholder.
 * @param array  $data        Array of key=>value data of fields need to insert/update
 * @param bool   $is_multiple if true, $data is treated as multiple values array
 *
 * @return int
 */
function db_replace_into($table, $data, $is_multiple = false)
{
    return call_user_func_array(array(Tygh::$app['db'], 'replaceInto'), func_get_args());
}

/**
 * Execute query
 *
 * @param string $query unparsed query
 * @param mixed ... unlimited number of variables for placeholders
 * @return mixed result set or the ID generated for an AUTO_INCREMENT field for insert statement
 */
function db_query()
{
    return call_user_func_array(array(Tygh::$app['db'], 'query'), func_get_args());
}

/**
 * Execute multiple queries. When native drivers (like mysqli) are used, only a single query is sent to the
 * database when used. This reduces the round robin time and brings significant performant improvements where database
 * queries take long to travel to the server and back.
 *
 * You should use the function as follows:
 * ```php
 * list ($companies, $products) = db_multi_query([
 *     [MultiQueryTypes::ARR,  'SELECT * FROM ?:companies'],
 *     [MultiQueryTypes::HASH, 'SELECT * FROM ?:products LIMIT ?i', 'product_id', 10],
 * ]);
 * ```
 *
 * The array will keep its indices, so you could also access the result using the previously defined offsets.
 * ```php
 * $result = db_multi_query([
 *     'companies' => [MultiQueryTypes::ARR,  'SELECT * FROM ?:companies'],
 *     'products'  => [MultiQueryTypes::HASH, 'SELECT * FROM ?:products LIMIT ?i', 'product_id', 10],
 * ]);
 * ```
 *
 * @param array<string, mixed> $queries An array of queries to execute
 *
 * @return mixed
 */
function db_multi_query(array $queries)
{
    return !empty($queries) ? Tygh::$app['db']->multiQuery($queries) : [];
}

/**
 * Start a transaction.
 *
 * In order to use transactions effectively, make sure that the tables involved in the transaction use a compatible
 * storage engine (such as InnoDB). The default MyISAM engine does not support transactions, although you could
 * change this for all tables to InnoDB relatively easy.
 *
 * For more information read the manual: https://dev.mysql.com/doc/refman/8.0/en/converting-tables-to-innodb.html
 *
 * @param Closure $closure  The Closure to execute within the transaction context
 * @param int     $attempts The amount of times to attempt the transaction
 *
 * @return mixed The result returned from the closure
 *
 * @throws DatabaseException When the transaction fails.
 */
function db_transaction(Closure $closure, int $attempts = 1)
{
    return Tygh::$app['db']->transaction($closure, $attempts);
}

/**
 * Parse query and replace placeholders with data
 *
 * @param string $query unparsed query
 * @param mixed ... unlimited number of variables for placeholders
 * @return string parsed query
 */
function db_quote()
{
    return call_user_func_array(array(Tygh::$app['db'], 'quote'), func_get_args());
}

/**
 * Parse query and replace placeholders with data
 *
 * @param string $query unparsed query
 * @param array $data data for placeholders
 * @return string parsed query
 */
function db_process()
{
    return call_user_func_array(array(Tygh::$app['db'], 'process'), func_get_args());
}

/**
 * Get column names from table
 *
 * @param string $table_name table name
 * @param array $exclude optional array with fields to exclude from result
 * @param bool $wrap_quote optional parameter, if true, the fields will be enclosed in quotation marks
 * @return array columns array
 */
function fn_get_table_fields()
{
    return call_user_func_array(array(Tygh::$app['db'], 'getTableFields'), func_get_args());
}

/**
 * Check if passed data corresponds columns in table and remove unnecessary data
 *
 * @param array $data data for compare
 * @param array $table_name table name
 * @return mixed array with filtered data or false if fails
 */
function fn_check_table_fields()
{
    return call_user_func_array(array(Tygh::$app['db'], 'checkTableFields'), func_get_args());
}

/**
 * Remove value from set (e.g. remove 2 from "1,2,3" results in "1,3")
 *
 * @param string $field table field with set
 * @param string $value value to remove
 * @return string database construction for removing value from set
 */
function fn_remove_from_set($field, $value)
{
    return Tygh::$app['db']->quote("TRIM(BOTH ',' FROM REPLACE(CONCAT(',', $field, ','), CONCAT(',', ?s, ','), ','))", $value);
}

/**
 * Add value to set (e.g. add 2 from "1,3" results in "1,3,2")
 *
 * @param string $field table field with set
 * @param string $value value to add
 * @return string database construction for add value to set
 */
function fn_add_to_set($field, $value)
{
    return Tygh::$app['db']->quote("TRIM(BOTH ',' FROM CONCAT_WS(',', ?p, ?s))", fn_remove_from_set($field, $value), $value);
}

/**
 * Create set from php array
 *
 * @param array $set_data values array
 * @return string database construction for creating set
 */
function fn_create_set($set_data = array())
{
    return empty($set_data) ? '' : implode(',', $set_data);
}

function fn_find_array_in_set($arr, $set, $find_empty = false)
{
    $conditions = array();
    if ($find_empty) {
        $conditions[] = "$set = ''";
    }
    if (!empty($arr)) {
        foreach ($arr as $val) {
            $conditions[] = Tygh::$app['db']->quote("FIND_IN_SET(?i, $set)", $val);
        }
    }

    return empty($conditions) ? '' : implode(' OR ', $conditions);
}

/**
 * Connect to database server and select database
 *
 * @param string $host database host
 * @param string $user database user
 * @param string $password database password
 * @param string $name database name
 * @param array  $params additional connection parameters (name, table prefix)
 * @return resource database connection identifier, false if error occurred
 */
function db_initiate($host, $user, $password, $name, $params = array())
{
    $is_connected = Tygh::$app['db']->connect($user, $password, $host, $name, $params);

    if ($is_connected) {
        Registry::set('runtime.database.skip_errors', false);

        return true;
    }

    return false;
}

/**
 * Change default connect to $dbc_name
 *
 * @param array $params Params for database connection
 * @param string $name Database name
 * @return bool True on success false otherwise
 */
function db_connect_to($params, $name)
{
    return Tygh::$app['db']->changeDb($name, $params);
}

/**
 * Get the number of found rows from the last query
 *
 */
function db_get_found_rows()
{
    return Tygh::$app['db']->getField("SELECT FOUND_ROWS()");
}

/**
 * Exports database to file
 *
 * @param string                                            $file_name           path to file will be created
 * @param array                                             $dbdump_tables       List of tables to be exported
 * @param bool                                              $dbdump_schema       Export database schema
 * @param bool                                              $dbdump_data         Export tatabase data
 * @param bool                                                $log                 Log database export action
 * @param bool                                                $show_progress       Show or do not show process by printing ' .'
 * @param bool                                                $move_progress_bar   Move COMET progress bar or not on show progress
 * @param array                                               $change_table_prefix Array with 2 keys (from, to) to change table prefix
 * @param \Tygh\Tools\Backup\ADatabaseBackupper $backupper             Database backupper
 *
 * @return bool false, if file is not accessible
 */
function db_export_to_file($file_name, $dbdump_tables, $dbdump_schema, $dbdump_data, $log = true, $show_progress = true, $move_progress_bar = true, $change_table_prefix = array(), $backupper = null)
{
    if (!$backupper) {
        /** @var \Tygh\Tools\Backup\DatabaseBackupperFactory $factory */
        $factory = Tygh::$app['backupper.database'];
        $config = Registry::get('config');
        $params = array(
            'db_schema'       => $dbdump_schema,
            'db_data'         => $dbdump_data,
            'show_progress'   => $show_progress,
            'move_progress'   => $move_progress_bar,
            'change_table_prefix' => $change_table_prefix,
        );

        try {
            $backupper = $factory->createNativeBackupper($config, $dbdump_tables, $params, $file_name, Tygh::$app['server.env.ini_vars']);
        } catch (NativeBackupperException $e) {
            try {
                $backupper = $factory->createFallbackBackupper($config, $dbdump_tables, $params, $file_name, Tygh::$app['db']);
            } catch (InputException $e) {
                fn_set_notification('E', __('error'), __('dump_cant_create_file'));
            } catch (DatabaseException $e) {
                fn_set_notification('E', __('error'), __('could_not_connect_to_database'));
            } catch (\Exception $e) {
                fn_set_notification('E', __('error'), $e->getMessage());
            }
        }
    }

    if (!$backupper) {
        return false;
    }

    if ($log) {
        // Log database backup
        fn_log_event('database', 'backup');
    }

    $result = $backupper->makeBackup();

    if ($result) {
        @chmod($file_name, DEFAULT_FILE_PERMISSIONS);
    }

    return $result;
}

/**
 * Fuctnions parses SQL file and import data from it
 *
 * @param string $file File for import
 * @param integer $buffer Buffer size for fread function
 * @param bool $show_status Show or do not show process by printing ' .'
 * @param integer $show_create_table 0 - Do not print the name of created table, 1 - Print name and get lang_var('create_table'), 2 - Print name without getting lang_var
 * @param bool $check_prefix Check table prefix and replace it with the installed in config.php
 * @param bool $track Use queries cache. Do not execute queries that already are executed.
 * @param bool $skip_errors Skip errors or not
 * @param bool $move_progress_bar Move COMET progress bar or not on show progress
 * @return bool false, if file is not accessible
 */
function db_import_sql_file($file, $buffer = 16384, $show_status = true, $show_create_table = 1, $check_prefix = false, $track = false, $skip_errors = false, $move_progress_bar = true)
{
    if (file_exists($file)) {

        $path = dirname($file);
        $file_name = fn_basename($file);
        $tmp_file = $path . "/$file_name.tmp";

        $executed_queries = array();
        if ($track && file_exists($tmp_file)) {
            $executed_queries = unserialize(fn_get_contents($tmp_file));
        }

        if ($skip_errors) {
            $_skip_errors = Registry::get('runtime.database.skip_errors');
            Registry::set('runtime.database.skip_errors', true);
        }

        $fd = fopen($file, 'r');
        if ($fd) {
            $ret = array();
            $rest = '';
            $fs = filesize($file);

            if ($show_status && $move_progress_bar) {
                fn_set_progress('step_scale', ceil($fs / $buffer));
            }

            while (!feof($fd)) {
                $str = $rest.fread($fd, $buffer);

                $rest = fn_parse_queries($ret, $str);

                if ($show_status) {
                    fn_set_progress('echo', '<br />'. __('importing_data'), $move_progress_bar);
                }

                if (!empty($ret)) {
                    foreach ($ret as $query) {
                        if (!in_array($query, $executed_queries)) {
                            if ($show_create_table && preg_match('/CREATE\s+TABLE\s+`?(\w+)`?/i', $query, $matches)) {
                                if ($show_create_table == 1) {
                                    $_text = __('creating_table');
                                } elseif ($show_create_table == 2) {
                                    $_text = 'Creating table';
                                }
                                $table_name = $check_prefix ? fn_check_db_prefix($matches[1], Registry::get('config.table_prefix')) : $matches[1];
                                if ($show_status) {
                                    fn_set_progress('echo', '<br />'. $_text . ': <b>' . $table_name . '</b>', $move_progress_bar);
                                }
                            }

                            if ($check_prefix) {
                                $query = fn_check_db_prefix($query);
                            }
                            Tygh::$app['db']->query($query);

                            if ($track) {
                                $executed_queries[] = $query;
                                fn_put_contents($tmp_file, serialize($executed_queries));
                            }

                            if ($show_status) {
                                fn_echo(' .');
                            }
                        }
                    }
                    $ret = array();
                }
            }

            fclose($fd);

            return true;
        }

        if ($skip_errors) {
            Registry::set('runtime.database.skip_errors', $_skip_errors);
        }
    }

    return false;
}

/**
 * Gets auto increment value for table
 *
 * @param string $table Database table name
 *
 * @return int Returns the auto increment value
 * @deprecated
 * Don't fetch the value of the counter:
 * - It is possible to make do without it in all cases.
 * - It is not safe when query contention is high.
 */
function db_get_next_auto_increment_id($table)
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    $db->raw = true;
    $db->query('ANALYZE TABLE ?:?p', $table);

    $table_status = $db->getRow("SHOW TABLE STATUS LIKE '?:?p'", $table);

    return !empty($table_status['Auto_increment']) ? $table_status['Auto_increment'] : $table_status['AUTO_INCREMENT'];
}

/**
 * Function removes all records in child table with no parent records
 * Table names must be in SQL notation without placeholder.
 * @param string $child_table Name of table for removing records.
 * @param string $child_foreign_key Name of field in child table with parent record id
 * @param string $parent_table Name of table with parent records.
 * @param string $parent_primary_key primary key in parent table, if empty will be equal $child_foreign_key
 */
function db_remove_missing_records($child_table, $child_foreign_key, $parent_table, $parent_primary_key = '')
{
    if ($parent_primary_key == '') {
        $parent_primary_key = $child_foreign_key;
    }
    Tygh::$app['db']->query("DELETE FROM ?:$child_table WHERE $child_foreign_key NOT IN (SELECT $parent_primary_key FROM ?:$parent_table)");
}

/**
 * Sort query results
 *
 * @param array  $params        Sort params
 * @param array  $sortings      Available sortings
 * @param string $default_by    Default sort field
 * @param string $default_order Default order
 *
 * @return string SQL substring
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
 */
function db_sort(array &$params, array $sortings, $default_by = '', $default_order = '')
{
    $directions = [
        'asc'     => 'desc',
        'desc'    => 'asc',
        'descasc' => 'ascdesc', // when sorting by 2 fields
        'ascdesc' => 'descasc' // when sorting by 2 fields
    ];

    if (empty($params['sort_order']) || empty($directions[$params['sort_order']])) {
        $params['sort_order'] = $default_order;
    }

    if (empty($params['sort_by']) || empty($sortings[$params['sort_by']])) {
        $params['sort_by'] = $default_by;
    }

    if (in_array($params['sort_order'], $directions)) {
        $params['sort_order_rev'] = $directions[$params['sort_order']];
    } else {
        $params['sort_order_rev'] = !empty($default_order) ? $directions[$default_order] : $directions['asc'];
    }

    if (!isset($sortings[$params['sort_by']])) {
        return '';
    }

    if (is_array($sortings[$params['sort_by']])) {
        if ($params['sort_order'] === 'descasc') {
            $order = implode(' desc, ', $sortings[$params['sort_by']]) . ' asc';
        } elseif ($params['sort_order'] === 'ascdesc') {
            $order = implode(' asc, ', $sortings[$params['sort_by']]) . ' desc';
        } else {
            $order = implode(' ' . $params['sort_order'] . ', ', $sortings[$params['sort_by']]) . ' ' . $params['sort_order'];
        }
    } else {
        $order = $sortings[$params['sort_by']] . ' ' . $params['sort_order'];
    }

    return ' ORDER BY ' . $order;
}

/**
 * Paginate query results
 *
 * @param int $page page number
 * @param int $items_per_page items per page
 * @return string SQL substring
 */
function db_paginate(&$page, &$items_per_page, $total_items = 0)
{
    $page = (int) $page;
    $items_per_page = (int) $items_per_page;

    if ($page <= 0) {
        $page = 1;
    }

    if ($items_per_page <= 0) {
        $items_per_page = (int) Registry::ifGet('settings.Appearance.admin_elements_per_page', 10);
    }

    // Check if page in valid limits
    if ($total_items > 0) {
        $page = db_get_valid_page($page, $items_per_page, $total_items);
    }

    return ' LIMIT ' . (($page - 1) * $items_per_page) . ', ' . $items_per_page;
}

function db_get_valid_page($page, $items_per_page, $total_items)
{
    if (($page - 1) * $items_per_page >= $total_items) {
        $page = ceil($total_items / $items_per_page);
    }

    return empty($page) ? 1 : $page;
}

/**
 * Get enum/set possible values in field of database
 *
 * @param string $table_name         Table name
 * @param string $field_name         Field name
 * @param bool   $get_with_lang_vars Getting with lang vars
 * @param string $lang_code          Lang code
 * @param string $lang_prefix        Lang vars prefix
 * @return array List of elements
 */
function db_get_list_elements($table_name, $field_name, $get_with_lang_vars = false, $lang_code = CART_LANGUAGE, $lang_prefix = '')
{
    $elements = (array) Tygh::$app['db']->getListElements($table_name, $field_name);

    if ($elements && $get_with_lang_vars) {
        $lang_elements = array();
        foreach ($elements as $element) {
            $lang_elements[$element] = __($lang_prefix . $element, array(), $lang_code);
        }

        return $lang_elements;
    }

    return $elements;
}

/**
 * Check if the table exists in the database
 *
 * @param string $table_name Table name
 * @param bool $set_prefix Set prefix before check
 * @return bool
 */
function db_has_table($table_name, $set_prefix = true)
{
    return Tygh::$app['db']->hasTable($table_name, $set_prefix);
}
