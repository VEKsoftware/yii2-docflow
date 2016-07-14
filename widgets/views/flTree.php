<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 15:00
 *
 * @var array  $items
 * @var string $dataUrl
 * @var string $titleList
 */
use docflow\assets\TreeViewAsset;

?>
    <div class="row">
        <div class="col-sm-3">
            <h3>
                <?php echo $titleList ?>
            </h3>
            <span id="tree-change-status"></span>
            <div id="tree"></div>
        </div>
        <div class="col-sm-9">
            <div id="tree-leaf"></div>
        </div>
    </div>
<?php

$this->registerJs("var dataUrl = '$dataUrl'");

TreeViewAsset::register($this);

$this->registerJs(<<<'JS'
    initFlTree(dataUrl);
JS
);
