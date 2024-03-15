<?php

namespace uzdevid\scheduler;

use uzdevid\scheduler\events\TaskEvent;
use uzdevid\scheduler\models\SchedulerLog;
use uzdevid\scheduler\models\SchedulerTask;
use Yii;
use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;

/**
 * Class TaskRunner
 *
 * @package uzdevid\scheduler
 * @property Task $task
 */
class TaskRunner extends Component {

    /**
     * Indicates whether an error occured during the executing of the task.
     *
     * @var bool
     */
    public $error;

    /**
     * The task that will be executed.
     *
     * @var Task
     */
    private $_task;

    /**
     * @var SchedulerLog
     */
    private $_log;

    /**
     * @var bool
     */
    private $running = false;

    /**
     * @param Task $task
     */
    public function setTask(Task $task) {
        $this->_task = $task;
    }

    /**
     * @return Task
     */
    public function getTask() {
        return $this->_task;
    }

    /**
     * @param SchedulerLog $log
     */
    public function setLog($log) {
        $this->_log = $log;
    }

    /**
     * @return SchedulerLog
     */
    public function getLog() {
        return $this->_log;
    }

    /**
     * @param bool $forceRun
     */
    public function runTask($forceRun = false) {
        $task = $this->getTask();

        if ($task->shouldRun($forceRun)) {
            $event = new TaskEvent([
                'task' => $task,
                'success' => true,
            ]);
            $this->trigger(Task::EVENT_BEFORE_RUN, $event);
            if (!$event->cancel) {
                $task->start();
                ob_start();
                try {
                    $this->running = true;
                    $this->shutdownHandler();
                    $task->run();
                    $this->running = false;
                    $output = ob_get_contents();
                    ob_end_clean();
                    $this->log($output);
                    $task->stop();
                } catch (\Exception $e) {
                    $this->running = false;
                    $task->exception = $e;
                    $event->exception = $e;
                    $event->success = false;
                    $this->handleError($e);
                }
                $this->trigger(Task::EVENT_AFTER_RUN, $event);
            }
        }
        $task->getModel()->save();
    }

    /**
     * If the yii error handler has been overridden with `\uzdevid\scheduler\ErrorHandler`,
     * pass it this instance of TaskRunner, so it can update the state of tasks in the event of a fatal error.
     */
    public function shutdownHandler() {
        $errorHandler = Yii::$app->getErrorHandler();

        if ($errorHandler instanceof ErrorHandler) {
            Yii::$app->getErrorHandler()->taskRunner = $this;
        }
    }

    /**
     * @param \Exception|ErrorException|Exception $exception
     */
    public function handleError(\Exception $exception) {
        echo sprintf(
            "%s: %s \n\n Stack Trace: \n %s",
            method_exists($exception, 'getName') ? $exception->getName() : get_class($exception),
            $exception->getMessage(),
            $exception->getTraceAsString()
        );

        // if the failed task was mid transaction, rollback so we can save.
        if (null !== ($tx = Yii::$app->db->getTransaction())) {
            $tx->rollBack();
        }

        $output = '';

        if (ob_get_length() > 0) {
            $output = ob_get_contents();
            ob_end_clean();
        }

        $this->error = true;
        $this->log($output);
        $this->getTask()->getModel()->status_id = SchedulerTask::STATUS_ERROR;
        $this->getTask()->stop();

        $this->getTask()->trigger(Task::EVENT_FAILURE, new TaskEvent([
            'task' => $this->getTask(),
            'output' => $output,
            'success' => false,
            'exception' => $exception,
        ]));
    }

    /**
     * @param string $output
     */
    public function log($output) {
        $model = $this->getTask()->getModel();
        $log = $this->getLog();
        $log->started_at = $model->started_at;
        $log->ended_at = date('Y-m-d H:i:s');
        $log->error = $this->error ? 1 : 0;
        $log->output = $output;
        $log->scheduler_task_id = $model->id;
        $log->save(false);
    }
}
