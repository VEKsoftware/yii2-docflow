<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 12.07.16
 * Time: 16:14
 *
 * @var array $flTreeWidgetParam
 */

use docflow\widgets\FlTreeWidgetWithLeaf;

echo FlTreeWidgetWithLeaf::widget([
    'base' => [
        'titleList' => $flTreeWidgetParam['titleList']
    ],
    'widget' => [
        'source' => $flTreeWidgetParam['flTreeUrl'],
        'showCheckBox' => false
    ]
]);
