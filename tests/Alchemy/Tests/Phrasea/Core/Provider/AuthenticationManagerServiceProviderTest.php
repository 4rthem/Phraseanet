<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\Provider\TokensServiceProvider;
use Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Silex\Application;

/**
 * @covers Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider
 */
class AuthenticationManagerServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication',
                'Alchemy\\Phrasea\\Authentication\\Authenticator',
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.token-validator',
                'Alchemy\Phrasea\Authentication\Token\TokenValidator'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.persistent-manager',
                'Alchemy\Phrasea\Authentication\PersistentCookie\Manager'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.suggestion-finder',
                'Alchemy\Phrasea\Authentication\SuggestionFinder'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.providers.factory',
                'Alchemy\Phrasea\Authentication\Provider\Factory'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.providers',
                'Alchemy\Phrasea\Authentication\ProvidersCollection'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.manager',
                'Alchemy\Phrasea\Authentication\Manager'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'auth.password-encoder',
                'Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'auth.old-password-encoder',
                'Alchemy\Phrasea\Authentication\Phrasea\OldPasswordEncoder'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'auth.native.failure-manager',
                'Alchemy\Phrasea\Authentication\Phrasea\FailureManager'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'auth.native',
                'Alchemy\Phrasea\Authentication\Phrasea\PasswordAuthenticationInterface'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider',
                'authentication.providers.account-creator',
                'Alchemy\Phrasea\Authentication\AccountCreator'
            ),
        );
    }

    public function testFailureManagerAttemptsConfiguration()
    {
        $app = $this->loadApp();
        $app['root.path'] = __DIR__ . '/../../../../../../';
        $app->register(new TokensServiceProvider());
        $app->register(new AuthenticationManagerServiceProvider());
        $app->register(new ConfigurationServiceProvider());

        $app['conf']->set(['authentication', 'captcha', 'trials-before-display'], 42);

        $app['EM'] = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $app['recaptcha'] = $this->getMockBuilder('Neutron\ReCaptcha\ReCaptcha')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $app['auth.native.failure-manager'];
        $this->assertEquals(42, $manager->getTrials());
    }

    public function testFailureAccountCreator()
    {
        $app = new PhraseaApplication();
        $app->register(new ConfigurationServiceProvider());

        $app['conf']->set(['authentication', 'auto-create'], ['templates' => []]);

        $app['authentication.providers.account-creator'];
    }

    public function testAuthNativeWithCaptchaEnabled()
    {
        $app = $this->loadApp();
        $app['root.path'] = __DIR__ . '/../../../../../../';
        $app->register(new AuthenticationManagerServiceProvider());
        $app->register(new ConfigurationServiceProvider());
        $app['phraseanet.appbox'] = self::$DI['app']['phraseanet.appbox'];

        $app['conf']->set(['authentication', 'captcha'], ['enabled' => true]);

        $app['EM'] = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $app['recaptcha'] = $this->getMockBuilder('Neutron\ReCaptcha\ReCaptcha')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertInstanceOf('Alchemy\Phrasea\Authentication\Phrasea\FailureHandledNativeAuthentication', $app['auth.native']);
    }

    public function testAuthNativeWithCaptchaDisabled()
    {
        $app = $this->loadApp();
        $app['root.path'] = __DIR__ . '/../../../../../../';
        $app->register(new AuthenticationManagerServiceProvider());
        $app->register(new ConfigurationServiceProvider());
        $app['phraseanet.appbox'] = self::$DI['app']['phraseanet.appbox'];

        $app['conf']->set(['authentication', 'captcha'], ['enabled' => false]);

        $app['EM'] = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $app['recaptcha'] = $this->getMockBuilder('Neutron\ReCaptcha\ReCaptcha')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertInstanceOf('Alchemy\Phrasea\Authentication\Phrasea\NativeAuthentication', $app['auth.native']);
    }

    public function testAccountCreator()
    {
        $app = new PhraseaApplication();

        $random = $app['tokens'];
        $template1 = $user = self::$DI['app']['manipulator.user']->createUser('template' . $random->generatePassword(), $random->generatePassword());
        $template1->set_template(self::$DI['user']);
        $template2 = self::$DI['app']['manipulator.user']->createUser('template' . $random->generatePassword(), $random->generatePassword());
        $template2->set_template(self::$DI['user']);

        $app['conf']->set(['authentication', 'auto-create'], ['templates' => [$template1->get_id(), $template2->get_login()]]);

        $this->assertEquals(array($template1, $template2), $app['authentication.providers.account-creator']->getTemplates());

        $template1->delete();
        $template2->delete();
    }
}
