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

namespace Tygh\Database;

use Closure;
use Throwable;
use Tygh\Backend\Database\IBackendMultiQuery;
use Tygh\Database;
use Tygh\Debugger;
use Tygh\Enum\MultiQueryTypes;
use Tygh\Exceptions\DatabaseException;
use Tygh\Exceptions\DatabaseMultiQueryException;
use Tygh\Registry;
use Tygh\Tygh;
use Tygh\Backend\Database\IBackend;

/**
 * Database connection class
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
 */
class Connection
{
    const MULTI_QUERY_TYPES = [
        MultiQueryTypes::ARR         => [
            'processor' => 'processResultToArray',
            'arg_count' => 0,
            'fallback'  => 'getArray',
        ],
        MultiQueryTypes::FIELD       => [
            'processor' => 'processResultToField',
            'arg_count' => 0,
            'fallback'  => 'getField',
        ],
        MultiQueryTypes::COLUMN      => [
            'processor' => 'processResultToColumn',
            'arg_count' => 0,
            'fallback'  => 'getColumn',
        ],
        MultiQueryTypes::ROW         => [
            'processor' => 'processResultToRow',
            'arg_count' => 0,
            'fallback'  => 'getRow',
        ],
        MultiQueryTypes::HASH        => [
            'processor' => 'processResultToHash',
            'arg_count' => 1,
            'fallback'  => 'getHash',
        ],
        MultiQueryTypes::SINGLE_HASH => [
            'processor' => 'processResultToSingleHash',
            'arg_count' => 1,
            'fallback'  => 'getSingleHash',
        ],
        MultiQueryTypes::MULTI_HASH  => [
            'processor' => 'processResultToMultiHash',
            'arg_count' => 1,
            'fallback'  => 'getMultiHash',
        ],
        MultiQueryTypes::QUERY       => [
            'processor' => 'processQuery',
            'arg_count' => 0,
            'fallback'  => 'query',
        ],
    ];


    /**
     * If set to true, next query will be executed without additional processing by hooks.
     *
     * @var bool
     */
    public $raw = false;

    /**
     * If set to true, the errors will be logged.
     *
     * @var bool
     */
    public $log_error = true;

    /**
     * Driver instance.
     *
     * @var IBackend
     */
    protected $driver;

    /**
     * Max reconnects count.
     *
     * @var int
     */
    protected $max_reconnect_count = 10;

    /**
     * Max retry query exec count.
     *
     * @var int
     */
    protected $max_retry_exec_count = 10;

    /**
     * List connection codes.
     *
     * @var array<int>
     */
    protected $lost_connection_codes = [
        2006,
        2013,
    ];

    /**
     * List connection codes.
     *
     * @var array<int>
     */
    protected $deadlock_error_codes = [
        1213
    ];

    /**
     * Database connections list.
     *
     * @var array<mixed>
     *
     * @deprecated since 4.3.6
     */
    protected $dbs = [];

    /**
     * Current database connection
     *
     * @var IBackend Active driver instance
     *
     * @deprecated since 4.3.6. Use $this->driver instead
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $db;

    /**
     * Current database connection name (main by default).
     *
     * @var string
     *
     * @deprecated since 4.3.6
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $dbc_name;

    /**
     * Table prefix for current connection.
     *
     * @var string
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected $table_prefix;

    /**
     * Table fields cache.
     *
     * @var array<string, array<mixed>>
     */
    protected $table_fields_cache = [];

    /**
     * A lock that we use to lock the database connection in blocking contexts.
     *
     * @var bool
     */
    protected $multi_query_lock = false;

    /**
     * Keeps track of the current transaction level to allow nested transactions.
     *
     * @var int
     */
    protected $transaction_count = 0;

    /**
     * Connection constructor
     *
     * @param IBackend $driver Driver instance
     */
    public function __construct(IBackend $driver = null)
    {
        if ($driver) {
            $this->driver = $driver;
        } else {
            $driver_class = Tygh::$app['db.driver.class'];
            $this->driver = new $driver_class();
        }

        /** @psalm-suppress DeprecatedProperty */
        $this->db = $this->driver; // FIXME
    }

    /**
     * Connects to the database server
     *
     * @param string                  $user     Username
     * @param string                  $passwd   Password
     * @param string                  $host     Host name
     * @param string                  $database Database name
     * @param array<array-key, mixed> $params   Connection params
     *
     * @return bool True on success, false otherwise
     *
     * @throws DatabaseException Wrong sql query or issues with execution.
     *
     * @psalm-suppress DeprecatedProperty
     */
    public function connect($user, $passwd, $host, $database, array $params = [])
    {
        // Default params
        $params = array_merge([
            'dbc_name'     => 'main', // @deprecated since 4.3.6
            'table_prefix' => '',
        ], $params);

        if (empty($this->dbs[$params['dbc_name']])) {
            if ($params['dbc_name'] !== 'main') { // Backward compatibility.
                /** @var class-string<\Tygh\Backend\Database\IBackend> $fqcn */
                $fqcn = Tygh::$app['db.driver.class'];
                $this->driver = new $fqcn();
            }
            $this->dbs[$params['dbc_name']] = [
                'db'       => $this->driver,
                'user'     => $user,
                'passwd'   => $passwd,
                'host'     => $host,
                'database' => $database,
                'params'   => $params,
            ];

            Debugger::checkpoint('Before database connect');
            $result = $this->driver->connect($user, $passwd, $host, $database);
            Debugger::checkpoint('After database connect');

            if (!$result) {
                $this->dbs[$params['dbc_name']] = null;
            }
        } else {
            $result = true;
        }

        if ($result) {
            $this->dbc_name = $params['dbc_name'];
            $this->db = &$this->dbs[$params['dbc_name']]['db'];
            $this->table_prefix = $params['table_prefix'];

            if (empty($params['names'])) {
                $params['names'] = 'utf8';
            }
            if (empty($params['group_concat_max_len'])) {
                $params['group_concat_max_len'] = 3000; // 3Kb
            }

            $this->db->initCommand(
                $this->quote(
                    'SET NAMES ?s, sql_mode = ?s, SESSION group_concat_max_len = ?i',
                    $params['names'],
                    '',
                    $params['group_concat_max_len']
                )
            );
        }

        return $result;
    }

