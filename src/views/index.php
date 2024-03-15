<?php
/**
 * Index View for scheduled tasks
 *
 * @var View $this
 * @var ActiveDataProvider $dataProvider
 * @var SchedulerTask $model
 */

use uzdevid\scheduler\models\SchedulerTask;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\Pjax;


$this->title = SchedulerTask::label(2);
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="scheduler-index">

    <h1><?= $this->title ?></h1>

    <div class="table-responsive">
        <?php Pjax::begin(); ?>
        <?= GridView::widget([
            'layout' => '{summary}{pager}{items}{pager}',
            'dataProvider' => $dataProvider,
            'pager' => [
                'class' => yii\widgets\LinkPager::class,
                'firstPageLabel' => Yii::t('app', 'First'),
                'lastPageLabel' => Yii::t('app', 'Last'),
            ],
            'columns' => [
                [
                    'attribute' => 'name',
                    'format' => 'raw',
                    'value' => static function ($t) {
                        return Html::a($t->name, ['update', 'id' => $t->id]);
                    }
                ],

                'name',
                'description',
                'schedule',
                'status'
            ],
        ]); ?>
        <?php Pjax::end(); ?>
    </div>
</div>
