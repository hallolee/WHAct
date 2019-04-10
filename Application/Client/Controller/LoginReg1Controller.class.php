<?php
namespace Client\Controller;

class LoginReg1Controller extends GlobalController {

    protected $m_m;

    public function _initialize(){
        parent::_initialize();
        $this->m_m = D('Admin/Item');
    }


    //发送短信验证码
    public function sendSms(){
        $raw = $this->RxData;
        $ret = [ 'status' => 5, 'errstr' => '' ];

        $keys = [ 'phone', 'type' ];
        foreach ($keys as $value) {
            if( !isset( $raw[ $value ] ) || !is_numeric( $raw[ $value ] ) )
                goto END;

            ${$value} = $raw[ $value ];
        }

        if( !in_array( $type, [ SMS_COMMON, SMS_LOGIN ] ) )
            goto END;

        $code = rand(100000,999999);
        $d = [
            'major' => 2,
            'minor' => 2,
            'push_id' => 1,
            'data'  => [
                'phone'    => $raw['phone'],
                'content'  => 'Dear customer,your verification code:'.$code
            ]
        ];

        $push = new \Push\Controller\PushController();
        $z = $push->inPush($d);
        if( $z != E_OK )
            goto END;

        S( ['type'=>'memcached'] );
        $z = S( $raw['phone'].$type, $code );

        $ret['status'] = E_OK;
        $ret['errstr'] = "";
END:
        $this->retReturn($ret);
    }


