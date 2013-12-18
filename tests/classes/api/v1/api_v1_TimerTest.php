<?php

use Alchemy\Phrasea\Core\PhraseaEvents;
use Symfony\Component\EventDispatcher\Event;

class API_V1_TimerTest extends \PhraseanetTestCase
{
    public function testRegister()
    {
        $start = microtime(true);

        $app = $this->loadApp();
        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->expects($this->exactly(9))
            ->method('addListener');
        $app['dispatcher'] = $dispatcher;
        $app->register(new API_V1_Timer());

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $app['api.timers']);
        $this->assertGreaterThan($start, $app['api.timers.start']);
    }

    public function testTriggerEvent()
    {
        $app = $this->loadApp();
        $app->register(new API_V1_Timer());

        $app['dispatcher']->dispatch(PhraseaEvents::API_RESULT, new Event());

        $timers = $app['api.timers']->toArray();

        $this->assertCount(1, $timers);

        $timer = array_pop($timers);

        $this->assertArrayHasKey('name', $timer);
        $this->assertArrayHasKey('memory', $timer);
        $this->assertArrayHasKey('time', $timer);

        $this->assertEquals(PhraseaEvents::API_RESULT, $timer['name']);
        $this->assertGreaterThan(0, $timer['time']);
        $this->assertGreaterThan(400000, $timer['memory']);
    }
}
