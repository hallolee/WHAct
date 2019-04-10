<?php
namespace Admin\Controller;

class ProfileController extends GlobalController {

    protected $m_m;
    protected $m_m1;
    protected $modelUser;


    public function _initialize(){
        parent::_initialize();
        $this->m_m = D('Admin/Backend');
        $this->m_m1 = D('Admin/AuthRule');
        $this->modelUser = D('Admin/User');
    }


    public function showInfo(){
        $ret = [];
        $keys = [ 'icon','phone','nickname','name','status' ,'roles' ,'atime'  ,'user' ,'admin_role','role_firm_id' ];
        $raw = $this->RxData;
        if( $this->out['uid'] <= 0 ){
            foreach ($keys as $val) {
                 $res[ $val ] = $this->out[ $val ];
            }
        }else{
            $res = $this->m_m->findUser( $keys, [ 'uid' => $this->out['uid'] ] );
        }
        $res['role_firm_id'] = explode(',',$res['role_firm_id']);

        if( $res ){
            $res_group = $this->m_m1->findAuthGroupAccessWithGroup('a.group_id',['a.uid'=>$this->out['uid']]);
            $admin = $this->m_m1->FindAuthGroup( 'id', [ 'rules' => '0' ] );
            // 用户权限为全权限的管理员组跳过检测
            if( $res_group['group_id'] == $admin['id'] || $this->out['uid'] == -1 ){
                $res['admin_role'] = S_TRUE;
            }

            \Common\validDaTokenWrite($res,$raw['token'],TOKEN_APPEND);
            $res['icon'] = \Common\getCompleteUrl($res['icon']);
            $ret = \Common\enforceInt($res);
        }

        $this->retReturn( $ret );
    }


    public function editPass(){
        $raw = $this->RxData;
        $ret = [ 'status' => 1, 'errstr' => '' ];

        $keys = [ 'old_pass', 'new_pass' ];
        foreach ($keys as $val) {
            if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                goto END;

            ${$val} = $raw[ $val ];
        }
        if( $new_pass == $old_pass ){
            $ret['status'] = E_NOCHANGE;
            goto END;
        }

        if( \Common\getRealPass( $old_pass ) != $this->out['pass'] ){
            $ret['status'] = E_PASS;
            goto END;

        }

        $d['pass'] = \Common\getRealPass( $new_pass );
        $res = $this->m_m->saveUser( [ 'uid' => $this->out['uid'] ], $d );

        if( $res !== false ){
            \Common\validDaTokenWrite( $d, $raw['token'], TOKEN_APPEND );
            $ret['status'] = 0;
        }
END:
        $this->retReturn( $ret );
    }



    public function editInfo(){
        $raw = $this->RxData;
        $ret = [];

        $keys = [
            'user', 'pass', 'icon', 'name', 'phone'
        ];

        foreach ($keys as $v) {
            if ($raw[$v])
                $data[$v] = $raw[$v];
        }

        if($raw['phone']){
            $exist = $this->modelUser->findAdminUser('', ['uid' => ['neq',$this->out['uid']],'phone'=>$raw['phone']]);
            if ($exist) {
                $ret['status'] = E_EXIST;
                $ret['errstr'] = 'phone already exist';
                goto END;
            }
        }
        
        if($raw['user']){
            $exist = $this->modelUser->findAdminUser('', ['uid' => ['neq',$this->out['uid']],'user'=>$raw['user']]);
            if ($exist) {
                $ret['status'] = E_EXIST;
                $ret['errstr'] = 'user already exist';
                goto END;
            }
        }

        //图片处理
        $img_keys = ['icon'];
        foreach($img_keys as $v1){
            if($raw[$v1]) {
                if (strstr($raw[$v1], 'temp')) {
                    $file_name = basename( $raw[$v1]);
                    $file = C("upload_path") . 'admin_'. $this->out['uid'].'/'.$file_name;
                    $res_move = \Common\ownUploadImgIndirect2($raw[$v1], C("upload_path").'admin_'.$this->out['uid'].'/'.$file_name);
                    $data[$v1] =  $file;
                }
            }
        }

        $res = $this->modelUser->editAdminUser($data,['uid' => $this->out['uid']]);





        $ret['status'] = 0;
        $ret['errstr'] = '';
END:
        $this->retReturn($ret);
    }



    public function showMenu(){                     //菜单列表
        $data = [];
        $uid = $this->out['uid'];   //获取用户拥有的权限模块
        $where['uid'] = $uid;
        $aga_reslut = $this->m_m1->selectAuthGroupAccess( '', $where );

        $rules = [];
        if( $aga_reslut[0]['group_id'] == 1 ){

            $group_where = [];
            $group_result = $this->m_m1->selectAuthRule( 'id', $group_where );

            foreach ($group_result as $value) {
                $rule_result[] = $value['id'];
            }

            $rules = implode(",", $rule_result);
        }else{
            $group_id = [];
            foreach ($aga_reslut as $val) {
                $group_id[] = $val['group_id'];
            }

            $group_where['id'] = [ 'in', $group_id ];
            $group_where['status'] =  1;
            $group_result = $this->m_m1->selectAuthGroup( '', $group_where );

            $rules = [];
            foreach ($group_result as $val) {
                $rules = array_merge($rules, explode(',', trim($val['rules'], ',')));
            }

            $rules = array_unique($rules);   //用户拥有的权限
        }

        $ar_where['id'] = array('in',$rules?$rules:'');
        $ar_where['category'] = array('exp',"!='api'");
        $ar_where['menu_title'] = array('exp',"!=''");
        $ar_result = $this->m_m1->selectAuthRule( '', $ar_where, '`order` asc ' );     //获取用户拥有的所有菜单

        $a = [];
        foreach ($ar_result as $value) {
            $a[] = $value['pid'];
        }

        $a = array_unique($a);//用户权限模块

        $set_where['category'] = 'title';
        $set_where['id'] = ['in',implode(',', $a)];
        $set_result = $this->m_m1->selectAuthRule( '', $set_where, '`order` asc ');       //获取相应的一级菜单

        foreach ($set_result as $value) {
            $id = $value['id'];

            $rest[$id]['title'] = $value['title'];
            $rest[$id]['icon'] = $value['icon'];
            if( !is_numeric( $value['name'] ) ){
                $rest[$id]['url'] = $value['name'];
            }
        }

        foreach ($ar_result as $value) {       //整理用户的权限菜单
            $id = $value['id'];

            if( $value['category'] == 'title' )
                continue;

            $url = $value['name'];

            $menu_title = $value['menu_title'];
            $class = $value['class'];
            $pid = $value['pid'];
            $menu[] = array('id'=> $id,'url'=> $url,'title'=> $menu_title,'pid'=> $pid,'class' => $class);
        }

        foreach ($menu as $key => $value) {   //输出相应用户能查看的菜单
            if(in_array($value['pid'],$a)){
                $id = $value['id'];
                $pid = $value['pid'];
                $rest[$pid]['submenu'][] = array('url'=>$value['url'],'title'=>$value['title']);
            }
        }

        foreach ($rest as $key => $value) {
            if(!empty($value['title'])){
                $data[] = $value;
            }
        }

        $this->retReturn($data);
    }

}