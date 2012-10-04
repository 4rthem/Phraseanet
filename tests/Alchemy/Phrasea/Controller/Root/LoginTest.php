<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Symfony\Component\HttpKernel\Client;

class LoginTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::login
     * @covers \Alchemy\Phrasea\Controller\Root\Login::connect
     */
    public function testLoginAlreadyAthenticated()
    {
        self::$DI['client']->request('GET', '/login/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/prod/', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::login
     */
    public function testLoginRedirectPostLog()
    {
        self::$DI['app']->closeAccount();

        self::$DI['client']->request('GET', '/login/', array('postlog'  => '1', 'redirect' => 'prod'));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/logout/?redirect=prod', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::login
     * @dataProvider errorAndNoticeMsgProvider
     */
    public function testLoginError($warning, $notice)
    {
        self::$DI['app']->closeAccount();

        self::$DI['client']->request('GET', '/login/', array(
            'error'  => $warning,
            'notice' => $notice
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function errorAndNoticeMsgProvider()
    {
        return array(
            array('auth', 'ok'),
            array('maintenance', 'already'),
            array('no-connection', 'mail-sent'),
            array('captcha', 'register-ok'),
            array('mail-not-confirmed', 'register-ok-wait'),
            array('no-base', 'password-update-ok'),
            array('session', 'no-register-available')
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailNoCode()
    {
        self::$DI['client']->request('GET', '/login/register-confirm/');
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?redirect=/prod&error=code-not-found', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailWrongCode()
    {
        self::$DI['client']->request('GET', '/login/register-confirm/', array('code'    => '34dT0k3n'));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?redirect=/prod&error=token-not-found', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailUserNotFound()
    {
        $email = $this->generateEmail();
        $token = \random::getUrlToken(self::$DI['app'], \random::TYPE_EMAIL, 0, null, $email);
        self::$DI['client']->request('GET', '/login/register-confirm/', array('code'    => $token));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?redirect=/prod&error=user-not-found', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailUnlocked()
    {
        $email = $this->generateEmail();
        $token = \random::getUrlToken(self::$DI['app'], \random::TYPE_EMAIL, self::$DI['user']->get_id(), null, $email);

        self::$DI['user']->set_mail_locked(false);

        self::$DI['client']->request('GET', '/login/register-confirm/', array('code'    => $token));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?redirect=prod&notice=already', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMail()
    {
        $email = $this->generateEmail();
        $appboxRegister = new \appbox_register(self::$DI['app']['phraseanet.appbox']);
        $token = \random::getUrlToken(self::$DI['app'], \random::TYPE_EMAIL, self::$DI['user']->get_id(), null, $email);

        self::$DI['user']->set_mail_locked(true);
        $this->deleteRequest();
        $appboxRegister->add_request(self::$DI['user'], self::$DI['collection']);
        self::$DI['client']->request('GET', '/login/register-confirm/', array('code'    => $token));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?redirect=prod&notice=confirm-ok-wait', $response->headers->get('location'));
        $this->assertFalse(self::$DI['user']->get_mail_locked());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::registerConfirm
     */
    public function testRegisterConfirmMailNoCollAwait()
    {
        $email = $this->generateEmail();
        $token = \random::getUrlToken(self::$DI['app'], \random::TYPE_EMAIL, self::$DI['user']->get_id(), null, $email);

        self::$DI['user']->set_mail_locked(true);

        $this->deleteRequest();

        self::$DI['client']->request('GET', '/login/register-confirm/', array('code'    => $token));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());

        $this->assertEquals('/login/?redirect=prod&notice=confirm-ok', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordInvalidEmail()
    {
        self::$DI['client']->request('POST', '/login/forgot-password/', array('mail'    => 'invalid.email.com'));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/forgot-password/?error=invalidmail', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordUnknowEmail()
    {
        self::$DI['client']->request('POST', '/login/forgot-password/', array('mail'    => 'invalid_email@test.com'));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/forgot-password/?error=noaccount', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     */
    public function testRenewPasswordMail()
    {
        self::$DI['client']->request('POST', '/login/forgot-password/', array('mail'    => self::$DI['user']->get_email()));
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/forgot-password/?sent=ok', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     * @dataProvider passwordProvider
     */
    public function testRenewPasswordBadArguments($password, $passwordConfirm, $redirect)
    {
        self::$DI['client']->request('POST', '/login/forgot-password/', array(
            'token'                 => '1Cx6Z7',
            'form_password'         => $password,
            'form_password_confirm' => $passwordConfirm
            )
        );
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals($redirect, $response->headers->get('location'));
    }

    public function testRenewPasswordBadToken()
    {
        self::$DI['client']->request('POST', '/login/forgot-password/', array(
            'token'                 => 'badToken',
            'form_password'         => 'password',
            'form_password_confirm' => 'password'
            )
        );
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/forgot-password/?error=token', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::renewPassword
     * @dataProvider passwordProvider
     */
    public function testRenewPassword()
    {
        $token = \random::getUrlToken(self::$DI['app'], \random::TYPE_PASSWORD, self::$DI['user']->get_id());

        self::$DI['client']->request('POST', '/login/forgot-password/', array(
            'token'                 => $token,
            'form_password'         => 'password',
            'form_password_confirm' => 'password'
            )
        );
        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?notice=password-update-ok', $response->headers->get('location'));
    }

    public function passwordProvider()
    {
        return array(
            array('password', 'password_not_identical', '/login/forgot-password/?pass-error=pass-match'),
            array('min', 'min', '/login/forgot-password/?pass-error=pass-short'),
            array('in valid password', 'in valid password', '/login/forgot-password/?pass-error=pass-invalid'),
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::displayForgotPasswordForm
     */
    public function testGetForgotPasswordSendMsg()
    {
        self::$DI['client']->request('GET', '/login/forgot-password/', array(
            'sent' => 'ok',
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::displayForgotPasswordForm
     */
    public function testGetForgotBadToken()
    {
        $crawler = self::$DI['client']->request('GET', '/login/forgot-password/', array(
            'token' => 'one-token'
            ));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals(1, $crawler->filter('.alert-error')->count());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::displayForgotPasswordForm
     * @dataProvider errorMessageProvider
     */
    public function testGetForgotPasswordErrorMsg($errorMsg)
    {
        $crawler = self::$DI['client']->request('GET', '/login/forgot-password/', array(
            'error' => $errorMsg
            ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals(1, $crawler->filter('.alert-error')->count());
    }

    public function errorMessageProvider()
    {
        return array(
            array('invalidmail'),
            array('mailserver'),
            array('noaccount'),
            array('mail'),
            array('token'),
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::displayForgotPasswordForm
     * @dataProvider badPasswordMsgProvider
     */
    public function testGetForgotPasswordBadPassword($msg)
    {
        self::$DI['client']->request('GET', '/login/forgot-password/', array(
            'pass-error' => $msg,
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function badPasswordMsgProvider()
    {
        return array(
            array('pass-match'),
            array('pass-short'),
            array('pass-invalid'),
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::displayRegisterForm
     * @covers \Alchemy\Phrasea\Controller\Root\Login::getRegisterFieldConfiguration
     * @dataProvider fieldErrorProvider
     */
    public function testGetRegister($error)
    {
        self::$DI['client']->request('GET', '/login/register/', array(
            'needed' => array(
                'field_name' => $error,
            )
        ));

        /**
         * @todo change this
         */
        $login = new \login();
        if ( ! $login->register_enabled(self::$DI['app'])) {
            $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        } else {
            $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        }
    }

    public function fieldErrorProvider()
    {
        return array(
            array('required-field'),
            array('pass-match'),
            array('pass-short'),
            array('pass-invalid'),
            array('email-invalid'),
            array('login-short'),
            array('login-mail-exists'),
            array('user-mail-exists'),
            array('no-collections'),
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::register
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testPostRegisterBadRequest()
    {
        self::$DI['client']->request('POST', '/login/register/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::register
     * @dataProvider parametersProvider
     */
    public function testPostRegisterbadArguments($parameters)
    {
        self::$DI['client']->request('POST', '/login/register/', $parameters);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    public function parametersProvider()
    {
        return array(
            array(array(//required field
                    "form_login"            => '',
                    "form_password"         => 'password',
                    "form_password_confirm" => 'password',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//password mismatch
                    "form_login"            => 'login',
                    "form_password"         => 'password',
                    "form_password_confirm" => 'passwordmismatch',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//password tooshort
                    "form_login"            => 'login',
                    "form_password"         => 'min',
                    "form_password_confirm" => 'min',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//password invalid
                    "form_login"            => 'login',
                    "form_password"         => 'invalid pass word',
                    "form_password_confirm" => 'invalid pass word',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//email invalid
                    "form_login"            => 'login',
                    "form_password"         => 'password',
                    "form_password_confirm" => 'password',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//login exists
                    "form_login"            => 'test_phpunit',
                    "form_password"         => 'invalid pass word',
                    "form_password_confirm" => 'invalid pass word',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//mails exists
                    "form_login"            => 'login',
                    "form_password"         => 'invalid pass word',
                    "form_password_confirm" => 'noone@example.com',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            )),
            array(array(//no demands
                    "form_login"            => 'login',
                    "form_password"         => 'invalid pass word',
                    "form_password_confirm" => 'email@email.com',
                    "form_gender"           => 'M',
                    "form_lastname"         => 'lastname',
                    "form_firstname"        => 'firstname',
                    "form_email"            => 'email@email.com',
                    "form_job"              => 'job',
                    "form_company"          => 'company',
                    "form_activity"         => 'activity',
                    "form_phone"            => 'phone',
                    "form_fax"              => 'fax',
                    "form_address"          => 'adress',
                    "form_zip"              => 'zip',
                    "form_geonameid"        => 'geoname_id',
                    "demand"                => array()
            ))
        );
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::register
     */
    public function testPostRegister()
    {
        $bases = array();

        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $bases[] = $collection->get_base_id();
            }
        }

        $login = \random::generatePassword();
        $email = $login . '@google.com';

        self::$DI['client']->request('POST', '/login/register/', array(
            "form_login"            => $login,
            "form_password"         => 'password',
            "form_password_confirm" => 'password',
            "form_gender"           => 'M',
            "form_lastname"         => 'lastname',
            "form_firstname"        => 'firstname',
            "form_email"            => $email,
            "form_job"              => 'job',
            "form_company"          => 'company',
            "form_activity"         => 'activity',
            "form_phone"            => 'phone',
            "form_fax"              => 'fax',
            "form_address"          => 'adress',
            "form_zip"              => 'zip',
            "form_geonameid"        => 'geoname_id',
            "demand"                => $bases
        ));

        if ( ! $userId = \User_Adapter::get_usr_id_from_login(self::$DI['app'], $login)) {
            $this->fail('User not created');
        }

        $user = new User_Adapter((int) $userId, self::$DI['app']);

        $user->delete();

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertEquals('/login/?notice=mail-sent', self::$DI['client']->getResponse()->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::logout
     */
    public function testGetLogout()
    {
        $this->assertTrue(self::$DI['app']->isAuthenticated());
        self::$DI['client']->request('GET', '/login/logout/', array('app' => 'prod'));
        $this->assertFalse(self::$DI['app']->isAuthenticated());

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::sendConfirmMail
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testSendConfirmMailBadRequest()
    {
        self::$DI['client']->request('GET', '/login/send-mail-confirm/');
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::sendConfirmMail
     */
    public function testSendConfirmMail()
    {
        self::$DI['client']->request('GET', '/login/send-mail-confirm/', array('usr_id' => self::$DI['user']->get_id()));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?notice=mail-sent', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::sendConfirmMail
     */
    public function testSendConfirmMailWrongUser()
    {
        self::$DI['client']->request('GET', '/login/send-mail-confirm/', array('usr_id' => 0));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/?error=user-not-found', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testAuthenticate()
    {
        $password = \random::generatePassword();
        $login = self::$DI['app']['phraseanet.user']->get_login();
        self::$DI['app']['phraseanet.user']->set_password($password);
        self::$DI['app']->closeAccount();
        self::$DI['client'] = new Client(self::$DI['app'], array());
        $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC, self::$DI['app']);
        self::$DI['client']->request('POST', '/login/authenticate/', array(
            'login' => $login,
            'pwd'   => $password
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegExp('/^\/prod\/$/', self::$DI['client']->getResponse()->headers->get('Location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testBadAuthenticate()
    {
        self::$DI['app']->closeAccount();
        self::$DI['client']->request('POST', '/login/authenticate/', array(
            'login' => self::$DI['user']->get_login(),
            'pwd'   => 'test'
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegexp('/error=auth/', self::$DI['client']->getResponse()->headers->get('location'));
        $this->assertFalse(self::$DI['app']->isAuthenticated());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testMailLockedAuthenticate()
    {
        self::$DI['app']->closeAccount();
        $password = \random::generatePassword();
        self::$DI['user']->set_mail_locked(true);
        self::$DI['client']->request('POST', '/login/authenticate/', array(
            'login' => self::$DI['user']->get_login(),
            'pwd'   => $password
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegexp('/error=mail-not-confirmed/', self::$DI['client']->getResponse()->headers->get('location'));
        $this->assertFalse(self::$DI['app']->isAuthenticated());
        self::$DI['user']->set_mail_locked(false);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Login::authenticate
     */
    public function testAuthenticateUnavailable()
    {
        self::$DI['app']->closeAccount();
        $password = \random::generatePassword();
        self::$DI['app']['phraseanet.registry']->set('GV_maintenance', true , \registry::TYPE_BOOLEAN);

        self::$DI['client'] = new Client(self::$DI['app'], array());

        self::$DI['client']->request('POST', '/login/authenticate/', array(
            'login' => self::$DI['user']->get_login(),
            'pwd'   => $password
        ));
        self::$DI['app']['phraseanet.registry']->set('GV_maintenance', false, \registry::TYPE_BOOLEAN);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegexp('/error=maintenance/', self::$DI['client']->getResponse()->headers->get('location'));
        $this->assertFalse(self::$DI['app']->isAuthenticated());

    }

    /**
     * Delete inscription demand made by the current authenticathed user
     * @return void
     */
    private function deleteRequest()
    {
        $sql = "DELETE FROM demand WHERE usr_id = :usr_id";
        $stmt = self::$DI['app']['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => self::$DI['user']->get_id()));
        $stmt->closeCursor();
    }

    /**
     * Generate a new valid email adress
     * @return string
     */
    private function generateEmail()
    {
        return \random::generatePassword() . '_email@email.com';
    }
}
