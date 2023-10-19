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

namespace Tygh\Tools\Backup;

use Tygh\Tools\SecurityHelper;

/**
 * Class MysqldumpDatabaseBackupper implements database backupper performing all operations using system call to mysqldump utility.
 *
 * @package Tygh\Tools\Backup
 */
class MysqldumpDatabaseBackupper extends ADatabaseBackupper
{
    /** @inheritdoc */
    protected $id = 'mysqldump';

    /**
     * @var string $destination Path to write backup to
     */
    protected $destination;

    /**
     * Executes mysqldump binary with passed console line parameters.
     *
     * @param array $arguments_list Arguments to pass to mysqldump
     *
     * @return array(
     *             $exit_code - system call exit code; equals to 0 if the call was successful
     *             $output - output produced by system call
     *         )
     */
    private static function callBinary(array $arguments_list)
    {
        $cli = array_merge(array('mysqldump'), SecurityHelper::escapeShellArgs($arguments_list), array('2>&1'));

        $output = array();
        $exit_code = -1;
        exec(implode(' ', $cli), $output, $exit_code);

        return array($exit_code, $output);
    }

    /**
     * Sets file to write backup to.
     *
     * @param string $destination File path
     *
     * @return $this
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * Replaces table prefix in created database backup file.
     *
     * @param string $file    File to search
     * @param string $search  Search prefix
     * @param string $replace Replacement prefix
     */
    private function replaceTablePrefix($file, $search, $replace)
    {
        $tmp_file = $this->config['dir']['cache_misc'] . basename($file);
        $in = fopen($file, 'r');
        $out = fopen($tmp_file, 'w');

        while (!feof($in)) {
            $line = str_replace(
                array("CREATE TABLE `{$search}", "INSERT INTO `{$search}", "DROP TABLE IF EXISTS `{$search}"),
                array("CREATE TABLE `{$replace}", "INSERT INTO `{$replace}", "DROP TABLE IF EXISTS `{$replace}"),
                fgets($in)
            );
            fputs($out, $line);
        }

        fclose($in);
        fclose($out);

        // replace files
        fn_copy($tmp_file, $file);
        fn_rm($tmp_file);
    }

    /**
     * Checks if mysqldump can be used.
     *
     * @param array $ini_vars
     *
     * @return bool
     */
    public static function isBinaryCallable(array $ini_vars)
    {
        $is_exec_allowed =
            function_exists('exec')
            && empty($ini_vars['safe_mode'])
            && (empty($ini_vars['disabled_functions'])
                || !in_array('exec', array_map('trim', explode(',', $ini_vars['disabled_functions'])))
            );

        if ($is_exec_allowed) {
            list($exit_code,) = static::callBinary(array('--print-defaults'));

            return $exit_code === 0;
        }

        return false;
    }

    /** @inheritdoc */
    public function makeBackup()
    {
        $args = [
            '--skip-add-locks',
            '--skip-set-charset',
            '--skip-disable-keys',
            '--skip-comments',
            '--skip-opt',
            '--skip-tz-utc',
            '--no-create-db',
            '--add-drop-table',
            '--create-options',
            '--complete-insert',
            '--skip-extended-insert',
            ['--default-character-set' => 'utf8'],
            ['--user' => $this->config['db_user']],
            ['--password' => $this->config['db_password']],
            ['--result-file' => $this->destination],
        ];

        if (strpos($this->config['db_host'], ':') !== false) {
            list($host, $port) = explode(':', $this->config['db_host']);
            $args[] = ['--host' => $host];
            $args[] = ['--port' => $port];
        } else {
            $args[] = ['--host' => $this->config['db_host']];
        }

        if (!$this->params['db_schema']) {
            $args[] = '--no-create-info';
        }

        if (!$this->params['db_data']) {
            $args[] = '--no-data';
        }

        $args[] = $this->config['db_name'];

        $args = array_merge($args, $this->tables);

        list($exit_code,) = static::callBinary($args);

        if ($exit_code === 0 && $this->params['change_table_prefix']) {
            $this->replaceTablePrefix($this->destination, $this->params['change_table_prefix']['from'],
                $this->params['change_table_prefix']['to']);
        }

        return $exit_code === 0;
    }
}
