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

namespace Tygh\Backend\Database;

/**
 * Class Mysqli
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
 */
class Mysqli implements IBackend, IBackendMultiQuery
{
    /**
     * @var \mysqli
     */
    private $conn;

    /**
     * Connects to database server
     *
     * @param string $user     Username
     * @param string $passwd   Password
     * @param string $host     Server host name
     * @param string $database Database name
     *
     * @return bool True on success, false - otherwise
     */
    public function connect($user, $passwd, $host, $database)
    {
        if (!$host || !$user) {
            return false;
        }

        @list($host, $port) = explode(':', $host);

        mysqli_report(MYSQLI_REPORT_OFF);
        $this->conn = new \mysqli($host, $user, $passwd, $database, $port);

        if (!empty($this->conn) && empty($this->conn->connect_errno)) {
            return true;
        }

        return false;
    }

    /**
     * Disconnects from the database
     */
    public function disconnect()
    {
        $this->conn->close();
        $this->conn = null;
    }

    /**
     * Changes current database
     *
     * @param string $database Database name
     *
     * @return bool True on success, false - otherwise
     */
    public function changeDb($database)
    {
        if ($this->conn->select_db($database)) {
            return true;
        }

        return false;
    }

    /**
     * Queries database
     *
     * @param string $query SQL query
     *
     * @return mixed Query result
     */
    public function query($query)
    {
        return $this->conn->query($query);
    }

    /**
     * Fetches row from query result set
     *
     * @param mixed  $result Result set
     * @param string $type   Fetch type - 'assoc' or 'indexed'
     *
     * @return array<array-key, mixed> Fetched data
     */
    public function fetchRow($result, $type = 'assoc')
    {
        if ($type === 'assoc') {
            return $result->fetch_assoc();
        }

        return $result->fetch_row();
    }

    /**
     * Frees result set
     *
     * @param mixed $result Result set
     *
     * @return mixed
     */
    public function freeResult($result)
    {
        return $result->free();
    }

    /**
     * Return number of rows affected by query
     *
     * @param mixed $result Result set
     *
     * @return int Number of rows
     */
    public function affectedRows($result)
    {
        return $this->conn->affected_rows;
    }

    /**
     * Returns last value of auto increment column
     *
     * @return int Value
     */
    public function insertId()
    {
        return $this->conn->insert_id;
    }

    /**
     * Gets last error code
     *
     * @return int Error code
     */
    public function errorCode()
    {
        return $this->conn->errno;
    }

    /**
     * Gets last error description
     *
     * @return string Error description
     */
    public function error()
    {
        return $this->conn->error;
    }

    /**
     * Escapes value
     *
     * @param mixed $value Value to escape
     *
     * @return string Escaped value
     */
    public function escape($value)
    {
        return $this->conn->real_escape_string($value);
    }

    /**
     * Executes Command after when connecting to MySQL server
     *
     * @param string $command Command to execute
     */
    public function initCommand($command)
    {
        if (empty($command)) {
            return;
        }

        $this->query($command);
        $this->conn->options(MYSQLI_INIT_COMMAND, $command);
    }

    /**
     * @inheritDoc
     */
    public function multiQuery(array $multi_query)
    {
        return $this->conn->multi_query(
            is_array($multi_query) ? implode(';', $multi_query) : $multi_query
        );
    }

    /**
     * @inheritDoc
     */
    public function getMultiQueryResult()
    {
        return $this->conn->store_result();
    }

    /**
     * @inheritDoc
     */
    public function hasMoreResults()
    {
        return $this->conn->more_results();
    }

    /**
     * @inheritDoc
     */
    public function nextResult()
    {
        return $this->conn->next_result();
    }

    /**
     * @inheritDoc
     */
    public function getVersion()
    {
        return $this->conn->server_version;
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction()
    {
        return $this->conn->begin_transaction();
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        return $this->conn->commit();
    }

    /**
     * @inheritDoc
     */
    public function rollback()
    {
        return $this->conn->rollback();
    }
}
