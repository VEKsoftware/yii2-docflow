<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 15:00
 *
 * @var array $buttons
 * @var array $detailViewConfig
 * @var array $sources
 * @var array $base
 */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

$sourceSimpleKeyExist = array_key_exists('flTreeWithSimpleUrl', $sources);

$buttonsExist = (!empty($buttons) && is_array($buttons));

$buttonUpdateExist = false;
$buttonDeleteExist = false;
$buttonUpExist = false;
$buttonDownExist = false;
$buttonLeftExist = false;
$buttonRightExist = false;

if ($buttonsExist) {
    $buttonUpdateExist = array_key_exists('update', $buttons);
    $buttonDeleteExist = array_key_exists('delete', $buttons);
    $buttonUpExist = array_key_exists('treeUp', $buttons);
    $buttonDownExist = array_key_exists('treeDown', $buttons);
    $buttonLeftExist = array_key_exists('treeLeft', $buttons);
    $buttonRightExist = array_key_exists('treeRight', $buttons);
}

?>
    <div class="statuses-view">
        <h1><?php echo Html::encode($base['title']) ?></h1>
        <p>
        <div class="row">
            <div class="col-xs-2 text-left">
                <?php if ($buttonsExist && $buttonUpdateExist): ?>
                    <?php echo Html::a(
                        $buttons['update']['name'],
                        $buttons['update']['url'],
                        ['class' => 'btn btn-primary']
                    ) ?>
                <?php endif; ?>
            </div>
            <div class="col-xs-8 text-center" id="actions-tree-buttons">
                <?php if ($buttonsExist && $buttonLeftExist): ?>
                    <?php echo Html::tag(
                        'div',
                        $buttons['treeLeft']['name'],
                        [
                            'name' => 'left-in-tree',
                            'data-href' => Url::toRoute($buttons['treeLeft']['url']),
                            'data-fl-tree-url' => Url::toRoute($sources['flTreeUrl']),
                            'data-name' => $base['nodeName'],
                            'class' => 'btn btn-primary glyphicon glyphicon-arrow-left'
                        ]
                    ) ?>
                <?php endif; ?>
                <?php if ($buttonsExist && $buttonUpExist): ?>
                    <?php echo Html::tag(
                        'div',
                        $buttons['treeUp']['name'],
                        [
                            'name' => 'up-in-tree',
                            'data-href' => Url::toRoute($buttons['treeUp']['url']),
                            'data-fl-tree-url' => Url::toRoute($sources['flTreeUrl']),
                            'data-name' => $base['nodeName'],
                            'class' => 'btn btn-primary glyphicon glyphicon-arrow-up'
                        ]
                    ) ?>
                <?php endif; ?>
                <?php if ($buttonsExist && $buttonDownExist): ?>
                    <?php echo Html::tag(
                        'div',
                        $buttons['treeDown']['name'],
                        [
                            'name' => 'down-in-tree',
                            'data-href' => Url::toRoute($buttons['treeDown']['url']),
                            'data-fl-tree-url' => Url::toRoute($sources['flTreeUrl']),
                            'data-name' => $base['nodeName'],
                            'class' => 'btn btn-primary glyphicon glyphicon-arrow-down'
                        ]
                    ) ?>
                <?php endif; ?>
                <?php if ($buttonsExist && $buttonRightExist): ?>
                    <?php echo Html::tag(
                        'div',
                        $buttons['treeRight']['name'],
                        [
                            'name' => 'right-in-tree',
                            'data-href' => Url::toRoute($buttons['treeRight']['url']),
                            'data-fl-tree-url' => Url::toRoute($sources['flTreeUrl']),
                            'data-name' => $base['nodeName'],
                            'class' => 'btn btn-primary glyphicon glyphicon-arrow-right'
                        ]
                    ) ?>
                <?php endif; ?>
            </div>
            <div class="col-xs-2 text-right">
                <?php if ($buttonsExist && $buttonDeleteExist): ?>
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
                <?php endif; ?>
            </div>
        </div>
        </p>
        <?php echo DetailView::widget($detailViewConfig) ?>
    </div>
<?php if ($sourceSimpleKeyExist): ?>
    <div class="statuses-index">
        <h3>
            <?php echo Html::encode($base['titleLink']) ?>
        </h3>
        <span id="simple-link-change-status"></span>
        <div id="tree-simple-link"></div>
    </div>
<?php endif; ?>
<?php
if ($sourceSimpleKeyExist) {
    $this->registerJs("var dataUrl = '" . Url::toRoute($sources['flTreeWithSimpleUrl']) . "'");
    $this->registerJs(
        <<<'JS'
        initFlTreeWithSimpleLinks(dataUrl);
JS
    );
}
