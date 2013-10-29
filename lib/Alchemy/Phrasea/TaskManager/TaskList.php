<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager;

use Alchemy\TaskManager\TaskListInterface;
use Symfony\Component\Process\ProcessBuilder;
use Entities\Task as TaskEntity;
use Repositories\TaskRepository;

class TaskList implements TaskListInterface
{
    private $repository;
    private $root;
    private $phpConf;
    private $phpExec;

    public function __construct(TaskRepository $repository, $root, $phpExec, $phpConf = null)
    {
        $this->repository = $repository;
        $this->root = $root;
        $this->phpConf = $phpConf;
        $this->phpExec = $phpExec;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh()
    {
        return array_map(array($this, 'entityToTask'), $this->repository->findActiveTasks());
    }

    public function entityToTask(TaskEntity $task)
    {
        $name = 'task #' . $task->getId() . ' ' . $task->getClassname();
        $arguments = array($this->phpExec);

        if ($this->phpConf) {
            $arguments[] = '-c';
            $arguments[] = $this->phpConf;
        }

        $arguments[] = '-f';
        $arguments[] = $this->root . '/bin/console';
        $arguments[] = '--';
        $arguments[] = '-q';
        $arguments[] = 'task:run';
        $arguments[] = $task->getId();
        $arguments[] = '--listen-signal';
        $arguments[] = '--max-duration';
        $arguments[] = '1800';
        $arguments[] = '--max-memory';
        $arguments[] = 128 << 20;

        $builder = ProcessBuilder::create($arguments);

        return new Task($name, $task->isSingleRun() ? 1 : INF, $builder->getProcess());
    }
}
