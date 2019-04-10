<?php
namespace Client\Controller;

class LoginRegController extends GlobalController {

    protected $m_m;
    protected $act_m;

    public function _initialize(){
        parent::_initialize();
        $this->act_m = D('Client/Act');
        $this->m_m = D('Client/Profile');
    }


    //发送短信验证码
    public function sendSms(){
        $raw = $this->RxData;
        $ret = [ 'status' => 5, 'errstr' => '' ];

        $keys = [ 'phone', 'type' ];
        foreach ($keys as $value) {
            ${$value} = $raw[ $value ];
        }

        if( !in_array( $type, [ SMS_COMMON, SMS_REG, SMS_LOGIN, SMS_REPASS, SMS_USERINFO ] ) )
            goto END;

        $code = rand(100000,999999);
        $d = [
            'major' => 2,
            'minor' => 1,
            'push_id' => 1,
            'data'  => [
                'phone'    => $raw['phone'],
                'content'  => ''
            ]
        ];

        switch ($type) {
            case SMS_REG:
                $d['content'] = '您的注册验证码是：'.$code;
                break;

            default:
                $d['content'] = '您的验证码是：'.$code;
        }

        $push = new \Push\Controller\PushController();
        $z = $push->inPush($d);
        if( $z != 0 )
            goto END;

        S( ['type'=>'memcached'] );
        $z = S( $raw['phone'], $code );

        $ret['status'] = 0;
        $ret['errstr'] = "";
END:
        $this->retReturn($ret);
    }


