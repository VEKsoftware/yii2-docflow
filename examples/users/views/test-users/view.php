<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 12.07.16
 * Time: 16:14
 *
 * @var array $flTreeWidgetParam
 */

use docflow\widgets\FlTreeWidget;
use docflow\widgets\FlTreeWidgetWithLeaf;
use yii\bootstrap\Modal;
use yii\helpers\Html;

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

echo FlTreeWidgetWithLeaf::widget([
    'base' => [
        'titleList' => $flTreeWidgetParam['titleList']
    ],
    'widget' => [
        'source' => $flTreeWidgetParam['flTreeUrl'],
        'showCheckBox' => false
    ]
]);
