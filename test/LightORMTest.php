<?php

use PHPUnit\Framework\TestCase;

include(dirname(__FILE__).'/../LightORM.php');
include('DatabaseTest.php');

class Users extends LightORM {
    protected static $_required = ['name'];
}

class LightORMTest extends TestCase
{
    protected static $connection;

    protected static $schemaLoader;

    protected $userAttrs = [
        'name' => 'Pablo',
        'email' => 'pablo@mail.net',
        'birthdate' => '1970-01-01',
        'sex' => 'M'
    ];

    public static function setUpBeforeClass() {
        DatabaseTest::setUp();
        Users::establishConnection(DatabaseTest::$connection);
    }

    public static function tearDownAfterClass()
    {
        DatabaseTest::clear();
    }

    public function setUp() {
        if (!isset($this->user)) {
            $this->user = Users::create($this->userAttrs);
        }
    }

    public function testGetPrimaryKey() {
        $this->assertEquals(Users::getPrimaryKey(), 'id');
    }

    public function testCreateUser() {
        $this->assertInstanceOf('Users', $this->user);
        $this->assertNotEmpty($this->user->id);
    }

    public function testCreateUserWithId() {
        $id = rand(100, 1000);
        $user = Users::create($this->userAttrs + ['id' => $id]);
        $this->assertEquals($user->id, $id);
    }

    /**
     * @expectedException Exception
     */
    public function testPresenceValidation() {
        $attrs = $this->userAttrs;
        unset($attrs['name']);
        $user = Users::create($attrs);
        $this->expectException(Exception::class);
        $this->assertFalse($user->save());
        $this->assertNotEmpty($user->errors());
    }

    public function testFindUser() {
        $found = Users::find($this->user->id);
        $this->assertInstanceOf('Users', $found);
        $this->assertEquals($this->user->reload(), $found);
    }

    public function testFindUserByName() {
        $found = Users::findByName($this->user->name);
        $this->assertInstanceOf('Users', $found);
        $this->assertEquals($this->user->name, $found->name);
    }

    public function testAttributeSetter() {
        $name = 'Alan';
        $this->user->name = $name;
        $this->user->save();
        $this->assertEquals($this->user->name, $name);
    }

    public function testAttributeGetter() {
        $this->assertEquals($this->user->name, $this->userAttrs['name']);
    }

    public function testAttributesGetLostOnReload() {
        $name = 'Alan';
        $this->user->name = $name;
        $this->user->reload();
        $this->assertNotEquals($this->user->name, $name);
    }

    /**
     * @expectedException Exception
     */
    public function testDestroyUser() {
        $id = $this->user->id;
        $this->user->destroy();
        Users::find($id);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Record not found in database.');
        $this->assertTrue($this->user->_destroy);
        $this->assertEmpty($this->user->id);
    }

    /**
     * @expectedException Exception
     */
    public function testDestroyedUserMutating() {
        $this->user->destroy();
        $this->user->name = 'Pablo';
        $this->user->save();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Record not found in database.');
    }

    public function testAssignAttributes() {
        $this->user->assignAttributes([
            'name' => 'Ellie',
            'sex' => 'F'
        ]);
        $this->assertEquals($this->user->name, 'Ellie');
        $this->assertEquals($this->user->sex, 'F');
    }
}