    /**
    * 用户登录函数
    */
    public function loginReg(){
        $ret = [ 'status' => E_SYSTEM, 'token' => '', 'errstr' => '' ];
        $raw = $this->RxData;

        //登录方式判断
        $reg_type = [ REG_WECHAT ];
        if( !isset( $raw['type'] ) || !in_array( $raw['type'], $reg_type ) ){
            $ret['status'] = 5;
            $ret['errstr'] = 'miss type';
            goto END;

        }

        switch ($raw['type']) {
            case REG_PASSWD:

                //验证参数
                $keys = [ 'user', 'pass' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //生成条件
                $where['openid'] = $user;

                $ret['action'] = ACTION_LOG;
                $column = '';
                $res = $this->m_m->findClient( $column, $where );
                if( !$res )
                    goto END;

                break;

            case REG_WECHAT:

                //验证参数
                $keys = [ 'code' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //获取 openid 与 access_token
                if( C('IS_TEST') && $code == C('TEST_DATA.PHONE_TEST_CODE') ){
                    // 测试环境使用的测试机制
                    $col_str = 'TEST_DATA.'.strtoupper( $col );
                    $wechat['openid'] = C($col_str);
                }else{
                    $wechat = $this->getOpenid( $code );
                    if( $wechat['openid'] == '' ){
                        goto END;
                    }
                }

                //生成条件
                $where = [
                    $col => $wechat['openid'],
                ];

                //获取用户信息
                $ret['action'] = ACTION_LOG;
                $column = '';
                $res = $this->m_m->findClient( $column, $where );

                if( !$res ){
                    $wechat_user = false;
                    $user[ 'openid' ] = $wechat['openid'];
                    $user[ 'atime' ] = time();
                    // 显示授权补充用户信息
                    if( $wechat_user ){
                        $user[ 'nickname' ] = $wechat_user['nickname']?urlencode( $wechat_user['nickname'] ):'';
                        $user[ 'icon' ] = $wechat_user['headimgurl']?$wechat_user['headimgurl']:'';
                        // $user[ 'province' ] = $wechat_user['province']?$wechat_user['province']:'';
                    }

                    // 是否开启 unionid 机制， 并且返回的微信用户信息中是否有 unionid
/*                    if( C('WECHAT_UNIONID') == COL_OPEN ){

                        if( $wechat_user === false ){
                            // 授权模式出错：静默授权，无法获取用户微信信息
                            $ret['status'] = E_WX_USERINFO;
                            goto END;
                        }else if( !isset( $wechat_user['unionid'] ) ){
                            // wechat info 不含 unionid，微信配置环境不全
                            $ret['status'] = E_WX_SETTING;
                            goto END;
                        }

                        // 判断 openid 是否已在系统数据库中, 获取用户信息并更新 $res 返回
                        $res = $this->m_m->findClient( $column, [ 'openid' => $wechat['openid'] ] );
                        if( $res ){
                            $save_info[ $col ] = $wechat['openid'];
                            // 更新用户相应平台的 openid
                            $this->m_m->saveClient( $save_info, [ 'uid' => $res['uid'] ]);
                        }else{
                            // 记录 unionid
                            $user['unionid'] = $wechat_user['unionid'];
                        }
                    }*/

                    // 上述操作仍未获取到用户信息，则开启注册
                    if( !$res ){
                        //注册用户信息
                        $res = $this->m_m->addClient( $user  );
                        if( !$res )
                            goto END;

                        $ret['action'] = ACTION_REG;
                        $res = array_merge( $res, $user );
                    }
                }

                break;

            default:
                goto END;
        }

        if( $res ){
            //生成登录token
            $token = \Common\GenDaTokenFile( $res );

            $ret[ 'status' ] = E_OK;
            $ret[ 'errstr' ] = '';
            $ret[ 'token' ] = $token;
        }
END:
        $this->retReturn( $ret );
    }


    /*
    * 验证toeken 是否有效
    */
    public function checkToken(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_TOKEN, 'errstr' => '' ];

        \Common\CheckDaTokenFile( $raw['token'], $out );

        if( isset( $out['uid'] ) && !empty( $out['uid'] ) ){
            $ret[ 'status' ] = E_OK;
            $ret[ 'time' ] = $out['etime'];

        }

        $this->retReturn( $ret );
    }

    /*
    * 验证toeken 是否有效
    */
    public function Logout() {
        $raw = $this->RxData;

        $ret = [
            'status' => E_OK,
            'errstr' => 'No Login or Loginout Fail',
        ];

        if( !$raw['token'] ) goto END;

        \Common\ValidDaTokenFile( $raw['token'], $out );
        if( empty( $out ) ) goto END;

        \Common\ValidDaTokenWrite( [], $raw['token'] );
        $ret[ 'errstr' ] = '';

END:
        $this->retReturn( $ret );
    }


    /*
    * 找回密码
    */
    public function resetPwd(){
        $raw = $this->RxData;
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];

        //验证参数( 必填 )
        $keys = [ 'phone', 'phonecode', 'new_pass' ];
        foreach ($keys as $val) {
            if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                goto END;

            ${$val} = $raw[ $val ];
        }

        //验证短信验证码
        $chk_res = \Common\checkPhoneCode( $phone, $phonecode, SMS_REPASS );
        if( $chk_res != E_OK ){
            $ret[ 'status' ] = $chk_res;
            goto END;
        }

        //生成条件
        $where['phone'] = $phone;

        //验证账号是否已存在
        $user_res = $this->m_m->findClient( 'uid', $where );
        if( !$user_res ){
            $ret['status'] = E_NOEXIST;
            goto END;
        }

        $where['uid'] = $user_res['uid'];
        $d['pass'] = \Common\GetRealPass( $new_pass );

        $res = $this->m_m->saveClient( $where, $d );

        if( $res !== false )
            $ret[ 'status' ] = E_OK;
END:
        $this->retReturn( $ret );
    }

    /*
    * Get wechat openid
    */
    function getOpenid( $code='', $platform=1 ){
        $ret = [];
        if( !$code ) return $ret;

        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=wx5a135e7d9e057e61&secret=4af1ef76689ed5deb5d61411419ed469&js_code='.$code.'&grant_type=authorization_code';

        $result_str = \Common\UrlGet($url);
        $re_array = json_decode($result_str,true);

        if( !isset($re_array['openid']) )
        {
            \Common\Dexp( __FUNCTION__, $re_array );
            return $ret;
        }

        $ret = $re_array;

        return $ret;
    }

}
