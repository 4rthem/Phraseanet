<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class TOUTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    public function setUp()
    {
        parent::setUp();
        self::resetUsersRights(self::$DI['app'], self::$DI['user_alt2']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\TOU::displayTermsOfUse
     */
    public function testGetTOUNotAJAX()
    {
        self::$DI['client']->request('GET', '/prod/TOU/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\TOU::denyTermsOfUse
     */
    public function testGetTOUAJAX()
    {
        $this->XMLHTTPRequest('GET', '/prod/TOU/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\TOU::displayTermsOfUse
     */
    public function testDenyTOU()
    {
        $databoxes = self::$DI['app']->getDataboxes();
        $databox = array_shift($databoxes);
        self::$DI['app']['authentication']->setUser(self::$DI['user_alt2']);
        $this->XMLHTTPRequest('POST', '/prod/TOU/deny/'.$databox->get_sbas_id() .'/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response, $databoxes);

        foreach ($databox->get_collections() as $collection) {
            $this->assertFalse(self::$DI['app']->getAclForUser(self::$DI['user_alt2'])->has_access_to_base($collection->get_base_id()));
        }
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\TOU::denyTermsOfUse
     * @covers Alchemy\Phrasea\Controller\Prod\TOU::connect
     * @covers Alchemy\Phrasea\Controller\Prod\TOU::call
     */
    public function testDenyTOURequireAuthentication()
    {
        $databoxes = self::$DI['app']->getDataboxes();
        $databox = array_shift($databoxes);
        $this->logout(self::$DI['app']);
        self::$DI['client']->request('POST', '/prod/TOU/deny/'. $databox->get_sbas_id() .'/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        unset($databoxes);
    }
}
