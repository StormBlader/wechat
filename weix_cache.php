<?php

class WeixCache
{
    private static $_instance;
    private $_redis;

    private function __construct()
    {/*{{{*/
        $config = include "./weix_config.php";
        $this->_redis = new Redis();
        $this->_redis->connect($config['redis_host'], $config['redis_port']);
    }/*}}}*/

    private function __clone()
    {}

    public static function getInstance()
    {/*{{{*/
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }/*}}}*/

    public function get($key)
    {/*{{{*/
        return $this->_redis->get($key);
    }/*}}}*/

    public function set($key, $value, $cache_time=7200)
    {/*{{{*/
        return $this->_redis->setex($key, $cache_time, $value);
    }/*}}}*/

    public function __call($method, $args)
    {/*{{{*/
        return call_user_func_array([$this->_redis, $method], $args);
    }/*}}}*/

}
