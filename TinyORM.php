<?php

/**
 * @package tynyorm
 * @author Egor Romanov <unsaidxpl@gmail.com>
 */
abstract class TinyORM
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
     * @param PDO $connection PDO connection instance
     * @throws Exception
     */
    public static function establishConnection($connection) {
        if (!$connection) {
            throw new Exception("Connection is invalid");
        }

        self::$connection = $connection;
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
        $sql = sprintf("SELECT * FROM `%s` WHERE `%s` = ?", self::getTableName(), self::getPrimaryKey());
        $query = self::$connection->prepare($sql);
        $query->execute([$id]);

        $result = $query->fetch(PDO::FETCH_ASSOC);
        if (empty($result)) {
            throw new Exception('Record not found in database.');
        }
        return new static($result);
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
            $sql = sprintf("DELETE FROM `%s` WHERE `%s` = ?", self::getTableName(), $primaryKey);
            $query = self::$connection->prepare($sql);
            $result = $query->execute([$this->{$primaryKey}]);

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
            $statements[] = sprintf("`%s` = %s", $key, self::$connection->quote($value));
        }
        $sql = sprintf("UPDATE `%s` SET `%s` WHERE `%s` = `%s`", self::getTableName(),
            implode(', ', $statements), $primaryKey, $this->{$primaryKey});
        try {
            self::$connection->query($sql);
        } catch(Exception $e) {
            return false;
        }

        return $this;
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
            $values[] = self::$connection->quote($value);
        }

        $sql = sprintf("INSERT INTO `%s` (%s) VALUES (%s)", self::getTableName(),
            implode(', ', $columns), implode(', ', $values));
        try {
            self::$connection->query($sql);
        } catch (Exception $e) {
            return false;
        }

        $this->id = self::$connection->lastInsertId();
        return $this;
    }
}