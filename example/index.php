<?php
require 'database.config.php';
require 'Schema.php';
require 'Users.php';

// Establish connection
$connection = new PDO("mysql:host={$db['host']};dbname={$db['name']}", $db['user'], $db['password']);
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize schema
$schema = new Schema($connection);
try {
    $schema->createDatabase($db['name']);
    $schema->createUsersTable('users');
} catch (Exception $e) {
    print $e->getMessage();
}

// Play around with model
Users::establishConnection($connection);
$user = Users::create([
    'name' => 'John Doe',
    'email' => 'johndoe@example.com',
    'birthdate' => '1991-01-01 00:00:00',
    'sex' => 'M'
]);

print_r($user);

/*
Users Object
(
    [_destroy:protected] =>
    [attributes:protected] => Array
    (
        [name] => John Doe
        [email] => johndoe@example.com
        [birthdate] => 1991-01-01 00:00:00
        [sex] => M
        [id] => 1
    )
)
*/

$user->name = 'Jane';
$user->sex = 'F';
$user->save();

print_r($user);

/*
Users Object
(
    [_destroy:protected] =>
    [attributes:protected] => Array
    (
        [name] => Jane
        [email] => johndoe@example.com
        [birthdate] => 1991-01-01 00:00:00
        [sex] => F
        [id] => 1
    )
)
*/

$id = $user->id;
$user->destroy();

try {
    Users::find($id);
} catch (Exception $e) {
    print $e->getMessage();
}

/*
Record not found in database.
 */
