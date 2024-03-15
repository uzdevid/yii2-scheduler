<?php

namespace uzdevid\scheduler;

use ReflectionException;
use uzdevid\scheduler\models\SchedulerLog;
use uzdevid\scheduler\models\SchedulerTask;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\console\Application;
use yii\helpers\ArrayHelper;

/**
 * Class Module
 *
 * @package uzdevid\scheduler
 */
class Module extends \yii\base\Module implements BootstrapInterface {
    /**
     * Path where task files can be found in the application structure.
     *
     * @var string
     */
    public $taskPath = '@app/tasks';

    /**
     * Namespace that tasks use.
     *
     * @var string
     */
    public $taskNameSpace = 'app\tasks';

    /**
     * Bootstrap the console controllers.
     *
     * @param \yii\base\Application $app
     */
    public function bootstrap($app) {
        Yii::setAlias('@scheduler', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src');

        if ($app instanceof Application && !isset($app->controllerMap[$this->id])) {
            $app->controllerMap[$this->id] = [
                'class' => 'uzdevid\scheduler\console\SchedulerController',
            ];
        }
    }

    /**
     * Scans the taskPath for any task files, if any are found it attempts to load them,
     * creates a new instance of each class and appends it to an array, which it returns.
     *
     * @return Task[]
     * @throws ErrorException
     */
    public function getTasks() {
        $dir = Yii::getAlias($this->taskPath);

        if (!is_readable($dir)) {
            throw new ErrorException("Task directory ($dir) does not exist");
        }

        $files = array_diff(scandir($dir), array('..', '.'));
        $tasks = [];

        foreach ($files as $fileName) {
            // strip out the file extension to derive the class name
            $className = preg_replace('/\.[^.]*$/', '', $fileName);

            // validate class name
            if (preg_match('/^[a-zA-Z0-9_]*Task$/', $className)) {
                $tasks[] = $this->loadTask($className);
            }
        }

        $this->cleanTasks($tasks);

        return $tasks;
    }

    /**
     * Removes any records of tasks that no longer exist.
     *
     * @param Task[] $tasks
     */
    public function cleanTasks($tasks) {
        $currentTasks = ArrayHelper::map($tasks, function ($task) {
            return $task->getName();
        }, 'description');

        foreach (SchedulerTask::find()->indexBy('name')->all() as $name => $task) {
            /* @var SchedulerTask $task */
            if (!array_key_exists($name, $currentTasks)) {
                SchedulerLog::deleteAll(['scheduler_task_id' => $task->id]);
                $task->delete();
            }
        }
    }

    /**
     * Given the className of a task, it will return a new instance of that task.
     * If the task doesn't exist, null will be returned.
     *
     * @param $className
     *
     * @return null|object
     * @throws InvalidConfigException
     */
    public function loadTask($className) {
        $className = implode('\\', [$this->taskNameSpace, $className]);

        try {
            $task = Yii::createObject($className);
            $task->setModel(SchedulerTask::createTaskModel($task));
        } catch (ReflectionException $e) {
            $task = null;
        }

        return $task;
    }


}
