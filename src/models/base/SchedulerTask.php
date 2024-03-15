<?php

namespace uzdevid\scheduler\models\base;

use Yii;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the base-model class for table "scheduler_task".
 *
 * @property integer $id
 * @property string $name
 * @property string $schedule
 * @property string $description
 * @property integer $status_id
 * @property string $started_at
 * @property string $last_run
 * @property string $next_run
 * @property integer $active
 *
 * @property \uzdevid\scheduler\models\SchedulerLog[] $schedulerLogs
 */
class SchedulerTask extends ActiveRecord {
    /**
     * @inheritdoc
     */
    public static function tableName(): string {
        return 'scheduler_task';
    }

    /**
     * @param int $n
     *
     * @return string
     */
    public static function label($n = 1): string {
        return Yii::t('app', '{n, plural, =1{Scheduler Task} other{Scheduler Tasks}}', ['n' => $n]);
    }

    /**
     * @return string
     */
    public function __toString() {
        return (string)$this->id;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array {
        return [
            [['name', 'schedule', 'description', 'status_id'], 'required'],
            [['description'], 'string'],
            [['status_id', 'active'], 'integer'],
            [['started_at', 'last_run', 'next_run'], 'safe'],
            [['name', 'schedule'], 'string', 'max' => 45],
            [['name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'schedule' => Yii::t('app', 'Schedule'),
            'description' => Yii::t('app', 'Description'),
            'status_id' => Yii::t('app', 'Status ID'),
            'started_at' => Yii::t('app', 'Started At'),
            'last_run' => Yii::t('app', 'Last Run'),
            'next_run' => Yii::t('app', 'Next Run'),
            'active' => Yii::t('app', 'Active'),
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getSchedulerLogs(): ActiveQuery {
        return $this->hasMany(\uzdevid\scheduler\models\SchedulerLog::class, ['scheduled_task_id' => 'id']);
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param null $params
     *
     * @return ActiveDataProvider
     * @throws InvalidConfigException
     */
    public function search($params = null): ActiveDataProvider {
        $formName = $this->formName();
        $params = !$params ? Yii::$app->request->get($formName, array()) : $params;
        $query = self::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $this->load($params, $formName);

        $query->andFilterWhere([
            'id' => $this->id,
            'status_id' => $this->status_id,
            'active' => $this->active,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'schedule', $this->schedule])
            ->andFilterWhere(['like', 'description', $this->description])
            ->andFilterWhere(['like', 'started_at', $this->started_at])
            ->andFilterWhere(['like', 'last_run', $this->last_run])
            ->andFilterWhere(['like', 'next_run', $this->next_run]);

        return $dataProvider;
    }
}

