<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'api\controllers',
    'defaultRoute' => 'site/index',
    'components' => [
         'user' => [
             'identityClass' => 'common\models\User',
             'enableAutoLogin' => true,
         ],
        'request'=>[
            // Enable Yii Validate CSRF Token
            'enableCsrfValidation' => false,
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                ['class' => 'yii\rest\UrlRule',
                 'controller' => [
                        'product',
                        'user',
                    ],
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'wechat',
                    'extraPatterns' => [
                        'GET valid' => 'valid',
                        'GET accesstoken' => 'accesstoken',
                        'POST userinfo' => 'userinfo',
                        'POST pay' => 'pay',
                        'POST notify' => 'notify',
                        'POST config' => 'config',
                    ],
                ],
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
    ],
    'language' =>'zh-CN',
    'params' => $params,
];
