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
    initFlTreeWithSimpleLinks(dataUrl);
JS
);
