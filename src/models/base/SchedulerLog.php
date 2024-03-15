<?php

namespace uzdevid\scheduler\models\base;

use Yii;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the base-model class for table "scheduler_log".
 *
 * @property integer $id
 * @property integer $scheduler_task_id
 * @property string $started_at
 * @property string $ended_at
 * @property string $output
 * @property integer $error
 *
 * @property \uzdevid\scheduler\models\SchedulerTask $schedulerTask
 */
class SchedulerLog extends ActiveRecord {
    /**
     * @inheritdoc
     */
    public static function tableName(): string {
        return 'scheduler_log';
    }

    /**
     * @param int $n
     *
     * @return string
     */
    public static function label($n = 1): string {
        return Yii::t('app', '{n, plural, =1{Scheduler Log} other{Scheduler Logs}}', ['n' => $n]);
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
            [['scheduler_task_id', 'output'], 'required'],
            [['scheduler_task_id', 'error'], 'integer'],
            [['started_at', 'ended_at'], 'safe'],
            [['output'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array {
        return [
            'id' => Yii::t('app', 'ID'),
            'scheduler_task_id' => Yii::t('app', 'Scheduler Task ID'),
            'started_at' => Yii::t('app', 'Started At'),
            'ended_at' => Yii::t('app', 'Ended At'),
            'output' => Yii::t('app', 'Output'),
            'error' => Yii::t('app', 'Error'),
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getSchedulerTask(): ActiveQuery {
        return $this->hasOne(\uzdevid\scheduler\models\SchedulerTask::class, ['id' => 'scheduler_task_id']);
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
            'scheduler_task_id' => $this->scheduler_task_id,
            'error' => $this->error,
        ]);

        $query->andFilterWhere(['like', 'started_at', $this->started_at])
            ->andFilterWhere(['like', 'ended_at', $this->ended_at])
            ->andFilterWhere(['like', 'output', $this->output]);

        return $dataProvider;
    }
}

