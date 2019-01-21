<?php

namespace codingyuan\push\api;

class Jpush extends Push
{
    public $app_key;
    public $master_secret;
    public $msg_type;
    public $url = 'https://api.jpush.cn/v3/push';

    /**
     * 默认为0，还有1，2，3可选，用来指定选择哪种通知栏样式，其他值无效。
     * 有三种可选分别为bigText=1，Inbox=2，bigPicture=3。
     */
    public $jpush_style = [
        'bigText' => 1, //大文本通知栏样式
        'inBox' => 2, //json 的每个 key 对应的 value 会被当作文本条目逐条展示
        'bigPicture' => 3 //大图片通知栏样式
    ];

    public function __construct($options=[])
    {
        parent::__construct();
        if(count($options)) {
            $this->app_key = $options['app_key'];
            $this->master_secret = $options['master_secret'];
        } else {
            $this->app_key = $this->config['jpush']['app_key'];
            $this->master_secret = $this->config['jpush']['master_secret'];
        }


        $this->curl->options['CURLOPT_USERPWD'] = $this->app_key . ':' . $this->master_secret;
        $this->curl->options['CURLOPT_RETURNTRANSFER'] = true;
        $this->curl->options['CURLOPT_SSL_VERIFYPEER'] = false;
        $this->data['platform'] = $this->platform;
        $this->data['audience'] = $this->audience;
//        $this->data['cid'] = 'b4db0861228951f5d51aaa49-33268556-9354-4759-a33f-3bbf19ced68c';

    }

    public function getCid()
    {
        $url = 'https://api.jpush.cn/v3/push/cid?count=3';
        return $this->curl->get($url);
    }


    public function setPlatform($platform)
    {
        $this->data['platform'] = $platform;
        return $this;
    }

    /**
     * @param $audience string
     * @return $this
     */
    public function setAudience($audience)
    {
        $this->data['audience'] = [
            'registration_id' => explode(',', $audience)
        ];
        return $this;
    }

    /**
     * @param $alis string
     */
    public function setAlias($alis)
    {
        $this->data['audience'] = [
            'alias' => explode(',', $alis)
        ];
        return $this;
    }

    /**
     * @param $notification title, content, extras
     */
    public function setNotification($notification)
    {
        $this->data['notification'] = [
            'android' => [
                'alert' => $notification['content'],
                'title' => $notification['title'],
                'extras' => $this->extras
            ],
            'ios' => [
                'alert' => $notification['content'],
                'extras' => $this->extras
            ]
        ];
        return $this;

    }

    public function setMessage($message)
    {
        $this->data['message'] = [
            'msg_content' => $message['content'],
            'title' => $message['title'],
            'extras' => $this->extras
        ];
        return $this;
    }

    public function setTimeToLive($time)
    {
        !isset($this->data['options']) && ($this->data['options'] =  []);
        $this->data['options']['time_to_live'] = $time;
    }

    public function send()
    {
        $data = json_encode($this->data);

        $t1 = microtime(true);
        $response = $this->curl->post($this->url, $data);
        $t2 = microtime(true);
        $time = $t2 - $t1;

        $resBody = (Array)json_decode($response->body);

        count($response) && $this->pushLog([
            'url' => $this->url,
            'status' => $response->headers['Status'],
            'taskid' => isset($resBody['msg_id']) ? $resBody['msg_id'] : '',
            'content' => $data,
            'time' => $time,
            'sseq' => $this->sseq,
            'error' => isset($resBody['msg_id']) ? '' : $response->body
        ]);
        $msg_id = isset($resBody['msg_id']) ? $resBody['msg_id'] : '';
        return [
            'code' => empty($msg_id) ? 40001 : 0,
            'msg_id' => $msg_id,
            'msg' => $response->body
        ];
    }


    //送达统计 GET
    public function received($taskids)
    {
        $taskid = implode(',', $taskids);
        $url = 'https://report.jpush.cn/v3/received?msg_ids=' . $taskid;
        return $this->curl->get($url);
    }

    /**
     * @param $msg_id 消息ID integer
     * @param $reg_ids 注册ID string类型，逗号分隔
     * @return array
     */
    public function getStatus($msg_id, $reg_ids)
    {

        if(empty($msg_id)) {
            return [
                'code' => 1,
                'msg' => 'msg_id不能为空'
            ];
        }

        if(empty($reg_ids)) {
            return [
                'code' => 2,
                'msg' => 'reg_ids不能为空'
            ];
        }

        $reg_ids = explode(',', $reg_ids);
        $data = json_encode([
            'msg_id' => (int)$msg_id,
            'registration_ids' => $reg_ids
        ]);

        $url = 'https://report.jpush.cn/v3/status/message';
        $this->curl->headers['host'] = 'report.jpush.cn';
        set_time_limit(0);
        $response = json_decode($this->curl->post($url, $data), true);
        $res = [];
        foreach ($response as $key => $val) {
            isset($val['status']) && $res[$key] = $this->getStatusCode($val['status']);
        }
        (isset($response['error'])) && ($res=$response);
        return json_encode($res);

    }

    public function getStatusCode($code)
    {
        $status = [
            '0' => 40002, //送达
            '1' => 40003, //未送达
            '2' => 40004, //registration_id 不属于该应用
            '3' => 40005, //registration_id 属于该应用，但不是该条 message 的推送目标
            '4' => 40006  //系统异常
        ];
        return $status[$code];
    }


}