    /**
     * Changes database for current or passed connection
     *
     * @param string                  $database Database name
     * @param array<array-key, mixed> $params   The database changing parameters.
     *
     * @return bool true if database was changed, false - otherwise
     *
     * @deprecated since 4.3.6
     */
    public function changeDb($database, array $params = [])
    {
        if (empty($params['dbc_name'])) {
            $params['dbc_name'] = 'main';
        }

        if (!empty($this->dbs[$params['dbc_name']])) {
            if ($this->dbs[$params['dbc_name']]['db']->changeDb($database)) {
                $this->dbc_name = $params['dbc_name'];
                $this->db = &$this->dbs[$params['dbc_name']]['db'];
                $this->table_prefix = !empty($params['table_prefix']) ? $params['table_prefix'] : $this->dbs[$params['dbc_name']]['params']['table_prefix'];

                return true;
            }

            if ($this->hasLostConnectionError() && $this->tryReconnect()) {
                return $this->changeDb($database, $params);
            }
        }

        return false;
    }

    /**
     * Execute query and format result as associative array with column names as keys.
     *
     * @param string $query   Unparsed query
     * @param mixed  ...$args Unlimited number of variables for placeholders
     *
     * @return array<array-key, array<array-key, mixed>> Structured data
     *
     * @throws DatabaseException Wrong sql query or issues with execution.
     */
    public function getArray($query, ...$args)
    {
        return $this->processResultToArray($this->query($query, ...$args));
    }

    /**
     * Process result as an array.
     *
     * @param mixed $db_result The raw database result
     *
     * @return array<array-key, array<array-key, mixed>>
     */
    private function processResultToArray($db_result)
    {
        if ($db_result) {
            $result = [];

            while ($arr = $this->driver->fetchRow($db_result)) {
                $result[] = $arr;
            }

            $this->driver->freeResult($db_result);
        }

        return !empty($result) ? $result : [];
    }

    /**
     * Execute query and format result as associative array with column names as keys and index as defined field.
     *
     * @param string $query   Unparsed query
     * @param string $field   Field for array index
     * @param mixed  ...$args Unlimited number of variables for placeholders
     *
     * @return array<array-key, array<array-key, mixed>> Structured data
     *
     * @throws DatabaseException Wrong sql query or issues with execution.
     */
    public function getHash($query, $field, ...$args)
    {
        return $this->processResultToHash($this->query($query, ...$args), $field);
    }

    /**
     * Process result as hashed array.
     *
     * @param mixed  $db_result The raw database result
     * @param string $field     The field to hash the array by
     *
     * @return array<array-key, array<array-key, mixed>>
     */
    private function processResultToHash($db_result, $field)
    {
        if ($db_result) {
            $result = [];

            while ($arr = $this->driver->fetchRow($db_result)) {
                if (!isset($arr[$field])) {
                    continue;
                }

                $result[$arr[$field]] = $arr;
            }

            $this->driver->freeResult($db_result);
        }

        return !empty($result) ? $result : [];
    }

    /**
     * Execute query and format result as associative array with column names as keys and then return first element of
     * this array
     *
     * @param string $query   Unparsed query
     * @param mixed  ...$args Unlimited number of variables for placeholders
     *
     * @return array<array-key, mixed> Structured data
     *
     * @throws DatabaseException Wrong sql query or issues with execution.
     */
    public function getRow($query, ...$args)
    {
        return $this->processResultToRow($this->query($query, ...$args));
    }

    /**
     * Process the result as a row.
     *
     * @param mixed $db_result The raw database result
     *
     * @return array<array-key, mixed> Structured data
     */
    private function processResultToRow($db_result)
    {
        if ($db_result) {
            $result = $this->driver->fetchRow($db_result);

            $this->driver->freeResult($db_result);
            return is_array($result) ? $result : [];
        }

        return [];
    }

    /**
     * Execute query and returns first field from the result.
     *
     * @param string $query   Unparsed query
     * @param mixed  ...$args Unlimited number of variables for placeholders
     *
     * @return string The retrieved field
     *
     * @throws DatabaseException Wrong sql query or issues with execution.
     */
    public function getField($query, ...$args)
    {
        return $this->processResultToField($this->query($query, ...$args));
    }

    /**
     * Process result as field, return the first field from the result
     *
     * @param mixed $db_result The raw database result
     *
     * @return string
     */
    private function processResultToField($db_result)
    {
        if ($db_result) {
            $result = $this->driver->fetchRow($db_result, 'indexed');
            $this->driver->freeResult($db_result);
        }

        return (isset($result) && is_array($result)) ? $result[0] : '';
    }

    /**
     * Execute query and format result as set of first column from all rows
     *
     * @param string $query   Unparsed query
     * @param mixed  ...$args Unlimited number of variables for placeholders
     *
     * @return array<mixed> Structured data
     *
     * @throws DatabaseException Wrong sql query or issues with execution.
     */
    public function getColumn($query, ...$args)
    {
        return $this->processResultToColumn($this->query($query, ...$args));
    }

    /**
     * Process result as column.
     *
     * @param mixed $db_result The raw database result
     *
     * @return array<mixed>
     */
    private function processResultToColumn($db_result)
    {
        $result = [];

        if ($db_result) {
            while ($arr = $this->driver->fetchRow($db_result, 'indexed')) {
                $result[] = $arr[0];
            }

            $this->driver->freeResult($db_result);
        }

        return $result;
    }

