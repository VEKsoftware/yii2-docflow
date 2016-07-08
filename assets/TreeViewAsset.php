<?php
namespace docflow\assets;

use yii\web\AssetBundle;

/**
 * Bower asset for Bootstrap Tree View
 *
 */
class TreeViewAsset extends AssetBundle {
    public $sourcePath = '@bower/bootstrap-treeview/src';
    public $publishOptions = [
        'forceCopy' => true,
        'linkAssets' => true,
    ];
    public $js = [
        'js/bootstrap-treeview.js',
    ];

    public $css = [
        'css/bootstrap-treeview.css',
    ];

    public $depends = [
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapAsset',
        'docflow\assets\DocTypeViewAsset',
    ];
}