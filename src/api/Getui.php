<?php

namespace kangshifu\push\api;

use kangshifu\push\api\Push;
use kangshifu\push\curl\Curl;

class Getui extends Push
{
    public $appid;
    public $appSecret;
    public $appKey;
    public $masterSecret;
    public $singleUrl;
    public $toappUrl;
    public $cid = []; //App的用户唯一标识
    public $today;
    public $alias = [];
    public $todayToken; // 今日token
    public $transmission = [];

    public function __construct()
    {
        parent::__construct();
        $this->today = date('Y-m-d');
        $this->todayToken = md5('getui_auth_token_' . $this->today);
        $getui = $this->config['getui'];
        $this->appid = $getui['appid'];
        $this->appSecret = $getui['appSecret'];
        $this->appKey = $getui['appKey'];
        $this->masterSecret = $getui['masterSecret'];
        $this->singleUrl = 'https://restapi.getui.com/v1/' . $this->appid . '/push_single';
        $this->toappUrl = 'https://restapi.getui.com/v1/' . $this->appid . '/push_app';

        $this->curl->options['CURLOPT_USERPWD'] = $this->appKey . ':' . $this->masterSecret;
        $this->curl->options['CURLOPT_RETURNTRANSFER'] = true;
        $this->curl->options['CURLOPT_SSL_VERIFYPEER'] = false;

        $this->data['requestid'] = "".time(); //推送ID
        $this->curl->headers['authtoken'] = $this->getAuthToken();
    }

    /**
     * 获取authtoken,从缓存中获取
     * 有效时间是1天，如果超时则重新获取
     * 为了保险起见，保存时间为23小时，超时刷新
     */
    public function getAuthToken(){
        // 从缓存中获取 [缓存中保存的字段标识是：getui_auth_token]
        $authToken= $this->getCache( $this->todayToken );
        // 如果存在token参数,则说明没有过期
        if($authToken){
            // 返回auth_token
            return $authToken;
        }else{
            // 刷新token，会返回数组格式
            $res=$this->refreshAuthToken();
            // 返回的数组中 result=ok 代表获取成功
            if($res['result']=='ok'){
                // 向缓存中存储 token,有效时间是23小时
                $this->setCache($this->todayToken, $res['auth_token']);
                return $res['auth_token'];
            }
            return false;
        }
    }

    public function getCache($name)
    {
        $file = PUSH . '/cache/getui_' . md5($name);
        if(is_file($file)) {
            return file_get_contents($file);
        }
    }

    public function setCache($key, $val)
    {
        $file = PUSH . '/cache/getui_' . md5($key);
        file_put_contents($file, $val);
    }

    /**
     * 刷新或者初次获取 authtoken
     * 通过 restAPI刷新
     * protected 方法
     */
    protected function refreshAuthToken(){
        // 从配置中获取相关的数据
        $appKey=$this->appKey;
        $appId=$this->appid;
        $masterSecret=$this->masterSecret;
        // 获取毫秒数 秒数*1000
        $timestamp=time()*1000;
        // 构建sign
        $sign=strtolower(hash('sha256',$appKey.$timestamp.$masterSecret,false));
        // 构建需要发送的数据
        $dataArr=[
            'sign'=>$sign,
            'timestamp'=>$timestamp,
            'appkey'=>$appKey,
        ];
        // 个推所有的api发送的数据都是json格式，因此不能发送函数，需要发送json
        $content=json_encode($dataArr);
        // 构建header
        $header=array(
            'Content-Type: application/json',
        );
        $url='https://restapi.getui.com/v1/'.$appId.'/auth_sign';
        // 发送http post请求
        $res = $this->curl->post($url, $content);
//        $res=curl_post_json($url,$header,$content);
        $res=json_decode($res,true);
        // 返回数组格式,如果res.result是ok，说明没问题
        return $res;
    }

    /**
     * 关闭鉴权
     */
    public function closeAuthToken(){
        $appId=$this->appid;
        // 获取auth_token,调用函数获取，如果超时则会自动刷新
        $authToken=$this->getAuthToken();
        if(!$authToken){
            return false;
        }
        // 构建header
        $url='https://restapi.getui.com/v1/'.$appId.'/auth_close';
        $curl = new  Curl();
        $curl->headers['authtoken'] = $authToken;
        $curl->headers['Content-Type'] = 'application/json';
        $res = $curl->post($url);
        $res=json_decode($res,true);
        // 返回数组格式,如果res.result是ok，说明没问题
        return $res;
    }

