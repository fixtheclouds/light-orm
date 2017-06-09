<?php
/**
 * @package lightorm
 * @author Egor Romanov <unsaidxpl@gmail.com>
 */

namespace LightORM;


class QueryBuilder
{

    /**
     * @var PDO
     */
    protected $connection;

    /**
     * @var string
     */
    protected $sql;

    /**
     * @var array
     */
    protected $where = [], $order = [], $limit = [], $select = [], $group = [];


    public function __construct($connection, $tableName) {
        if (!$connection) {
            throw new \Exception('Connection is invalid');
        }
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    public function all() {
        $this->select[] = "`{$this->tableName}`.*";
        return $this;
    }

    public function select($columns) {
        $params = array($columns);

        foreach ($params as $column) {
            if (preg_match('/(\w+)\sAS\s(\w+)/i')) {
                $this->select[] = preg_replace('(\w+), (AS), (\w+)', '`$1` AS `$3`');
            } else {
                $this->select[] = "`$column`";
            }
        }

        return $this;
    }

    public function where($conditions) {
        if (empty($conditions) || !is_array($conditions)) {
            throw  new \Exception('Invalid conditions. Expected: flat key-value array');
        }
        $where = [];
        foreach ($conditions as $key => &$value) {
            if (is_array($value)) {
                foreach ($value as &$val) {
                    $val = $this->connection->quote($val);
                }
                $where[] = sprintf("`%s` IN (%s)", $key, implode(', ', $value));
            } else {
                $where[] = sprintf("`%s` = %s", $key, $this->connection->quote($value));
            }
        }
        $this->where[] = '(' . implode(' AND ', $where) . ')';
        return $this;
    }

    public function not() {
        if (!empty($this->where)) {
            $where = &end($this->where);
            $where = str_replace("` = '", "` <> '", $where);
            $where = str_replace("` IN ", '` NOT IN ', $where);
        }

        return $this;
    }

    public function order($order) {
        $params = array($order);
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $dir = in_array(strtoupper($value), ['ASC', 'DESC']) ? strtoupper($value) : 'ASC';
                $this->order[] = "`$key` $dir";
            } else if (is_integer($key)) {
                $this->order[] = "`$value` ASC";
            }
        }
        return $this;
    }

    public function result() {
        $result = $this->connection->query($this->buildSQL(), \PDO::FETCH_ASSOC);
        return $result->fetchAll();
    }


    protected function buildSQL() {
        $this->sql = "SELECT " . implode(', ', $this->select) .
            " FROM `{$this->tableName}`";
        if (!empty($this->where)) {
            $whereString = implode(' AND ', $this->where);
            $this->sql .= " WHERE $whereString ";
        }
        if (!empty($this->order)) {
            $this->sql .= ' ORDER BY ' . implode(', ', $this->order);
        }
        return $this->sql;
    }
}
