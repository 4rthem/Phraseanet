<?php

namespace Alchemy\Tests\Phrasea\Controller\Client;

use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;

class RootTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Root::connect
     * @covers Alchemy\Phrasea\Controller\Client\Root::call
     * @covers Alchemy\Phrasea\Controller\Client\Root::getClient
     * @covers Alchemy\Phrasea\Controller\Client\Root::getDefaultClientStartPage
     * @covers Alchemy\Phrasea\Controller\Client\Root::getQueryStartPage
     * @covers Alchemy\Phrasea\Controller\Client\Root::getHelpStartPage
     * @covers Alchemy\Phrasea\Controller\Client\Root::getPublicationStartPage
     * @covers Alchemy\Phrasea\Controller\Client\Root::getGridProperty
     * @covers Alchemy\Phrasea\Controller\Client\Root::getDocumentStorageAccess
     * @covers Alchemy\Phrasea\Controller\Client\Root::getTabSetup
     * @covers Alchemy\Phrasea\Controller\Client\Root::getCssFile
     */
    public function testGetClient()
    {
        if (!extension_loaded('phrasea2')) {
            $this->markTestSkipped('Phrasea2 is required for this test');
        }

        $this->authenticate(self::$DI['app']);
        self::$DI['client']->request("GET", "/client/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Root::getClientLanguage
     */
    public function testGetLanguage()
    {
        self::$DI['client']->request("GET", "/client/language/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Root::getClientPublications
     */
    public function testGetPublications()
    {
        self::$DI['client']->request("GET", "/client/publications/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Root::getClientHelp
     */
    public function testGetClientHelp()
    {
        self::$DI['client']->request("GET", "/client/help/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Client\Root::query
     * @covers Alchemy\Phrasea\Controller\Client\Root::buildQueryFromRequest
     */
    public function testExecuteQuery()
    {
        if (!extension_loaded('phrasea2')) {
            $this->markTestSkipped('Phrasea2 is required for this test');
        }

        self::$DI['app']['manipulator.user'] = $this->getMockBuilder('Alchemy\Phrasea\Model\Manipulator\UserManipulator')
            ->setConstructorArgs([self::$DI['app']['model.user-manager'], self::$DI['app']['auth.password-encoder'], self::$DI['app']['geonames.connector'], self::$DI['app']['repo.users'], self::$DI['app']['random.low']])
            ->setMethods(['logQuery'])
            ->getMock();

        self::$DI['app']['manipulator.user']->expects($this->once())->method('logQuery');

        $queryParameters = [];
        $queryParameters["mod"] = self::$DI['app']['settings']->getUserSetting(self::$DI['user'], 'client_view', '3X6');
        $queryParameters["bas"] = array_keys(self::$DI['app']['acl']->get(self::$DI['user'])->get_granted_base());
        $queryParameters["qry"] = self::$DI['app']['settings']->getUserSetting(self::$DI['user'], 'start_page_query', 'all');
        $queryParameters["pag"] = 0;
        $queryParameters["search_type"] = SearchEngineOptions::RECORD_RECORD;
        $queryParameters["qryAdv"] = '';
        $queryParameters["opAdv"] = [];
        $queryParameters["status"] = [];
        $queryParameters["recordtype"] = SearchEngineOptions::TYPE_ALL;
        $queryParameters["sort"] = '';
        $queryParameters["infield"] = [];
        $queryParameters["ord"] = SearchEngineOptions::SORT_MODE_DESC;

        self::$DI['client']->request("POST", "/client/query/", $queryParameters);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
}
