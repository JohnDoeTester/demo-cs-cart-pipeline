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
use Tygh\Registry;

/**
 * Class Query
 *
 * @package Tygh\Addons\ProductVariations\Tools
 */
class Query
{
    /** @var \Tygh\Database\Connection  */
    protected $db_connection;

    /** @var string */
    protected $table_id;

    /** @var null|string */
    protected $table_alias;

    /** @var array  */
    protected $params = [];

    /** @var array  */
    protected $joins = [];

    /** @var array  */
    protected $conditions = [];

    /** @var string */
    protected $having;

    /** @var string[]  */
    protected $fields = [];

    /** @var string[]  */
    protected $group_by = [];

    /** @var string[]  */
    protected $order_by = [];

    /** @var int */
    protected $limit;

    /** @var int */
    protected $offset;

    /**
     * Query constructor.
     *
     * @param \Tygh\Database\Connection $db_connection
     * @param string                    $table_id
     * @param null|string               $table_alias
     */
    public function __construct(Connection $db_connection, $table_id, $table_alias = null)
    {
        $this->db_connection = $db_connection;
        $this->table_id = $table_id;
        $this->table_alias = $table_alias;
    }

    /**
     * @param array       $conditions
     * @param string|null $table_alias
     *
     * @return self
     */
    public function addConditions(array $conditions, $table_alias = null)
    {
        foreach ($conditions as $field => $value) {
            if (is_numeric($field)) {
                $operation = array_shift($value);

                if ($operation === 'IN') {
                    $this->addInCondition(array_shift($value), array_shift($value), $table_alias);
                } elseif ($operation === 'NOT IN') {
                    $this->addNotInCondition(array_shift($value), array_shift($value), $table_alias);
                }
            } elseif (is_array($value)) {
                $this->addInCondition($field, $value, $table_alias);
            } elseif (is_numeric($value)) {
                $this->conditions[] = sprintf('%s = ?i', $this->buildField($field, $table_alias));
                $this->params[] = $value;
            } else {
                $this->conditions[] = sprintf('%s = ?s', $this->buildField($field, $table_alias));
                $this->params[] = $value;
            }
        }

        return $this;
    }

