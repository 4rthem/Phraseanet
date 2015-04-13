<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Form\TaskForm;
use Alchemy\Phrasea\Model\Entities\Task;
use Alchemy\Phrasea\TaskManager\LiveInformation;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Process\Process;

class TaskManagerController extends Controller
{
    public function startScheduler(Application $app, Request $request)
    {
        $app['task-manager.status']->start();

        $cmdLine = sprintf(
            '%s %s %s',
            $app['conf']->get(['main', 'binaries', 'php_binary']),
            realpath(__DIR__ . '/../../../../../bin/console'),
            'task-manager:scheduler:run'
        );

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addListener(KernelEvents::TERMINATE, function () use ($cmdLine) {
            $process = new Process($cmdLine);
            $process->setTimeout(0);
            $process->disableOutput();
            set_time_limit(0);
            ignore_user_abort(true);

            $process->run();
        }, -1000);

        return $app->redirectPath('admin_tasks_list');
    }

    public function stopScheduler(Application $app, Request $request)
    {
        $app['task-manager.status']->stop();

        /** @var LiveInformation $info */
        $info = $app['task-manager.live-information'];
        $data = $info->getManager();
        if (null !== $pid = $data['process-id']) {
            if (substr(php_uname(), 0, 7) == "Windows"){
                exec(sprintf('TaskKill /PID %d', $pid));
            } else {
                exec(sprintf('kill %d', $pid));
            }
        }

        return $app->redirectPath('admin_tasks_list');
    }

    public function getRoot(Application $app, Request $request)
    {
        return $app->redirectPath('admin_tasks_list');
    }