    /**
    * 用户登录注册函数
    */
    public function loginReg(){
        $ret = [ 'status' => 1, 'token' => '', 'errstr' => '' ];
        $raw = $this->RxData;

        //登录方式判断
        $reg_type = [ REG_WECHAT_MP ];
        if( !isset( $raw['type'] ) || !in_array( $raw['type'], $reg_type ) )
            goto END;

        switch ($raw['type']) {
            case REG_PASSWD:
                // 密码登录暂不支持自动注册方式
                // 验证参数
                $keys = [ 'user', 'pass' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //生成条件
                $where['user'] = $user;
                $where['pass'] = \Common\getRealPass( $pass );

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
                $conf = C('WECHAT_CONF');
                if( !isset( $raw['pf'] ) || !isset( $conf[ $raw['pf'] ] ) )
                    goto END;

                $platform = $raw['pf'];
                $col = $conf[ $platform ]['COL'];

                //获取 openid 与 access_token
                if( C('IS_TEST') && $code == C('TEST_DATA.PHONE_TEST_CODE') ){
                    // 测试环境使用的测试机制
                    $col_str = 'TEST_DATA.'.strtoupper( $col );
                    $wechat['openid'] = C($col_str);
                }else{
                    $wechat = \Common\getOpenid( $code, $platform );
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

                // 未注册即注册
                if( !$res ){
                    $wechat_user = false;
                    //显式授权时，获取用户的微信信息
                    if( C('WECHAT_AUTH') == WX_DISPLAY ){
                        if( C('IS_TEST') && $code == C('TEST_DATA.PHONE_TEST_CODE') ){
                            // 测试环境使用的测试机制
                            $wechat_user = C('TEST_DATA.WECHAT_DATA');
                        }else{
                            $wechat_user = \Common\getWeChatInfo( $wechat['access_token'], $wechat['openid'] );
                        }
                    }

                    $user_d[ $col ] = $wechat['openid'];
                    // 显示授权补充用户信息
                    if( $wechat_user ){
                        $user_d[ 'nickname' ] = $wechat_user['nickname']?urlencode( $wechat_user['nickname'] ):'';
                        $user_d[ 'icon' ] = $wechat_user['headimgurl']?$wechat_user['headimgurl']:'';
                        // $user_d[ 'province' ] = $wechat_user['province']?$wechat_user['province']:'';
                    }

                    // 是否开启 unionid 机制， 并且返回的微信用户信息中是否有 unionid
                    if( C('WECHAT_UNIONID') == COL_OPEN ){

                        if( $wechat_user === false ){
                            // 授权模式出错：静默授权，无法获取用户微信信息
                            $ret['status'] = 10001;
                            goto END;
                        }else if( !isset( $wechat_user['unionid'] ) ){
                            // wechat info 不含 unionid，微信配置环境不全
                            $ret['status'] = 10002;
                            goto END;
                        }

                        // 判断 unionid 是否已在系统数据库中, 获取用户信息并更新 $res 返回
                        $res = $this->m_m->findClient( $column, [ 'unionid' => $wechat_user['unionid'] ] );
                        if( $res ){
                            $save_info[ $col ] = $wechat['openid'];
                            // 更新用户相应平台的 openid
                            $this->m_m->saveClient( [ 'uid' => $res['uid'] ], $save_info );
                        }else{
                            // 记录 unionid
                            $user_d['unionid'] = $wechat_user['unionid'];
                        }
                    }

                    // 上述操作仍未获取到用户信息，则开启注册
                    if( !$res ){
                        //注册用户信息
                        $res = $this->m_m->addClient( [ $user_d ] );
                        if( !$res )
                            goto END;

                        $ret['action'] = ACTION_REG;
                        $res = array_merge( $res, $user_d );
                    }
                }

                break;

            case REG_PHONE:

                //验证参数
                $keys = [ 'phone' ,'phonecode' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                //验证短信验证码
                $chk_res = \Common\checkPhoneCode( $phone, $phonecode, SMS_LOGIN );
                if( $chk_res != 0 ){
                    $ret[ 'status' ] = $chk_res;
                    goto END;
                }
                $ret['action'] = ACTION_LOG;

                //生成条件
                $where['phone'] = $phone;

                //验证账号是否已存在
                $res = $this->m_m->findClient( '', $where );
                // 上述操作仍未获取到用户信息，则开启注册
                if( !$res ){
                    $user_d = [
                        'user'      => '',
                        'pass'      => '',
                        'openid'    => '',
                        'phone'     => $phone,
                    ];

                    //注册用户信息
                    $res = $this->m_m->addClient( [ $user_d ] );
                    if( !$res )
                        goto END;

                    $ret['action'] = ACTION_REG;
                    $res = array_merge( $res, $user_d );
                }

                break;

            case REG_WECHAT_MP:

                //验证参数
                $keys = [ 'code', 'aid' ];
                foreach ($keys as $val) {
                    if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                        goto END;

                    ${$val} = $raw[ $val ];
                }

                $chk_act = $this->act_m->findAct( '', [ 'id' => $aid ] );
                if( !$chk_act || !$chk_act['fid'] )
                    goto END;

                $conf = C('WECHAT_CONF');
                $platform = 3;
                $col = $conf[ $platform ]['COL'];

                //获取 openid, session_key & unionid
                if( C('IS_TEST') && $code == C('TEST_DATA.PHONE_TEST_CODE') ){
                    // 测试环境使用的测试机制
                    $col_str = 'TEST_DATA.'.strtoupper( $col );
                    $wechat['openid'] = C($col_str);
                }else{
                    $wechat = \Common\getOpenidByMP( $code, $platform );
                    if( $wechat['openid'] == '' ){
                        $ret['status'] = 19;
                        goto END;
                    }
                }

                //获取用户信息
                $ret['action'] = ACTION_LOG;
                $column = '';
                $where = [ 'openid' => $wechat['openid'], 'fid' => $chk_act['fid'] ];
                $res = $this->m_m->findClient( $column, $where );
                if( !$res ){
                    if( !isset( $raw['nickname'] ) || !isset( $raw['idcard'] ) || empty( $raw['nickname'] ) || empty( $raw['idcard'] ) ){
                        $ret['status'] = 10007;
                        goto END;
                    }

                    $where = [ 'nickname' => $raw['nickname'], 'idcard' => $raw['idcard'], 'fid' => $chk_act['fid'] ];
                    $res = $this->m_m->findClient( '', $where );
                    if( !$res ){
                        $ret['status'] = 10007;
                        goto END;
                    }

                    $user['openid'] = $wechat['openid'];
                    $res['openid'] = $wechat['openid'];
                    $this->m_m->saveClient( [ 'uid' => $res['uid'] ], $user );
                }
                $res['session_key'] = $wechat['session_key'];
                break;

            default:
                goto END;
        }

        if( $res ){
            //生成登录token
            $token = \Common\genDaToken( $res );

            $ret[ 'status' ] = 0;
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
        $ret = [ 'status' => 8, 'errstr' => '' ];

        \Common\checkDaToken( $raw['token'], $out );

        if( isset( $out['uid'] ) && !empty( $out['uid'] ) ){
            $ret[ 'status' ] = 0;
            $ret[ 'time' ] = $out['etime'];

        }

        $this->retReturn( $ret );
    }


    /*
    * 清除 token，退出登录
    */
    public function Logout() {
        $raw = $this->RxData;

        $ret = [
            'status' => 1,
            'errstr' => '',
        ];

        if( !$raw['token'] ) goto END;

        $res = \Common\delDaToken( $raw['token'] );
        if( $res )
            $ret['status'] = 0;
END:
        $this->retReturn( $ret );
    }



}
