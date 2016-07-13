<?php
namespace docflow\assets;

use yii\web\AssetBundle;

/**
 * Bower asset for Bootstrap Tree View
 *
 */
class TreeViewAsset extends AssetBundle {
    public $sourcePath = '@vendor/VEKsoftware/bootstrap-treeview/dist';
    public $publishOptions = [
        'forceCopy' => true,
        'linkAssets' => true,
    ];
    public $js = [
        'bootstrap-treeview.min.js',
    ];

    public $css = [
        'bootstrap-treeview.min.css',
    ];

    public $depends = [
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapAsset',
        'docflow\assets\DocTypeViewAsset',
    ];
}