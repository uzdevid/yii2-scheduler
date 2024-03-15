<?php

namespace uzdevid\scheduler\actions;

use uzdevid\scheduler\models\SchedulerTask;
use yii\base\Action;

/**
 * Class IndexAction
 *
 * @package uzdevid\scheduler\actions
 */
class IndexAction extends Action {
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
    public function run() {
        $model = new SchedulerTask();
        $dataProvider = $model->search($_GET);

        return $this->controller->render($this->view ?: $this->id, [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }
}
