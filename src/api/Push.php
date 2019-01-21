<?php

namespace kangshifu\push\api;

use kangshifu\push\config;
use kangshifu\push\curl\Curl;


class Push
{
    public $curl;
    public $platform = 'all';//平台
    public $audience = 'all';//目标
    public $notification = [];
    public $message = [];
    public $data = []; //数据包
    public $msg_type; //消息类型
    public $extras = [];
    public $sseq;

    public function __construct()
    {
        $this->curl = new Curl;
        $this->curl->headers['Content-Type'] = 'application/json';
        $this->config = config::getConfig();
        $this->curl->options['CURLOPT_RETURNTRANSFER'] = true;
        $this->curl->options['CURLOPT_SSL_VERIFYPEER'] = false;
        date_default_timezone_set('PRC');
    }

    public static function pushLog($options)
    {
        if(is_array($options)) {

            $dir = \Yii::$app->getBasePath();

            $logContent = date('Y-m-d H:i:s')
                . ' ' . $options['url'] . PHP_EOL
                . ' Taskid: ' . $options['taskid']
                . ' Time: ' . ((float)$options['time'] * 1000) . 'ms'
                . ' Status: ' . $options['status'] . PHP_EOL
                . ' Sseq: ' . $options['sseq'] . PHP_EOL
                . ' Error: ' . $options['error']
                . PHP_EOL;
            $logContent .= ' data: ' . $options['content'] . PHP_EOL . PHP_EOL;
            file_put_contents($dir . '/logs/out/push-' . date('Y-m-d') . '.log', $logContent, FILE_APPEND);

        }
    }

    /** 设置
     * @param $sseq 序列号
     */
    public function setSseq($sseq)
    {
        $this->sseq = $sseq;
    }

    //通知消息
    public function setNotification($notification)
    {

    }

    //透传消息
    public function setMessage($message)
    {

    }

    //发送
    public function send()
    {

    }

    //离线保存时长
    public function setTimeToLive($time)
    {

    }

    //送达状态查询
    public function getStatus($msg_id, $reg_ids)
    {

    }

    //设置别名
    public function setAlias($alis)
    {

    }

    public function setMsgType($msgType)
    {
        $this->msg_type = $msgType;
        $this->extras['msg_type'] = $msgType;
    }

}