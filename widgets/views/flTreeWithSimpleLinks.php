<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 15:00
 *
 * @var string $dataUrl
 * @var array  $buttons
 * @var string $title
 * @var string $titleLink
 * @var array  $dataViewConfig
 * @var string $flTreeWithSimpleUrl
 * @var string $flTreeUrl
 * @var string $nodeName
 */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

?>
    <div class="statuses-view">
        <h1><?php echo Html::encode($title) ?></h1>
        <p>
        <div class="row">
            <div class="col-xs-2 text-left">
                <?php echo Html::a(
                    $buttons['update']['name'],
                    $buttons['update']['url'],
                    ['class' => 'btn btn-primary']
                ) ?>
            </div>
            <div class="col-xs-8 text-center" id="actions-tree-buttons">
                <?php echo Html::tag(
                    'div',
                    $buttons['treeLeft']['name'],
                    [
                        'name' => 'left-in-tree',
                        'data-href' => Url::toRoute($buttons['treeLeft']['url']),
                        'data-fl-tree-url' => $flTreeUrl,
                        'data-name' => $nodeName,
                        'class' => 'btn btn-primary glyphicon glyphicon-arrow-left'
                    ]
                ) ?>
                <?php echo Html::tag(
                    'div',
                    $buttons['treeUp']['name'],
                    [
                        'name' => 'up-in-tree',
                        'data-href' => Url::toRoute($buttons['treeUp']['url']),
                        'data-fl-tree-url' => $flTreeUrl,
                        'data-name' => $nodeName,
                        'class' => 'btn btn-primary glyphicon glyphicon-arrow-up'
                    ]
                ) ?>
                <?php echo Html::tag(
                    'div',
                    $buttons['treeDown']['name'],
                    [
                        'name' => 'down-in-tree',
                        'data-href' => Url::toRoute($buttons['treeDown']['url']),
                        'data-fl-tree-url' => $flTreeUrl,
                        'data-name' => $nodeName,
                        'class' => 'btn btn-primary glyphicon glyphicon-arrow-down'
                    ]
                ) ?>
                <?php echo Html::tag(
                    'div',
                    $buttons['treeRight']['name'],
                    [
                        'name' => 'right-in-tree',
                        'data-href' => Url::toRoute($buttons['treeRight']['url']),
                        'data-fl-tree-url' => $flTreeUrl,
                        'data-name' => $nodeName,
                        'class' => 'btn btn-primary glyphicon glyphicon-arrow-right'
                    ]
                ) ?>
            </div>
            <div class="col-xs-2 text-right">
                <?php echo Html::a(
                    $buttons['delete']['name'],
                    $buttons['delete']['url'],
                    [
                        'class' => 'btn btn-danger',
                        'data' => [
                            'confirm' => Yii::t('docflow', 'Are you sure you want to delete this item?'),
                            'method' => 'post',
                        ],
                    ]
                ) ?>
            </div>
        </div>
        </p>
        <?php echo DetailView::widget($dataViewConfig) ?>
    </div>
    <div class="statuses-index">
        <h3>
            <?php echo Html::encode($titleLink) ?>
        </h3>
        <span id="simple-link-change-status"></span>
        <div id="tree-simple-link"></div>
    </div>
<?php

$this->registerJs("var dataUrl = '$flTreeWithSimpleUrl'");
$this->registerJs(<<<'JS'
    initFlTreeWithSimpleLinks(dataUrl);
JS
);
