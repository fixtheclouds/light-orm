<?php

/**
 * @package tynyorm
 * @author Egor Romanov <unsaidxpl@gmail.com>
 */
abstract class TinyORM
{

    const DEFAULT_PRIMARY_KEY = 'id';

    /**
     * @var mysqli
     */
    protected static $connection;

    /**
     * @var string
     */
    protected static $dbName;

    /**
     * @var string table primary key
     */
    protected static $primaryKey = self::DEFAULT_PRIMARY_KEY;

    /**
     * @var bool
     */
    protected $_destroy = false;

    /**
     * @var array
     */
    protected $attributes = [];

    /**************************
     * Magic methods
     **************************/

    /**
     * Model constructor
     *
     * @param array $attributes array of attributes
     */
    public function __construct($attributes) {
        return $this->assignAttributes($attributes);
    }

    /**
     * Model attribute getter
     *
     * @param $attribute
     * @return mixed
     * @throws Exception
     */
    public function __get($attribute) {
        if (!$this->_destroy) {
            return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
        }
        throw new Exception("Cannot get data from destroyed record");
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
        throw new Exception("Cannot set data of destroyed record");
    }

    /**************************
     * Class methods
     **************************/

    /**
     * Establish database connection
     *
     * @param mysqli $connection MySQLi connection instance
     * @param string $dbName database name
     * @throws Exception
     */
    public static function establishConnection($connection, $dbName) {
        if (!$connection || !$dbName) {
            throw new Exception("Connection is invalid");
        }

        self::$connection = $connection;
        self::$dbName = $dbName;

        self::$connection->select_db($dbName);
    }

    /**
     * Return model table name (defaults to class name)
     *
     * @return string
     */
    public static function getTableName() {
        $className = get_called_class();

        return isset($className::$table) ? $className::$table : self::$connection->real_escape_string(strtolower($className));
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
     * @return mixed
     */
    public static function create($attributes) {
        $record = new static($attributes);
        return $record->save();
    }

    /**
     * Find record by id and return model instance
     * @param int $id
     * @throws Exception
     * @return TinyORM
     */
    public static function find($id) {
        $query = sprintf("SELECT * FROM `%s` WHERE `%s` = %s", self::getTableName(), self::getPrimaryKey(), intval($id));
        $result = self::$connection->query($query);

        if (!$result->num_rows) {
            throw new Exception('Record not found in database.');
        }
        return new static($result->fetch_assoc());
    }

    /**************************
     * Instance methods
     **************************/

    /**
     * Save record to database
     *
     * @return mixed
     */
    public function save() {
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
            $primaryKey = self::getPrimaryKey();
            $query = sprintf("DELETE FROM `%s` WHERE `%s` = %s", self::getTableName(), $primaryKey, intval($this->{$primaryKey}));

            $result = self::$connection->query($query);
            unset($this->{$primaryKey});
            $this->_destroy = true;
            return $result;
        } else {
            throw new Exception("Can`t delete a record that isn`t present in database");
        }
    }

    /**
     * Assign attributes from array to model instance
     *
     * @param array $attributes
     * @return TinyORM
     */
    public function assignAttributes($attributes) {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Detect whether record is persisted in database
     *
     * @return bool
     */
    protected function isPersisted() {
        $primaryKey = self::getPrimaryKey();
        if (!$this->{$primaryKey}) {
            return false;
        }
        try {
            self::find($this->{$primaryKey});
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Update existing record in database
     *
     * @throws Exception
     * @return TinyORM | boolean
     */
    protected function update() {
        if ($this->_destroy) {
            throw new Exception('Cannot update destroyed record');
        }
        $primaryKey = self::getPrimaryKey();
        $statements = [];

        foreach ($this->attributes as $key => $value) {
            $statements[] = sprintf("`%s` = '%s'", $key, self::$connection->real_escape_string($value));
        }

        $query = sprintf("UPDATE `%s` SET `%s` WHERE `%s` = `%s`", self::getTableName(), implode(', ', $statements), $primaryKey, $this->{$primaryKey});
        if (self::$connection->query($query)) {
            return $this;
        }
        return false;
    }

    /**
     * Insert new record into database
     *
     * @return TinyORM | boolean
     */
    protected function insert() {
        $columns = [];
        $values = [];
        foreach ($this->attributes as $key => $value) {
            $columns[] = "`$key`";
            $values[] = "'" . self::$connection->real_escape_string($value) . "'";
        }

        $query = sprintf("INSERT INTO `%s` (%s) VALUES (%s)", self::getTableName(), implode(', ', $columns), implode(', ', $values));

        if (self::$connection->query($query)) {
            $this->id = self::$connection->insert_id;
            return $this;
        }
        return false;
    }
}