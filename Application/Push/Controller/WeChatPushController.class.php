<?php
namespace Push\Controller;

class WeChatPushController extends GlobalController {

    protected $m_m;
    public function __construct() { }



    //获取token
    protected function actionGetToken(){
        $wechat = \Common\getWechatTuples();

        $url = 'https://api.weixin.qq.com/cgi-bin/token'.
            '?grant_type=client_credential'.
            '&appid='.$wechat['app_id'].
            '&secret='.$wechat['app_secret'];

        $res = json_decode(\Common\urlGet($url));
        return $res->access_token;
    }


    public function HuXiHundred( $data=[] ){
        //获取token
        $token = $this->actionGetToken();
        //设置url
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$token;

        //设置发送的消息
        $message = [
            'template_id'   => $data['conf']['template_id'],    // 模板ID
            'touser'        => $data['basic']['openid'],     // 用户openid
            'url'           => $data['basic']['url'],        // 跳转链接
            'data'          => $data['data']        // 模板数据
        ];
        $data = json_encode($message);

        //发送
        $res = \Common\post($url,$data);
        if( $res['errcode'] == E_OK ){
            $ret = E_OK;
        }else{
            $ret = E_SYSTEM;
        }

        return $ret;
    }


}
