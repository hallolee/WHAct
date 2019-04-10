<?php
namespace Client\Controller;

class IndexController extends GlobalController
{

    public function _initialize(){
        parent::_initialize();
    }


    public function getConf(){
        $wechat_config = \Common\getWechatTuples(3);

        $ret['APPID'] = $wechat_config['app_id'];
        // $ret['SERVER'] = C('PREENT');

        $this->retReturn( $ret );
    }


    public function getWeChatJsApi(){
        $ret = [];

        $url = $this->RxData[ 'url' ];
        $ret = \Common\getWechatJsApiConf( $url );

END:
        $this->retReturn( $ret );
    }


    public function showActFirm(){
        $ret = [];
        $raw = $this->RxData;

        $res = D('Client/Act')->findAct( 'title,fid', [ 'id' => $raw['id'] ] );
        if( !$res || !$res['fid'] )
            goto END;

        $firm_res = D('Client/Profile')->findFirm('name,logo', ['id' => $res['fid'] ] );
        if( !$firm_res )
            goto END;

        $ret = [
            'id' => (int)$raw['id'],
            'title' => $res['title'],
            'firm' => [
                'id' => (int)$res['fid'],
                'name' => $firm_res['name'],
                'logo' => \Common\getCompleteUrl($firm_res['logo'])
            ]
        ];
END:
        $this->retReturn($ret);
    }



}