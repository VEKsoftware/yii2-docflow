<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 10.08.16
 * Time: 11:05
 *
 * @var array $flTreeWidgetParam
 */

use docflow\widgets\FlTreeWidget;
use docflow\widgets\FlTreeWidgetWithLeaf;
use yii\bootstrap\Modal;
use yii\helpers\Html;

$this->title = 'Operations Float Tree';
$this->params['breadcrumbs'][] = $this->title;

echo FlTreeWidgetWithLeaf::widget([
    'base' => [
        'titleList' => $flTreeWidgetParam['titleList']
    ],
    'widget' => [
        'source' => $flTreeWidgetParam['flTreeUrl'],
        'showCheckBox' => false
    ]
]);
