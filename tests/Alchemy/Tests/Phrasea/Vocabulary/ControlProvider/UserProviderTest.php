<?php

namespace Alchemy\Tests\Phrasea\Vocabulary\ControlProvider;

use Alchemy\Phrasea\Vocabulary\ControlProvider\UserProvider;

class UserProviderTest extends \PhraseanetTestCase
{
    /**
     * @var UserProvider
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new UserProvider(self::$DI['app']);
    }

    /**
     * Verify that Type is scalar and that the classname is like {Type}Provider
     */
    public function testGetType()
    {
        $type = $this->object->getType();

        $this->assertTrue(is_scalar($type));

        $data = explode('\\', get_class($this->object));
        $classname = array_pop($data);

        $this->assertEquals($classname, $type . 'Provider');
    }

    public function testGetName()
    {
        $this->assertTrue(is_scalar($this->object->getName()));
    }

    public function testFind()
    {
        $this->setConnectionEnvironment('prod');
        self::$DI['user'];

        $results = $this->object->find('BABE', self::$DI['user'], self::$DI['collection']->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);

        $results = $this->object->find(self::$DI['user']->getEmail(), self::$DI['user'], self::$DI['collection']->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);
        $this->assertTrue($results->count() > 0);

        $results = $this->object->find(self::$DI['user']->getFirstName(), self::$DI['user'], self::$DI['collection']->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);
        $this->assertTrue($results->count() > 0);

        $results = $this->object->find(self::$DI['user']->getLastName(), self::$DI['user'], self::$DI['collection']->get_databox());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $results);
        $this->assertTrue($results->count() > 0);
    }

    public function testValidate()
    {
        $this->assertFalse($this->object->validate(-200));
        $this->assertFalse($this->object->validate('A'));
        $this->assertTrue($this->object->validate(self::$DI['user']->getId()));
    }

    public function testGetValue()
    {
        try {
            $this->object->getValue(-200);
            $this->fail('Should raise an exception');
        } catch (\Exception $e) {

        }

        try {
            $this->object->getValue('A');
            $this->fail('Should raise an exception');
        } catch (\Exception $e) {

        }

        $this->assertEquals(self::$DI['user']->getDisplayName(), $this->object->getValue(self::$DI['user']->getId()));
    }
}
