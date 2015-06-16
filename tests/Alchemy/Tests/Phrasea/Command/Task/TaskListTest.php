<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\TaskList;

/**
 * @group functional
 * @group legacy
 */
class TaskListTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $output->expects($this->any())
            ->method('getFormatter')
            ->will($this->returnValue($this->getMock('Symfony\Component\Console\Formatter\OutputFormatterInterface')));

        self::$DI['cli']['monolog'] = self::$DI['cli']->share(function () {
            return $this->createMonologMock();
        });

        $command = new TaskList();
        $command->setContainer(self::$DI['cli']);

        $application = new \Symfony\Component\Console\Application();
        $application->add($command);

        $setupCommand = $application->find('task-manager:task:list');
        $setupCommand->run($input, $output);
    }
}
