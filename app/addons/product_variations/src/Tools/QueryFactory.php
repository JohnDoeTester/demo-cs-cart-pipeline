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


namespace Tygh\Addons\ProductVariations\Tools;


use Tygh\Database\Connection;

/**
 * Class QueryFactory
 *
 * @package Tygh\Addons\ProductVariations\Tools
 */
class QueryFactory
{
    protected $db_connection;

    public function __construct(Connection $db_connection)
    {
        $this->db_connection = $db_connection;
    }

    public function createQuery($table_id, array $conditions = [], array $fields = [], $table_alias = null)
    {
        if (is_array($table_id)) {
            $table_alias = reset($table_id);
            $table_id = key($table_id);
        }

        $query = new Query($this->db_connection, $table_id, $table_alias);

        if ($conditions) {
            $query->addConditions($conditions);
        }

        if ($fields) {
            $query->setFields($fields);
        }

        return $query;
    }
}