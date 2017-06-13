# light-orm

Dead simple ORM implementation created to play around with PHP metaprogramming abilities. Supports basic CRUD operations with database records. 

Supports MySQL.

## Requirements

- PHP 5.4+
- PDO extension


## Setup

```php
<?php
$connection = new PDO("adapter:host=host;dbname=dbname", 'username', 'password');
LightORM::establishConnection($connection);
```

## Basic usage

```php
<?php
// define model
class Books extends LightORM {}

// create instance and save it to DB
$book = Books::create(['name' => '1984', 'author' => 'George Orwell']); // => object(Books) ...

// create instance and play with it
$book = new Book();
$book->name = 'A Clockwork Orange';
$book->author = 'Anthony Burgess';
$book->save(); // => true
$book->ID(); // => 2

// find records
Books::find(1); // => object(Books) ...
Books::findByName('1984'); // => object(Books) ...

// destroy records
$book->destroy();
$book->save(); // throws exception
```

## Set different table name or primary key
```php
<?php
class Books extends LightORM {
    private $tableName = 'entries';
    private $primaryKey = 'no';
}
```

## Field presence validation

```php
<?php
class Books extends LightORM {
    private $_required = ['name'];
}

$book = new Book('author' => 'Someone');
$book->save(); // => false
$book->errors(); // = array([0] => 'Name can`t be blank');
```

## Running tests

- Make sure `PHP 5.6+` is installed
- Install PHPUnit
```
composer install
```
- Copy `database.config.example.php` to `database.config.php`
- Edit database credentials as needed (should at least have access to `CREATE TABLE` for given user/database)
- Run
```
phpunit test/
```
