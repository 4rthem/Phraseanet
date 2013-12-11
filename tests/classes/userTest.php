<?php

class userTest extends PhraseanetPHPUnitAbstract
{
    public function testMail()
    {
        $this->assertFalse(User_Adapter::get_usr_id_from_email(self::$DI['app'], null));
        try {
            self::$DI['user']->set_email(null);

            $this->assertFalse(User_Adapter::get_usr_id_from_email(self::$DI['app'], null));
            self::$DI['user']->set_email('');
            $this->assertFalse(User_Adapter::get_usr_id_from_email(self::$DI['app'], null));
            self::$DI['user']->set_email('noone@example.com');
            $this->assertEquals(self::$DI['user']->get_id(), User_Adapter::get_usr_id_from_email(self::$DI['app'], 'noone@example.com'));
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
        try {

            self::$DI['user']->set_email('noonealt1@example.com');
            $this->fail('A user already got this address');
        } catch (Exception $e) {

        }
        $this->assertFalse(User_Adapter::get_usr_id_from_email(self::$DI['app'], null));
    }

    public function testDeleteSetMailToNullAndRemovesProviders()
    {
        try {
            $usrId = \User_Adapter::get_usr_id_from_login(self::$DI['app'], 'test_phpunit_providers');
            $user = \User_Adapter::getInstance($usrId, self::$DI['app']);
        } catch (\Exception $e) {
            $user = \User_Adapter::create(self::$DI['app'], 'test_phpunit_providers', 'any', null, false);
        }

        $provider = new Entities\UsrAuthProvider();
        $provider->setDistantId(12345);
        $provider->setProvider('custom-one');
        $provider->setUsrId($user->get_id());

        self::$DI['app']['EM']->persist($provider);
        self::$DI['app']['EM']->flush();

        $user->delete();

        $repo = self::$DI['app']['EM']->getRepository('Entities\UsrAuthProvider');
        $this->assertNull($repo->findWithProviderAndId('custom-one', 12345));
    }

    public function testDeleteSetMailToNullAndRemovesSessions()
    {
        try {
            $usrId = \User_Adapter::get_usr_id_from_login(self::$DI['app'], 'test_phpunit_sessions');
            $user = \User_Adapter::getInstance($usrId, self::$DI['app']);
        } catch (\Exception $e) {
            $user = \User_Adapter::create(self::$DI['app'], 'test_phpunit_sessions', 'any', null, false);
        }

        $session = new Entities\Session();
        $session
            ->setUsrId($user->get_id())
            ->setUserAgent('');

        self::$DI['app']['EM']->persist($session);
        self::$DI['app']['EM']->flush();

        $user->delete();

        $repo = self::$DI['app']['EM']->getRepository('Entities\Session');
        $this->assertCount(0, $repo->findByUser($user));
    }

    public function testGetPref()
    {
        $user = $this->get_user();

        $this->assertNull($user->getPrefs('lalala'));
        $this->assertSame('popo', $user->getPrefs('lalala', 'popo'));
        $this->assertSame(\User_Adapter::$def_values['editing_top_box'], $user->getPrefs('editing_top_box'));
    }

    public function testGetPrefWithACustomizedConf()
    {
        $data = isset(self::$DI['app']['phraseanet.configuration']['user-settings']) ? self::$DI['app']['phraseanet.configuration']['user-settings'] : null;

        self::$DI['app']['phraseanet.configuration']['user-settings'] = array(
            'images_per_page' => 42,
            'images_size'     => 666,
            'lalala'          => 'didou',
        );

        $user = $this->get_user();
        $user->setPrefs('images_per_page', 35);

        $user = new \User_Adapter($user->get_id(), self::$DI['app']);

        $this->assertNull($user->getPrefs('lalala'));
        $this->assertEquals(666, $user->getPrefs('images_size'));
        $this->assertEquals(35, $user->getPrefs('images_per_page'));
        $this->assertEquals(\User_Adapter::$def_values['editing_top_box'], $user->getPrefs('editing_top_box'));

        if (null === $data) {
            unset(self::$DI['app']['phraseanet.configuration']['user-settings']);
        } else {
            self::$DI['app']['phraseanet.configuration']['user-settings'] = $data;
        }
    }

    public function testSetPref()
    {
        $user = $this->get_user();

        $user->setPrefs('prout', 'pooop');
        $this->assertSame('pooop', $user->getPrefs('prout'));
    }

    public function testGetNotificationPref()
    {
        $user = $this->get_user();

        $this->assertSame('1', $user->get_notifications_preference(self::$DI['app'], 'eventsmanager_notify_push'));
    }

    public function testNotificationPref()
    {
        $user = $this->get_user();

        $this->assertSame('1', $user->get_notifications_preference(self::$DI['app'], 'eventsmanager_notify_push'));
        $user->set_notification_preference(self::$DI['app'], 'eventsmanager_notify_push', false);
        $this->assertSame('0', $user->get_notifications_preference(self::$DI['app'], 'eventsmanager_notify_push'));
        $user->set_notification_preference(self::$DI['app'], 'eventsmanager_notify_push', true);
        $this->assertSame('1', $user->get_notifications_preference(self::$DI['app'], 'eventsmanager_notify_push'));
    }

    private function get_user()
    {
        $usr_id = \User_Adapter::get_usr_id_from_login(self::$DI['app'], 'notif_ref_test');
        if ($usr_id) {
            $user = \User_Adapter::getInstance($usr_id, self::$DI['app']);
            $user->delete();
        }

        return \User_Adapter::create(self::$DI['app'], 'notif_ref_test', mt_rand(), null, false);
    }
}
