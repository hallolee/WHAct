<?php
namespace Admin\Controller;

class LoginController extends GlobalController {

    protected $m_m;
    protected $modelUser;

    public function _initialize(){
        parent::_initialize();
        $this->m_m = D('Admin/Backend');
        $this->modelUser = D('Admin/User');
    }


    public function login(){
        $ret = [ 'status' => 1, 'errstr' => '' ];
        $raw = $this->RxData;

        $keys = [ 'user', 'pass' ];
        foreach ($keys as $val) {
            ${$val} = $raw[ $val ];
        }

        if( $user == C('ADMIN_USER') ){
            // 超管账号直接存于程序配置中
            $res = [
                'uid'         => -1,
                'user'        => C('ADMIN_USER'),
                'pass'        => C('ADMIN_PASS'),
                'realname'    => '超级管理员',
                'sex'         => 1,
                'birthday'    => '1994-07-29',
                'icon'        => '',
                'admin_role'        => 1,
                'phone'       => '',
                'status'      => 1,
                'atime'       => 0,
            ];

        }else{
            // 普通用户信息验证
            $res = $this->m_m->findUser( 'uid,user,pass,realname,sex,icon,phone,status,atime,admin_role,role_firm_id', [ 'user' => $user, 'pass' => \Common\getRealPass( $pass ) ] );

            if( !$res ){
                $ret['status'] = 7;
                goto END;
            }
            if( $res['status'] == 2 ){
                $ret['status'] = 21;
                goto END;
            }
        }
        $res['role_firm_id'] = $res['admin_role'] == S_TRUE?[]:explode(',',$res['role_firm_id']);

        $token = \Common\genDaToken( $res );
        $ret[ 'status' ] = 0;
        $ret[ 'errstr' ] = '';
        $ret[ 'token' ] = $token;

END:
        $this->retReturn( $ret );
    }

    //验证完登陆之后再验证权限
    public function checkToken(){
        $raw = $this->RxData;
        $ret = ['status' => 8, 'errstr' => '' ];

        // 验证token 是否有效
        \Common\validDaToken( $raw['token'], $out );
        if( !isset( $out['uid'] ) || empty( $out['uid'] ) ) goto END;

        $ret[ 'status' ] = 0;
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
                $CheckAuth = 0;
            }else{
                $CheckAuth = CheckAuth( $where['uid'], $raw['page'] );
            }

            if( $CheckAuth != 0 )
                $ret = [ 'status' => 14, 'errstr' => '' ];
        }
END:
        $this->retReturn( $ret );
    }


    public function Logout() {
        $raw = $this->RxData;

        $ret = [
            'status' => 8,
            'errstr' => '',
        ];

        \Common\validDaToken( $raw['token'], $out );
        if( empty( $out ) ){
            $ret[ 'status' ] = 0;
            goto END;
        }

        \Common\validDaTokenWrite( [], $raw['token'] );
        $ret[ 'status' ] = 0;

END:
        $this->retReturn( $ret );
    }


    //图形验证码
    public function checkVerifyCode(){
        S( ['type'=>'memcached'] );

        $raw = $this->RxData;
        $ret['status'] = 0;
        $ret['errstr'] = '';
        $yzm = $raw['verify'];
        if(!$yzm || !$raw['id']){
            $ret = ['status'=>5,'errstr'=>''];
            goto END;
        }
        $v = new \Think\Verify();
        if(!$v->checkDirect($yzm,$raw['id'])){
            $ret = ['status'=>1,'errstr'=>'wrong code'];
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
