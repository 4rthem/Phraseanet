<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class Feed_CollectionTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     *
     * @var Feed_Adapter
     */
    protected static $object;
    protected static $title = 'Feed test';
    protected static $subtitle = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?';

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $appbox = self::$application['phraseanet.appbox'];
        $auth = new Session_Authentication_None(self::$user);
        self::$application->openAccount($auth);
        self::$object = Feed_Adapter::create(self::$application, self::$user, self::$title, self::$subtitle);
        self::$object->set_public(true);
    }

    public static function tearDownAfterClass()
    {
        self::$object->delete();
        parent::tearDownAfterClass();
    }

    public function testLoad_all()
    {
        $appbox = self::$application['phraseanet.appbox'];
        $coll = Feed_Collection::load_all(self::$application, self::$user);

        foreach ($coll->get_feeds() as $feed) {
            $this->assertInstanceOf('Feed_Adapter', $feed);
        }
    }

    public function testGet_feeds()
    {
        $appbox = self::$application['phraseanet.appbox'];
        $coll = Feed_Collection::load_public_feeds(self::$application);

        foreach ($coll->get_feeds() as $feed) {
            $this->assertInstanceOf('Feed_Adapter', $feed);
        }
    }

    public function testGet_aggregate()
    {
        $appbox = self::$application['phraseanet.appbox'];
        $coll = Feed_Collection::load_public_feeds(self::$application);

        $this->assertInstanceOf('Feed_Aggregate', $coll->get_aggregate());
    }

    public function testLoad_public_feeds()
    {
        $appbox = self::$application['phraseanet.appbox'];
        $coll = Feed_Collection::load_public_feeds(self::$application);

        foreach ($coll->get_feeds() as $feed) {
            $this->assertTrue($feed->is_public());
        }
    }
}
