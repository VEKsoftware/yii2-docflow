<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 15:00
 */
use yii\helpers\Html;

?>

    <div class="statuses-index">
        <h3><?= Html::encode(Yii::t('docflow', 'Statuses Links')) ?></h3>
        <span id="simple-link-change-status"></span>
        <div id="tree-simple-link"></div>
    </div>
<?php
$tree = json_encode($tree);
$this->registerJs("var dataTree = $tree");
$this->registerJs(<<<'JS'
var onChecked = function (undefined, item) {
    var url = '/docflow/doc-types/ajax-add-simple-link?' + item.href;
    var $tree = $('#tree-simple-link');
    
    $tree.treeview('selectNode', [ item.nodeId, { silent: true } ]);
    getSimpleLinksAjax(url);
}

var onUnchecked = function (undefined, item) {
    var url = '/docflow/doc-types/ajax-remove-simple-link?' + item.href;
    var $tree = $('#tree-simple-link');
    
    $tree.treeview('selectNode', [ item.nodeId, { silent: true } ]);
    getSimpleLinksAjax(url);
}

var $tree = $('#tree-simple-link').treeview({
    data: dataTree,
    showCheckbox: true,
    levels: 5,
    onNodeChecked: onChecked,
    onNodeUnchecked: onUnchecked
});
JS
);
