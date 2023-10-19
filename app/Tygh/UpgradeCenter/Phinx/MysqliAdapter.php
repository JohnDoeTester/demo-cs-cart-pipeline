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

namespace Tygh\UpgradeCenter\Phinx;

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use Phinx\Config\Config;
use Phinx\Db\Action\AddColumn;
use Phinx\Db\Action\AddForeignKey;
use Phinx\Db\Action\AddIndex;
use Phinx\Db\Action\ChangeColumn;
use Phinx\Db\Action\ChangeComment;
use Phinx\Db\Action\ChangePrimaryKey;
use Phinx\Db\Action\DropForeignKey;
use Phinx\Db\Action\DropIndex;
use Phinx\Db\Action\DropTable;
use Phinx\Db\Action\RemoveColumn;
use Phinx\Db\Action\RenameColumn;
use Phinx\Db\Action\RenameTable;
use Phinx\Db\Adapter\AbstractAdapter;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\DirectActionInterface;
use Phinx\Db\Adapter\UnsupportedColumnTypeException;
use Phinx\Db\Table as DbTable;
use Phinx\Db\Table\Table;
use Phinx\Db\Table\Column;
use Phinx\Db\Table\ForeignKey;
use Phinx\Db\Table\Index;
use Phinx\Db\Util\AlterInstructions;
use Phinx\Migration\MigrationInterface;
use Phinx\Util\Literal;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Tygh\Exceptions\DatabaseException;

/**
 * Phinx MySQLi adapter.
 *
 * @package Tygh\UpgradeCenter\Phinx
 */
class MysqliAdapter extends AbstractAdapter implements AdapterInterface, DirectActionInterface
{
    /**
     * @var Mysqli
     */
    protected $connection;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $schemaTableName = 'phinxlog';

    /**
     * @var float
     */
    protected $commandStartTime;

    /**
     * @var bool[]
     */
    protected $signedColumnTypes = [
        self::PHINX_TYPE_INTEGER => true,
        self::PHINX_TYPE_TINY_INTEGER => true,
        self::PHINX_TYPE_SMALL_INTEGER => true,
        self::PHINX_TYPE_MEDIUM_INTEGER => true,
        self::PHINX_TYPE_BIG_INTEGER => true,
        self::PHINX_TYPE_FLOAT => true,
        self::PHINX_TYPE_DECIMAL => true,
        self::PHINX_TYPE_DOUBLE => true,
        self::PHINX_TYPE_BOOLEAN => true,
    ];

    /**
     * @var array<array-key, string>
     */
    protected $column_types = [
        'string',
        'char',
        'text',
        'integer',
        'biginteger',
        'float',
        'decimal',
        'datetime',
        'timestamp',
        'time',
        'date',
        'binary',
        'boolean',
        'uuid',
        // Geospatial data types
        'geometry',
        'point',
        'linestring',
        'polygon',
    ];

    /**
     * @var string[]
     */
    protected static $specificColumnTypes = [
        self::PHINX_TYPE_ENUM,
        self::PHINX_TYPE_SET,
        self::PHINX_TYPE_YEAR,
        self::PHINX_TYPE_JSON,
        self::PHINX_TYPE_BINARYUUID,
        self::PHINX_TYPE_TINYBLOB,
        self::PHINX_TYPE_MEDIUMBLOB,
        self::PHINX_TYPE_LONGBLOB,
        self::PHINX_TYPE_MEDIUM_INTEGER,
    ];

    public const TEXT_TINY    = 255;
    public const TEXT_SMALL   = 255; /* deprecated, alias of TEXT_TINY */
    public const TEXT_REGULAR = 65535;
    public const TEXT_MEDIUM  = 16777215;
    public const TEXT_LONG    = 4294967295;

    public const BLOB_TINY = 255;
    public const BLOB_SMALL = 255; /* deprecated, alias of BLOB_TINY */
    public const BLOB_REGULAR = 65535;
    public const BLOB_MEDIUM = 16777215;
    public const BLOB_LONG = 4294967295;

    public const INT_TINY = 255;
    public const INT_SMALL = 65535;
    public const INT_MEDIUM = 16777215;
    public const INT_REGULAR = 4294967295;
    public const INT_BIG = 18446744073709551615;

    public const BIT = 64;

    public const TYPE_YEAR = 'year';

    public const FIRST = 'FIRST';

