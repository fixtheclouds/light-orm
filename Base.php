<?php
/**
 * @package lightorm
 * @author Egor Romanov <unsaidxpl@gmail.com>
 */

namespace LightORM;

abstract class Base
{

    const DEFAULT_PRIMARY_KEY = 'id';

    /**
     * @var PDO
     */
    protected static $connection;

    /**
     * @var string table primary key
     */
    protected static $primaryKey = self::DEFAULT_PRIMARY_KEY;

    /**
     * @var array required fields
     */
    protected static $_required = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var bool
     */
    protected $_destroy = false;

    /**
     * @var array validation errors
     */
    protected $_errors = [];

    /**************************
     * Magic methods
     **************************/

    /**
     * Model constructor
     *
     * @param array $attributes array of attributes
     * @return LightORM
     */
    public function __construct($attributes) {
        return $this->assignAttributes($attributes);
    }

    /**
     * Model attribute getter
     *
     * @param $attribute
     * @throws Exception
     * @return mixed
     */
    public function __get($attribute) {
        if (!$this->_destroy) {
            return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
        }
        throw new \Exception("Cannot get data from destroyed record");
    }

    /**
     * Model attribute setter
     *
     * @param $attribute
     * @param $value
     * @throws Exception
     * @return mixed
     */
    public function __set($attribute, $value) {
        if (!$this->_destroy) {
            return $this->attributes[$attribute] = $value;
        }
        throw new \Exception("Cannot set data of destroyed record");
    }

    /**
     * Magic method for creating static methods dynamically
     *
     * @param string $name
     * @param array $arguments
     * @return LightORM
     */
    public static function __callStatic($name, $arguments)
    {
        if (strpos($name, 'findBy') === 0 && count($arguments) > 0) {
            $key = strtolower(str_replace('findBy', '', $name));
            return static::load($key, $arguments[0]);
        }
    }

    /**************************
     * Class methods
     **************************/

    /**
     * Establish database connection
     *
     * @param PDO $connection PDO connection instance
     * @throws Exception
     */
    public static function establishConnection($connection) {
        if (!$connection) {
            throw new \Exception("Connection is invalid");
        }

        static::$connection = $connection;
    }

    /**
     * Return model table name (defaults to class name)
     *
     * @return string
     */
    public static function getTableName() {
        $className = get_called_class();

        return isset($className::$table) ? $className::$table : strtolower($className);
    }

    /**
     * Getter for model primary key (defaults to 'id')
     *
     * @return string
     */
    public static function getPrimaryKey() {
        $className = get_called_class();

        return $className::$primaryKey;
    }

    /**
     * Create record and save it to database
     *
     * @param $attributes
     * @throws Exception
     * @return LightORM
     */
    public static function create($attributes) {
        $record = new static($attributes);
        if (!$record->save()) {
            $message = empty($record->_errors) ? "Failed to create user" :
                "Validation failed: " . implode('; ', $record->_errors);
            throw new \Exception($message);
        }
        return $record;
    }

    /**
     * Find record by id
     * @param int $id
     * @return LightORM
     */
    public static function find($id) {
        return static::load(static::getPrimaryKey(), $id);
    }

    /**
     * Create query interface
     * @return QueryBuilder
     */
    public static function query() {
        return new QueryBuilder(static::$connection, static::getTableName());
    }

    /**
     * Retrieve first record found by certain column value
     * @param $key
     * @param $value
     * @throws Exception
     * @return LightORM
     */
    protected static function load($key, $value) {
        $sql = sprintf("SELECT * FROM `%s` WHERE `%s` = ?", static::getTableName(), $key);
        $query = static::$connection->prepare($sql);
        $query->execute([$value]);

        $result = $query->fetch(\PDO::FETCH_ASSOC);
        if (empty($result)) {
            throw new \Exception('Record not found in database.');
        }
        return new static($result);
    }

    /**************************
     * Instance methods
     **************************/

    /**
     * Save record to database
     *
     * @return boolean
     */
    public function save() {
        $this->_errors = [];
        if (!$this->isValid()) {
            return false;
        }

        if (!$this->isPersisted()) {
            return $this->insert();
        } else {
            return $this->update();
        }
    }

    /**
     * Remove record from database
     *
     * @throws Exception
     * @return boolean
     */
    public function destroy()
    {
        if ($this->isPersisted()) {
            $primaryKey = static::getPrimaryKey();
            $sql = sprintf("DELETE FROM `%s` WHERE `%s` = ?", static::getTableName(), $primaryKey);
            $query = static::$connection->prepare($sql);
            $result = $query->execute([$this->{$primaryKey}]);

            unset($this->{$primaryKey});
            $this->_destroy = true;
            return $result;
        } else {
            throw new \Exception("Can`t delete a record that isn`t present in database");
        }
    }

    /**
     * Assign attributes from array to model instance
     *
     * @param array $attributes
     * @return LightORM
     */
    public function assignAttributes($attributes) {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Errors getter
     *
     * @return array
     */
    public function errors() {
        return $this->_errors;
    }

    /**
     * Fetch record from DB
     */
    public function reload() {
        $record = static::find($this->{self::getPrimaryKey()});
        $this->assignAttributes($record->attributes);
        return $record;
    }

    /**
     * Detect whether record is persisted in database
     *
     * @return bool
     */
    protected function isPersisted() {
        $primaryKey = static::getPrimaryKey();
        if (!$this->{$primaryKey}) {
            return false;
        }
        try {
            static::find($this->{$primaryKey});
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Update existing record in database
     *
     * @throws Exception
     * @return LightORM | boolean
     */
    protected function update() {
        if ($this->_destroy) {
            throw new \Exception('Cannot update destroyed record');
        }
        $primaryKey = static::getPrimaryKey();
        $statements = [];

        foreach ($this->attributes as $key => $value) {
            $statements[] = sprintf("`%s` = %s", $key, static::$connection->quote($value));
        }
        $sql = sprintf("UPDATE `%s` SET %s WHERE `%s` = %s", static::getTableName(),
            implode(', ', $statements), $primaryKey, $this->{$primaryKey});

        try {
            static::$connection->query($sql);
        } catch(\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Insert new record into database
     *
     * @return LightORM | boolean
     */
    protected function insert() {
        $columns = [];
        $values = [];
        foreach ($this->attributes as $key => $value) {
            $columns[] = "`$key`";
            $values[] = static::$connection->quote($value);
        }

        $sql = sprintf("INSERT INTO `%s` (%s) VALUES (%s)", static::getTableName(),
            implode(', ', $columns), implode(', ', $values));

        try {
            static::$connection->query($sql);
        } catch (\Exception $e) {
            return false;
        }

        $this->{self::getPrimaryKey()} = static::$connection->lastInsertId();
        return true;
    }

    /**
     * Validate required column presence
     *
     * @param string $key
     * @param mixed $value
     */
    protected function validatePresence($key, $value) {
        if (in_array($key, static::$_required) && empty($value)) {
            $this->_errors[] = "$key should not be blank";
        }
    }

    /**
     * Returns true if record passes validations
     *
     * @return bool
     */
    protected function isValid() {
        foreach (static::$_required as $required) {
            if (!isset($this->attributes[$required])) {
                $this->attributes[$required] = null;
            }
        }
        foreach ($this->attributes as $key => $value) {
            $this->validatePresence($key, $value);
        }
        return empty($this->_errors);
    }
}
