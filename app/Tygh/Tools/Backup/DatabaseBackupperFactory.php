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

use Tygh\Database\Connection;
use Tygh\Exceptions\InputException;
use Tygh\Exceptions\NativeBackupperException;

/**
 * Class DatabaseBackupperFactory provides means to create native and fallback backuppers to backup a database.
 *
 * @package Tygh\Tools\Backup
 */
class DatabaseBackupperFactory
{
    /**
     * Provides native (uses system calls to binary utilities) database backupper.
     *
     * @param array  $config      Software config
     * @param array  $tables      Tables to backup
     * @param array  $params      Backup parameters
     * @param string $destination Path to output file
     * @param array  $ini_vars    PHP configuration options.
     *                            The following options are used in checks: `safe_mode` and `disabled_functions`
     *
     * @return \Tygh\Tools\Backup\MysqldumpDatabaseBackupper Prepared native backupper
     * @throws \Tygh\Exceptions\InputException When directory for a dump can't be created
     * @throws \Tygh\Exceptions\NativeBackupperException When no native backupper found
     */
    public function createNativeBackupper(array $config, array $tables, array $params, $destination, array $ini_vars)
    {
        // check if mysqldump can be used
        if (empty($config['tweaks']['backup_db_mysqldump'])) {
            throw new NativeBackupperException('backup via mysqldump is disabled by config');
        }

        // check if mysqldump is callable
        if (!MysqldumpDatabaseBackupper::isBinaryCallable($ini_vars)) {
            throw new NativeBackupperException('mysqldump is not available');
        }

        // check if directory exists / can be created
        if (!fn_mkdir(dirname($destination))) {
            throw new InputException('Unable to create ' . dirname($destination));
        }

        $backupper = new MysqldumpDatabaseBackupper($config);

        $backupper->setTables($tables)
            ->setParameters($params)
            ->setDestination($destination);

        return $backupper;
    }

    /**
     * Provides fallback (uses software implementaion) database backupper.
     *
     * @param array                     $config      Software config
     * @param array                     $tables      Tables to backup
     * @param array                     $params      Backup parameters
     * @param string                    $destination Path to output file
     * @param \Tygh\Database\Connection $database    Connection to backed up database
     *
     * @return \Tygh\Tools\Backup\FallbackDatabaseBackupper Prepared fallback backupper
     * @throws \Tygh\Exceptions\DatabaseException When unable to connect to the database
     * @throws \Tygh\Exceptions\InputException When output file can't be opened for writing or directory for it can't be created
     */
    public function createFallbackBackupper(array $config, array $tables, array $params, $destination, Connection $database)
    {
        // ping connection (will throw exception when there is a problem with the connection)
        $database->raw = true;
        $database->query('SELECT 1');

        // check if directory exists / can be created
        if (!fn_mkdir(dirname($destination))) {
            throw new InputException('Unable to create ' . dirname($destination));
        }

        // try to open file for writing
        $output_file = @fopen($destination, 'w');
        if (!$output_file) {
            throw new InputException('Unable to write into ' . $destination);
        }

        $backupper = new FallbackDatabaseBackupper($config);

        $backupper->setDatabase($database)
            ->setTables($tables)
            ->setParameters($params)
            ->setOutputFile($output_file);

        return $backupper;
    }
}