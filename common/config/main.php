<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'session'=>[
            'timeout'=>20,
        ],
    ],
    'language' =>'zh-CN',
];
