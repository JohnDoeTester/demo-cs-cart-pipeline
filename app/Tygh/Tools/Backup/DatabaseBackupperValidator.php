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
use Tygh\Exceptions\DeveloperException;
use Tygh\Tools\SecurityHelper;

/**
 * Class DatabaseBackupperValidator provides means to ensure that database can be properly backed up and restored
 * using a database backupper.
 *
 * @package Tygh\Tools\Backup
 */
class DatabaseBackupperValidator
{
    /**
     * @var int $tables_count Amount of tables in sample data
     */
    public $tables_count = 3;

    /**
     * @var int $rows_count Amount of rows per sample table
     */
    public $rows_count = 20;

    /**
     * @var array $config Software config
     */
    protected $config;

    /**
     * @var array Sample data
     */
    protected $data = array();

    /**
     * @var \Tygh\Tools\Backup\ADatabaseBackupper $backupper Backupper to check
     */
    protected $backupper;

    /**
     * @var \Tygh\Database\Connection $database
     */
    protected $database;

    /**
     * DatabaseBackupperValidator constructor.
     *
     * @param \Tygh\Tools\Backup\ADatabaseBackupper $backupper   Backupper to check
     * @param array                                 $config      Software config
     * @param \Tygh\Database\Connection             $database    Database
     * @param string                                $backup_path Path to write backup
     */
    public function __construct($backupper, array $config, Connection $database, $backup_path)
    {
        $this->config = $config;

        $this->database = $database;
        $this->database->raw = true;

        $this->backup_path = $backup_path;
        $this->backupper = clone $backupper;
    }

    /**
     * Creates sample tables and fills them with the sample data.
     *
     * @param int $tables_count Amount of tables in sample data
     * @param int $rows_count   Amount of rows per sample table
     */
    public function initSampleData($tables_count, $rows_count)
    {
        $prefix = $this->database->process('?:');

        $template = $prefix . 'backup_validator_' . date('YmdHis');

        $max = getrandmax();

        $i = 0;
        while (sizeof($this->data) < $tables_count) {

            // populate table name
            do {
                $i++;
                $table = "{$template}_{$i}";
            } while ($this->database->hasTable($table) || isset($this->data[$table]));
            $this->data[$table] = array();

            // populate data
            for ($j = 0; $j < $rows_count; $j++) {
                $this->data[$table][] = array(
                    'id'    => $j + 1,
                    'col_1' => md5($table) . SecurityHelper::generateRandomString(),
                    'col_2' => sprintf('%.2f', (float) rand(1, $max) / (float) rand(1, $max)),
                );
            }

            // create table
            $this->database->query(
                "CREATE TABLE ?f ("
                . " id mediumint(9) unsigned NOT NULL AUTO_INCREMENT,"
                . " col_1 VARCHAR(64) NOT NULL DEFAULT '',"
                . " col_2 DECIMAL(10,2) NOT NULL DEFAULT 0,"
                . " PRIMARY KEY (id)"
                . " ) ENGINE=MyISAM DEFAULT CHARSET=utf8",
                $table
            );

            // put data
            $this->database->query('INSERT INTO ?f ?m', $table, $this->data[$table]);
        }
    }

    /**
     * Creates backup of previously created sample data.
     *
     * @return bool True of success
     * @throws \Tygh\Exceptions\DeveloperException When sample data is not initiated
     */
    public function tryBackup()
    {
        if (!$this->data) {
            throw new DeveloperException('Sample data is not initiated');
        }

        return $this->backupper
            ->setTables(array_keys($this->data))
            ->setParameters(array(
                'backup_schema' => true,
                'backup_data'   => true,
            ))
            ->makeBackup();
    }

    /**
     * Tries to restore previously backed up data.
     *
     * @return bool True on success
     * @throws \Tygh\Exceptions\DeveloperException When sample data is not initiated
     */
    public function tryRestore()
    {
        if (!$this->data) {
            throw new DeveloperException('Sample data is not initiated');
        }

        foreach ($this->data as $table => $data) {
            $this->database->query('TRUNCATE TABLE ?f', $table);
        }

        return db_import_sql_file(
            $file = $this->backup_path,
            $buffer = 16384,
            $show_status = false,
            $show_create_table = false,
            $check_prefix = false,
            $track = false,
            $skip_errors = false,
            $move_progress_bar = false
        );
    }

    /**
     * Validates that sample data imported from the backup is the same that was backed up.
     *
     * @return bool True of success
     */
    public function validate()
    {
        $result = true;

        $this->initSampleData($this->tables_count, $this->rows_count);

        if (!$this->tryBackup() || !$this->tryRestore()) {
            $result = false;
        }

        if ($result) {
            foreach ($this->data as $table => $data) {
                if (!$this->database->hasTable($table, false)
                    || $data != $this->database->getArray("SELECT * FROM ?f ORDER BY id ASC", $table)
                ) {
                    $result = false;
                    break;
                }
            }
        }

        $this->cleanup();

        return $result;
    }

    /**
     * Cleans up sample data from the DB.
     */
    public function cleanup()
    {
        if ($this->data) {
            foreach ($this->data as $table => $data) {
                $this->database->query('DROP TABLE IF EXISTS ?f', $table);
            }
        }
    }
}
