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
// phpcs:ignoreFile

namespace Tygh\SmartyEngine;


use Smarty_Internal_Resource_String;

class Security extends \Smarty_Security
{
    /**
     * This is an array of trusted PHP functions.
     * If empty all functions are allowed.
     * To disable all PHP functions set $php_functions = null.
     *
     * @var array
     */
    public $php_functions = [];

    /**
     * This is an array of trusted PHP modifiers.
     * If empty all modifiers are allowed.
     * To disable all modifier set $php_modifiers = null.
     *
     * @var array
     */
    public $php_modifiers = [];

    /**
     * @var string[]
     */
    protected $php_disabled_functions = [
        'exec', 'passthru', 'shell_exec', 'system', 'proc_open', 'popen', 'parse_ini_file', 'show_source', 'pcntl_exec',
        'assert', 'create_function', 'include', 'include_once', 'require', 'require_once', 'ob_start', 'fn_install_addon',
        'call_user_func', 'call_user_func_array', 'db_initiate', 'db_connect_to', 'db_get_found_rows', 'fsockopen',
        'db_get_next_auto_increment_id', 'db_import_sql_file', 'db_remove_missing_records', 'db_get_list_elements',
        'db_has_table', 'extract', 'db_export_to_file', 'db_query', 'db_multi_query', 'db_transaction', 'db_process',
        'db_get_hash_array', 'db_get_row', 'db_get_field', 'db_get_fields', 'db_get_hash_multi_array', 'pfsockopen',
        'db_get_hash_single_array', 'db_replace_into', 'mail', 'header', 'proc_nice', 'proc_terminate', 'proc_close',
        'posix_kill', 'posix_mkfifo', 'posix_setpgid', 'posix_setsid', 'posix_setuid', 'fopen', 'tmpfile', 'bzopen',
        'gzopen', 'chgrp', 'chmod', 'chown', 'copy', 'file_put_contents', 'lchgrp', 'lchown', 'link', 'mkdir',
        'move_uploaded_file', 'rename', 'rmdir', 'symlink', 'tempnam', 'touch', 'unlink', 'imagepng', 'imagewbmp',
        'image2wbmp', 'imagejpeg', 'imagexbm', 'imagegif', 'imagegd', 'imagegd2', 'iptcembed', 'ftp_get', 'ftp_nb_get',
        'file_exists', 'file_get_contents', 'file', 'fileatime', 'filectime', 'filegroup', 'fileinode', 'filemtime',
        'fileowner', 'fileperms', 'filesize', 'filetype', 'glob', 'is_dir', 'is_executable', 'is_file', 'is_link',
        'is_readable', 'is_uploaded_file', 'is_writable', 'is_writeable', 'linkinfo', 'lstat', 'parse_ini_file',
        'apache_child_terminate', 'readfile', 'readlink', 'fn_mkdir', 'fn_compress_files', 'fn_decompress_files',
        'fn_copy', 'fn_rm', 'fn_get_dir_contents', 'fn_get_contents', 'fn_put_contents', 'fn_get_url_data', 'fn_rename',
        'fn_get_local_data', 'fn_get_last_key', 'fn_filter_uploaded_data', 'fn_filter_instant_upload', 'fn_fgetcsv',
        'fn_check_uploaded_data', 'fn_remove_temp_data', 'fn_create_temp_file', 'fn_ftp_connect', 'fn_ftp_chmod_file',
        'fn_get_files_dir_path', 'fn_get_public_files_path', 'fn_check_copy_ability', 'fn_get_server_data',
        'fn_get_file', 'fn_get_phpinfo', 'phpinfo', 'fn_generate_ekey', 'fn_get_object_by_ekey', 'fn_get_ekeys',
        'fn_change_session_param', 'fn_catch_exception', 'fn_get_dev_files', 'fn_set_store_mode', 'fn_convert_encoding',
        'fn_get_file_description', 'fn_redirect', 'fn_encrypt_text', 'fn_decrypt_text', 'fn_delete_static_data',
        'fn_get_static_data', 'fn_debug', 'fn_echo', 'fn_set_progress', 'fn_create_description', 'fn_get_cookie',
        'fn_write_ini_file', 'fn_find_file', 'fn_rm_by_ftp', 'fn_get_logs', 'fn_export', 'fn_import', 'fn_exim_get_csv',
        'fn_exim_put_csv', 'fn_copy_by_ftp',
    ];

