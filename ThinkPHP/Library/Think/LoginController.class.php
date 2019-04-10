<?php
namespace Admin\Controller;

class LoginController extends GlobalController {

    protected $m_m;

    public function _initialize(){
        parent::_initialize();
        $this->m_m = D('Admin/Backend');
    }


    public function login(){
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $raw = $this->RxData;

        $keys = [ 'user', 'pass' ];
        foreach ($keys as $val) {
            if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                goto END;

            ${$val} = $raw[ $val ];
        }

        if( $username == C('ADMIN_USER') ){
            // 超管账号直接存于程序配置中
            $result = [
                'uid'         => -1,
                'user'        => C('ADMIN_USER'),
                'pass'        => C('ADMIN_PASS'),
                'realname'    => '超级管理员',
                'sex'         => 1,
                'birthday'    => '1994-07-29',
                'icon'        => '',
                'phone'       => '',
                'status'      => 1,
                'atime'       => 0,
            ];

        }else{
            // 普通用户信息验证
            $res = $this->m_m->findUser( 'uid,user,pass,sex,icon,phone,status,atime', [ 'user' => $user, 'pass' => \Common\GetRealPass( $pass ) ] );

            if( !$res ){
                $ret['status'] = E_PASS;
                goto END;
            }

            if( $res['status'] == 2 ){
                $ret['status'] = E_DISABLE;
                goto END;
            }
        }

        $token = \Common\GenDaTokenFile( $res );
        $ret[ 'status' ] = E_OK;
        $ret[ 'errstr' ] = '';
        $ret[ 'token' ] = $token;

END:
        $this->retReturn( $ret );
    }

    //验证完登陆之后再验证权限
    public function checkToken(){
        $raw = $this->RxData;
        $ret = [
            'status' => E_TOKEN,
            'errstr' => 'NO Login or Timeout',
        ];

        // 验证token 是否有效
        \Common\ValidDaTokenFile( $raw['token'], $out );
        if( !isset( $out['uid'] ) || empty( $out['uid'] ) ) goto END;

        $ret[ 'status' ] = E_OK;
        $ret[ 'errstr' ] = '';

        if( !$raw['page'] ){
            // 验证页面参数是否存在
            $ret[ 'status' ] = 14;
            goto END;
        }else if( $raw['page'] == 'index.html' ){
            // 过滤index.html 特殊页面
            goto END;
        }else if( $out['uid'] == -1 && isset( $out['group_id'] ) && $out['group_id'] == C('ADMIN_GROUP') ){
            // 超管直接跳过
            goto END;
        }else{
            $auth = D('Admin/AuthRule');
            $where['uid'] = $out['uid'];
            $group = $auth->FindAuthGroupAccess( '', $where );
            $admin = $auth->FindAuthGroup( 'id', [ 'rules' => '0' ] );

            // 用户权限为全权限的管理员组跳过检测
            if( $group['group_id'] == $admin['id'] ){
                $CheckAuth = E_OK;
            }else{
                $CheckAuth = CheckAuth( $where['uid'], $raw['page'] );
            }

            if( $CheckAuth != E_OK )
                $ret = [ 'status' => 14, 'errstr' => '' ];
        }
END:
        $this->retReturn( $ret );
    }


    public function Logout() {
        $raw = $this->RxData;

        $ret = [
            'status' => E_TOKEN,
            'errstr' => '',
        ];

        if( !$raw['token'] ) goto END;

        \Common\ValidDaTokenFile( $raw['token'], $out );
        if( empty( $out ) ){
            $ret[ 'status' ] = E_OK;
            goto END;
        }

        \Common\ValidDaTokenWrite( [], $raw['token'] );
        $ret[ 'status' ] = E_OK;

END:
        $this->retReturn( $ret );
    }



    //图形验证码
    public function checkVerifyCode(){
        S( ['type'=>'memcached'] );

        $raw = $this->RxData;
        $ret['status'] = E_OK;
        $ret['errstr'] = '';
        $yzm = $raw['verify'];
        if(!$yzm || !$raw['id']){
            $ret = ['status'=>E_DATA,'errstr'=>''];
            goto END;
        }
        $v = new \Think\Verify();
        if(!$v->checkDirect($yzm,$raw['id'])){
            $ret = ['status'=>E_OK,'errstr'=>'wrong code'];
        }
END:
        $this->retReturn($ret);

    }

    public function getVerifyCode(){
        $v = new \Think\Verify();
        $t = time();
        $id = rand(1,99).substr($t,6);
        $data = $v->entryDirect($id);

        $ret['img_base64'] = $this->base64EncodeImage($data);
        $ret['id'] = $id;
        $this->retReturn($ret);

    }

    private function base64EncodeImage($file){

        $base64 = chunk_split(base64_encode($file));
        $data = 'data:image/jpg/png/gif;base64,' . $base64 .'';
        return $data;
    }


}
