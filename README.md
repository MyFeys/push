# 推送

### 支持
1. 通知消息
2. 透传消息
3. 指定regID或alias推送
4. 多个推送平台

#### 安装： <br />
> composer require codingyuan/push '1.1.0' <br />
> 或者 github 上clone到本地vendor目录下

#### 配置

codingyuan/push/src/config.php
```
    
    return [
    
           'redis' => [
               'host' => '127.0.0.1',
               'port' => 6379
           ],
           
           'jpush' => [
               'app_key' => 'b4db0861228951f5d51aaa49',
               'master_secret' => '7ff02e0a6a2bedc7f9ff6adf',
           ],
           
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

```

#### 推送

```
    use codingyuan\push\Push;
    
    public function actionIndex()
    {
        $push = new Push($this->data);
        return $push->send();
    }
```

数据格式：

```
   $this->data = [
        'service' => 'jpush',
        'notification' => [
            'title' => '通知标题',
            'content' => '通知消息内容'
        ],
        'message' => [
            'title' => '透传标题',
            'content' => '透传消息内容'
        ],
        'audience' => 'all', //默认为all，向所有设备发送
        // 'alias' => 'xidada, telangpu' //根据别名推送
        'time_to_live' => 0, //离线缓存时长
    ]
    
    /**
     * message 与 notification 二选一，必填一个，两者并存透传有效，极光推送两者有效。
     * audience 为注册ID，多个用逗号分隔
     * service 为推送平台，目前实现的有极光、个推
     */

```

返回值

```

    {
        code: 0,  //0表示推送成功，1表示推送失败
        msg: ''  //错误消息
    }

```

#### 查询状态

```
    use codingyuan\push\Push;
    
    public function actionIndex()
    {
        $push = new Push($this->data);
        return $push->getStatus($msg_id, $reg_ids);
    }
```

数据格式：

```
   $this->data = [
        'service' => 'jpush',
        'msg_id' => '123123132',
        'reg_ids' => '123412,sadfas'
    ]
    
    /**
     * msg_id 消息id
     * reg_ids 注册ID
     * service 为推送平台，目前实现的有极光、个推
     */

```

返回值

```

    {
        "02078f0f1b8": {
            "status": 2
        },
        "1507bfd3a7c568d4761": {
            "status": 0
        },
        "0207870a9b8": {
            "status": 2
        }
    }
    
    // status 含义：
    
    // 0: 送达；
    // 1: 未送达；
    // 2: registration_id 不属于该应用；
    // 3: registration_id 属于该应用，但不是该条 message 的推送目标；
    // 4: 系统异常。

```

<br />




