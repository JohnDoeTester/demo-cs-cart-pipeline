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

namespace Tygh\Addons\AdvancedImport\Readers;

interface IReader
{
    /**
     * IReader constructor.
     *
     * @param string $path    File path
     * @param array  $options Read options
     */
    public function __construct($path, array $options = array());

    /**
     * Gets header of an imported file.
     *
     * @return \Tygh\Common\OperationResult List of fields that imported items have
     */
    public function getSchema();

    /**
     * Gets content of an imported file.
     *
     * @param int|null   $count  Amount of lines to fetch, null to get all
     * @param array|null $schema Schema to map item properties to
     *
     * @return \Tygh\Common\OperationResult File contents
     */
    public function getContents($count = null, array $schema = null);

    /**
     * Gets the approximate amount of lines in the imported file.
     *
     * @return int
     */
    public function getApproximateLinesCount();

    /**
     * Provides file extension that is handled by the reader.
     *
     * @return string
     */
    public function getExtension();
}