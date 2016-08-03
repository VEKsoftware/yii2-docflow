<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 15:00
 *
 * @var array $widget
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
    </div>
<?php
$isShow = ($widget['showCheckBox'] === true)
    ? 'true'
    : 'false';

$this->registerJs("var flTreeDataUrl = '" . Url::toRoute($widget['source']) . "'");
$this->registerJs('var flTreeShowCheckbox = '. $isShow);

TreeViewAsset::register($this);

$this->registerJs(<<<'JS'
    initFlTree(flTreeDataUrl, flTreeShowCheckbox);
JS
);