    public function setNotification($notification)
    {
        $this->data['notification'] = [
            'style' => [
                'type' => 0,
                'text' => $notification['content'],
                'title' => $notification['title']
            ],
            'transmission_type' => true,
            'transmission_content' => ''
        ];
        empty($this->message) && $this->data['message'] = [
            'appkey' => $this->appKey,
            'is_offline' => true,
            'msgtype' => 'notification'
        ];
        return $this;
    }

    public function setMessage($message)
    {
        $this->data['message'] = [
            'appkey' => $this->appKey,
            'is_offline' => true,
            'msgtype' => 'transmission'
        ];

        $this->data['transmission'] = [
            'transmission_type' => false, //立即启动
            'transmission_content' => $message['content']
        ];

        $this->message = $this->data['message'];
        return $this;
    }

    public function setTimeToLive($time)
    {
        if(empty($time)) {
            $this->data['message']['is_offline'] = false;
            $this->data['message']['offline_expire_time'] = 0;
        } else {
            $this->data['message']['is_offline'] = true;
            $this->data['message']['offline_expire_time'] = $time * 1000;
        }
    }

    public function setAudience($audience)
    {
        $this->cid = explode(',', $audience);
        return $this;
    }

    public function setAlias($alias)
    {
        $this->alias = explode(',', $alias);
        return $this;
    }

    //tolist群推
    public function toList()
    {

        //1. save_list_body保存消息共同体
        $save_list_body_url = 'https://restapi.getui.com/v1/' . $this->appid . '/save_list_body';
        $data = json_encode([
            'message' => $this->data['message'],
            'notification' => $this->data['notification']
        ]);
        $t1 = microtime(true);
        $res =  $this->curl->post($save_list_body_url, $data);
        $res = json_decode($res->body, true);

        if($res['result'] != 'ok') {
            return json_encode([
                'code' => 1,
                'msg' => 'tolist消息保存失败'
            ]);
        }
        $taskid = $res['taskid']; //任务标识号
        //2. 开始群推
        $push_list_url = 'https://restapi.getui.com/v1/' . $this->appid . '/push_list';
        $data = [
            'taskid' => $taskid,
            'need_detail' => true
        ];

        if(empty($this->alias)) {
            $data['cid'] = $this->cid;
        } else {
            $data['alias'] = $this->alias;
        }

        $data = json_encode($data);
        $response = $this->curl->post($push_list_url, $data);
        $t2 = microtime(true);
        $time = $t2 - $t1;
        $res = json_decode($response->body, true);

        $code = ($res['result'] == 'ok') ? 0 : 40001;
        $msg = $res['result'];
        $detail = $res;

        count($res) && $this->pushLog([
            'url' => $push_list_url,
            'status' => $response->headers['Status'],
            'taskid' => isset($res['taskid']) ? $res['taskid'] : '',
            'content' => $data,
            'time' => $time,
            'error' => isset($res['taskid']) ? '' : json_encode($res)
        ]);

        exit(json_encode([
            'code' => $code,
            'msg_id' => $taskid,
            'msg' => $msg,
            'detail' => $detail
        ]));
    }


    public function send()
    {
        $url = $this->toappUrl;
//        !empty($this->cid) && ($url = $this->singleUrl);

        //判断是单推还是群推

        if(!empty($this->alias) || !empty($this->cid)) {
            if( count($this->alias) == 1 || count($this->cid) == 1 ) {
                !empty($this->cid) && $this->data['cid'] = $this->cid[0];
                !empty($this->alias) && $this->data['alias'] = $this->alias[0];
                $url = $this->singleUrl; //指定单个用户
            } else {
                //多个用户
                return $this->toList();
            }
        }

        $data = json_encode($this->data);

        set_time_limit(0);
        $t1 = microtime(true);
        $response = $this->curl->post($url, $data);
        $t2 = microtime(true);
        $time = $t2 - $t1;

        $resBody = (Array)json_decode($response->body);

        count($response) && $this->pushLog([
            'url' => $url,
            'status' => $response->headers['Status'],
            'taskid' => isset($resBody['taskid']) ? $resBody['taskid'] : '',
            'content' => $data,
            'time' => $time,
            'error' => isset($resBody['taskid']) ? '' : $response->body
        ]);
        $msg_id = isset($resBody['taskid']) ? $resBody['taskid'] : '';
        return json_encode([
            'code' => empty($msg_id) ? 40001 : 0,
            'msg_id' => $msg_id,
            'msg' => $response->body
        ]);
    }

}