<?php
namespace docflow\assets;

use yii\web\AssetBundle;

/**
 * Bower asset for Bootstrap Tree View
 *
 * @author eXeCUT
 */
class TreeViewAsset extends AssetBundle {
    public $sourcePath = '@bower/bootstrap-treeview/dist';
    public $js = [
        'bootstrap-treeview.min.js',
    ];

    public $css = [
        'bootstrap-treeview.min.css',
    ];

    public $depends = [
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}