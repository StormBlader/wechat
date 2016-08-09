<?php
require_once './weix_cache.php';

define("TOKEN", "lianjia_newh");
$wechatObj = new wechatCallbackapi();
if(isset($_GET['echostr'])) {
    $wechatObj->valid();
} else {
    $wechatObj->responseMsg();
}

class wechatCallbackapi
{
    private $_config;
    private $_cache;

    public function __construct()
    {
        $this->_config = include "./weix_config.php";
        $this->_cache = WeixCache::getInstance();
    }
    public function valid()
    {/*{{{*/
        $echoStr = $_GET["echostr"];

        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }/*}}}*/

    private function checkSignature()
    {/*{{{*/
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);

        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }/*}}}*/

    public function responseMsg()
    {/*{{{*/
        $post_str = $GLOBALS["HTTP_RAW_POST_DATA"];

        if(empty($post_str)) {
            echo "";
            exit;
        }

        libxml_disable_entity_loader(true);
        $post_obj = simplexml_load_string($post_str, 'SimpleXMLElement', LIBXML_NOCDATA);

        $receive_msg_type = $post_obj->MsgType;
        switch($receive_msg_type) {
            case 'event':
                $this->dealWeixinEvent($post_obj);
                break;
            case 'text':
                $this->dealWeixinText($post_obj);
                break;
            case 'image':
                break;
            case 'video':
                break;
        }
    }/*}}}*/

    private function dealWeixinEvent($post_obj)
    {/*{{{*/
        $receive_from_username = $post_obj->FromUserName;
        $receive_to_username = $post_obj->ToUserName;
        $receive_event = $post_obj->Event;
        $reply_from_username = $receive_to_username;
        $reply_to_username = $receive_from_username;
        if($receive_event == 'subscribe') {

            $cache_weix_subscribe = $this->_config['CACHE_WEIX_PREFIX'] . 'SUBSCRIBE_' . md5($this->_config['weix_appid'] . $this->_config['weix_secret']);
            $this->_cache->sAdd($cache_weix_subscribe, strval($receive_from_username));

            $this->sendPassiveTextMessage($reply_from_username, $reply_to_username, '欢迎关注api报警平台，遇到报警不用慌, 赶紧解决');
        } elseif($receive_event == 'unsubscribe') {
            $cache_weix_subscribe = $this->_config['CACHE_WEIX_PREFIX'] . 'SUBSCRIBE_' . md5($this->_config['weix_appid'] . $this->_config['weix_secret']);
            $this->_cache->sRem($cache_weix_subscribe, strval($receive_from_username));

        } elseif($receive_event == 'CLICK') {
            //$receive_event_key = $post_obj->EventKey;
            $this->sendPassiveTextMessage($reply_from_username, $reply_to_username, '成功');
        }
    }/*}}}*/

    private function dealWeixinText($post_obj)
    {/*{{{*/
        $receive_from_username = $post_obj->FromUserName;
        $receive_to_username = $post_obj->ToUserName;
        $receive_keyword = trim($post_obj->Content);
        $reply_from_username = $receive_to_username;
        $reply_to_username = $receive_from_username;
        if(!empty($receive_keyword))
        {
            $this->sendPassiveTextMessage($reply_from_username, $reply_to_username, '您好，感谢关注，我们会及时给出回复');
        }else{
            echo "Input something...";
        }

    }/*}}}*/

    public function sendPassiveTextMessage($from_user, $to_user, $content)
    {/*{{{*/
        $text_tpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag>0</FuncFlag>
            </xml>";
        $time = time();
        $ret = sprintf($text_tpl, $to_user, $from_user, $time, 'text', $content);
        echo $ret;

        return true;
    }/*}}}*/

}