    /**
     * @param string $sql_expression
     * @param array  $params
     */
    public function addCondition($sql_expression, array $params = [])
    {
        $this->conditions[] = $sql_expression;
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * @param string|string[] $fields
     * @param array           $values
     * @param null|string     $table_alias
     *
     * @return self
     */
    public function addInCondition($fields, array $values, $table_alias = null)
    {
        $this->addInConditionInternal($fields, $values, $table_alias, 'IN');
        return $this;
    }

    /**
     * @param string|string[] $fields
     * @param array           $values
     * @param null|string     $table_alias
     *
     * @return self
     */
    public function addNotInCondition($fields, array $values, $table_alias = null)
    {
        $this->addInConditionInternal($fields, $values, $table_alias, 'NOT IN');
        return $this;
    }

    /**
     * @param string[] $fields
     *
     * @return self
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @param string $field
     *
     * @return self
     */
    public function addField($field)
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * @param string[] $fields
     *
     * @return self
     */
    public function setGroupBy(array $fields)
    {
        $this->group_by = $fields;

        return $this;
    }

    /**
     * @param string[] $list
     *
     * @return $this
     */
    public function setOrderBy(array $list)
    {
        $this->order_by = $list;

        return $this;
    }

    /**
     * @param string $sql_expression
     *
     * @return self
     */
    public function setHaving($sql_expression)
    {
        $this->having = $sql_expression;

        return $this;
    }

    /**
     * @param string $table_alias
     * @param string $table_id
     * @param array  $link
     * @param array  $conditions
     *
     * @return self
     */
    public function addInnerJoin($table_alias, $table_id, array $link, array $conditions = [])
    {
        $this->joins[$table_alias] = [$table_id, $link, $conditions, 'INNER'];

        return $this;
    }

    /**
     * @param string $table_alias
     * @param string $table_id
     * @param array  $link
     * @param array  $conditions
     *
     * @return self
     */
    public function addLeftJoin($table_alias, $table_id, array $link, array $conditions = [])
    {
        $this->joins[$table_alias] = [$table_id, $link, $conditions, 'LEFT'];

        return $this;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param null|string|array|callable $index_by
     *
     * @return array
     */
    public function select($index_by = null)
    {
        if ($index_by && is_string($index_by)) {
            return $this->db_connection->getHash(
                $this->buildSelectSql(),
                $index_by,
                ...$this->params
            );
        } elseif ($index_by && is_array($index_by)) {
            return $this->db_connection->getMultiHash(
                $this->buildSelectSql(),
                $index_by,
                ...$this->params
            );
        } elseif ($index_by && is_callable($index_by)) {
            $data = $this->db_connection->getArray(
                $this->buildSelectSql(),
                ...$this->params
            );

            $result = [];

            foreach ($data as $datum) {
                $key = $index_by($datum);

                if (is_array($key)) {
                    $cnt = count($key);

                    if ($cnt === 2) {
                        list($key1, $key2) = $key;
                        $result[$key1][$key2] = $datum;
                    } elseif ($cnt > 2) {
                        list($key1, $key2, $value) = $key;
                        $result[$key1][$key2] = $value;
                    } else {
                        list($key1) = $key;
                        $result[$key1] = $datum;
                    }
                } else {
                    $result[$key] = $datum;
                }
            }

            return $result;
        }

        return $this->db_connection->getArray(
            $this->buildSelectSql(),
            ...$this->params
        );
    }

    /**
     * @param null|string|array $index_by
     *
     * @return array
     */
    public function column($index_by = null)
    {
        if ($index_by) {
            if (!is_array($index_by)) {
                $index_by = [$index_by, reset($this->fields)];
            }

            return $this->db_connection->getSingleHash(
                $this->buildSelectSql(),
                $index_by,
                ...$this->params
            );
        } else {
            return $this->db_connection->getColumn(
                $this->buildSelectSql(),
                ...$this->params
            );
        }
    }

    /**
     * @return array
     */
    public function row()
    {
        return $this->db_connection->getRow(
            $this->buildSelectSql(),
            ...$this->params
        );
    }

    /**
     * @return string
     */
    public function scalar()
    {
        return $this->db_connection->getField(
            $this->buildSelectSql(),
            ...$this->params
        );
    }

    public function delete()
    {
        $this->db_connection->query(
            sprintf('DELETE FROM ?:%s WHERE %s', $this->buildTableName(), implode(' AND ', $this->conditions)),
            ...$this->params
        );
    }

    public function update(array $data)
    {
        $this->db_connection->query(
            sprintf('UPDATE ?:%s SET ?u WHERE %s', $this->buildTableName(), implode(' AND ', $this->conditions)),
            $data, ...$this->params
        );
    }

    public function insert(array $data)
    {
        return $this->db_connection->query(sprintf('INSERT INTO ?:%s ?e', $this->table_id), $data);
    }

    public function multipleInsert(array $data)
    {
        $this->db_connection->query(sprintf('INSERT INTO ?:%s ?m', $this->table_id), $data);
    }

    public function replace(array $data)
    {
        return $this->db_connection->replaceInto($this->table_id, $data);
    }

    public function executeWithoutSharing(callable $callable)
    {
        $skip_sharing_selection = Registry::ifGet('runtime.skip_sharing_selection', false);
        Registry::set('runtime.skip_sharing_selection', true);

        $result = call_user_func($callable);

        Registry::set('runtime.skip_sharing_selection', $skip_sharing_selection);

        return $result;
    }

    protected function addInConditionInternal($fields, array $values, $table_alias = null, $operator = 'IN')
    {
        $fields = (array) $fields;

        if (count($fields) === 1) {
            $condition_values = [];
            $is_numeric = true;
            $field = reset($fields);

            foreach ($values as $item) {
                if (is_array($item)) {
                    $value = isset($item[$field]) ? $item[$field] : null;
                } else {
                    $value = $item;
                }

                $is_numeric = $is_numeric && is_numeric($value);
                $condition_values[] = $value;
            }

            $this->conditions[] = sprintf('%s %s (%s)', $this->buildField($field, $table_alias), $operator, $is_numeric ? '?n' : '?a');
            $this->params[] = $condition_values;
        } else {
            $in_values = [];
            $params = [];

            foreach ($values as $item) {
                foreach ($fields as $field) {
                    $params[] = isset($item[$field]) ? $item[$field] : null;
                }

                $in_values[] = sprintf('(%s)', implode(', ', array_fill(0, count($fields), '?s')));
            }

            $this->conditions[] = sprintf('(%s) %s (%s)', implode(', ', $this->buildFields($fields, $table_alias)), $operator, implode(', ', $in_values));
            $this->params = array_merge($this->params, $params);
        }

        return $this;
    }

    protected function buildSelectSql()
    {
        return sprintf(
            'SELECT %s FROM ?:%s%s WHERE %s%s%s%s%s%s',
            implode(', ', $this->fields), $this->buildTableName(), $this->buildJoins(),
            $this->conditions ? implode(' AND ', $this->conditions) : '1=1',
            $this->group_by ? sprintf(' GROUP BY %s', implode(', ', $this->group_by)) : '',
            $this->having ? sprintf(' HAVING %s', $this->having) : '',
            $this->order_by ? sprintf(' ORDER BY %s', implode(', ', $this->order_by)) : '',
            $this->limit ? sprintf(' LIMIT %d', $this->limit) : '',
            $this->offset ? sprintf(' OFFSET %d', $this->offset) : ''
        );
    }

    protected function buildJoins()
    {
        if (empty($this->joins)) {
            return '';
        }
        $joins = [];
        $values = [];

        foreach ($this->joins as $table_alias => $join_data) {
            list($table_id, $link, $conditions, $join_type) = $join_data;
            $link_sql = [];

            foreach ($link as $base_field => $join_field) {
                if (strpos($base_field, '.') === false) {
                    $base_field = sprintf('%s.%s', $this->table_alias, $base_field);
                }

                if (strpos($join_field, '.') === false) {
                    $join_field = sprintf('%s.%s', $table_alias, $join_field);
                }

                $link_sql[] = sprintf('%s = %s', $base_field, $join_field);
            }

            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $link_sql[] = sprintf('%s.%s IN (?a)', $table_alias, $field);
                } else {
                    $link_sql[] = sprintf('%s.%s = ?s', $table_alias, $field);
                }

                $values[] = $value;
            }

            $joins[] = sprintf('%s JOIN ?:%s AS %s ON %s', $join_type, $table_id, $table_alias, implode(' AND ', $link_sql));
        }

        if ($values) {
            array_unshift($this->params, ...$values);
        }

        return ' ' . implode(' ', $joins);
    }

    protected function buildTableName()
    {
        if ($this->table_alias) {
            return sprintf('%s AS %s', $this->table_id, $this->table_alias);
        }

        return $this->table_id;
    }

    protected function buildField($field, $table_alias = null)
    {
        if ($table_alias === null) {
            $table_alias = $this->table_alias;
        }

        if ($table_alias) {
            $table_alias = sprintf('%s.', $table_alias);
        }

        return sprintf('%s%s', $table_alias, $field);
    }

    protected function buildFields($fields, $table_alias = null)
    {
        $result = [];

        foreach ($fields as $field) {
            $result[] = $this->buildField($field, $table_alias);
        }

        return $result;
    }
}