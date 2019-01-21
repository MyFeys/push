<?php

namespace kangshifu\push\lib;

use kangshifu\push\config;
class Redis
{
    public $host;
    public $port;
    public $redis = null;
    public $pushQueue = 'push-queue';

    public function __construct()
    {
        $config = config::getConfig()['redis'];
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->redis = new \Redis();
//        $this->redis->pconnect($this->host, $this->port);
        $this->redis->pconnect($this->host, $this->port, 2.5, 'long-redis');
//        die($this->redis->ping());
    }

    /**
     * @param $data 向列表末尾添加元素
     */
    public function rpush($name, $data)
    {
        return $this->redis->rpush($name, $data);
    }

    /**
     * @return string 返回列表第一个元素并移除
     */

    public function lpop($name)
    {
        return $this->redis->lpop($name);
    }

    /**
     * 阻塞式
     * @return string 返回列表第一个元素并移除
     */

    public function blpop($name, $timeout)
    {
        return $this->redis->blpop($name, $timeout);
    }

    /**
     * @param array $options start开始位置， stop结束位置
     *  默认返回所有列表
     * @return array 指定区间的列表元素
     */
    public function getList($name, $options=[])
    {
        $start = 0;
        $stop = -1;
        isset($options['start']) && ($start = $options['start']);
        isset($options['stop']) && ($stop = $options['stop']);
        return $this->redis->lrange($name, $start, $stop);
    }

    /**
     * @param $name 名称
     * @return int List的长度
     */
    public function lsize($name)
    {
        return $this->redis->lSize($name);
    }

    /**
     * @param $name 名称
     * 清空key为$name的list
     */
    public function clear($name)
    {
        return $this->redis->lTrim($name, 1, 0);
    }


    /** 给hash表中某个key设置value
     * @param $tbname Hash表名
     * @param $key 键 string
     * @param $val 值 string
     * @return integer
     */
    public function hset($tbname, $key, $val)
    {
//        $this->redis->flushAll();
        return $this->redis->hSet($tbname, $key, $val);
    }

    /** 获取hash中某个key的值
     * @param $tbname
     * @param $key
     * @return string
     */
    public function hget($tbname, $key)
    {
        return $this->redis->hGet($tbname, $key);
    }

    /** 获取hash中所有的keys
     * @param $tbname
     * @return array
     */
    public function hkeys($tbname)
    {
        return $this->redis->hKeys($tbname);
    }

    /** 获取hash中所有的值 顺序是随机的
     * @param $tbname
     * @return array
     */
    public function hvals($tbname)
    {
        return $this->redis->hVals($tbname);
    }

    /** 获取一个hash中所有的key和value 顺序是随机的
     * @param $tbname
     * @return array
     */
    public function hgetall($tbname)
    {
        return $this->redis->hGetAll($tbname);
    }

    /** 获取hash中key的数量
     * @param $tbname
     * @return int
     */
    public function hlen($tbname)
    {
        return $this->redis->hLen($tbname);
    }

    /** 删除hash中一个key 如果表不存在或key不存在则返回false
     * @param $tbname
     * @param $key
     * @return int
     */
    public function hdel($tbname, $key)
    {
        return $this->redis->del($tbname, $key);
    }

    public function __destruct()
    {
        $this->redis->close();
    }

}