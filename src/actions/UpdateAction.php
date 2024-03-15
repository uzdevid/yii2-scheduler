<?php

namespace uzdevid\scheduler\actions;

use uzdevid\scheduler\models\SchedulerLog;
use uzdevid\scheduler\models\SchedulerTask;
use Yii;
use yii\base\Action;
use yii\web\HttpException;

/**
 * Class UpdateAction
 *
 * @package uzdevid\scheduler\actions
 */
class UpdateAction extends Action {
    /**
     * @var string the view file to be rendered. If not set, it will take the value of [[id]].
     * That means, if you name the action as "index" in "SchedulerController", then the view name
     * would be "index", and the corresponding view file would be "views/scheduler/index.php".
     */
    public $view;

    /**
     * Runs the action
     *
     * @return string result content
     */
    public function run($id) {
        $model = SchedulerTask::findOne($id);
        $request = Yii::$app->getRequest();

        if (!$model) {
            throw new HttpException(404, 'The requested page does not exist.');
        }

        if ($model->load($request->post())) {
            $model->save();
        }

        $logModel = new SchedulerLog();
        $logModel->scheduler_task_id = $model->id;
        $logDataProvider = $logModel->search($_GET);

        return $this->controller->render($this->view ?: $this->id, [
            'model' => $model,
            'logModel' => $logModel,
            'logDataProvider' => $logDataProvider,
        ]);
    }
}
