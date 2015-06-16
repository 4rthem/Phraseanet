<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\SchedulerResumeTasks;

/**
 * @group functional
 * @group legacy
 */
class SchedulerResumeTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        self::$DI['cli']['task-manager.status'] = $this->getMockBuilder('Alchemy\Phrasea\TaskManager\TaskManagerStatus')
            ->disableOriginalConstructor()
            ->getMock();
        self::$DI['cli']['task-manager.status']->expects($this->once())
            ->method('start');

        self::$DI['cli']['monolog'] = self::$DI['cli']->share(function () {
            return $this->createMonologMock();
        });

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new SchedulerResumeTasks();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
