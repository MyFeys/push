<?php

namespace codingyuan\push;

class config
{
    public static function getConfig()
    {
        $default =  [
            'redis' => [
                'host' => '127.0.0.1',
                'port' => 6379
            ],
            // 电话
            'jpush' => [
                'app_key' => 'b4db0861228951f5d51aaa49',
                'master_secret' => '7ff02e0a6a2bedc7f9ff6adf',
            ],
            /*'jpush' => [
                'app_key' => '7e7b8d5796089fc3cd906139',
                'master_secret' => 'e4590199bb6b0a0495f545e8',
            ],*/
            'getui' => [
                'appid' => 'eahZVAGVE58QKKdb1caOD',
                'appSecret' => 'mEtFbTGCbp9KLgNjoA7W12',
                'appKey' => '9NIFA16vcX8v3fYpw4aA78',
                'masterSecret' => 'Q8gNDuoYsd9X3R6ljCtp7A'
            ],
            'xmpush' => [
                'appid' => '2882303761517778721',
                'appKey' => '5351777811721',
                'appSecret' => '+Sz3gZv13cWJkg+b6lNsTQ=='
            ]
        ];
        $config = \Yii::$app->params['push']['cfg'];
        $res = $default;
        $config && ($res = $config);
        unset($default);
        unset($config);
        return $res;
    }
}