    /**
     * Execute query and format result as one of:
     *
     * ```
     * field => [field_2 => value],
     * field => [field_2 => row_data],
     * field => [[n] => row_data]
     * ```
     *
     * @param string                   $query   Unparsed query
     * @param array<array-key, string> $params  Array with 3 elements (field, field_2, value)
     * @param mixed                    ...$args Unlimited number of variables for placeholders
     *
     * @psalm-param array{0: string, 1: string, 2?: string} $params Array with 3 elements (field, field_2, value)
     *
     * @return array<array-key, array<array-key, array<array-key, mixed>>> Structured data
     *
     * @throws DatabaseException Wrong sql query or issues with execution.
     */
    public function getMultiHash($query, array $params, ...$args)
    {
        return $this->processResultToMultiHash($this->query($query, ...$args), $params);
    }

    /**
     * Process the result as a multi-hash.
     *
     * @param mixed                    $db_result The result returned from the database
     * @param array<array-key, string> $params    Array with 3 elements (field, field_2, value)
     *
     * @psalm-param array{0: string, 1: string, 2?: string} $params Array with 3 elements (field, field_2, value)
     *
     * @return array<array-key, array<array-key, array<array-key, mixed>>> Structured data
     */
    private function processResultToMultiHash($db_result, array $params)
    {
        /** @psalm-suppress PossiblyUndefinedArrayOffset */
        @list($field, $field_2, $value) = $params;

        if ($db_result) {
            while ($arr = $this->driver->fetchRow($db_result)) {
                if (!empty($field_2)) {
                    $result[$arr[$field]][$arr[$field_2]] = !empty($value) ? $arr[$value] : $arr;
                } else {
                    $result[$arr[$field]][] = $arr;
                }
            }

            $this->driver->freeResult($db_result);
        }

        return !empty($result) ? $result : [];
    }

    /**
     * Execute query and format result as key => value array.
     *
     * @param string                      $query   Unparsed query
     * @param array{0: string, 1: string} $params  Array with 2 elements (key, value)
     * @param mixed                       ...$args Unlimited number of variables for placeholders
     *
     * @return array<array-key, string> Structured data
     *
     * @throws DatabaseException Wrong sql query or issues with execution.
     */
    public function getSingleHash($query, array $params, ...$args)
    {
        return $this->processResultToSingleHash($this->query($query, ...$args), $params);
    }

    /**
     * Process the result as single hash
     *
     * @param mixed                       $db_result The raw database result
     * @param array{0: string, 1: string} $params    The parameters for hashing the array
     *
     * @return array<array-key, string> Structured data.
     */
    private function processResultToSingleHash($db_result, array $params)
    {
        @list($key, $value) = $params;

        if ($db_result) {
            $result = [];

            while ($arr = $this->driver->fetchRow($db_result)) {
                $result[$arr[$key]] = $arr[$value];
            }

            $this->driver->freeResult($db_result);
        }

        return !empty($result) ? $result : [];
    }

    /**
     * Prepare data and execute REPLACE INTO query to DB.
     *
     * If one of $data element is null function unsets it before query.
     *
     * @param string                  $table         Name of table that condition generated. Must be in SQL notation
     *                                               without placeholder.
     * @param array<array-key, mixed> $data          Array of key=>value data of fields need to insert/update
     * @param bool                    $is_multiple   If true, $data is treated as multiple values array
     * @param array<string>           $update_fields List of field usage to update
     *
     * @return int The amount of affected rows
     *
     * @throws DatabaseException Wrong sql query or issues with execution.
     */
    public function replaceInto($table, array $data, $is_multiple = false, array $update_fields = null)
    {
        if (empty($data)) {
            return 0;
        }

        if ($is_multiple) {
            $query = [];
            $fields = array_keys(reset($data));

            foreach ($fields as $field) {
                if ($update_fields !== null && !in_array($field, $update_fields, true)) {
                    continue;
                }

                $field = $this->field($field);
                $query[] = sprintf('`%s` = VALUES(`%s`)', $field, $field);
            }

            return $this->query('INSERT INTO ?:' . $table . ' ?m ON DUPLICATE KEY UPDATE ' . implode(', ', $query), $data);
        }

        $update_data = $data;

        if ($update_fields !== null) {
            $update_data = array_intersect_key($update_data, array_flip($update_fields));
        }

        return $this->query('INSERT INTO ?:' . $table . ' ?e ON DUPLICATE KEY UPDATE ?u', $data, $update_data);
    }

    /**
     * Creates new database
     *
     * @param string $database Database name
     *
     * @return bool True on success, false - otherwise
     *
     * @throws DatabaseException Wrong sql query or issues with execution.
     */
    public function createDb($database)
    {
        return (bool) $this->query('CREATE DATABASE IF NOT EXISTS `' . $this->driver->escape($database) . '`');
    }

    /**
     * Execute query
     *
     * @param string $query   Unparsed query
     * @param mixed  ...$args Unlimited number of variables for placeholders
     *
     * @return bool|int|\mysqli_result|\PDOStatement Mixed result set for "SELECT" statement / generated ID for an
     *                                               AUTO_INCREMENT field for insert statement / Affected rows count
     *                                               for DELETE/UPDATE statements
     *
     * @throws DatabaseException Whenever an exception occurs when e.g. having faulty SQL.
     */
    public function query($query, ...$args)
    {
        if ($this->multi_query_lock) {
            throw new DatabaseMultiQueryException('Query executed while in MultiQuery mode');
        }

        $this->raw = $this->raw ?: Database::$raw; // Backward compatibility

        if (!$this->raw) {
            fn_set_hook('db_query', $query);
        }

        $query = $this->process($query, $args, true);
        $result = false;

        if (!empty($query)) {
            if (!$this->raw) {
                fn_set_hook('db_query_process', $query);
            }

            if (defined('DEBUG_QUERIES')) {
                /** @psalm-suppress ForbiddenCode */
                // phpcs:ignore
                fn_print_r($query);
            }

            $time_start = microtime(true);

            $result = $this->execQuery($query);

            if ($result && !$this->hasError()) {
                $insert_id = $this->driver->insertId();
                Debugger::set_query($query, microtime(true) - $time_start);
                $result = $this->queryPostProcess($query, $result, $insert_id);
            }
        }

        $this->raw = false;
        Database::$raw = false; // Backward compatibility

        return $result;
    }

