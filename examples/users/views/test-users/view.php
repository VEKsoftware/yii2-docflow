<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 12.07.16
 * Time: 16:14
 *
 * @var array $flTreeWidgetParam
 */

use docflow\widgets\FlTreeWidget;

echo FlTreeWidget::widget([
    'renderView' => 'flTree',
    'dataUrl' => $flTreeWidgetParam['dataUrl'],
    'titleList' => $flTreeWidgetParam['titleList']
]);
