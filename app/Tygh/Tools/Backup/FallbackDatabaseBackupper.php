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

/**
 * Class Fallback implements database backupper performing all operations using core software functionality.
 *
 * @package Tygh\Tools\Backup
 */
class FallbackDatabaseBackupper extends ADatabaseBackupper
{
    /** @inheritdoc */
    protected $id = 'fallback';

    /**
     * @var \Tygh\Database\Connection $database Database to backup
     */
    protected $database;

    /**
     * @var resource $output_file File resource to write backup to
     */
    protected $output_file;

    /** @inheritdoc */
    public function setParameters(array $params)
    {
        $params = array_merge(array(
            'max_row_size'  => DB_MAX_ROW_SIZE,
            'rows_per_pass' => DB_ROWS_PER_PASS,
        ), $params);

        return parent::setParameters($params);
    }

    /**
     * Sets output file to write backup to.
     *
     * @param resource $output_file File resource
     *
     * @return $this
     */
    public function setOutputFile($output_file)
    {
        $this->output_file = $output_file;

        return $this;
    }

    /**
     * Sets database to backup.
     *
     * @param \Tygh\Database\Connection $database Database to backup
     *
     * @return $this
     */
    public function setDatabase(Connection $database)
    {
        $this->database = $database;

        return $this;
    }

    /** @inheritdoc */
    public function makeBackup()
    {
        fseek($this->output_file, 0);

        // set export format
        $this->database->query("SET @SQL_MODE = 'MYSQL323'");

        if ($this->params['show_progress'] && $this->params['move_progress']) {
            $this->setProgressTotal($this->estimateTotal());
        }

        // get status data
        $t_status = $this->database->getHash("SHOW TABLE STATUS", 'Name');

        // get views tables name
        $is_views = $this->database->getHash(
            'SELECT table_name AS view_name'
            . ' FROM information_schema.tables'
            . ' WHERE table_type = ?s'
            . ' AND table_schema = ?s',
            'view_name',
            'VIEW',
            $this->config['db_name']
        );

        foreach ($this->tables as $k => $table) {
            $_table = !empty($this->params['change_table_prefix']) ? str_replace($this->params['change_table_prefix']['from'],
                $this->params['change_table_prefix']['to'], $table) : $table;

            $is_db_data = $this->params['db_data'];

            if ($this->params['db_schema']) {
                if ($this->params['show_progress']) {
                    $this->setProgress(__('backupping_schema') . ': <b>' . $table . '</b>',
                        $this->params['move_progress']);
                }

                if (!empty($is_views) && isset($is_views[$table])) {
                    fwrite($this->output_file, "\nDROP VIEW IF EXISTS `" . $_table . "`;\n");
                    $scheme = $this->database->getRow("SHOW CREATE VIEW `$table`");
                    $_scheme = $scheme['Create View'];
                    $is_db_data = false;
                } else {
                    fwrite($this->output_file, "\nDROP TABLE IF EXISTS `" . $_table . "`;\n");
                    $scheme = $this->database->getRow("SHOW CREATE TABLE `$table`");
                    $_scheme = array_pop($scheme);
                }

                if ($this->params['change_table_prefix']) {
                    $_scheme = str_replace($this->params['change_table_prefix']['from'],
                        $this->params['change_table_prefix']['to'], $_scheme);
                }

                fwrite($this->output_file, $_scheme . ";\n\n");
            }

            if (!$is_db_data) {
                continue;
            }

            if ($this->params['show_progress']) {
                $this->setProgress(
                    __('backupping_data') . ': <b>' . $table . '</b>',
                    $this->params['move_progress']
                );
            }

            $total_rows = $this->database->getField("SELECT COUNT(*) FROM `$table`");

            // Define iterator
            if (!empty($t_status[$table]) && $t_status[$table]['Avg_row_length'] < $this->params['max_row_size']) {
                $it = $this->params['rows_per_pass'];
            } else {
                $it = 1;
            }
            for ($i = 0; $i < $total_rows; $i = $i + $it) {
                $table_data = $this->database->getArray("SELECT * FROM `$table` LIMIT $i, $it");
                foreach ($table_data as $_tdata) {
                    $_tdata = fn_add_slashes($_tdata, true);
                    $values = [];
                    foreach ($_tdata as $v) {
                        $values[] = ($v !== null)
                            ? "'$v'"
                            : 'NULL';
                    }

                    $fields = implode('`, `', array_keys($_tdata));
                    $values = implode(', ', $values);
                    fwrite(
                        $this->output_file,
                        "INSERT INTO `$_table` (`{$fields}`) VALUES ({$values});\n"
                    );
                }

                if (!$this->params['show_progress']) {
                    continue;
                }

                $this->pulseCommet();
            }
        }

        return fclose($this->output_file);
    }

    public function __clone()
    {
        // create new file resource when cloning an object
        if (is_resource($this->output_file)) {
            $finfo = stream_get_meta_data($this->output_file);
            $this->output_file = fopen($finfo['uri'], 'w');
        }
    }
}