    /**
     * Process a query result to return an integer on insertion.
     *
     * @param mixed $db_result The raw database result.
     *
     * @return int|null
     */
    public function processQuery($db_result)
    {
        $result = null;

        if (!empty($insert_id = $this->driver->insertId())) {
            $result = $insert_id;
        }

        return $result;
    }


    /**
     * Process a query after having it executed.
     *
     * @param string $query     Parsed SQL
     * @param mixed  $result    The raw database result
     * @param int    $insert_id The insertion id (if any)
     *
     * @return mixed If an insert ID has been provided, that will be returned. Otherwise the affected row count, if any.
     */
    private function queryPostProcess($query, $result, $insert_id = 0)
    {
        // Provide a duplicate as this must not be changed by reference.
        $is_locked = $this->multi_query_lock;

        if (!$this->raw) {
            /**
             * Allows the handling of a query after executing one.
             *
             * @param string $query     The sql query.
             * @param mixed  $result    An instance of the result returned from the database.
             * @param bool   $is_locked A boolean indicating whether the database connection is currently locked for multi query.
             */
            fn_set_hook('db_query_executed', $query, $result, $is_locked);
        }

        if ($result === true) {
            // "true" will be returned for Update/Delete/Insert/Replace statements. "SELECT" returns MySQLi/PDO object
            $cmd = substr($query, 0, 6);

            if (!empty($insert_id)) {
                $result = $insert_id;
            } elseif ($cmd === 'UPDATE' || $cmd === 'DELETE' || $cmd === 'INSERT') {
                $result = $this->driver->affectedRows($result);
            }

            // Check if query updated data in the database and run cache handlers
            if (!empty($result) && preg_match('/^(UPDATE|INSERT INTO|REPLACE INTO|DELETE FROM) ' . $this->table_prefix . '(\w+) /', $query, $m)) {
                Registry::setChangedTables($m[2]);
            }

            // Clear table fields cache if table structure was changed
            if (!empty($result) && preg_match('/^(ALTER( IGNORE)? TABLE) ' . $this->table_prefix . '(\w+) /', $query, $m)) {
                $this->clearTableFieldsCache($m[3]);
            }
        }

        return $result;
    }

    /**
     * Sends multiple queries in a single go to reduce round trip times.
     *
     * @param array<array-key, mixed> $queries The queries to be sent simultaneously
     *
     * @return array<array-key, mixed> The multi-query result
     *
     * @throws DatabaseException Throws an error on invalid SQL and when the database is locked.
     */
    public function multiQuery(array $queries)
    {
        if ($this->multi_query_lock) {
            throw new DatabaseMultiQueryException('MultiQuery executed while in MultiQuery mode');
        }

        $this->raw = $this->raw ?: Database::$raw; // Backward compatibility

        if (!($this->driver instanceof IBackendMultiQuery)) {
            foreach ($queries as $offset => &$query) {
                list ($method) = $query;
                $method = self::MULTI_QUERY_TYPES[$method]['fallback'];
                $query = call_user_func_array([$this, $method], array_slice($query, 1));
            }
            unset($query);

            return $queries;
        }

        $processed_queries = [];
        foreach ($queries as $key => $query) {
            list($method, $sql) = $query;

            if (!$this->raw) {
                fn_set_hook('db_query', $query);
            }

            $extra_arguments = self::MULTI_QUERY_TYPES[$method]['arg_count'];
            $args = array_slice($query, 2 + $extra_arguments);

            $processed_queries[$key] = $this->process($sql, $args);

            if ($this->raw) {
                continue;
            }

            fn_set_hook('db_query_process', $processed_queries[$key]);
        }

        $result = [];
        if (!empty($processed_queries)) {
            if (defined('DEBUG_QUERIES')) {
                /** @psalm-suppress ForbiddenCode */
                // phpcs:ignore
                fn_print_r($processed_queries);
            }

            $time_start = microtime(true);

            if ($this->driver->multiQuery($processed_queries) && !$this->hasError()) {
                Debugger::set_query(implode(';', $processed_queries), microtime(true) - $time_start);

                $offsets = array_keys($processed_queries);
                reset($offsets);

                do {
                    $offset = current($offsets);
                    $db_result = $this->driver->getMultiQueryResult();
                    list($method) = $queries[$offset];

                    $processor = self::MULTI_QUERY_TYPES[$method]['processor'];
                    $extra_arguments = self::MULTI_QUERY_TYPES[$method]['arg_count'];
                    $processor_args = array_slice($queries[$offset], 2, $extra_arguments);

                    array_unshift($processor_args, $db_result);

                    $result[$offset] = call_user_func_array([$this, $processor], $processor_args);
                    $this->queryPostProcess($processed_queries[$offset], $db_result, $this->driver->insertId());

                    next($offsets);
                } while ($this->driver->hasMoreResults() && $this->driver->nextResult());

                $this->multi_query_lock = false;

                if (!$this->raw) {
                    /**
                     * Allows handling of queries after executing a multi-query.
                     *
                     * @param array $queries each query presented in the multi query.
                     * @param mixed $result  the result after executing all queries.
                     */
                    fn_set_hook('db_multi_query_executed', $processed_queries, $result);
                }
            } elseif ($this->hasError()) {
                $this->multi_query_lock = false;
                $error_code = $this->driver->errorCode();
                $error_message = $this->driver->error();

                // Lost connection, try to reconnect
                if ($this->hasLostConnectionError() && $this->tryReconnect()) {
                    $this->raw = true;
                    return $this->multiQuery($queries);
                }

                $this->throwError(implode(';', $processed_queries), (string) $error_code, $error_message);
            }
        }

        $this->raw = false;
        Database::$raw = false; // Backward compatibility

        return $result;
    }

