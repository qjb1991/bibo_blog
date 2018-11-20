<?php
namespace app\admin\controller;

use cmf\controller\HomeBaseController;
use think\db;

/**
 * Class WechatController
 * @package app\admin\controller
 * 微信接入管理
 */
class WxController extends HomeBaseController
{
    public $client;
    public $wechat_config;
    public function _initialize(){
        parent::_initialize();
        //获取微信配置信息
        $this->wechat_config =  Db::name('wx_user')->find();
        $options = array(
            'token'=>$this->wechat_config['w_token'], //填写你设定的key
            'encodingaeskey'=>$this->wechat_config['aeskey'], //填写加密用的EncodingAESKey
            'appid'=>$this->wechat_config['appid'], //填写高级调用功能的app id
            'appsecret'=>$this->wechat_config['appsecret'], //填写高级调用功能的密钥
        );

    }

    public function oauth(){

    }

    public function index(){
        if($this->wechat_config['wait_access'] == 0)
            exit($_GET["echostr"]);
        else
            $this->responseMsg();
    }

    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        //extract post data
        if (empty($postStr))
            exit("");

        /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
           the best way is to check the validity of xml by yourself */
        libxml_disable_entity_loader(true);
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $keyword = trim($postObj->Content);
        $time = time();

        //点击菜单拉取消息时的事件推送
        /*
         * 1、click：点击推事件
         * 用户点击click类型按钮后，微信服务器会通过消息接口推送消息类型为event的结构给开发者（参考消息接口指南）
         * 并且带上按钮中开发者填写的key值，开发者可以通过自定义的key值与用户进行交互；
         */
        if($postObj->MsgType == 'event')
        {
            if ($postObj->Event=='subscribe') {   //关注公众号回复
                $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                <FuncFlag>0</FuncFlag>
                                </xml>";
                $contentStr = '欢迎来到落木测试公众号';
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
                exit($resultStr);
            }else if ($postObj->Event=='CLICK') {   //点击事件
                $keyword = trim($postObj->EventKey);
                $resultStr=$this->text($keyword,$fromUsername,$toUsername,$time);
                exit($resultStr);
            }

        }else if ($postObj->MsgType == 'text') {
            $resultStr=$this->text($keyword,$fromUsername,$toUsername,$time);
            exit($resultStr);
        }else {
            $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                <FuncFlag>0</FuncFlag>
                                </xml>";
            if ($postObj->MsgType == 'image') {
                $contentStr = "[皱眉][皱眉]\r\n大人请见谅\r\n落落还回复不了图片消息";
            }else if ($postObj->MsgType == 'voice') {
                $contentStr = "[皱眉][皱眉]\r\n大人请见谅\r\n落落还回复不了语音消息";
            }else if ($postObj->MsgType == 'video') {
                $contentStr = "[皱眉][皱眉]\r\n大人请见谅\r\n落落还回复不了视频消息";
            }else if ($postObj->MsgType == 'shortvideo') {
                $contentStr = "[皱眉][皱眉]\r\n大人请见谅\r\n落落还回复不了小视频消息";
            }
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
            exit($resultStr);
        }
    }

    /**
     * 图灵机器人
     */
    public function tuling($type='',$value='')
    {
        $url = 'http://openapi.tuling123.com/openapi/api/v2';
        $arr = array();
        if ($type=='text') { //文本消息
            $arr = array("inputText" => array("text" => $value));
        }elseif($type=='image'){ //图片消息
            $arr = array("inputImage" => array("url" => $value));
        }elseif($type=='voice'){ //语音消息
            $arr = array("inputMedia" => array("url" => $value));
        }
        $data = array(
            "reqType"=>0,
            "perception"=>$arr,
            "userInfo" => array("apiKey"=>'4412011a22964981a761eaf2557ea18e',"userId"=>'317376'),
        );
        $data = json_encode($data);
        $return = httpRequest($url,'POST',$data);
        $results = json_decode($return, true);
        if ($type=='text') {
            return $results['results'][0]['values']['text'];
        }else{
            return false;
        }
    }

    /**
     * 文本消息处理
     */
    public function text($keyword,$fromUsername='',$toUsername='',$time='')
    {
        if(empty($keyword))
            exit("Input something...");
        // 多图文回复
        $wx_news = Db::name('wx_news')->where("keyword like '%$keyword%'")->find();
        if($wx_news)
        {
            $item='';
            $arr = array_reverse(explode(',',$wx_news['img_id']));
            foreach ($arr as $v) {
                $img = Db::name('wx_img')->where("id",$v)->find();
                $xml="<item>
                          <Title><![CDATA[%s]]></Title>
                          <Description><![CDATA[%s]]></Description>
                          <PicUrl><![CDATA[%s]]></PicUrl>
                          <Url><![CDATA[%s]]></Url>
                      </item>";
                $item .= sprintf($xml, $img['title'], $img['desc'], $img['pic'], $img['url']);
            }
            $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <ArticleCount><![CDATA[%s]]></ArticleCount>
                                <Articles>".$item."</Articles>
                                </xml>";
            $resultStr = sprintf($textTpl,$fromUsername,$toUsername,$time,'news',count($arr));
            return $resultStr;
        }


        // 单图文回复
        $wx_img = Db::name('wx_img')->where("keyword like '%$keyword%'")->find();
        if($wx_img)
        {
            $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <ArticleCount><![CDATA[%s]]></ArticleCount>
                                <Articles>
                                    <item>
                                        <Title><![CDATA[%s]]></Title>
                                        <Description><![CDATA[%s]]></Description>
                                        <PicUrl><![CDATA[%s]]></PicUrl>
                                        <Url><![CDATA[%s]]></Url>
                                    </item>
                                </Articles>
                                </xml>";
            $resultStr = sprintf($textTpl,$fromUsername,$toUsername,$time,'news','1',$wx_img['title'],$wx_img['desc']
                , $wx_img['pic'], $wx_img['url']);
            return $resultStr;
        }


        // 文本回复
        $wx_text = Db::name('wx_text')->where("keyword like '%$keyword%'")->find();
        if($wx_text)
        {
            $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                <FuncFlag>0</FuncFlag>
                                </xml>";
            $contentStr = $wx_text['text'];
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
            return $resultStr;
        }


        // 其他文本回复
        $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                <FuncFlag>0</FuncFlag>
                                </xml>";
        $contentStr = $this->tuling('text',$keyword);
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
        return $resultStr;
    }
}