<?php
require_once "./weix_curl.php";
require_once "./weix_cache.php";

class WeixToolFactory
{
    private static $_instance;
    private $_config;
    private $_cache;

    private function __construct()
    {/*{{{*/
        $this->_config = include "./weix_config.php";
        $this->_cache = WeixCache::getInstance();
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

    public function getAccessToken()
    {/*{{{*/
        $access_url = 'https://api.weixin.qq.com/cgi-bin/token';
        $access_url_params = [
            'grant_type' => 'client_credential',
            'appid'      => $this->_config['weix_appid'],
            'secret'     => $this->_config['weix_secret'],
        ];

        $cache_key = $this->_config['CACHE_WEIX_PREFIX'] . 'ACCESSTOKEN_'. md5($this->_config['weix_appid'] . $this->_config['weix_secret']);
        $cache_ret = $this->_cache->get($cache_key);

        if($cache_ret) {
            return $cache_ret;
        }

        $ret = WeixCurl::curl($access_url, 'GET', $access_url_params);
        if($ret['httpcode'] != 200) {
            return false;
        }
        $access_token_ret = json_decode($ret['body'], true);
        $access_token = ($access_token_ret && isset($access_token_ret['access_token'])) ? $access_token_ret['access_token'] : '';
        $access_token_expire = ($access_token_ret && isset($access_token_ret['expires_in'])) ? ($access_token_ret['expires_in'] - 200) : 0;

        if($access_token && $access_token_expire) {
            $this->_cache->set($cache_key, $access_token, $access_token_expire);
        }

        return $access_token;
    }/*}}}*/

    public function getAllFollowers()
    {/*{{{*/
        $cache_weix_subscribe = $this->_config['CACHE_WEIX_PREFIX'] . 'SUBSCRIBE_' . md5($this->_config['weix_appid'] . $this->_config['weix_secret']);
        return $this->_cache->sMembers($cache_weix_subscribe);
    }/*}}}*/

    public function sendCustomerTextMessage($content, $to_users = '')
    {/*{{{*/
        $access_token = $this->getAccessToken();
        $customer_url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=$access_token";
        $to_users = empty($to_users) ? $this->getAllFollowers() : $to_users;
        foreach($to_users as $to_user) {
            $post_params = [sprintf('{
                "touser":"%s",
                "msgtype":"text",
                "text":
                {
                    "content":"%s"
                }
            }', $to_user, $content)];


            WeixCurl::curl($customer_url, 'POST', $post_params);
        }

        return true;
    }/*}}}*/

}