    public function getLiveInformation(Application $app, Request $request)
    {
        if (false === $app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        if ($request->getRequestFormat() !== "json") {
            $app->abort(406, 'Only JSON format is accepted.');
        }

        $tasks = [];
        foreach ($app['repo.tasks']->findAll() as $task) {
            $tasks[$task->getId()] = $app['task-manager.live-information']->getTask($task);
        }

        return $app->json([
            'manager' => $app['task-manager.live-information']->getManager(),
            'tasks' => $tasks
        ]);
    }

    public function getScheduler(Application $app, Request $request)
    {
        if (false === $app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        if ($request->getRequestFormat() !== "json") {
            $app->abort(406, 'Only JSON format is accepted.');
        }

        return $app->json([
            'name' => $app->trans('Task Scheduler'),
            'configuration' => $app['task-manager.status']->getStatus(),
            'urls' => [
                'start' => $app->path('admin_tasks_scheduler_start'),
                'stop' => $app->path('admin_tasks_scheduler_stop'),
                'log' => $app->path('admin_tasks_scheduler_log'),
            ]
        ]);
    }

    public function getTasks(Application $app, Request $request)
    {
        $tasks = [];

        foreach ($app['repo.tasks']->findAll() as $task) {
            $tasks[] = [
                'id' => $task->getId(),
                'name' => $task->getName(),
                'status' => $task->getStatus()
            ];
        }

        if ($request->getRequestFormat() === "json") {
            foreach ($tasks as $k => $task) {
                $tasks[$k]['urls'] = $this->getTaskResourceUrls($app, $task['id']);
            }

            return $app->json($tasks);
        }

        return $app['twig']->render('admin/task-manager/index.html.twig', [
            'available_jobs' => $app['task-manager.available-jobs'],
            'tasks' => $tasks,
            'scheduler' => [
                'id'   => null,
                'name' => $app->trans('Task Scheduler'),
                'status' => $app['task-manager.status']->getStatus(),
            ]
        ]);
    }

    public function postCreateTask(Application $app, Request $request)
    {
        try {
            $job = $app['task-manager.job-factory']->create($request->request->get('job-name'));
        } catch (InvalidArgumentException $e) {
            $app->abort(400, $e->getMessage());
        }

        $task = $app['manipulator.task']->create(
            $job->getName(),
            $job->getJobId(),
            $job->getEditor()->getDefaultSettings($app['conf']),
            $job->getEditor()->getDefaultPeriod()
        );

        return $app->redirectPath('admin_tasks_task_show', ['task' => $task->getId()]);
    }

    public function getSchedulerLog(Application $app, Request $request)
    {
        $logFile = $app['task-manager.log-file.factory']->forManager();
        if ($request->query->get('clr')) {
            $logFile->clear();
        }

        return $app['twig']->render('admin/task-manager/log.html.twig', [
            'logfile' => $logFile,
            'logname' => 'Scheduler',
        ]);
    }

    public function getTaskLog(Application $app, Request $request, Task $task)
    {
        $logFile = $app['task-manager.log-file.factory']->forTask($task);
        if ($request->query->get('clr')) {
            $logFile->clear();
        }

        return $app['twig']->render('admin/task-manager/log.html.twig', [
            'logfile' => $logFile,
            'logname' => sprintf('%s (task id %d)', $task->getName(), $task->getId()),
        ]);
    }

    public function postTaskDelete(Application $app, Request $request, Task $task)
    {
        if (false === $app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        $app['manipulator.task']->delete($task);

        return $app->redirectPath('admin_tasks_list');
    }

    public function postStartTask(Application $app, Request $request, Task $task)
    {
        if (false === $app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        $app['manipulator.task']->start($task);

        return $app->redirectPath('admin_tasks_list');
    }

    public function postStopTask(Application $app, Request $request, Task $task)
    {
        if (false === $app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        $app['manipulator.task']->stop($task);

        return $app->redirectPath('admin_tasks_list');
    }

    public function postResetCrashes(Application $app, Request $request, Task $task)
    {
        $app['manipulator.task']->resetCrashes($task);

        return $app->json(['success' => true]);
    }

    public function postSaveTask(Application $app, Request $request, Task $task)
    {
        if (false === $app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        if (!$this->doValidateXML($request->request->get('settings'))) {
            return $app->json(['success' => false, 'message' => sprintf('Unable to load XML %s', $request->request->get('xml'))]);
        }

        $form = $app->form(new TaskForm());
        $form->setData($task);
        $form->bind($request);
        if ($form->isValid()) {
            $app['manipulator.task']->update($task);

            return $app->json(['success' => true]);
        }

        return $app->json([
            'success' => false,
            'message' => implode("\n", $form->getErrors())
        ]);
    }

    public function postTaskFacility(Application $app, Request $request, Task $task)
    {
        return $app['task-manager.job-factory']
            ->create($task->getJobId())
            ->getEditor()
            ->facility($app, $request);
    }

    public function postXMLFromForm(Application $app, Request $request, Task $task)
    {
        return $app['task-manager.job-factory']
            ->create($task->getJobId())
            ->getEditor()
            ->updateXMLWithRequest($request);
    }

    public function getTask(Application $app, Request $request, Task $task)
    {
        if (false === $app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        if ('json' === $request->getContentType()) {
            return $app->json(array_replace([
                'id' => $task->getId(),
                'name' => $task->getName(),
                'urls' => $this->getTaskResourceUrls($app, $task->getId())
            ],
                $app['task-manager.live-information']->getTask($task)
            ));
        }

        $editor = $app['task-manager.job-factory']
            ->create($task->getJobId())
            ->getEditor();

        $form = $app->form(new TaskForm());
        $form->setData($task);

        return $app['twig']->render($editor->getTemplatePath(), [
            'task' => $task,
            'form' => $form->createView(),
            'view' => 'XML',
        ]);
    }

    public function validateXML(Application $app, Request $request)
    {
        if (false === $app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        return $app->json(['success' => $this->doValidateXML($request->getContent())]);
    }

    private function doValidateXML($string)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->strictErrorChecking = true;

        return (Boolean) @$dom->loadXML($string);
    }

    private function getTaskResourceUrls(Application $app, $taskId)
    {
        return [
            'show' => $app->path('admin_tasks_task_show', ['task' => $taskId]),
            'start' => $app->path('admin_tasks_task_start', ['task' => $taskId]),
            'stop' => $app->path('admin_tasks_task_stop', ['task' => $taskId]),
            'delete' => $app->path('admin_tasks_task_delete', ['task' => $taskId]),
            'log' => $app->path('admin_tasks_task_log', ['task' => $taskId]),
        ];
    }
}