    /**
     * Begin a transaction.
     *
     * @return void
     *
     * @throws DatabaseException When we were unable to start a transaction.
     */
    public function beginTransaction()
    {
        // We only start a transaction if we are *not* inside another transaction.
        if ($this->transaction_count === 0 && !$this->driver->beginTransaction()) {
            throw new DatabaseException('Unable to begin transaction');
        } else {
            // If we are inside another transaction, we create a savepoint.
            $this->query('SAVEPOINT trans' . $this->transaction_count);
        }

        $this->transaction_count++;
    }

    /**
     * Commit the current transaction. If we are running inside another transaction, we wait.
     *
     * @return void
     *
     * @throws DatabaseException When we are unable to commit a transaction.
     */
    public function commit()
    {
        if ($this->transaction_count === 0) {
            throw new DatabaseException('Attempting to commit when no transaction is active');
        }

        $this->transaction_count--;

        if ($this->transaction_count === 0 && !$this->driver->commit()) {
            throw new DatabaseException('Failed to commit transaction');
        }
    }

    /**
     * Rollback to the current transaction save point if there are multiple, otherwise wait.
     *
     * @return void
     *
     * @throws DatabaseException When we are unable to rollback a transaction.
     */
    public function rollback()
    {
        if ($this->transaction_count === 0) {
            throw new DatabaseException('Attempting to rollback when no transaction is active');
        }

        $this->transaction_count--;

        if ($this->transaction_count) {
            $this->query('ROLLBACK TO trans' . $this->transaction_count);
        } elseif (!$this->driver->rollback()) {
            throw new DatabaseException('Unable to rollback transaction');
        }
    }

    /**
     * Start a transaction.
     *
     * @param Closure $closure  The Closure to execute within the transaction context
     * @param int     $attempts The amount of times to attempt the transaction
     *
     * @return mixed The result returned from the closure
     *
     * @throws DatabaseException When the transaction fails.
     */
    public function transaction(Closure $closure, int $attempts = 1)
    {
        $result = null;

        for ($curr = 1; $curr <= $attempts; $curr++) {
            $this->beginTransaction();

            try {
                $result = $closure();
                $this->commit();
                return $result;
            } catch (Throwable $e) {
                $this->rollback();
            }
        }

        return $result;
    }

    /**
     * Parse query and replace placeholders with data.
     *
     * @param string $query   Unparsed query
     * @param mixed  ...$args Unlimited number of variables for placeholders
     *
     * @return string Parsed query
     *
     * @throws DatabaseException An error arose during execution.
     */
    public function quote($query, ...$args)
    {
        return $this->process($query, $args, false);
    }

    /**
     * Parse query and replace placeholders with data.
     *
     * @param string                  $pattern The pattern to process
     * @param array<array-key, mixed> $data    Data for placeholders
     * @param bool                    $replace Whether to replace the database placeholder
     *
     * @return string Parsed query
     *
     * @throws DatabaseException Thrown when the conditions can not be build.
     *
     * @psalm-suppress InvalidFalsableReturnType
     */
    public function process($pattern, array $data = [], $replace = true)
    {
        // Replace table prefixes
        if ($replace) {
            $pattern = str_replace('?:', $this->table_prefix, $pattern);
        }

        if (!empty($data) && preg_match_all('/\?(i|s|l|d|t|a|n|u|e|m|p|w|f)+/', $pattern, $m)) {
            $offset = 0;
            foreach ($m[0] as $k => $ph) {
                if ($ph === '?u' || $ph === '?e') {
                    $table_pattern = '\?\:';

                    if ($replace) {
                        $table_pattern = $this->table_prefix;
                    }

                    if (preg_match('/^(UPDATE|INSERT INTO|REPLACE INTO|DELETE FROM) ' . $table_pattern . '(\w+) /', $pattern, $m)) {
                        $data[$k] = $this->checkTableFields($data[$k], $m[2]);
                        if (empty($data[$k])) {
                            //TODO Throw DeveloperException
                            return false;
                        }
                    }
                }

                switch ($ph) {
                    // integer
                    case '?i':
                        $pattern = $this->strReplace($ph, $this->intVal($data[$k]), $pattern, $offset); // Trick to convert int's and longint's
                        break;

                    // string
                    case '?s':
                        $pattern = $this->strReplace($ph, '\'' . $this->driver->escape($data[$k]) . '\'', $pattern, $offset);
                        break;

                    // string for LIKE operator
                    case '?l':
                        $pattern = $this->strReplace($ph, '\'' . $this->driver->escape(str_replace('\\', '\\\\', $data[$k])) . '\'', $pattern, $offset);
                        break;

                    // float
                    case '?d':
                        if ((float) $data[$k] === INF) {
                            $data[$k] = PHP_INT_MAX;
                        }
                        $pattern = $this->strReplace($ph, sprintf('%01.2f', $data[$k]), $pattern, $offset);
                        break;

                    // float triple precision
                    case '?t':
                        if ((float) $data[$k] === INF) {
                            $data[$k] = PHP_INT_MAX;
                        }
                        $pattern = $this->strReplace($ph, sprintf('%01.3f', $data[$k]), $pattern, $offset);
                        break;

                    // array of string
                    // @FIXME: add trim
                    case '?a':
                        $data[$k] = is_array($data[$k]) ? $data[$k] : [$data[$k]];
                        if (!empty($data[$k])) {
                            $pattern = $this->strReplace($ph, implode(', ', $this->filterData($data[$k], true, true)), $pattern, $offset);
                        } else {
                            if (Debugger::isActive() || fn_is_development()) {
                                trigger_error('Empty array was passed into SQL statement IN()', E_USER_DEPRECATED);
                            }
                            $pattern = $this->strReplace($ph, 'NULL', $pattern, $offset);
                        }
                        break;

                    // array of integer
                    // FIXME: add trim
                    case '?n':
                        $data[$k] = is_array($data[$k]) ? $data[$k] : [$data[$k]];
                        $pattern = $this->strReplace($ph, !empty($data[$k])
                            ? implode(', ', array_map([$this, 'intVal'], $data[$k]))
                            : '\'\'', $pattern, $offset);
                        break;

                    // update
                    case '?u':
                        $clue = ($ph === '?u') ? ', ' : ' AND ';
                        $q = implode($clue, $this->filterData($data[$k], false));
                        $pattern = $this->strReplace($ph, $q, $pattern, $offset);

                        break;

                    //condition with and
                    case '?w':
                        $q = $this->buildConditions($data[$k]);
                        $pattern = $this->strReplace($ph, $q, $pattern, $offset);

                        break;

                    // insert
                    case '?e':
                        $filtered = $this->filterData($data[$k], true);
                        $pattern = $this->strReplace(
                            $ph,
                            '(' . implode(', ', array_keys($filtered)) . ') VALUES (' . implode(', ', array_values($filtered)) . ')',
                            $pattern,
                            $offset
                        );
                        break;

                    // insert multi
                    case '?m':
                        $values = [];
                        foreach ($data[$k] as $value) {
                            $filtered = $this->filterData($value, true);
                            $values[] = '(' . implode(', ', array_values($filtered)) . ')';
                        }
                        $pattern = $this->strReplace($ph, '(' . implode(', ', array_keys($filtered)) . ') VALUES ' . implode(', ', $values), $pattern, $offset);
                        break;

                    // field/table/database name
                    case '?f':
                        $pattern = $this->strReplace($ph, $this->field($data[$k]), $pattern, $offset);
                        break;

                    // prepared statement
                    case '?p':
                        /**
                         * @psalm-suppress PossiblyInvalidArgument
                         */
                        $pattern = $this->strReplace($ph, $this->tablePrefixReplace('?:', $this->table_prefix, $data[$k]), $pattern, $offset);
                        break;
                }
            }
        }

        return $pattern;
    }

