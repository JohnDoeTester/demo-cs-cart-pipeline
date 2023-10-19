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

/**
 * Class ADatabase implements abstract database backupper.
 *
 * @package Tygh\Tools\Backup
 */
abstract class ADatabaseBackupper
{
    /**
     * @var string $id Handler ID
     */
    protected $id = '';

    /**
     * @var array $config Software config
     */
    protected $config;

    /**
     * @var array $tables Tables
     */
    protected $tables = array();

    /**
     * @var array $params Backup parameters
     */
    protected $params = array();

    /**
     * ADatabase constructor.
     *
     * @param array $config Software config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Sets tables to backup.
     *
     * @param array $targets Tables
     *
     * @return $this
     */
    public function setTables(array $targets)
    {
        $this->tables = $targets;

        return $this;
    }

    /**
     * Gets total amount of steps for progress bar.
     *
     * @return int
     */
    public function estimateTotal()
    {
        return sizeof($this->tables) * ((int) $this->params['db_schema'] + (int) $this->params['db_data']);
    }

    /**
     * Gets backupper ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets backup parameters.
     *
     * @param array $params Parameters
     *
     * @return $this
     */
    public function setParameters(array $params)
    {
        $default_params = array(
            'db_schema'           => true,
            'db_data'             => true,
            'show_progress'       => true,
            'move_progress'       => true,
            'change_table_prefix' => array(),
        );

        $this->params = array_merge($default_params, $params);

        return $this;
    }

    /**
     * Performs backup.
     *
     * @return bool
     */
    public function makeBackup()
    {
        return false;
    }

    /**
     * Sets total amount of steps for progress bar.
     *
     * @param int $steps Amount of step
     *
     * @return bool
     */
    public function setProgressTotal($steps)
    {
        return fn_set_progress('step_scale', $steps);
    }

    /**
     * Sets progress title.
     *
     * @param string $title             Title to show
     * @param bool   $move_progress_bar Whether to move progress bar
     *
     * @return bool
     */
    public function setProgress($title, $move_progress_bar)
    {
        return fn_set_progress('echo', '<br />' . $title, $move_progress_bar);
    }

    /**
     * Provides Comet output.
     */
    public function pulseCommet()
    {
        return fn_echo(' .');
    }
}
