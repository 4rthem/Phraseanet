<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Tests\Repositories;

use Entities\User;

class UserRepositoryTest extends \PhraseanetPHPUnitAbstract
{
    public function testFindAdminsWithNoAdmins()
    {
        $user = new User();
        $user->setLogin('login');
        $user->setPassword('toto');
        $this->insertOneUser($user);
        $users = self::$DI['app']['EM']->getRepository('Entities\User')->findAdmins();
        $this->assertEquals(0, count($users));
    }

    public function testFindAdminsWithOneAdmin()
    {
        $user = new User();
        $user->setLogin('login');
        $user->setPassword('toto');
        $user->setAdmin(true);
        $this->insertOneUser($user);
        $users = self::$DI['app']['EM']->getRepository('Entities\User')->findAdmins();
        $this->assertEquals(1, count($users));
    }

    public function testFindAdminsWithOneAdminButTemplate()
    {
        $user = new User();
        $user->setLogin('login');
        $user->setPassword('toto');
        $user->setAdmin(true);
        
        $template = new User();
        $template->setLogin('logint');
        $template->setPassword('totot');
        
        $user->setModelOf($template);

        $users = self::$DI['app']['EM']->getRepository('Entities\User')->findAdmins();
        $this->assertEquals(0, count($users));
    }

    public function testFindAdminsWithOneAdminButDeleted()
    {
        $user = new User();
        $user->setLogin('login');
        $user->setPassword('toto');
        $user->setAdmin(true);
        $user->setDeleted(true);

        $users = self::$DI['app']['EM']->getRepository('Entities\User')->findAdmins();
        $this->assertEquals(0, count($users));
    }

    public function testFindByLogin()
    {
        $user = new User();
        $user->setLogin('login');
        $user->setPassword('toto');
        $this->insertOneUser($user);
        $user = self::$DI['app']['EM']->getRepository('Entities\User')->findByLogin('login');
        $this->assertInstanceOf('Entities\User', $user);
        $this->assertNull(self::$DI['app']['EM']->getRepository('Entities\User')->findByLogin('wrong-login'));
    }

    public function testFindUserByEmail()
    {
        $user = new User();
        $user->setLogin('login');
        $user->setPassword('toto');
        $user->setEmail('toto@toto.to');
        $this->insertOneUser($user);
        $user = self::$DI['app']['EM']->getRepository('Entities\User')->findByEmail('toto@toto.to');
        $this->assertInstanceOf('Entities\User', $user);
    }

    public function testFindUserByEmailButDeleted()
    {
        $user = new User();
        $user->setLogin('login');
        $user->setPassword('toto');
        $user->setEmail('toto@toto.to');
        $user->setDeleted(true);
        $this->insertOneUser($user);
        $user = self::$DI['app']['EM']->getRepository('Entities\User')->findByEmail('toto@toto.to');
        $this->assertNull($user);
    }

    public function testFindUserByEmailButNullEmail()
    {
        $user = new User();
        $user->setLogin('login');
        $user->setPassword('toto');
        $user->setEmail(null);
        $user->setDeleted(true);
        $this->insertOneUser($user);
        $user = self::$DI['app']['EM']->getRepository('Entities\User')->findByEmail('toto@toto.to');
        $this->assertNull($user);
    }
}
