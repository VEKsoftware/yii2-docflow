<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 15:00
 *
 * @var array $buttons
 * @var array $detailViewConfig
 * @var array $widget
 * @var array $base
 * @var View  $this
 */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\DetailView;

$widgetExist = (!empty($widget) && is_array($widget) && array_key_exists('source', $widget));

$buttonsExist = (!empty($buttons) && is_array($buttons));

$buttonUpdateExist = false;
$buttonDeleteExist = false;
$buttonUpExist = false;
$buttonDownExist = false;
$buttonLeftExist = false;
$buttonRightExist = false;
$buttonSetParentExist = false;

if ($buttonsExist) {
    $buttonUpdateExist = array_key_exists('update', $buttons);
    $buttonDeleteExist = array_key_exists('delete', $buttons);
    $buttonUpExist = array_key_exists('treeUp', $buttons);
    $buttonDownExist = array_key_exists('treeDown', $buttons);
    $buttonLeftExist = array_key_exists('treeLeft', $buttons);
    $buttonRightExist = array_key_exists('treeRight', $buttons);
    $buttonSetParentExist = array_key_exists('setParent', $buttons);
}

?>
    <div class="statuses-view">
        <h1><?php echo Html::encode($base['title']) ?></h1>
        <p>
        <div class="row">
            <?php if ($buttonsExist && $buttonUpdateExist): ?>
                <div class="col-xs-2 text-left">
                    <?php echo Html::a(
                        $buttons['update']['name'],
                        $buttons['update']['url'],
                        ['class' => 'btn btn-primary']
                    ) ?>
                </div>
            <?php endif; ?>
            <?php if ($buttonsExist): ?>
                <div class="col-xs-8 text-center" id="actions-tree-buttons">
                    <?php if ($buttonLeftExist): ?>
                        <?php echo Html::tag(
                            'div',
                            $buttons['treeLeft']['name'],
                            [
                                'name' => 'left-in-tree',
                                'data-href' => Url::toRoute($buttons['treeLeft']['url']),
                                'data-fl-tree-url' => Url::toRoute($base['renderTree']),
                                'data-name' => $base['nodeName'],
                                'class' => 'btn btn-primary glyphicon glyphicon-arrow-left'
                            ]
                        ) ?>
                    <?php endif; ?>
                    <?php if ($buttonUpExist): ?>
                        <?php echo Html::tag(
                            'div',
                            $buttons['treeUp']['name'],
                            [
                                'name' => 'up-in-tree',
                                'data-href' => Url::toRoute($buttons['treeUp']['url']),
                                'data-fl-tree-url' => Url::toRoute($base['renderTree']),
                                'data-name' => $base['nodeName'],
                                'class' => 'btn btn-primary glyphicon glyphicon-arrow-up'
                            ]
                        ) ?>
                    <?php endif; ?>
                    <?php if ($buttonDownExist): ?>
                        <?php echo Html::tag(
                            'div',
                            $buttons['treeDown']['name'],
                            [
                                'name' => 'down-in-tree',
                                'data-href' => Url::toRoute($buttons['treeDown']['url']),
                                'data-fl-tree-url' => Url::toRoute($base['renderTree']),
                                'data-name' => $base['nodeName'],
                                'class' => 'btn btn-primary glyphicon glyphicon-arrow-down'
                            ]
                        ) ?>
                    <?php endif; ?>
                    <?php if ($buttonRightExist): ?>
                        <?php echo Html::tag(
                            'div',
                            $buttons['treeRight']['name'],
                            [
                                'name' => 'right-in-tree',
                                'data-href' => Url::toRoute($buttons['treeRight']['url']),
                                'data-fl-tree-url' => Url::toRoute($base['renderTree']),
                                'data-name' => $base['nodeName'],
                                'class' => 'btn btn-primary glyphicon glyphicon-arrow-right'
                            ]
                        ) ?>
                    <?php endif; ?>
                    <?php if ($buttonSetParentExist): ?>
                        <?php echo Html::button(
                            $buttons['setParent']['name'],
                            [
                                'id' => 'set-parent-button',
                                'class' => 'btn btn-primary',
                                'data-toggle' => 'modal',
                                'data-target' => $buttons['setParent']['modalId'],
                                'data-render-tree-parent' => Url::toRoute($base['renderTree']),
                                'data-render-tree-children' => Url::toRoute($base['renderTree']),
                                'data-show-checkbox-parent' => $buttons['setParent']['parentShowCheckBox'],
                                'data-show-checkbox-children' => $buttons['setParent']['childShowCheckBox'],
                                'data-set-parent-url' => Url::toRoute($buttons['setParent']['setParentUrl'])
                            ]
                        ) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($buttonsExist && $buttonDeleteExist): ?>
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
            <?php endif; ?>
        </div>
        </p>
        <?php echo DetailView::widget($detailViewConfig) ?>
    </div>
<?php if ($widgetExist): ?>
    <div class="statuses-index">
        <h3>
            <?php echo Html::encode($base['titleLink']) ?>
        </h3>
        <span id="simple-link-change-status"></span>
        <div id="tree-simple-link"></div>
    </div>
<?php endif; ?>
<?php
if ($widgetExist) {
    $isShow = ($widget['showCheckBox'] === true)
        ? 'true'
        : 'false';

    $this->registerJs("var flTreeWithSimpleLinksDataUrl = '" . Url::toRoute($widget['source']) . "'");
    $this->registerJs('var flTreeWithSimpleLinksShowCheckbox = ' . $isShow);
    $this->registerJs(
        <<<'JS'
        initFlTreeWithSimpleLinks(flTreeWithSimpleLinksDataUrl, flTreeWithSimpleLinksShowCheckbox);
JS
    );
}
