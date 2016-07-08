<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 15:00
 *
 * @var string $dataUrl
 */
use yii\helpers\Html;

?>

    <div class="statuses-index">
        <h3><?php echo Html::encode(Yii::t('docflow', 'Statuses Links')) ?></h3>
        <span id="simple-link-change-status"></span>
        <div id="tree-simple-link"></div>
    </div>
<?php

$this->registerJs("var dataUrl = $dataUrl");
$this->registerJs(<<<'JS'
var onSelected = function(event, item) {
    var tree = $('#tree-simple-link').treeview(true);

    if (item.href_child && item.nodes === undefined) {
        $.get(item.href_child, function(vars) {
            var parent = tree.findNodes(item.text, 'text')[0];
            tree.addNode(vars, parent, 0, { silent: true });
        });
    }
    
    if (item.href_next) {
       $.get(item.href_next, function(vars) {
            var parrent = false;
    
            if (item.parentId !== undefined)
            {
                parrent = tree.findNodes(item.parentId, 'nodeId')[0];
            }
            
            tree.removeNode(item, { silent: true });
            tree.addNode(vars, parrent, false, { silent: true });
        });
    }
}

var onCollapsed = function(event, item) {
    item.state.selected = false;
    
    var currentNode = {
        'text': item.text,
        'href': item.href,
        'href_child': item.href_child,
        'tags': item.tags,
        'state': item.state
    };
    var currentIndex = item.index;
    var parrent = false;
    
    if (item.parentId !== undefined)
    {
        parrent = tree.findNodes(item.parentId, 'nodeId')[0];
    }

    tree.removeNode(item, { silent: true });
    tree.addNode(currentNode, parrent, currentIndex, { silent: true });
}

var onChecked = function (event, item) {
    var $tree = $('#tree-simple-link');
    
    if (item.href_addSimple) {
        getSimpleLinksAjax(item.href_addSimple);
    }
}

var onUnchecked = function (event, item) {
    var $tree = $('#tree-simple-link');
    
    if (item.href_delSimple) {
        getSimpleLinksAjax(item.href_delSimple);
    }
}

var $tree = $('#tree-simple-link').treeview({
    dataUrl: {
        'url': dataUrl
    },
    showCheckbox: true,
    levels: 5,
    showTags: true,
    onNodeChecked: onChecked,
    onNodeUnchecked: onUnchecked,
    onNodeSelected: onSelected,
    onNodeCollapsed: onCollapsed
});
JS
);
