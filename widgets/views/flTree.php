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

$data = json_encode($items);
$this->registerJs("var data = $data");

TreeViewAsset::register($this);

$this->registerJs(<<<'JS'
var onSelect = function (undefined, item) {
    if (item.href !== location.pathname) {
        $("#tree-leaf").load(item.href, function() {
            $("#tree-leaf").trigger("domChanged");
        });
    }
}

var onUnselect = function (undefined, item) {
    $("#tree-leaf").html('');
}

var $searchableTree = $('#tree').treeview({
    data: data,
    levels: 5,
    onNodeSelected: onSelect,
    onNodeUnselected: onUnselect
});

var search = function(e) {
    var pattern = $('#input-search').val();
    var options = {
        ignoreCase: $('#chk-ignore-case').is(':checked'),
        exactMatch: $('#chk-exact-match').is(':checked'),
        revealResults: $('#chk-reveal-results').is(':checked')
    };
    var results = $searchableTree.treeview('search', [ pattern, options ]);

    var output = '<p>' + results.length + ' matches found</p>';
    $.each(results, function (index, result) {
        output += '<p>- ' + result.text + '</p>';
    });
    $('#search-output').html(output);
}
JS
);