    /**
     * Get column names from table
     *
     * @param string        $table_name Table name
     * @param array<string> $exclude    Optional array with fields to exclude from result
     * @param bool          $wrap       Optional parameter, if true, the fields will be enclosed in quotation marks
     *
     * @return array<string>|bool Columns array
     */
    public function getTableFields($table_name, array $exclude = [], $wrap = false)
    {
        if (!isset($this->table_fields_cache[$table_name])) {
            $this->table_fields_cache[$table_name] = $this->getColumn("SHOW COLUMNS FROM ?:$table_name");
        }

        $fields = $this->table_fields_cache[$table_name];

        if (!$fields) {
            return false;
        }

        if ($exclude) {
            $fields = array_diff($fields, $exclude);
        }

        if ($wrap) {
            foreach ($fields as &$v) {
                $v = "`$v`";
            }
        }

        return $fields;
    }

    /**
     * Check if passed data corresponds columns in table and remove unnecessary data.
     *
     * @param array<array-key, mixed> $data       Data for compare
     * @param string                  $table_name Table name
     *
     * @return array<string>|false Array with filtered data or false if fails.
     */
    public function checkTableFields(array $data, $table_name)
    {
        $fields = $this->getTableFields($table_name);

        if (is_array($fields)) {
            foreach (array_keys($data) as $k) {
                if (in_array((string) $k, $fields, true)) {
                    continue;
                }

                unset($data[$k]);
            }

            return $data;
        }

        return false;
    }

    /**
     * Get enum/set possible values in field of database.
     *
     * @param string $table_name Table name
     * @param string $field_name Field name
     *
     * @return array<string>|bool List of elements
     */
    public function getListElements($table_name, $field_name)
    {
        $column_info = $this->getRow('SHOW COLUMNS FROM ?:?p WHERE Field = ?s', $table_name, $field_name);

        if (
            !empty($column_info)
            && preg_match('/^(\w{3,4})\((.*)\)$/', $column_info['Type'], $matches)
            && in_array($matches[1], ['set', 'enum'])
            && !empty($matches[2])
        ) {
            $elements = [];

            foreach (explode(',', $matches[2]) as $element) {
                $elements[] = trim($element, '\'');
            }

            return $elements;
        }

        return false;
    }

    /**
     * Placeholder replace helper
     *
     * @param string $needle      String to replace
     * @param string $replacement Replacement
     * @param string $subject     String to search for replace
     * @param int    $offset      Offset to search from
     *
     * @return string The replaced fragment
     */
    protected function strReplace($needle, $replacement, $subject, &$offset)
    {
        $pos = strpos($subject, $needle, $offset);
        $offset = $pos + strlen($replacement);

        return substr($subject, 0, $pos) . $replacement . substr($subject, $pos + 2);
    }


    /**
     * Function finds $needle and replace it by $replacement only when $needle is not in quotes.
     *
     * For example in sting "SELECT ?:products ..." ?: will be replaced,
     * but in "... WHERE name = '?:products'" ?: will not be replaced by table_prefix.
     *
     * @param string $needle      String to replace
     * @param string $replacement Replacement
     * @param string $subject     String to search for replace
     *
     * @return string
     */
    protected function tablePrefixReplace($needle, $replacement, $subject)
    {
        // check that needle exists
        if (($pos = strpos($subject, $needle)) === false) {
            return $subject;
        }

        // if there are no ', replace all occurrences
        if (strpos($subject, "'") === false) {
            return str_replace($needle, $replacement ?? '', $subject);
        }

        $needle_len = strlen($needle);
        // find needle
        while (($pos = strpos($subject, $needle, $pos)) !== false) {
            // get the first part of string
            $tmp = substr($subject, 0, $pos);
            // remove escaped slashes
            $tmp = str_replace('\\\\', '', $tmp);
            // remove slashed single quotes
            $tmp = str_replace("\'", '', $tmp);
            // if we have even count of ', it means that we are not in the quotes
            if (substr_count($tmp, "'") % 2 === 0) {
                // so we should make a replacement
                $subject = substr_replace($subject, $replacement ?? '', $pos, $needle_len);
            } else {
                // we are in the quotes, skip replacement and move forward
                $pos += $needle_len;
            }
        }

        return $subject;
    }

