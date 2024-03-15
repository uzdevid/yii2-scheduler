<?php


namespace uzdevid\scheduler;

use Cron\CronExpression;
use DateTime;
use Exception;
use uzdevid\scheduler\events\TaskEvent;
use uzdevid\scheduler\models\SchedulerTask;
use Yii;
use yii\base\Component;
use yii\base\Event;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * Class Task
 *
 * @package uzdevid\scheduler
 *
 * @property-read string $name
 * @property null|SchedulerTask $model
 */
abstract class Task extends Component {
    public const EVENT_BEFORE_RUN = 'TaskBeforeRun';
    public const EVENT_AFTER_RUN = 'TaskAfterRun';
    public const EVENT_FAILURE = ' TaskFailure';

    /**
     * @var bool create a database lock to ensure the task only runs once
     */
    public bool $databaseLock = true;

    /**
     * Exception raised during run (if any)
     *
     * @var Exception|null
     */
    public Exception|null $exception;

    /**
     * Brief description of the task.
     *
     * @var String
     */
    public string $description;

    /**
     * The cron expression that determines how often this task should run.
     *
     * @var String
     */
    public string $schedule;

    /**
     * Active flag allows you to set the task to inactive (meaning it will not run)
     *
     * @var bool
     */
    public bool $active = true;

    /**
     * How many seconds after due date to wait until the task becomes overdue and is re-run.
     * This should be set to at least 2x the amount of time the task takes to run as the task will be restarted.
     *
     * @var int
     */
    public int $overdueThreshold = 3600;

    /**
     * @var null|SchedulerTask
     */
    private SchedulerTask|null $_model;

    public function init(): void {
        parent::init();

        $lockName = 'TaskLock' . Inflector::camelize(self::className());
        Event::on(self::class, self::EVENT_BEFORE_RUN, static function ($event) use ($lockName) {
            /* @var $event TaskEvent */
            $db = Yii::$app->db;
            $result = $db->createCommand("GET_LOCK(:lockname, 1)", [':lockname' => $lockName])->queryScalar();

            if (!$result) {
                $event->cancel = true;
            }
        });
        Event::on(self::class, self::EVENT_AFTER_RUN, static function ($event) use ($lockName) {
            $db = Yii::$app->db;
            $db->createCommand("RELEASE_LOCK(:lockname, 1)", [':lockname' => $lockName])->queryScalar();
        });
    }

    /**
     * The main method that gets invoked whenever a task is ran, any errors that occur
     * inside this method will be captured by the TaskRunner and logged against the task.
     *
     * @return mixed
     */
    abstract public function run(): mixed;

    /**
     * @param DateTime|string $currentTime
     *
     * @return string
     */
    public function getNextRunDate(DateTime|string $currentTime = 'now'): string {
        return CronExpression::factory($this->schedule)
            ->getNextRunDate($currentTime)
            ->format('Y-m-d H:i:s');
    }

    /**
     * @return string
     */
    public function getName(): string {
        return StringHelper::basename(get_class($this));
    }

    /**
     * @param SchedulerTask $model
     */
    public function setModel(SchedulerTask $model): void {
        $this->_model = $model;
    }

    /**
     * @return SchedulerTask
     */
    public function getModel(): SchedulerTask|null {
        return $this->_model;
    }

    /**
     * @param $str
     */
    public function writeLine($str): void {
        echo $str . PHP_EOL;
    }

    /**
     * Mark the task as started
     */
    public function start(): void {
        $model = $this->getModel();
        $model->started_at = date('Y-m-d H:i:s');
        $model->save(false);
    }

    /**
     * Mark the task as stopped.
     */
    public function stop() {
        $model = $this->getModel();
        $model->last_run = $model->started_at;
        $model->next_run = $this->getNextRunDate();
        $model->started_at = null;
        $model->save(false);
    }

    /**
     * @param bool $forceRun
     *
     * @return bool
     */
    public function shouldRun($forceRun = false): bool {
        $model = $this->getModel();
        $isDue = in_array($model->status_id, [SchedulerTask::STATUS_DUE, SchedulerTask::STATUS_OVERDUE, SchedulerTask::STATUS_ERROR], true);
        $isRunning = $model->status_id === SchedulerTask::STATUS_RUNNING;
        $overdue = false;
        if ((strtotime($model->started_at ?? 0) + $this->overdueThreshold) <= time()) {
            $overdue = true;
        }

        return ($model->active && ((!$isRunning && ($isDue || $forceRun)) || ($isRunning && $overdue)));
    }

}