    public function connect()
    {
        if ($this->connection !== null) {
            return;
        }

        $options = $this->getOptions();

        // Fail-safe defaults
        array_key_exists('host', $options) || ($options['host'] = ini_get('mysqli.default_host'));
        array_key_exists('user', $options) || ($options['user'] = ini_get('mysqli.default_user'));
        array_key_exists('pass', $options) || ($options['pass'] = ini_get('mysqli.default_pw'));
        array_key_exists('port', $options) || ($options['port'] = ini_get('mysqli.default_port'));
        array_key_exists('unix_socket', $options) || ($options['unix_socket'] = ini_get('mysqli.default_socket'));
        array_key_exists('name', $options) || ($options['name'] = '');

        $connection = new Mysqli(
            $options['host'], $options['user'], $options['pass'], $options['name'], $options['port'],
            $options['unix_socket']
        );

        if ($connection->connect_error) {
            throw new InvalidArgumentException(sprintf(
                'There was a problem connecting to the database: (%s) %s',
                $connection->errno,
                $connection->connect_error
            ));
        }

        if (isset($options['charset'])) {
            $connection->set_charset($options['charset']);
        }

        $this->connection = $connection;

        $this->setSessionSqlMode();

        if (!$this->hasSchemaTable()) {
            $this->createSchemaTable();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createSchemaTable()
    {
        try {
            $options = [
                'id' => false,
                'primary_key' => 'version',
            ];

            $table = new DbTable($this->getSchemaTableName(), $options, $this);
            $table->addColumn('version', 'biginteger')
                ->addColumn('migration_name', 'string', ['limit' => 100, 'default' => null, 'null' => true])
                ->addColumn('start_time', 'timestamp', ['default' => null, 'null' => true])
                ->addColumn('end_time', 'timestamp', ['default' => null, 'null' => true])
                ->addColumn('breakpoint', 'boolean', ['default' => false])
                ->save();
        } catch (Exception $exception) {
            throw new InvalidArgumentException(
                'There was a problem creating the schema table: ' . $exception->getMessage(),
                (int)$exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Sets "sql_mode" variable for current session.
     *
     * @return void
     */
    protected function setSessionSqlMode()
    {
        if ($this->connection === null) {
            return;
        }

        $global_sql_mode_list = explode(',', strtoupper($this->fetchRow('SELECT @@sql_mode')[0]));
        $session_sql_mode = implode(
            ',',
            array_filter($global_sql_mode_list, static function ($mode) {
                return $mode !== 'NO_ENGINE_SUBSTITUTION';
            })
        );
        $this->execute("SET sql_mode = '{$session_sql_mode}'");
    }

    public function disconnect()
    {
        $this->connection->close();
        $this->connection = null;
    }


    /**
     * Executes a SQL statement and returns the number of affected rows.
     *
     * @param string $sql SQL
     *
     * @return int
     */
    public function execute($sql)
    {
        if (!$this->getConnection()->query($sql)) {
            $this->onQueryError($sql);
        }

        return $this->connection->affected_rows;
    }

    /**
     * Executes a SQL statement and returns the result as an array.
     *
     * @param string $sql SQL
     *
     * @return array
     */
    public function query($sql)
    {
        if ($result = $this->getConnection()->query($sql)) {
            return $result;
        } else {
            $this->onQueryError($sql);
        }
    }

    /**
     * Executes a query and returns an array of rows.
     *
     * @param string $sql SQL
     *
     * @return array
     */
    public function fetchAll($sql)
    {
        $rows = [];

        if ($result = $this->getConnection()->query($sql)) {
            while ($row = $result->fetch_array(MYSQLI_BOTH)) {
                $rows[] = $row;
            }
            $result->free();
        } else {
            $this->onQueryError($sql);
        }


        return $rows;
    }

    /**
     * Executes a query and returns only one row as an array.
     *
     * @param string $sql SQL
     *
     * @return array
     */
    public function fetchRow($sql)
    {
        $row = [];
        if ($result = $this->getConnection()->query($sql)) {
            $row = $result->fetch_array(MYSQLI_BOTH);
            $result->free();
        } else {
            $this->onQueryError($sql);
        }

        return $row;
    }

    /**
     * @return Mysqli
     */
    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connect();
        }

        return $this->connection;
    }

    public function onQueryError($sql)
    {
        throw new DatabaseException($this->getConnection()->error, $this->getConnection()->errno);
    }

    public function renameColumn($tableName, $columnName, $newColumnName)
    {
        $instructions = $this->getRenameColumnInstructions($tableName, $columnName, $newColumnName);
        $this->executeAlterSteps($tableName, $instructions);
    }

    /**
     * Get the definition for a `DEFAULT` statement.
     *
     * @param mixed $default Default value
     * @param string|null $columnType column type added
     *
     * @return string
     */
    protected function getDefaultValueDefinition($default, $columnType = null)
    {
        // Ensure a defaults of CURRENT_TIMESTAMP(3) is not quoted.
        if (is_string($default) && strpos($default, 'CURRENT_TIMESTAMP') !== 0) {
            $default = $this->getConnection()->quote($default);
        } elseif (is_bool($default)) {
            $default = $this->castToBool($default);
        } elseif ($default !== null && $columnType === static::PHINX_TYPE_BOOLEAN) {
            $default = $this->castToBool((bool)$default);
        }

        return isset($default) ? " DEFAULT $default" : '';
    }

    /**
     * {@inheritdoc}
     */
    public function dropForeignKey($tableName, $columns, $constraint = null)
    {
        if ($constraint) {
            $instructions = $this->getDropForeignKeyInstructions($tableName, $constraint);
        } else {
            $instructions = $this->getDropForeignKeyByColumnsInstructions($tableName, $columns);
        }

        $this->executeAlterSteps($tableName, $instructions);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \RuntimeException
     */
    public function getVersionLog()
    {
        $result = [];

        switch ($this->options['version_order']) {
            case Config::VERSION_ORDER_CREATION_TIME:
                $orderBy = 'version ASC';
                break;
            case Config::VERSION_ORDER_EXECUTION_TIME:
                $orderBy = 'start_time ASC, version ASC';
                break;
            default:
                throw new RuntimeException('Invalid version_order configuration option');
        }

        // This will throw an exception if doing a --dry-run without any migrations as phinxlog
        // does not exist, so in that case, we can just expect to trivially return empty set
        try {
            $rows = $this->fetchAll(sprintf('SELECT * FROM %s ORDER BY %s', $this->quoteTableName($this->getSchemaTableName()), $orderBy));
        } catch (Exception $e) {
            if (!$this->isDryRunEnabled()) {
                throw $e;
            }
            $rows = [];
        }

        foreach ($rows as $version) {
            $result[$version['version']] = $version;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function resetAllBreakpoints()
    {
        return $this->execute(
            sprintf(
                'UPDATE %1$s SET %2$s = %3$s, %4$s = %4$s WHERE %2$s <> %3$s;',
                $this->quoteTableName($this->getSchemaTableName()),
                $this->quoteColumnName('breakpoint'),
                $this->castToBool(false),
                $this->quoteColumnName('start_time')
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hasTransactions()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rollbackTransaction()
    {
        $this->execute('ROLLBACK');
    }

    /**
     * @inheritDoc
     */
    public function insert(Table $table, $row)
    {
        $sql = sprintf(
            'INSERT INTO %s ',
            $this->quoteTableName($table->getName())
        );
        $columns = array_keys($row);
        $sql .= '(' . implode(', ', array_map([$this, 'quoteColumnName'], $columns)) . ')';

        foreach ($row as $column => $value) {
            if (is_bool($value)) {
                $row[$column] = $this->castToBool($value);
            }
        }

        if ($this->isDryRunEnabled()) {
            $sql .= ' VALUES (' . implode(', ', array_map([$this, 'quoteValue'], $row)) . ');';
            $this->output->writeln($sql);
        } else {
            $sql .= ' VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute(array_values($row));
        }
    }

    /**
     * @inheritDoc
     */
    public function bulkinsert(Table $table, $rows)
    {
        $sql = sprintf(
            'INSERT INTO %s ',
            $this->quoteTableName($table->getName())
        );
        $current = current($rows);
        $keys = array_keys($current);
        $sql .= '(' . implode(', ', array_map([$this, 'quoteColumnName'], $keys)) . ') VALUES ';

        if ($this->isDryRunEnabled()) {
            $values = array_map(function ($row) {
                return '(' . implode(', ', array_map([$this, 'quoteValue'], $row)) . ')';
            }, $rows);
            $sql .= implode(', ', $values) . ';';
            $this->output->writeln($sql);
        } else {
            $count_keys = count($keys);
            $query = '(' . implode(', ', array_fill(0, $count_keys, '?')) . ')';
            $count_vars = count($rows);
            $queries = array_fill(0, $count_vars, $query);
            $sql .= implode(',', $queries);
            $stmt = $this->getConnection()->prepare($sql);
            $vals = [];

            foreach ($rows as $row) {
                foreach ($row as $v) {
                    if (is_bool($v)) {
                        $vals[] = $this->castToBool($v);
                    } else {
                        $vals[] = $v;
                    }
                }
            }

            $stmt->execute($vals);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function quoteTableName($tableName)
    {
        return str_replace('.', '`.`', $this->quoteColumnName($tableName));
    }

    /**
     * {@inheritdoc}
     */
    public function quoteColumnName($columnName)
    {
        return '`' . str_replace('`', '``', $columnName) . '`';
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable($tableName)
    {
        if ($this->hasCreatedTable($tableName)) {
            return true;
        }

        if (strpos($tableName, '.') !== false) {
            [$schema, $table] = explode('.', $tableName);
            $exists = $this->hasTableWithSchema($schema, $table);
            // Only break here on success, because it is possible for table names to contain a dot.
            if ($exists) {
                return true;
            }
        }

        $options = $this->getOptions();

        return $this->hasTableWithSchema($options['name'], $tableName);
    }

    /**
     * @param string $schema The table schema
     * @param string $tableName The table name
     *
     * @return bool
     */
    protected function hasTableWithSchema($schema, $tableName)
    {
        $result = $this->fetchRow(sprintf(
            "SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s'",
            $schema,
            $tableName
        ));

        return !empty($result);
    }

    /**
     * @inheritDoc
     */
    public function createTable(Table $table, array $columns = [], array $indexes = [])
    {
        $defaultOptions = [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci',
        ];

        $options = array_merge(
            $defaultOptions,
            array_intersect_key($this->getOptions(), $defaultOptions),
            $table->getOptions()
        );

        // Add the default primary key
        if (!isset($options['id']) || (isset($options['id']) && $options['id'] === true)) {
            $options['id'] = 'id';
        }

        if (isset($options['id']) && is_string($options['id'])) {
            // Handle id => "field_name" to support AUTO_INCREMENT
            $column = new Column();
            $column->setName($options['id'])
                ->setType('integer')
                ->setSigned($options['signed'] ?? true)
                ->setIdentity(true);

            if (isset($options['limit'])) {
                $column->setLimit($options['limit']);
            }

            array_unshift($columns, $column);
            if (isset($options['primary_key']) && (array)$options['id'] !== (array)$options['primary_key']) {
                throw new InvalidArgumentException('You cannot enable an auto incrementing ID field and a primary key');
            }
            $options['primary_key'] = $options['id'];
        }

        // open: process table options like collation etc

        // process table engine (default to InnoDB)
        $optionsStr = 'ENGINE = InnoDB';
        if (isset($options['engine'])) {
            $optionsStr = sprintf('ENGINE = %s', $options['engine']);
        }

        // process table collation
        if (isset($options['collation'])) {
            $charset = explode('_', $options['collation']);
            $optionsStr .= sprintf(' CHARACTER SET %s', $charset[0]);
            $optionsStr .= sprintf(' COLLATE %s', $options['collation']);
        }

        // set the table comment
        if (isset($options['comment'])) {
            $optionsStr .= sprintf(' COMMENT=%s ', $this->getConnection()->quote($options['comment']));
        }

        // set the table row format
        if (isset($options['row_format'])) {
            $optionsStr .= sprintf(' ROW_FORMAT=%s ', $options['row_format']);
        }

        $sql = 'CREATE TABLE ';
        $sql .= $this->quoteTableName($table->getName()) . ' (';
        foreach ($columns as $column) {
            $sql .= $this->quoteColumnName($column->getName()) . ' ' . $this->getColumnSqlDefinition($column) . ', ';
        }

        // set the primary key(s)
        if (isset($options['primary_key'])) {
            $sql = rtrim($sql);
            $sql .= ' PRIMARY KEY (';
            if (is_string($options['primary_key'])) { // handle primary_key => 'id'
                $sql .= $this->quoteColumnName($options['primary_key']);
            } elseif (is_array($options['primary_key'])) { // handle primary_key => array('tag_id', 'resource_id')
                $sql .= implode(',', array_map([$this, 'quoteColumnName'], $options['primary_key']));
            }
            $sql .= ')';
        } else {
            $sql = substr(rtrim($sql), 0, -1); // no primary keys
        }

        // set the indexes
        foreach ($indexes as $index) {
            $sql .= ', ' . $this->getIndexSqlDefinition($index);
        }

        $sql .= ') ' . $optionsStr;
        $sql = rtrim($sql);

        // execute the sql
        $this->execute($sql);

        $this->addCreatedTable($table->getName());
    }

    /**
     * Gets the MySQL Column Definition for a Column object.
     *
     * @param Column $column Column
     *
     * @return string
     */
    protected function getColumnSqlDefinition(Column $column)
    {
        if ($column->getType() instanceof Literal) {
            $def = (string)$column->getType();
        } else {
            $sqlType = $this->getSqlType($column->getType(), $column->getLimit());
            $def = strtoupper($sqlType['name']);
        }
        if ($column->getPrecision() && $column->getScale()) {
            $def .= '(' . $column->getPrecision() . ',' . $column->getScale() . ')';
        } elseif (isset($sqlType['limit'])) {
            $def .= '(' . $sqlType['limit'] . ')';
        }

        $values = $column->getValues();
        if ($values && is_array($values)) {
            $def .= '(' . implode(', ', array_map(function ($value) {
                    // we special case NULL as it's not actually allowed an enum value,
                    // and we want MySQL to issue an error on the create statement, but
                    // quote coerces it to an empty string, which will not error
                    return $value === null ? 'NULL' : $this->getConnection()->quote($value);
                }, $values)) . ')';
        }

        $def .= $column->getEncoding() ? ' CHARACTER SET ' . $column->getEncoding() : '';
        $def .= $column->getCollation() ? ' COLLATE ' . $column->getCollation() : '';
        $def .= !$column->isSigned() && isset($this->signedColumnTypes[$column->getType()]) ? ' unsigned' : '';
        $def .= $column->isNull() ? ' NULL' : ' NOT NULL';

        $def .= $column->isIdentity() ? ' AUTO_INCREMENT' : '';
        $def .= $this->getDefaultValueDefinition($column->getDefault(), $column->getType());

        if ($column->getComment()) {
            $def .= ' COMMENT ' . $this->getConnection()->quote($column->getComment());
        }

        if ($column->getUpdate()) {
            $def .= ' ON UPDATE ' . $column->getUpdate();
        }

        return $def;
    }

    /**
     * Gets the MySQL Index Definition for an Index object.
     *
     * @param \Phinx\Db\Table\Index $index Index
     *
     * @return string
     */
    protected function getIndexSqlDefinition(Index $index)
    {
        $def = '';
        $limit = '';

        if ($index->getType() === Index::UNIQUE) {
            $def .= ' UNIQUE';
        }

        if ($index->getType() === Index::FULLTEXT) {
            $def .= ' FULLTEXT';
        }

        $def .= ' KEY';

        if (is_string($index->getName())) {
            $def .= ' `' . $index->getName() . '`';
        }

        $columnNames = $index->getColumns();
        $order = $index->getOrder() ?? [];
        $columnNames = array_map(function ($columnName) use ($order) {
            $ret = '`' . $columnName . '`';
            if (isset($order[$columnName])) {
                $ret .= ' ' . $order[$columnName];
            }

            return $ret;
        }, $columnNames);

        if (!is_array($index->getLimit())) {
            if ($index->getLimit()) {
                $limit = '(' . $index->getLimit() . ')';
            }
            $def .= ' (' . implode(',', $columnNames) . $limit . ')';
        } else {
            $columns = $index->getColumns();
            $limits = $index->getLimit();
            $def .= ' (';
            foreach ($columns as $column) {
                $limit = !isset($limits[$column]) || $limits[$column] <= 0 ? '' : '(' . $limits[$column] . ')';
                $columnSort = isset($order[$column]) ?? '';
                $def .= '`' . $column . '`' . $limit . ' ' . $columnSort . ', ';
            }
            $def = rtrim($def, ', ');
            $def .= ' )';
        }

        return $def;
    }

    /**
     * Gets the MySQL Foreign Key Definition for an ForeignKey object.
     *
     * @param ForeignKey $foreignKey
     * @return string
     */
    protected function getForeignKeySqlDefinition(ForeignKey $foreignKey)
    {
        $def = '';
        if ($foreignKey->getConstraint()) {
            $def .= ' CONSTRAINT ' . $this->quoteColumnName($foreignKey->getConstraint());
        }
        $columnNames = [];
        foreach ($foreignKey->getColumns() as $column) {
            $columnNames[] = $this->quoteColumnName($column);
        }
        $def .= ' FOREIGN KEY (' . implode(',', $columnNames) . ')';
        $refColumnNames = [];
        foreach ($foreignKey->getReferencedColumns() as $column) {
            $refColumnNames[] = $this->quoteColumnName($column);
        }
        $def .= ' REFERENCES ' . $this->quoteTableName($foreignKey->getReferencedTable()->getName()) . ' (' . implode(',', $refColumnNames) . ')';
        if ($foreignKey->getOnDelete()) {
            $def .= ' ON DELETE ' . $foreignKey->getOnDelete();
        }
        if ($foreignKey->getOnUpdate()) {
            $def .= ' ON UPDATE ' . $foreignKey->getOnUpdate();
        }

        return $def;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns($tableName)
    {
        $columns = [];
        $rows = $this->fetchAll(sprintf('SHOW COLUMNS FROM %s', $this->quoteTableName($tableName)));
        foreach ($rows as $columnInfo) {
            $phinxType = $this->getPhinxType($columnInfo['Type']);

            $column = new Column();
            $column->setName($columnInfo['Field'])
                ->setNull($columnInfo['Null'] !== 'NO')
                ->setDefault($columnInfo['Default'])
                ->setType($phinxType['name'])
                ->setSigned(strpos($columnInfo['Type'], 'unsigned') === false)
                ->setLimit($phinxType['limit'])
                ->setScale($phinxType['scale']);

            if ($columnInfo['Extra'] === 'auto_increment') {
                $column->setIdentity(true);
            }

            if (isset($phinxType['values'])) {
                $column->setValues($phinxType['values']);
            }

            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * Returns Phinx type by SQL type
     *
     * @internal param string $sqlType SQL type
     * @param string $sqlTypeDef SQL Type definition
     * @throws \Phinx\Db\Adapter\UnsupportedColumnTypeException
     *
     * @return array Phinx type
     */
    public function getPhinxType($sqlTypeDef)
    {
        $matches = [];
        if (!preg_match('/^([\w]+)(\(([\d]+)*(,([\d]+))*\))*(.+)*$/', $sqlTypeDef, $matches)) {
            throw new UnsupportedColumnTypeException('Column type "' . $sqlTypeDef . '" is not supported by MySQL.');
        }

        $limit = null;
        $scale = null;
        $type = $matches[1];
        if (count($matches) > 2) {
            $limit = $matches[3] ? (int)$matches[3] : null;
        }
        if (count($matches) > 4) {
            $scale = (int)$matches[5];
        }
        if ($type === 'tinyint' && $limit === 1) {
            $type = static::PHINX_TYPE_BOOLEAN;
            $limit = null;
        }
        switch ($type) {
            case 'varchar':
                $type = static::PHINX_TYPE_STRING;
                if ($limit === 255) {
                    $limit = null;
                }
                break;
            case 'char':
                $type = static::PHINX_TYPE_CHAR;
                if ($limit === 255) {
                    $limit = null;
                }
                if ($limit === 36) {
                    $type = static::PHINX_TYPE_UUID;
                }
                break;
            case 'tinyint':
                $type = static::PHINX_TYPE_TINY_INTEGER;
                $limit = static::INT_TINY;
                break;
            case 'smallint':
                $type = static::PHINX_TYPE_SMALL_INTEGER;
                $limit = static::INT_SMALL;
                break;
            case 'mediumint':
                $type = static::PHINX_TYPE_MEDIUM_INTEGER;
                $limit = static::INT_MEDIUM;
                break;
            case 'int':
                $type = static::PHINX_TYPE_INTEGER;
                if ($limit === 11) {
                    $limit = null;
                }
                break;
            case 'bigint':
                if ($limit === 20) {
                    $limit = null;
                }
                $type = static::PHINX_TYPE_BIG_INTEGER;
                break;
            case 'bit':
                $type = static::PHINX_TYPE_BIT;
                if ($limit === 64) {
                    $limit = null;
                }
                break;
            case 'blob':
                $type = static::PHINX_TYPE_BLOB;
                $limit = static::BLOB_REGULAR;
                break;
            case 'tinyblob':
                $type = static::PHINX_TYPE_TINYBLOB;
                $limit = static::BLOB_TINY;
                break;
            case 'mediumblob':
                $type = static::PHINX_TYPE_MEDIUMBLOB;
                $limit = static::BLOB_MEDIUM;
                break;
            case 'longblob':
                $type = static::PHINX_TYPE_LONGBLOB;
                $limit = static::BLOB_LONG;
                break;
            case 'tinytext':
                $type = static::PHINX_TYPE_TEXT;
                $limit = static::TEXT_TINY;
                break;
            case 'mediumtext':
                $type = static::PHINX_TYPE_TEXT;
                $limit = static::TEXT_MEDIUM;
                break;
            case 'longtext':
                $type = static::PHINX_TYPE_TEXT;
                $limit = static::TEXT_LONG;
                break;
            case 'binary':
                if ($limit === null) {
                    $limit = 255;
                }

                if ($limit > 255) {
                    $type = static::PHINX_TYPE_BLOB;
                    break;
                }

                if ($limit === 16) {
                    $type = static::PHINX_TYPE_BINARYUUID;
                }
                break;
        }

        try {
            // Call this to check if parsed type is supported.
            $this->getSqlType($type, $limit);
        } catch (UnsupportedColumnTypeException $e) {
            $type = Literal::from($type);
        }

        $phinxType = [
            'name' => $type,
            'limit' => $limit,
            'scale' => $scale,
        ];

        if ($type === static::PHINX_TYPE_ENUM || $type === static::PHINX_TYPE_SET) {
            $values = trim($matches[6], '()');
            $phinxType['values'] = [];
            $opened = false;
            $escaped = false;
            $wasEscaped = false;
            $value = '';
            $valuesLength = strlen($values);
            for ($i = 0; $i < $valuesLength; $i++) {
                $char = $values[$i];
                if ($char === "'" && !$opened) {
                    $opened = true;
                } elseif (
                    !$escaped
                    && ($i + 1) < $valuesLength
                    && (
                        $char === "'" && $values[$i + 1] === "'"
                        || $char === '\\' && $values[$i + 1] === '\\'
                    )
                ) {
                    $escaped = true;
                } elseif ($char === "'" && $opened && !$escaped) {
                    $phinxType['values'][] = $value;
                    $value = '';
                    $opened = false;
                } elseif (($char === "'" || $char === '\\') && $opened && $escaped) {
                    $value .= $char;
                    $escaped = false;
                    $wasEscaped = true;
                } elseif ($opened) {
                    if ($values[$i - 1] === '\\' && !$wasEscaped) {
                        if ($char === 'n') {
                            $char = "\n";
                        } elseif ($char === 'r') {
                            $char = "\r";
                        } elseif ($char === 't') {
                            $char = "\t";
                        }
                        if ($values[$i] !== $char) {
                            $value = substr($value, 0, strlen($value) - 1);
                        }
                    }
                    $value .= $char;
                    $wasEscaped = false;
                }
            }
        }

        return $phinxType;
    }

    /**
     * @inheritDoc
     */
    public function hasIndexByName($tableName, $indexName)
    {
        $indexes = $this->getIndexes($tableName);

        foreach ($indexes as $name => $index) {
            if ($name === $indexName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get an array of indexes from a particular table.
     *
     * @param string $tableName Table name
     *
     * @return array
     */
    protected function getIndexes($tableName)
    {
        $indexes = [];
        $rows = $this->fetchAll(sprintf('SHOW INDEXES FROM %s', $this->quoteTableName($tableName)));
        foreach ($rows as $row) {
            if (!isset($indexes[$row['Key_name']])) {
                $indexes[$row['Key_name']] = ['columns' => []];
            }
            $indexes[$row['Key_name']]['columns'][] = strtolower($row['Column_name']);
        }

        return $indexes;
    }

    /**
     * {@inheritdoc}
     */
    public function hasForeignKey($tableName, $columns, $constraint = null)
    {
        if (is_string($columns)) {
            $columns = [$columns]; // str to array
        }
        $foreignKeys = $this->getForeignKeys($tableName);
        if ($constraint) {
            if (isset($foreignKeys[$constraint])) {
                return !empty($foreignKeys[$constraint]);
            }

            return false;
        }

        foreach ($foreignKeys as $key) {
            if ($columns == $key['columns']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get an array of foreign keys from a particular table.
     *
     * @param string $tableName Table Name
     *
     * @return array
     */
    protected function getForeignKeys($tableName)
    {
        if (strpos($tableName, '.') !== false) {
            [$schema, $tableName] = explode('.', $tableName);
        }

        $foreignKeys = [];
        $rows = $this->fetchAll(sprintf(
            "SELECT
              CONSTRAINT_NAME,
              CONCAT(TABLE_SCHEMA, '.', TABLE_NAME) AS TABLE_NAME,
              COLUMN_NAME,
              CONCAT(REFERENCED_TABLE_SCHEMA, '.', REFERENCED_TABLE_NAME) AS REFERENCED_TABLE_NAME,
              REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME IS NOT NULL
              AND TABLE_SCHEMA = %s
              AND TABLE_NAME = '%s'
            ORDER BY POSITION_IN_UNIQUE_CONSTRAINT",
            empty($schema) ? 'DATABASE()' : "'$schema'",
            $tableName
        ));
        foreach ($rows as $row) {
            $foreignKeys[$row['CONSTRAINT_NAME']]['table'] = $row['TABLE_NAME'];
            $foreignKeys[$row['CONSTRAINT_NAME']]['columns'][] = $row['COLUMN_NAME'];
            $foreignKeys[$row['CONSTRAINT_NAME']]['referenced_table'] = $row['REFERENCED_TABLE_NAME'];
            $foreignKeys[$row['CONSTRAINT_NAME']]['referenced_columns'][] = $row['REFERENCED_COLUMN_NAME'];
        }

        return $foreignKeys;
    }

    /**
     * {@inheritdoc}
     */
    public function getSqlType($type, $limit = null)
    {
        switch ($type) {
            case static::PHINX_TYPE_FLOAT:
            case static::PHINX_TYPE_DOUBLE:
            case static::PHINX_TYPE_DECIMAL:
            case static::PHINX_TYPE_DATE:
            case static::PHINX_TYPE_ENUM:
            case static::PHINX_TYPE_SET:
            case static::PHINX_TYPE_JSON:
                // Geospatial database types
            case static::PHINX_TYPE_GEOMETRY:
            case static::PHINX_TYPE_POINT:
            case static::PHINX_TYPE_LINESTRING:
            case static::PHINX_TYPE_POLYGON:
                return ['name' => $type];
            case static::PHINX_TYPE_DATETIME:
            case static::PHINX_TYPE_TIMESTAMP:
            case static::PHINX_TYPE_TIME:
                return ['name' => $type, 'limit' => $limit];
            case static::PHINX_TYPE_STRING:
                return ['name' => 'varchar', 'limit' => $limit ?: 255];
            case static::PHINX_TYPE_CHAR:
                return ['name' => 'char', 'limit' => $limit ?: 255];
            case static::PHINX_TYPE_TEXT:
                if ($limit) {
                    $sizes = [
                        // Order matters! Size must always be tested from longest to shortest!
                        'longtext' => static::TEXT_LONG,
                        'mediumtext' => static::TEXT_MEDIUM,
                        'text' => static::TEXT_REGULAR,
                        'tinytext' => static::TEXT_SMALL,
                    ];
                    foreach ($sizes as $name => $length) {
                        if ($limit >= $length) {
                            return ['name' => $name];
                        }
                    }
                }

                return ['name' => 'text'];
            case static::PHINX_TYPE_BINARY:
                if ($limit === null) {
                    $limit = 255;
                }

                if ($limit > 255) {
                    return $this->getSqlType(static::PHINX_TYPE_BLOB, $limit);
                }

                return ['name' => 'binary', 'limit' => $limit];
            case static::PHINX_TYPE_BINARYUUID:
                return ['name' => 'binary', 'limit' => 16];
            case static::PHINX_TYPE_VARBINARY:
                if ($limit === null) {
                    $limit = 255;
                }

                if ($limit > 255) {
                    return $this->getSqlType(static::PHINX_TYPE_BLOB, $limit);
                }

                return ['name' => 'varbinary', 'limit' => $limit];
            case static::PHINX_TYPE_BLOB:
                if ($limit !== null) {
                    // Rework this part as the choosen types were always UNDER the required length
                    $sizes = [
                        'tinyblob' => static::BLOB_SMALL,
                        'blob' => static::BLOB_REGULAR,
                        'mediumblob' => static::BLOB_MEDIUM,
                    ];

                    foreach ($sizes as $name => $length) {
                        if ($limit <= $length) {
                            return ['name' => $name];
                        }
                    }

                    // For more length requirement, the longblob is used
                    return ['name' => 'longblob'];
                }

                // If not limit is provided, fallback on blob
                return ['name' => 'blob'];
            case static::PHINX_TYPE_TINYBLOB:
                // Automatically reprocess blob type to ensure that correct blob subtype is selected given provided limit
                return $this->getSqlType(static::PHINX_TYPE_BLOB, $limit ?: static::BLOB_TINY);
            case static::PHINX_TYPE_MEDIUMBLOB:
                // Automatically reprocess blob type to ensure that correct blob subtype is selected given provided limit
                return $this->getSqlType(static::PHINX_TYPE_BLOB, $limit ?: static::BLOB_MEDIUM);
            case static::PHINX_TYPE_LONGBLOB:
                // Automatically reprocess blob type to ensure that correct blob subtype is selected given provided limit
                return $this->getSqlType(static::PHINX_TYPE_BLOB, $limit ?: static::BLOB_LONG);
            case static::PHINX_TYPE_BIT:
                return ['name' => 'bit', 'limit' => $limit ?: 64];
            case static::PHINX_TYPE_BIG_INTEGER:
                return ['name' => 'bigint', 'limit' => $limit ?: 20];
            case static::PHINX_TYPE_MEDIUM_INTEGER:
                return ['name' => 'mediumint', 'limit' => $limit ?: 8];
            case static::PHINX_TYPE_SMALL_INTEGER:
                return ['name' => 'smallint', 'limit' => $limit ?: 6];
            case static::PHINX_TYPE_TINY_INTEGER:
                return ['name' => 'tinyint', 'limit' => $limit ?: 4];
            case static::PHINX_TYPE_INTEGER:
                if ($limit && $limit >= static::INT_TINY) {
                    $sizes = [
                        // Order matters! Size must always be tested from longest to shortest!
                        'bigint' => static::INT_BIG,
                        'int' => static::INT_REGULAR,
                        'mediumint' => static::INT_MEDIUM,
                        'smallint' => static::INT_SMALL,
                        'tinyint' => static::INT_TINY,
                    ];
                    $limits = [
                        'tinyint' => 4,
                        'smallint' => 6,
                        'mediumint' => 8,
                        'int' => 11,
                        'bigint' => 20,
                    ];
                    foreach ($sizes as $name => $length) {
                        if ($limit >= $length) {
                            $def = ['name' => $name];
                            if (isset($limits[$name])) {
                                $def['limit'] = $limits[$name];
                            }

                            return $def;
                        }
                    }
                } elseif (!$limit) {
                    $limit = 11;
                }

                return ['name' => 'int', 'limit' => $limit];
            case static::PHINX_TYPE_BOOLEAN:
                return ['name' => 'tinyint', 'limit' => 1];
            case static::PHINX_TYPE_UUID:
                return ['name' => 'char', 'limit' => 36];
            case static::PHINX_TYPE_YEAR:
                if (!$limit || in_array($limit, [2, 4])) {
                    $limit = 4;
                }

                return ['name' => 'year', 'limit' => $limit];
            default:
                throw new UnsupportedColumnTypeException('Column type "' . $type . '" is not supported by MySQL.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasDatabase($name)
    {
        $rows = $this->fetchAll(
            sprintf(
                'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = \'%s\'',
                $name
            )
        );

        foreach ($rows as $row) {
            if (!empty($row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function dropDatabase($name)
    {
        $this->execute(sprintf('DROP DATABASE IF EXISTS `%s`', $name));
        $this->createdTables = [];
    }

    /**
     * @inheritDoc
     */
    public function castToBool($value)
    {
        return (bool)$value ? 1 : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function renameTable($tableName, $newTableName)
    {
        $instructions = $this->getRenameTableInstructions($tableName, $newTableName);
        $this->executeAlterSteps($tableName, $instructions);
    }

    /**
     * {@inheritdoc}
     */
    public function changeColumn($tableName, $columnName, Column $newColumn)
    {
        $instructions = $this->getChangeColumnInstructions($tableName, $columnName, $newColumn);
        $this->executeAlterSteps($tableName, $instructions);
    }

    /**
     * {@inheritdoc}
     */
    public function dropColumn($tableName, $columnName)
    {
        $instructions = $this->getDropColumnInstructions($tableName, $columnName);
        $this->executeAlterSteps($tableName, $instructions);
    }

    /**
     * {@inheritdoc}
     */
    public function dropIndex($tableName, $columns)
    {
        $instructions = $this->getDropIndexByColumnsInstructions($tableName, $columns);
        $this->executeAlterSteps($tableName, $instructions);
    }

    /**
     * {@inheritdoc}
     */
    public function migrated(MigrationInterface $migration, $direction, $startTime, $endTime)
    {
        if (strcasecmp($direction, MigrationInterface::UP) === 0) {
            // up
            $sql = sprintf(
                "INSERT INTO %s (%s, %s, %s, %s, %s) VALUES ('%s', '%s', '%s', '%s', %s);",
                $this->quoteTableName($this->getSchemaTableName()),
                $this->quoteColumnName('version'),
                $this->quoteColumnName('migration_name'),
                $this->quoteColumnName('start_time'),
                $this->quoteColumnName('end_time'),
                $this->quoteColumnName('breakpoint'),
                $migration->getVersion(),
                substr($migration->getName(), 0, 100),
                $startTime,
                $endTime,
                $this->castToBool(false)
            );

            $this->execute($sql);
        } else {
            // down
            $sql = sprintf(
                "DELETE FROM %s WHERE %s = '%s'",
                $this->quoteTableName($this->getSchemaTableName()),
                $this->quoteColumnName('version'),
                $migration->getVersion()
            );

            $this->execute($sql);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toggleBreakpoint(MigrationInterface $migration)
    {
        $this->query(
            sprintf(
                'UPDATE %1$s SET %2$s = CASE %2$s WHEN %3$s THEN %4$s ELSE %3$s END, %7$s = %7$s WHERE %5$s = \'%6$s\';',
                $this->quoteTableName($this->getSchemaTableName()),
                $this->quoteColumnName('breakpoint'),
                $this->castToBool(true),
                $this->castToBool(false),
                $this->quoteColumnName('version'),
                $migration->getVersion(),
                $this->quoteColumnName('start_time')
            )
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setBreakpoint(MigrationInterface $migration)
    {
        return $this->markBreakpoint($migration, true);
    }

    /**
     * @inheritDoc
     */
    public function unsetBreakpoint(MigrationInterface $migration)
    {
        return $this->markBreakpoint($migration, false);
    }

    /**
     * Mark a migration breakpoint.
     *
     * @param \Phinx\Migration\MigrationInterface $migration The migration target for the breakpoint
     * @param bool $state The required state of the breakpoint
     *
     * @return \Phinx\Db\Adapter\AdapterInterface
     */
    protected function markBreakpoint(MigrationInterface $migration, $state)
    {
        $this->query(
            sprintf(
                'UPDATE %1$s SET %2$s = %3$s, %4$s = %4$s WHERE %5$s = \'%6$s\';',
                $this->quoteTableName($this->getSchemaTableName()),
                $this->quoteColumnName('breakpoint'),
                $this->castToBool($state),
                $this->quoteColumnName('start_time'),
                $this->quoteColumnName('version'),
                $migration->getVersion()
            )
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function executeActions(Table $table, array $actions)
    {
        $instructions = new AlterInstructions();

        foreach ($actions as $action) {
            switch (true) {
                case $action instanceof AddColumn:
                    $instructions->merge($this->getAddColumnInstructions($table, $action->getColumn()));
                    break;

                case $action instanceof AddIndex:
                    $instructions->merge($this->getAddIndexInstructions($table, $action->getIndex()));
                    break;

                case $action instanceof AddForeignKey:
                    $instructions->merge($this->getAddForeignKeyInstructions($table, $action->getForeignKey()));
                    break;

                case $action instanceof ChangeColumn:
                    $instructions->merge($this->getChangeColumnInstructions(
                        $table->getName(),
                        $action->getColumnName(),
                        $action->getColumn()
                    ));
                    break;

                case $action instanceof DropForeignKey && !$action->getForeignKey()->getConstraint():
                    $instructions->merge($this->getDropForeignKeyByColumnsInstructions(
                        $table->getName(),
                        $action->getForeignKey()->getColumns()
                    ));
                    break;

                case $action instanceof DropForeignKey && $action->getForeignKey()->getConstraint():
                    $instructions->merge($this->getDropForeignKeyInstructions(
                        $table->getName(),
                        $action->getForeignKey()->getConstraint()
                    ));
                    break;

                case $action instanceof DropIndex && $action->getIndex()->getName() !== null:
                    $instructions->merge($this->getDropIndexByNameInstructions(
                        $table->getName(),
                        $action->getIndex()->getName()
                    ));
                    break;

                case $action instanceof DropIndex && $action->getIndex()->getName() == null:
                    $instructions->merge($this->getDropIndexByColumnsInstructions(
                        $table->getName(),
                        $action->getIndex()->getColumns()
                    ));
                    break;

                case $action instanceof DropTable:
                    $instructions->merge($this->getDropTableInstructions(
                        $table->getName()
                    ));
                    break;

                case $action instanceof RemoveColumn:
                    $instructions->merge($this->getDropColumnInstructions(
                        $table->getName(),
                        $action->getColumn()->getName()
                    ));
                    break;

                case $action instanceof RenameColumn:
                    $instructions->merge($this->getRenameColumnInstructions(
                        $table->getName(),
                        $action->getColumn()->getName(),
                        $action->getNewName()
                    ));
                    break;

                case $action instanceof RenameTable:
                    $instructions->merge($this->getRenameTableInstructions(
                        $table->getName(),
                        $action->getNewName()
                    ));
                    break;

                case $action instanceof ChangePrimaryKey:
                    $instructions->merge($this->getChangePrimaryKeyInstructions(
                        $table,
                        $action->getNewColumns()
                    ));
                    break;

                case $action instanceof ChangeComment:
                    $instructions->merge($this->getChangeCommentInstructions(
                        $table,
                        $action->getNewComment()
                    ));
                    break;

                default:
                    throw new InvalidArgumentException(
                        sprintf("Don't know how to execute action: '%s'", get_class($action))
                    );
            }
        }

        $this->executeAlterSteps($table->getName(), $instructions);
    }

    /**
     * Returns the instructions to add the specified column to a database table.
     *
     * @param \Phinx\Db\Table\Table $table Table
     * @param \Phinx\Db\Table\Column $column Column
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getAddColumnInstructions(Table $table, Column $column)
    {
        $alter = sprintf(
            'ADD %s %s',
            $this->quoteColumnName($column->getName()),
            $this->getColumnSqlDefinition($column)
        );

        $alter .= $this->afterClause($column);

        return new AlterInstructions([$alter]);
    }

    /**
     * Exposes the MySQL syntax to arrange a column `FIRST`.
     *
     * @param \Phinx\Db\Table\Column $column The column being altered.
     *
     * @return string The appropriate SQL fragment.
     */
    protected function afterClause(Column $column)
    {
        $after = $column->getAfter();
        if (empty($after)) {
            return '';
        }

        if ($after === self::FIRST) {
            return ' FIRST';
        }

        return ' AFTER ' . $this->quoteColumnName($after);
    }

    /**
     * Returns the instructions to add the specified index to a database table.
     *
     * @param \Phinx\Db\Table\Table $table Table
     * @param \Phinx\Db\Table\Index $index Index
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getAddIndexInstructions(Table $table, Index $index)
    {
        $instructions = new AlterInstructions();

        if ($index->getType() === Index::FULLTEXT) {
            // Must be executed separately
            // SQLSTATE[HY000]: General error: 1795 InnoDB presently supports one FULLTEXT index creation at a time
            $alter = sprintf(
                'ALTER TABLE %s ADD %s',
                $this->quoteTableName($table->getName()),
                $this->getIndexSqlDefinition($index)
            );

            $instructions->addPostStep($alter);
        } else {
            $alter = sprintf(
                'ADD %s',
                $this->getIndexSqlDefinition($index)
            );

            $instructions->addAlter($alter);
        }

        return $instructions;
    }

    /**
     * Returns the instructions to adds the specified foreign key to a database table.
     *
     * @param \Phinx\Db\Table\Table $table The table to add the constraint to
     * @param \Phinx\Db\Table\ForeignKey $foreignKey The foreign key to add
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getAddForeignKeyInstructions(Table $table, ForeignKey $foreignKey)
    {
        $alter = sprintf(
            'ADD %s',
            $this->getForeignKeySqlDefinition($foreignKey)
        );

        return new AlterInstructions([$alter]);
    }

    /**
     * Returns the instructions to change a table column type.
     *
     * @param string $tableName Table name
     * @param string $columnName Column Name
     * @param \Phinx\Db\Table\Column $newColumn New Column
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getChangeColumnInstructions($tableName, $columnName, Column $newColumn)
    {
        $alter = sprintf(
            'CHANGE %s %s %s%s',
            $this->quoteColumnName($columnName),
            $this->quoteColumnName($newColumn->getName()),
            $this->getColumnSqlDefinition($newColumn),
            $this->afterClause($newColumn)
        );

        return new AlterInstructions([$alter]);
    }

    /**
     * Returns the instructions to drop the specified foreign key from a database table.
     *
     * @param string $tableName The table where the foreign key constraint is
     * @param string[] $columns The list of column names
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getDropForeignKeyByColumnsInstructions($tableName, $columns)
    {
        $instructions = new AlterInstructions();

        foreach ($columns as $column) {
            $rows = $this->fetchAll(sprintf(
                "SELECT
                    CONSTRAINT_NAME
                  FROM information_schema.KEY_COLUMN_USAGE
                  WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                    AND TABLE_NAME = '%s'
                    AND COLUMN_NAME = '%s'
                  ORDER BY POSITION_IN_UNIQUE_CONSTRAINT",
                $tableName,
                $column
            ));

            foreach ($rows as $row) {
                $instructions->merge($this->getDropForeignKeyInstructions($tableName, $row['CONSTRAINT_NAME']));
            }
        }

        if (empty($instructions->getAlterParts())) {
            throw new InvalidArgumentException(sprintf(
                "Not foreign key on columns '%s' exist",
                implode(',', $columns)
            ));
        }

        return $instructions;
    }

    /**
     * Returns the instructions to drop the specified foreign key from a database table.
     *
     * @param string $tableName The table where the foreign key constraint is
     * @param string $constraint Constraint name
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getDropForeignKeyInstructions($tableName, $constraint)
    {
        $alter = sprintf(
            'DROP FOREIGN KEY %s',
            $constraint
        );

        return new AlterInstructions([$alter]);
    }

    /**
     * Returns the instructions to drop the index specified by name from a database table.
     *
     * @param string $tableName The table name whe the index is
     * @param string $indexName The name of the index
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getDropIndexByNameInstructions($tableName, $indexName)
    {
        $indexes = $this->getIndexes($tableName);

        foreach ($indexes as $name => $index) {
            if ($name === $indexName) {
                return new AlterInstructions([sprintf(
                    'DROP INDEX %s',
                    $this->quoteColumnName($indexName)
                )]);
            }
        }

        throw new InvalidArgumentException(sprintf(
            "The specified index name '%s' does not exist",
            $indexName
        ));
    }

    /**
     * Returns the instructions to drop the specified index from a database table.
     *
     * @param string $tableName The name of of the table where the index is
     * @param mixed $columns Column(s)
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getDropIndexByColumnsInstructions($tableName, $columns)
    {
        if (is_string($columns)) {
            $columns = [$columns]; // str to array
        }

        $indexes = $this->getIndexes($tableName);
        $columns = array_map('strtolower', $columns);

        foreach ($indexes as $indexName => $index) {
            if ($columns == $index['columns']) {
                return new AlterInstructions([sprintf(
                    'DROP INDEX %s',
                    $this->quoteColumnName($indexName)
                )]);
            }
        }

        throw new InvalidArgumentException(sprintf(
            "The specified index on columns '%s' does not exist",
            implode(',', $columns)
        ));
    }

    /**
     * Returns the instructions to drop the specified database table.
     *
     * @param string $tableName Table name
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getDropTableInstructions($tableName)
    {
        $this->removeCreatedTable($tableName);
        $sql = sprintf('DROP TABLE %s', $this->quoteTableName($tableName));

        return new AlterInstructions([], [$sql]);
    }

    /**
     * Returns the instructions to drop the specified column.
     *
     * @param string $tableName Table name
     * @param string $columnName Column Name
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getDropColumnInstructions($tableName, $columnName)
    {
        $alter = sprintf('DROP COLUMN %s', $this->quoteColumnName($columnName));

        return new AlterInstructions([$alter]);
    }

    /**
     * Returns the instructions to rename the specified column.
     *
     * @param string $tableName Table name
     * @param string $columnName Column Name
     * @param string $newColumnName New Column Name
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getRenameColumnInstructions($tableName, $columnName, $newColumnName)
    {
        $rows = $this->fetchAll(sprintf('SHOW FULL COLUMNS FROM %s', $this->quoteTableName($tableName)));

        foreach ($rows as $row) {
            if (strcasecmp($row['Field'], $columnName) === 0) {
                $null = $row['Null'] === 'NO' ? 'NOT NULL' : 'NULL';
                $comment = isset($row['Comment']) ? ' COMMENT ' . '\'' . addslashes($row['Comment']) . '\'' : '';
                $extra = ' ' . strtoupper($row['Extra']);
                if (($row['Default'] !== null)) {
                    $extra .= $this->getDefaultValueDefinition($row['Default']);
                }
                $definition = $row['Type'] . ' ' . $null . $extra . $comment;

                $alter = sprintf(
                    'CHANGE COLUMN %s %s %s',
                    $this->quoteColumnName($columnName),
                    $this->quoteColumnName($newColumnName),
                    $definition
                );

                return new AlterInstructions([$alter]);
            }
        }

        throw new InvalidArgumentException(sprintf(
            "The specified column doesn't exist: " .
            $columnName
        ));
    }

    /**
     * Returns the instructions to rename the specified database table.
     *
     * @param string $tableName Table name
     * @param string $newTableName New Name
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getRenameTableInstructions($tableName, $newTableName)
    {
        $this->updateCreatedTableName($tableName, $newTableName);
        $sql = sprintf(
            'RENAME TABLE %s TO %s',
            $this->quoteTableName($tableName),
            $this->quoteTableName($newTableName)
        );

        return new AlterInstructions([], [$sql]);
    }

    /**
     * Returns the instructions to change the primary key for the specified database table.
     *
     * @param \Phinx\Db\Table\Table $table Table
     * @param string|string[]|null $newColumns Column name(s) to belong to the primary key, or null to drop the key
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getChangePrimaryKeyInstructions(Table $table, $newColumns)
    {
        $instructions = new AlterInstructions();

        // Drop the existing primary key
        $primaryKey = $this->getPrimaryKey($table->getName());
        if (!empty($primaryKey['columns'])) {
            $instructions->addAlter('DROP PRIMARY KEY');
        }

        // Add the primary key(s)
        if (!empty($newColumns)) {
            $sql = 'ADD PRIMARY KEY (';
            if (is_string($newColumns)) { // handle primary_key => 'id'
                $sql .= $this->quoteColumnName($newColumns);
            } elseif (is_array($newColumns)) { // handle primary_key => array('tag_id', 'resource_id')
                $sql .= implode(',', array_map([$this, 'quoteColumnName'], $newColumns));
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Invalid value for primary key: %s',
                    json_encode($newColumns)
                ));
            }
            $sql .= ')';
            $instructions->addAlter($sql);
        }

        return $instructions;
    }

    /**
     * Get the primary key from a particular table.
     *
     * @param string $tableName Table name
     *
     * @return array
     */
    public function getPrimaryKey($tableName)
    {
        $options = $this->getOptions();
        $rows = $this->fetchAll(sprintf(
            "SELECT
                k.CONSTRAINT_NAME,
                k.COLUMN_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS t
            JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
                USING(CONSTRAINT_NAME,TABLE_SCHEMA,TABLE_NAME)
            WHERE t.CONSTRAINT_TYPE='PRIMARY KEY'
                AND t.TABLE_SCHEMA='%s'
                AND t.TABLE_NAME='%s'",
            $options['name'],
            $tableName
        ));

        $primaryKey = [
            'columns' => [],
        ];
        foreach ($rows as $row) {
            $primaryKey['constraint'] = $row['CONSTRAINT_NAME'];
            $primaryKey['columns'][] = $row['COLUMN_NAME'];
        }

        return $primaryKey;
    }

    /**
     * Returns the instruction to change the comment for the specified database table.
     *
     * @param \Phinx\Db\Table\Table $table Table
     * @param string|null $newComment New comment string, or null to drop the comment
     *
     * @return \Phinx\Db\Util\AlterInstructions
     */
    protected function getChangeCommentInstructions(Table $table, $newComment)
    {
        $instructions = new AlterInstructions();

        // passing 'null' is to remove table comment
        $newComment = $newComment ?? '';
        $sql = sprintf(' COMMENT=%s ', $this->getConnection()->quote($newComment));
        $instructions->addAlter($sql);

        return $instructions;
    }

    /**
     * Executes all the ALTER TABLE instructions passed for the given table
     *
     * @param string $tableName The table name to use in the ALTER statement
     * @param \Phinx\Db\Util\AlterInstructions $instructions The object containing the alter sequence
     *
     * @return void
     */
    protected function executeAlterSteps($tableName, AlterInstructions $instructions)
    {
        $alter = sprintf('ALTER TABLE %s %%s', $this->quoteTableName($tableName));
        $instructions->execute($alter, [$this, 'execute']);
    }

    /**
     * @inheritDoc
     *
     * @throws \BadMethodCallException Exception.
     *
     * @return void
     */
    public function getQueryBuilder()
    {
        throw new BadMethodCallException('Query Builder is not supported');
    }

    /**
     * @inheritDoc
     */
    public function truncateTable($tableName)
    {
        $sql = sprintf(
            'TRUNCATE TABLE %s',
            $this->quoteTableName($tableName)
        );

        $this->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function dropTable($tableName)
    {
        $instructions = $this->getDropTableInstructions($tableName);
        $this->executeAlterSteps($tableName, $instructions);
    }

    /**
     * {@inheritdoc}
     */
    public function addColumn(Table $table, Column $column)
    {
        $instructions = $this->getAddColumnInstructions($table, $column);
        $this->executeAlterSteps($table->getName(), $instructions);
    }

    /**
     * {@inheritdoc}
     */
    public function addIndex(Table $table, Index $index)
    {
        $instructions = $this->getAddIndexInstructions($table, $index);
        $this->executeAlterSteps($table->getName(), $instructions);
    }

    /**
     * {@inheritdoc}
     */
    public function addForeignKey(Table $table, ForeignKey $foreignKey)
    {
        $instructions = $this->getAddForeignKeyInstructions($table, $foreignKey);
        $this->executeAlterSteps($table->getName(), $instructions);
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->execute('START TRANSACTION');
    }

    /**
     * {@inheritdoc}
     */
    public function commitTransaction()
    {
        $this->execute('COMMIT');
    }

    /**
     * {@inheritdoc}
     */
    public function hasColumn($tableName, $columnName)
    {
        $rows = $this->fetchAll(sprintf('SHOW COLUMNS FROM %s', $this->quoteTableName($tableName)));
        foreach ($rows as $column) {
            if (strcasecmp($column['Field'], $columnName) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function hasIndex($tableName, $columns)
    {
        if (is_string($columns)) {
            $columns = [$columns]; // str to array
        }

        $columns = array_map('strtolower', $columns);
        $indexes = $this->getIndexes($tableName);

        foreach ($indexes as $index) {
            if ($columns == $index['columns']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function hasPrimaryKey($tableName, $columns, $constraint = null)
    {
        $primaryKey = $this->getPrimaryKey($tableName);

        if (empty($primaryKey['constraint'])) {
            return false;
        }

        if ($constraint) {
            return $primaryKey['constraint'] === $constraint;
        } else {
            if (is_string($columns)) {
                $columns = [$columns]; // str to array
            }
            $missingColumns = array_diff($columns, $primaryKey['columns']);

            return empty($missingColumns);
        }
    }

    /**
     * Returns MySQL column types (inherited and MySQL specified).
     *
     * @return string[]
     */
    public function getColumnTypes()
    {
        return array_merge($this->column_types, static::$specificColumnTypes);
    }

    /**
     * {@inheritdoc}
     */
    public function createDatabase($name, $options = [])
    {
        $charset = $options['charset'] ?? 'utf8';

        if (isset($options['collation'])) {
            $this->execute(sprintf(
                'CREATE DATABASE `%s` DEFAULT CHARACTER SET `%s` COLLATE `%s`',
                $name,
                $charset,
                $options['collation']
            ));
        } else {
            $this->execute(sprintf('CREATE DATABASE `%s` DEFAULT CHARACTER SET `%s`', $name, $charset));
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws \BadMethodCallException
     *
     * @return void
     */
    public function createSchema($schemaName = 'public')
    {
        throw new BadMethodCallException('Creating a schema is not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function dropIndexByName($tableName, $indexName)
    {
        $instructions = $this->getDropIndexByNameInstructions($tableName, $indexName);
        $this->executeAlterSteps($tableName, $instructions);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \BadMethodCallException
     *
     * @return void
     */
    public function dropSchema($name)
    {
        throw new BadMethodCallException('Dropping a schema is not supported');
    }

    /**
     * @inheritdoc
     */
    public function changePrimaryKey(Table $table, $newColumns)
    {
        $instructions = $this->getChangePrimaryKeyInstructions($table, $newColumns);
        $this->executeAlterSteps($table->getName(), $instructions);
    }

    /**
     * @inheritdoc
     */
    public function changeComment(Table $table, $newComment)
    {
        $instructions = $this->getChangeCommentInstructions($table, $newComment);
        $this->executeAlterSteps($table->getName(), $instructions);
    }
}