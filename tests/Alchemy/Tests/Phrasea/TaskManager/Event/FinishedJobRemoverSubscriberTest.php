<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Event;

use Alchemy\Phrasea\TaskManager\Event\FinishedJobRemoverSubscriber;
use Alchemy\Phrasea\TaskManager\Event\JobFinishedEvent;
use Entities\Task;

class FinishedJobRemoverSubscriberTest extends \PhraseanetPHPUnitAbstract
{
    public function testOnJobFinish()
    {
        $task = new Task();
        $task
            ->setName('task')
            ->setClassname('Alchemy\Phrasea\TaskManager\Job\NullJob');
        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();
        $taskId = $task->getId();

        $subscriber = new FinishedJobRemoverSubscriber(self::$DI['app']['EM']);
        $subscriber->onJobFinish(new JobFinishedEvent($task));

        $this->assertNull(self::$DI['app']['EM']->getRepository('Entities\Task')->find($taskId));
    }
}
