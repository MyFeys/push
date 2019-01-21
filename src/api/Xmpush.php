<?php

namespace codingyuan\push\api;

use codingyuan\push\api\Push;

class Xmpush extends Push
{

    public $appid;
    public $appKey;
    public $appSecret;
    public $packages = 'com.lanmei.mipush'; //包名,string类型，多个用,分隔v3版本支持
    public $through = [
        'notification' => 0,
        'message' => 1
    ];
    public $data = [
        'payload' => '',
        'pass_throughpass_through' => 0,
        'title' => '通知',
        'description' => '',
        'notify_type' => -1, //提示音
        'description' => '描述'
    ];

    public $audience; //小米的regid, string类型, 多个逗号分隔
    public $audienceUrl = 'https://api.xmpush.xiaomi.com/v2/message/regid'; // 设备id单推url

/*
 *  V3版接口（兼容V2版接口并支持多包名）：
    向某个regid或一组regid列表推送某条消息（这些regId可以属于不同的包名）

    https://api.xmpush.xiaomi.com/v3/message/regid
    向某个alias或一组alias列表推送某条消息（这些alias可以属于不同的包名）

    https://api.xmpush.xiaomi.com/v3/message/alias
    向某个topic推送某条消息（可以指定一个或多个包名）

    https://api.xmpush.xiaomi.com/v3/message/topic
    向多个topic推送单条消息（可以指定一个或多个包名）

    https://api.xmpush.xiaomi.com/v3/message/multi_topic
    向所有设备推送某条消息（可以指定一个或多个包名）

    https://api.xmpush.xiaomi.com/v3/message/all
*/

    public function __construct($options)
    {
        parent::__construct();
        if(count($options)) {
            $this->appid = $options['appid'];
            $this->appKey = $options['appKey'];
            $this->appSecret = $options['appSecret'];
        } else {
            $this->appid = $this->config['xmpush']['appid'];
            $this->appKey = $this->config['xmpush']['appKey'];
            $this->appSecret = $this->config['xmpush']['appSecret'];
        }

        $this->curl->headers['Authorization'] = 'key=' . $this->appSecret;
        $this->curl->headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
        $this->data['restricted_package_name'] = $this->packages;
    }


    public function setAudience($regid)
    {
        $this->data['registration_id'] = $regid;
        return $this;
    }

    public function setMessage($message)
    {
        $this->data['pass_throughpass_through'] = $this->through['message'];
        $this->setData($message);
        return $this;
    }

    public function setNotification($notification)
    {
        $this->data['pass_throughpass_through'] = $this->through['notification'];
        $this->setData($notification);
        return $this;
    }

    private function setData($data)
    {
        $this->data['title'] = $data['title'];
        $this->data['payload'] = $data['content'];
        $this->data['description'] = $data['title'];
        return $this;
    }

    public function test()
    {
        $url = 'https://api.xmpush.xiaomi.com/v2/message/regid';
        /*$data = [
            'description' => 'notification description',
            'payload' => 'this+is+xiaomi+push',
            'restricted_package_name' => $this->package,
            'registration_id' => 123,
            'title' => 'notification title',
            'notify_type' => 2,
            'time_to_live' => 1000,
            'notify_id' => 0
        ];*/
        exit(json_encode($this->data));
        $response = $this->curl->post($url, http_build_query($this->data));
        return $response;
    }

    public function send()
    {
        $url = 'https://api.xmpush.xiaomi.com/v2/message/regid';
        /*$data = [
            'description' => 'notification description',
            'payload' => 'this+is+xiaomi+push',
            'restricted_package_name' => $this->package,
            'registration_id' => 123,
            'title' => 'notification title',
            'notify_type' => 2,
            'time_to_live' => 1000,
            'notify_id' => 0
        ];*/
//exit(json_encode($this->data));
        $t1 = microtime(true);
        $response = $this->curl->post($url, http_build_query($this->data));
        $t2 = microtime(true);
        $time = $t2 - $t1;

        $resBody = (Array)json_decode($response);

        count($response) && $this->pushLog([
            'url' => $url,
            'status' => $response->headers['Status'],
            'taskid' => '',
            'content' => json_encode($this->data),
            'time' => $time,
            'error' => $resBody['result'] != 'ok' ? $resBody['description'] . ' ' . $resBody['reason'] : ''
        ]);

//        "result": string，"ok" 表示成功, "error" 表示失败。
//
//        "description": string， 对发送消息失败原因的解释。
//
//        "data": string，本身就是一个json字符串（其中id字段的值就是消息的Id）。
//
//        "code": integer，0表示成功，非0表示失败。
//
//        "info": string，详细信息。
        
        return json_encode([
             'code' => $resBody['result'] == 'ok' ? 0 : 1,
             'msg' => $resBody['code'] . ' ' . $resBody['description'] . ' ' . $resBody['reason']
        ]);
    }


}