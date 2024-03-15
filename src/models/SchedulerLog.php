<?php

namespace uzdevid\scheduler\models;

use DateTime;
use Exception;
use Yii;
use yii\base\InvalidConfigException;

/**
 * This is the model class for table "scheduler_log".
 *
 * @property-read mixed $duration
 */
class SchedulerLog extends base\SchedulerLog {
    /**
     * @return string|null
     * @throws InvalidConfigException
     */
    public function __toString() {
        /** @var string $time */
        $time = Yii::$app->formatter->asDatetime($this->started_at);

        return $time;
    }

    /**
     * @throws Exception
     */
    public function getDuration(): string {
        $start = new DateTime($this->started_at);
        $end = new DateTime($this->ended_at);
        return $start->diff($end)->format('%hh %im %Ss');
    }

}
