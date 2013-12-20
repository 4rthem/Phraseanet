<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\RegistrationServiceProvider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\RegistrationServiceProvider
 */
class RegistrationServiceProvidertest extends \PhraseanetTestCase
{
    private $fields;

    public function setUp()
    {
        parent::setUp();
        $this->fields = self::$DI['app']['conf']->get('registration-fields', []);
    }

    public function tearDown()
    {
        self::$DI['app']['conf']->set('registration-fields', $this->fields);
        parent::tearDown();
    }

    public function testSameInstanceShouldBereturnedEveryTime()
    {
        self::$DI['app']->register(new RegistrationServiceProvider());
        self::$DI['app']['conf']->set('registration-fields', ['plop']);

        $this->assertEquals(['plop'], self::$DI['app']['registration.fields']);
        $this->assertEquals(['plop'], self::$DI['app']['registration.fields']);

        $this->assertInternalType('array', self::$DI['app']['registration.optional-fields']);
    }
}
