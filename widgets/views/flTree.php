<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 15:00
 *
 * @var array $sources
 * @var array $base
 */
use docflow\assets\TreeViewAsset;
use yii\helpers\Url;

?>
    <div class="row">
        <div class="col-sm-4">
            <h3>
                <?php echo $base['titleList'] ?>
            </h3>
            <span id="tree-change-status"></span>
            <div id="tree"></div>
        </div>
        <div class="col-sm-8">
            <div id="tree-leaf"></div>
        </div>
    </div>
<?php

$this->registerJs("var dataUrl = '" . Url::toRoute($sources['flTreeUrl']) . "'");

TreeViewAsset::register($this);

$this->registerJs(<<<'JS'
    initFlTree(dataUrl);
JS
);