    /**
     * @var string[]
     */
    protected $php_disabled_modifiers = [
        'exec', 'passthru', 'shell_exec', 'system', 'proc_open', 'popen', 'parse_ini_file', 'show_source', 'pcntl_exec',
        'assert', 'create_function', 'include', 'include_once', 'require', 'require_once', 'ob_start', 'fn_install_addon',
        'call_user_func', 'call_user_func_array', 'db_initiate', 'db_connect_to', 'db_get_found_rows', 'fsockopen',
        'db_get_next_auto_increment_id', 'db_import_sql_file', 'db_remove_missing_records', 'db_get_list_elements',
        'db_has_table', 'extract', 'db_export_to_file', 'db_query', 'db_multi_query', 'db_transaction', 'db_process',
        'db_get_hash_array', 'db_get_row', 'db_get_field', 'db_get_fields', 'db_get_hash_multi_array', 'pfsockopen',
        'db_get_hash_single_array', 'db_replace_into', 'mail', 'header', 'proc_nice', 'proc_terminate', 'proc_close',
        'posix_kill', 'posix_mkfifo', 'posix_setpgid', 'posix_setsid', 'posix_setuid', 'fopen', 'tmpfile', 'bzopen',
        'gzopen', 'chgrp', 'chmod', 'chown', 'copy', 'file_put_contents', 'lchgrp', 'lchown', 'link', 'mkdir',
        'move_uploaded_file', 'rename', 'rmdir', 'symlink', 'tempnam', 'touch', 'unlink', 'imagepng', 'imagewbmp',
        'image2wbmp', 'imagejpeg', 'imagexbm', 'imagegif', 'imagegd', 'imagegd2', 'iptcembed', 'ftp_get', 'ftp_nb_get',
        'file_exists', 'file_get_contents', 'file', 'fileatime', 'filectime', 'filegroup', 'fileinode', 'filemtime',
        'fileowner', 'fileperms', 'filesize', 'filetype', 'glob', 'is_dir', 'is_executable', 'is_file', 'is_link',
        'is_readable', 'is_uploaded_file', 'is_writable', 'is_writeable', 'linkinfo', 'lstat', 'parse_ini_file',
        'apache_child_terminate', 'readfile', 'readlink', 'fn_mkdir', 'fn_compress_files', 'fn_decompress_files',
        'fn_copy', 'fn_rm', 'fn_get_dir_contents', 'fn_get_contents', 'fn_put_contents', 'fn_get_url_data', 'fn_rename',
        'fn_get_local_data', 'fn_get_last_key', 'fn_filter_uploaded_data', 'fn_filter_instant_upload', 'fn_fgetcsv',
        'fn_check_uploaded_data', 'fn_remove_temp_data', 'fn_create_temp_file', 'fn_ftp_connect', 'fn_ftp_chmod_file',
        'fn_get_files_dir_path', 'fn_get_public_files_path', 'fn_check_copy_ability', 'fn_get_server_data',
        'fn_get_file', 'fn_get_phpinfo', 'phpinfo', 'fn_generate_ekey', 'fn_get_object_by_ekey', 'fn_get_ekeys',
        'fn_change_session_param', 'fn_catch_exception', 'fn_get_dev_files', 'fn_set_store_mode', 'fn_convert_encoding',
        'fn_get_file_description', 'fn_redirect', 'fn_encrypt_text', 'fn_decrypt_text', 'fn_delete_static_data',
        'fn_get_static_data', 'fn_debug', 'fn_echo', 'fn_set_progress', 'fn_create_description', 'fn_get_cookie',
        'fn_write_ini_file', 'fn_find_file', 'fn_rm_by_ftp', 'fn_get_logs', 'fn_export', 'fn_import', 'fn_exim_get_csv',
        'fn_exim_put_csv', 'fn_copy_by_ftp',
    ];

