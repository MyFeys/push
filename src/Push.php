<?php

namespace kangshifu\push;

use kangshifu\push\api\Getui;
use kangshifu\push\api\Jpush;
use kangshifu\push\api\Xmpush;

define('PUSH', __DIR__);

class Push
{
    public $formData = [];

    public $services = [
        'hms' => '华为推送',
        'xmpush' => '小米推送',
        'getui' => '个推推送',
        'jpush' => '极光推送'
    ];

    public function push($service)
    {
        $pushService = [];
        switch ($service) {
            case 'jpush':
                $pushService = new Jpush();
                break;
            case  'getui':
                $pushService = new Getui();
                break;
            case  'xmpush':
                $pushService = new Xmpush();
        }
        return $pushService;
    }

    public function __construct($formData=[])
    {
        $this->formData = $formData;
    }

    public function setFormData($data)
    {
        $this->formData = $data;
    }

    public function getStatus($msg_id, $reg_ids)
    {
        $jpush = new Jpush();
        return $jpush->getStatus($msg_id, $reg_ids);
    }


    /**
     * @return string|json
     */
    public function send()
    {
        $services = $this->services;

        $formData = $this->formData;
        if($formData && array_key_exists($formData['service'], $services)) {
            $push = $this->push($formData['service']);


            //设置消息类型
            $push->setMsgType($formData['msg_type']);

            //设置通知消息
            if(isset($formData['notification']) && 1==2) {
                $notification = (Array)$formData['notification'];
                $push->setNotification([
                    'title' => $notification['title'],
                    'content' => $notification['content']
                ]);
            }

            //设置接受设备，cid
            if(isset($formData['audience'])) {
                $audience  = (string)$formData['audience'];
                $push->setAudience($audience);
            }

            //设置别名
            if(isset($formData['alias'])) {
                $alias = (string)$formData['alias'];
                $push->setAlias($alias);
            }

            //设置透传
            if( isset($formData['message']) ) {
                $message = (Array)$formData['message'];
                $push->setMessage([
                    'title' => $message['title'],
                    'content' => $message['content']
                ]);
            }

            //设置透传
            if( isset($formData['sseq']) ) {
                $sseq = (string)$formData['sseq'];
                $push->setSseq($sseq);
            }

            //设置离线保存时长
            if( isset($formData['time_to_live']) ) {
                $timeToLive = (integer)$formData['time_to_live'];
                $push->setTimeToLive($timeToLive); //设置离线缓存时长
            }

            return $push->send();
//            exit(json_encode($response));
        } else {
            exit(json_encode([
                'code' => 40007,
                'msg_id' => '',
                'msg' => '缺少参数'
            ]));
        }
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.

    }

}

