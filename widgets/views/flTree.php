<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 15:00
 */
use docflow\assets\TreeViewAsset;

/**
 * @var $items array
 * @var $dataUrl string
 */
?>
    <div class="row">
        <div class="col-sm-3">
            <h3> <?php echo Yii::t('docflow', 'List of statuses') ?> </h3>
            <span id="tree-change-status"></span>
            <div id="tree"></div>
        </div>
        <div class="col-sm-9">
            <div id="tree-leaf"></div>
        </div>
    </div>
<?php

$this->registerJs("var dataUrl = $dataUrl");

TreeViewAsset::register($this);

$this->registerJs(<<<'JS'
var onSelect = function (event, item) {
    var tree = $('#tree').treeview(true);
    
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
            
            tree.removeNode( item, { silent: true });
            tree.addNode(vars, parrent, false, { silent: true });
        });
    }
    
    if (item.href !== location.pathname) {
        $("#tree-leaf").load(item.href, function() {
            $("#tree-leaf").trigger("domChanged");
        });
    }
}

var onUnselect = function (event, item) {
    $("#tree-leaf").html('');
}

var onCollapsed = function(event, item) {
    var tree = $('#tree').treeview(true);
    var currentNode = {
        'text': item.text,
        'href': item.href,
        'href_child': item.href_child,
        'tags': item.tags
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

var $searchableTree = $('#tree').treeview({
    dataUrl: {
        url: dataUrl,
    },
    levels: 5,
    showTags: true,
    onNodeSelected: onSelect,
    onNodeUnselected: onUnselect,
    onNodeCollapsed: onCollapsed
});
JS
);
