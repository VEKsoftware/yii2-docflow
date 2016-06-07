<?php
namespace docflow\assets;

use yii\web\AssetBundle;

class DocTypeViewAsset extends AssetBundle
{
    public $sourcePath = '@docflow/web';
    public $publishOptions = [
        'forceCopy' => true,
    ];
    public $js = [
        'js/doc-type-view.js',
    ];

    public $css = [
        'css/doc-type-view.css',
    ];

    public $depends = [
    ];
}