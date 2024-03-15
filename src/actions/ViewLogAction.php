<?php

namespace uzdevid\scheduler\actions;

use uzdevid\scheduler\models\SchedulerLog;
use yii\base\Action;
use yii\web\HttpException;

/**
 * Class UpdateAction
 *
 * @package uzdevid\scheduler\actions
 */
class ViewLogAction extends Action {
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
     * @throws HttpException
     */
    public function run($id) {
        $model = SchedulerLog::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'The requested page does not exist.');
        }

        return $this->controller->render($this->view ?: $this->id, [
            'model' => $model,
        ]);
    }
}