    /**
     * Convert variable to int/longint type
     *
     * @param mixed $int Variable to convert
     *
     * @return int|float
     */
    protected function intVal($int)
    {
        if ($int === true) {
            $int = 1;
        }

        if ((float) $int === INF) {
            $int = PHP_INT_MAX;
        }

        if (PHP_INT_SIZE === 4 && $int > PHP_INT_MAX) {
            return (float) $int;
        }

        return (int) $int;
    }

    /**
     * Check if variable is valid database table name, table field or database name
     *
     * @param string $field Field to check
     *
     * @return mixed Passed variable if valid, empty string otherwise
     */
    protected function field($field)
    {
        /** @psalm-suppress RedundantCastGivenDocblockType */
        if (preg_match('/([\w]+)/', $field, $m) && $m[0] === (string) $field) {
            return $field;
        }

        return '';
    }

    /**
     * Display database error.
     *
     * @param resource $result Result, returned by database server
     * @param string   $query  SQL query, passed to server
     *
     * @return mixed False if no error, dies with error message otherwise
     *
     * @throws DatabaseException Throws an error with context.
     */
    protected function error($result, $query)
    {
        if (empty($result) && $this->hasError()) {
            $this->throwError($query, $this->driver->error(), $this->driver->errorCode());
        }

        return false;
    }

    /**
     * Filters data to form correct SQL string
     *
     * @param array<array-key, mixed> $data        Key-value array of fields and values to filter
     * @param bool                    $key_value   Return result as key-value array if set true or as array of
     *                                             field-value pairs if set to false
     * @param bool                    $force_quote If true, values will be wrapped with quotes regardless their type
     *
     * @return array<array-key, mixed> Filtered data
     */
    protected function filterData(array $data, $key_value, $force_quote = false)
    {
        $filtered = [];
        foreach ($data as $field => $value) {
            $value = $this->prepareValue($value, $force_quote);

            if ($key_value === true) {
                $filtered['`' . $this->field($field) . '`'] = $value;
            } else {
                $filtered[] = '`' . $this->field($field) . '` = ' . $value;
            }
        }

        return $filtered;
    }

    /**
     * Prepare value for use at query
     *
     * @param mixed $value       Value to prepare
     * @param bool  $force_quote If true, value will be wrapped with quotes regardless its type
     *
     * @return int|string The prepared value
     */
    protected function prepareValue($value, $force_quote = false)
    {
        if ($force_quote) {
            $value = "'" . $this->driver->escape($value) . "'";
        } elseif (is_int($value) || is_float($value)) {
            //ok
        } elseif (is_numeric($value) && $value === (string) ($value + 0)) {
            $value += 0;
        } elseif ($value === null) {
            $value = 'NULL';
        } else {
            $value = '\'' . $this->driver->escape($value) . '\'';
        }

        return $value;
    }

    /**
     * Gets last error code
     *
     * @return int last error code
     */
    protected function errorCode()
    {
        return $this->driver->errorCode();
    }

