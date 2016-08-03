<?php
/**
 * @var View     $this
 * @var DocTypes $model
 * @var array    $flTreeWidgetParam
 */

use docflow\models\DocTypes;
use docflow\widgets\FlTreeWidget;
use docflow\widgets\FlTreeWidgetWithLeaf;
use yii\bootstrap\Modal;
use yii\helpers\Html;

use yii\web\View;
use yii\widgets\DetailView;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('docflow', 'Statuses Doctypes'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

Modal::begin(
    [
        'id' => 'myModal',
        'header' => 'Выберите нового родителя',
        'footer' => implode(
            '',
            [
                Html::button(
                    'Назначить',
                    [
                        'id' => 'set-parent',
                        'class' => 'btn btn-success',
                        'data-is-ajax' => 'false'
                    ]
                ),
                Html::button(
                    'Закрыть',
                    [
                        'id' => 'modal-close',
                        'class' => 'btn btn-default',
                        'data-dismiss' => 'modal'
                    ]
                )
            ]
        )
    ]
);
echo FlTreeWidget::widget([
    'renderView' => 'flTreeModal',
    'base' => [
        'titleList' => 'Список документов'
    ],
    'widget' => [
        'source' => $flTreeWidgetParam['flTreeUrl'],
        'showCheckBox' => false
    ],
]);
Modal::end();
?>
<div class="statuses-doctypes-view">

    <h1><?php echo Html::encode($this->title) ?></h1>

    <p>
        <?php echo Html::a(
            Yii::t('docflow', 'Update'),
            ['update', 'doc' => $model->tag],
            ['class' => 'btn btn-primary']
        ); ?>
        <?php echo Html::a(
            Yii::t('docflow', 'Delete'),
            ['delete', 'doc' => $model->tag],
            [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => Yii::t('docflow', 'Are you sure you want to delete this item?'),
                    'method' => 'post',
                ],
            ]
        ); ?>
    </p>

    <?php echo DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'tag',
        ],
    ]) ?>

    <p>
        <?php echo Html::a(
            Yii::t('docflow', 'Create Statuses'),
            ['create-status', 'doc' => $model->tag],
            ['class' => 'btn btn-success']
        ) ?>
    </p>

    <?php echo FlTreeWidgetWithLeaf::widget([
        'base' => [
            'titleList' => $flTreeWidgetParam['titleList']
        ],
        'widget' => [
            'source' => $flTreeWidgetParam['flTreeUrl'],
            'showCheckBox' => false
        ]
    ]) ?>
</div>