    public function isTrustedPhpFunction($function_name, $compiler)
    {
        if (
            empty($compiler->template->source->handler)
            || !$compiler->template->source->handler instanceof Smarty_Internal_Resource_String
        ) {
            return true;
        }

        if (!empty($this->php_disabled_functions) && in_array($function_name, $this->php_disabled_functions)) {
            $compiler->trigger_template_error("PHP function '{$function_name}' not allowed by security setting");
            return false;
        }

        return true;
    }


    /**
     * Check if static class is trusted.
     *
     * @param string $class_name
     * @param object $compiler compiler object
     *
     * @return boolean                 true if class is trusted
     */
    public function isTrustedStaticClass($class_name, $compiler)
    {
        return true;
    }

    /**
     * Check if static class method/property is trusted.
     *
     * @param string $class_name Class name
     * @param string $params     Params
     * @param object $compiler   Compiler object
     *
     * @return bool True if class method is trusted
     */
    public function isTrustedStaticClassAccess($class_name, $params, $compiler)
    {
        return true;
    }

    /**
     * Check if PHP modifier is trusted.
     *
     * @param string $modifier_name Modifier name
     * @param object $compiler      Compiler object
     *
     * @return bool True if modifier is trusted
     */
    public function isTrustedPhpModifier($modifier_name, $compiler)
    {
        if (
            empty($compiler->template->source->handler)
            || !$compiler->template->source->handler instanceof Smarty_Internal_Resource_String
        ) {
            return true;
        }

        if (!empty($this->php_disabled_modifiers) && in_array($modifier_name, $this->php_disabled_modifiers)) {
            $compiler->trigger_template_error("modifier '{$modifier_name}' not allowed by security setting");
            return false;
        }

        return true;
    }

    /**
     * Check if tag is trusted.
     *
     * @param string $tag_name Tag name
     * @param object $compiler Compiler object
     *
     * @return bool True if tag is trusted
     */
    public function isTrustedTag($tag_name, $compiler)
    {
        return true;
    }

    /**
     * Check if special $smarty variable is trusted.
     *
     * @param string $var_name Variable name
     * @param object $compiler Compiler object
     *
     * @return bool True if special $smarty variable is trusted
     */
    public function isTrustedSpecialSmartyVar($var_name, $compiler)
    {
        return true;
    }

    /**
     * Check if modifier plugin is trusted.
     *
     * @param string $modifier_name Modifier name
     * @param object $compiler      Compiler object
     *
     * @return bool True if modifier plugin is trusted
     */
    public function isTrustedModifier($modifier_name, $compiler)
    {
        return true;
    }

    /**
     * Check if constants are enabled or trusted
     *
     * @param string $const    Constant name
     * @param object $compiler Compiler object
     *
     * @return bool
     */
    public function isTrustedConstant($const, $compiler)
    {
        return true;
    }

    /**
     * Check if stream is trusted.
     *
     * @param string $stream_name Stream name
     *
     * @return bool True if stream is trusted
     */
    public function isTrustedStream($stream_name)
    {
        return true;
    }

    /**
     * Check if directory of file resource is trusted.
     *
     * @param string    $filepath File path
     * @param null|bool $isConfig Is Config
     *
     * @return bool true if directory is trusted
     */
    public function isTrustedResourceDir($filepath, $isConfig = null)
    {
        return true;
    }

    /**
     * Check if URI (e.g. {fetch} or {html_image}) is trusted
     *
     * @param string $uri URI
     *
     * @return bool True if URI is trusted
     */
    public function isTrustedUri($uri)
    {
        return true;
    }

    /**
     * Check if directory of file resource is trusted.
     *
     * @param string $filepath File path
     *
     * @return bool True if directory is trusted
     */
    public function isTrustedPHPDir($filepath)
    {
        return true;
    }
}
