<?php
namespace Push\Controller;

class SmsPushController extends GlobalController {

    protected $m_m;
    public function __construct() { }

    public function pushByAiXun( $data=[] ){
        $ret = E_SYSTEM;

        // username 账号
        // password 密码
        // method : get
        $url = 'http://120.55.248.18/smsSend.do'.
            '?username=huijinguoji'.
            '&password=3d42d0be009c5ba1ada17cb0ac77d9c8'.
            '&mobile='.$data['basic']['phone'].
            '&content='.$data['data']['content'];

        //发送
        $res = \Common\urlGet( $url );
        if( $res > 0 )
            $ret = E_OK;

        return $ret;
    }


    public function pushByHuYi( $data=[] ){
        $ret = E_SYSTEM;

        // account == apiid
        // password == apikey
        // format 返回数据格式：xml(默认), json
        // method : post / get 均可
        $url = 'http://106.ihuyi.cn/webservice/sms.php?method=Submit';
        $send_data = "account=C44264650&password=97d543c3d49e4dba72cf3c817402a6be&mobile=".$data['basic']['phone']."&content=".rawurlencode( $data['data']['content'] )."&format=json";

        //发送
        // res {"code": 2, "msg": "提交成功", "smsid": "15258556547500822775"}
        // $res = json_decode( \Common\post( $url, $send_data ), true );
        $res = json_decode( \Common\urlGet( $url.'&'.$send_data ), true );
        if( $res['code'] == 2 )
            $ret = E_OK;

        return $ret;
    }


    public function pushByAli( $data ){
        $ret = E_SYSTEM;
        vendor( 'LibAliSms.Sms' );

        $sms = new \Sms();
        $sms->setAccessKeyId( $data['conf']['accessKeyId'] );
        $sms->setAccessKeySecret( $data['conf']['accessKeySecret'] );
        $sms->setData([
            'phone' => $data['basic']['phone'],
            'signName' => $data['conf']['signName'],
            'templateCode' => $data['conf']['templateCode'],
            'param' => json_encode( $data['data']['param'] )
        ]);
        $res = (array)$sms->sendSms();
        if( $res['Code'] == 'OK' )
            $ret = E_OK;

        return $ret;
    }




}