    /**
     * Tries to reconnect to current database.
     *
     * @return bool True on reconnect try
     */
    protected function tryReconnect()
    {
        $reconnects = 0;
        $this->driver->disconnect();
        $dbc_data = $this->dbs[$this->dbc_name];
        unset($this->dbs[$this->dbc_name]);

        while ($reconnects < $this->max_reconnect_count) {
            $reconnects++;

            if ($this->connect($dbc_data['user'], $dbc_data['passwd'], $dbc_data['host'], $dbc_data['database'], $dbc_data['params'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $query   SQL query
     * @param int    $counter Retry attempt counter
     *
     * @return mixed
     *
     * @throws \Tygh\Exceptions\DatabaseException DatabaseException.
     */
    protected function execQuery($query, $counter = 1)
    {
        $result = $this->driver->query($query);

        if (!$this->hasError()) {
            return $result;
        }

        if ($counter >= $this->max_retry_exec_count) {
            $error_code = $this->driver->errorCode();
            $error_message = $this->driver->error();

            $this->throwError($query, (string) $error_code, $error_message);

            return $result;
        }

        $counter++;

        if ($this->hasLostConnectionError() && $this->tryReconnect()) {
            return $this->execQuery($query, $counter);
        }

        if ($this->hasDeadlockError() && $this->transaction_count === 0) {
            usleep(random_int(10 * $counter ** 3, 30 * $counter ** 3));
            return $this->execQuery($query, $counter);
        }

        $error_code = $this->driver->errorCode();
        $error_message = $this->driver->error();

        $this->throwError($query, (string) $error_code, $error_message);

        return $result;
    }

    /**
     * Retrieve the current version of the driver.
     *
     * @return int The version of the server.
     */
    public function getServerVersion()
    {
        return $this->driver->getVersion();
    }

    /**
     * Build string conditions.
     *
     * ```php
     * [
     *  'field' => 'value',
     *  ['field', 'operator', 'value']
     * ]
     * ```
     *
     * Available operators: '=', '<', '>', '<=', '>=', '!=', '<>', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'NULL'.
     * Example:
     *
     * ```php
     * [
     *  'status' => 'A',
     *  ['install_datetime', '>=', strtotime('-1 day')],
     *  ['name', 'IN', ['name1', 'name2']],
     *  ['title', 'LIKE', '%sub_title%],
     *  ['parent_id', 'NULL', true],
     *  ['has_child', 'NULL', false],
     * ]
     * ```
     *
     * @param array<array-key, mixed> $data Array conditions to convert to SQL
     *
     * @return string The built conditions
     *
     * @throws DatabaseException When conditions can not be build.
     */
    public function buildConditions(array $data)
    {
        $available_operators = [
            '=', '<', '>', '<=', '>=', '!=', '<>', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'NULL'
        ];

        $conditions = [];

        foreach ($data as $key => $item) {
            if (is_string($key)) {
                $field = $key;
                $operator = '=';
                $value = $item;

                if ($value === null) {
                    $operator = 'NULL';
                    $value = true;
                } elseif (is_array($value)) {
                    $operator = 'IN';
                }
            } else {
                if (!is_array($item) || count($item) < 3) {
                    throw new DatabaseException('Unsupported condition');
                }

                $item = array_values($item);

                $field = $item[0];
                $operator = strtoupper($item[1]);
                $value = $item[2];
            }

            if (!in_array($operator, $available_operators, true)) {
                throw new DatabaseException("Unsupported operator: {$operator}");
            }

            if (strpos($field, '.') !== false) {
                $field_parts = explode('.', $field, 2);

                $table = $this->process($field_parts[0]);
                $field = $field_parts[1];

                $field = '`' . $this->field($table) . '`.`' . $this->field($field) . '`';
            } else {
                $field = '`' . $this->field($field) . '`';
            }

            if ($operator === 'NULL') {
                $value = (bool) $value;

                if ($value) {
                    $conditions[] = "{$field} IS NULL";
                } else {
                    $conditions[] = "{$field} IS NOT NULL";
                }
            } elseif ($operator === 'IN' || $operator === 'NOT IN') {
                $value = (array) $value;
                $force_quote = false;

                foreach ($value as $datum) {
                    if (is_string($datum)) {
                        $force_quote = true;
                        break;
                    }
                }

                $value = implode(', ', $this->filterData($value, true, $force_quote));
                $conditions[] = "{$field} {$operator} ({$value})";
            } elseif ($operator === 'LIKE' || $operator === 'NOT LIKE') {
                $value = $this->driver->escape(str_replace('\\', '\\\\', $value));
                $conditions[] = "{$field} {$operator} '{$value}'";
            } else {
                $value = $this->prepareValue($value);
                $conditions[] = "{$field} {$operator} {$value}";
            }
        }

        return implode(' AND ', $conditions);
    }

    /**
     * Clear table fields cache.
     *
     * @param string $table_name Table to clean fields cache for. Cache for all tables is cleaned if empty.
     */
    protected function clearTableFieldsCache($table_name = '')
    {
        if (empty($table_name)) {
            $this->table_fields_cache = [];
        } else {
            unset($this->table_fields_cache[$table_name]);
        }
    }

    /**
     * Check if the table exists in the database.
     *
     * @param string $table_name Table name
     * @param bool   $set_prefix Set prefix before check
     *
     * @return bool
     */
    public function hasTable($table_name, $set_prefix = true)
    {
        if ($set_prefix) {
            $table_name = $this->table_prefix . $table_name;
        }

        if ($this->getRow('SHOW TABLES LIKE ?s', $table_name)) {
            return true;
        }

        return false;
    }

    /**
     * Checks last query has error.
     *
     * @return bool
     */
    protected function hasError()
    {
        // phpcs:ignore
        return $this->errorCode() != 0;
    }

    /**
     * Checks last query has lost connection error.
     *
     * @return bool
     */
    protected function hasLostConnectionError()
    {
        return in_array($this->errorCode(), $this->lost_connection_codes);
    }

    /**
     * Checks last query has deadlock error.
     *
     * @return bool
     */
    protected function hasDeadlockError()
    {
        return in_array($this->errorCode(), $this->deadlock_error_codes);
    }

    /**
     * Throw database query error.
     *
     * @param string $query   SQL query
     * @param string $code    Error code
     * @param string $message Error message
     *
     * @throws DatabaseException Thrown on faulty SQL or when locked.
     */
    protected function throwError($query, $code, $message)
    {
        $error = [
            'message' => $message . ' <b>(' . $code . ')</b>',
            'query'   => $query,
        ];

        if (Registry::get('runtime.database.skip_errors') === true) {
            Registry::push('runtime.database.errors', $error);
        } else {
            if ($this->log_error) {
                Registry::set('runtime.database.skip_errors', true);

                // Log database errors
                fn_log_event('database', 'error', [
                    'error'     => $error,
                    // phpcs:ignore
                    'backtrace' => debug_backtrace()
                ]);

                Registry::set('runtime.database.skip_errors', false);
            }

            throw new DatabaseException($error['message'] . "<p>{$error['query']}</p>");
        }
    }

    /**
     * Returns last value of auto increment column.
     *
     * @return int
     */
    public function getInsertId()
    {
        return $this->driver->insertId();
    }

    /**
     * Prepares and executes REPLACE INTO query to DB with data was getting from selection from another table.
     *
     * @param string        $table           Table name to be replaced to
     * @param array<string> $fields          Fields list is returned by select
     * @param string        $select_query    Prepared select query for data which to be replaced to
     * @param array<string> $replaced_fields List of the fields which will be replaced. all fields will be replaced if
     *                                       empty
     *
     * @return int
     */
    public function replaceSelectionInto(
        $table,
        array $fields,
        $select_query,
        array $replaced_fields = []
    ) {
        if (empty($table) || empty($select_query) || empty($fields)) {
            return 0;
        }

        $replaced_fields = empty($replaced_fields) ? $fields : $replaced_fields;

        $fields = array_map(function ($field) {
            return $this->field($field);
        }, $fields);

        $query = [];

        foreach ($replaced_fields as $field) {
            $field = $this->field($field);
            $query[] = sprintf('`%s` = VALUES(`%s`)', $field, $field);
        }

        return $this->query(
            'INSERT INTO ?:?p (?p) ?p ON DUPLICATE KEY UPDATE ?p',
            $table,
            implode(', ', $fields),
            $select_query,
            implode(', ', $query)
        );
    }
}
