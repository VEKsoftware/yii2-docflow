<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 04.07.16
 * Time: 15:00
 *
 * @var array $widget
 * @var array $base
 * @var View  $this
 */
use docflow\assets\TreeViewAsset;
use yii\helpers\Url;
use yii\web\View;

?>
    <div class="row">
        <div class="col-sm-12">
            <h4><?php echo $base['titleList'] ?></h4>
            <div id="tree-modal-status"></div>
            <div>
                <label for="parent-root">Перенести в корень</label>
                <input type="checkbox" id="parent-root">
            </div>
            <div id="tree-modal"></div>
        </div>
    </div>
<?php
$isShow = ($widget['showCheckBox'] === true)
    ? 'true'
    : 'false';

$this->registerJs("var flTreeModalDataUrl = '" . Url::toRoute($widget['source']) . "'");
$this->registerJs('var flTreeModalShowCheckbox = '. $isShow);

TreeViewAsset::register($this);

$this->registerJs(<<<'JS'
    initFlTreeModal(flTreeModalDataUrl, flTreeModalShowCheckbox);
JS
);
