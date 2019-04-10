<?php
namespace Admin\Controller;

class UserController extends GlobalController {

    protected $m_m;
    protected $m_m1;
    protected $m_m2;
    protected $modelAct;
    protected $modelUser;

    public function _initialize(){
        parent::_initialize();
        $this->m_m = D('Admin/Backend');
        $this->m_m1 = D('Admin/AuthRule');
        $this->m_m2 = D('Admin/BasicInfo');
        $this->modelAct = D('Admin/Activity');
        $this->modelUser = D('Admin/User');
    }


    //client
    public function showUserList(){
        $raw = $this->RxData;
        $ret = $this->modelUser->getUserList($raw,$this->out);

        unset($ret['status']);

END:
        $this->retReturn($ret);
    }

    //user
    public function showUserDetail()
    {
        $raw = $this->RxData;
        $ret = [];
        $uid = $raw['uid'];

        if(!isset($raw['uid']) || !is_numeric($raw['uid']))
            goto END;

        $columns = '';
        $res = $this->modelUser->findUser($columns,['uid'=>$uid]);
        if(!$res)
            goto END;

        $url_keys = ['icon','cover'];
        foreach($url_keys as $v)
            $res[$v] =\Common\getCompleteUrl($res[$v]);

        $int_keys = ['uid','phone','sex','status','dept_id','age','fid'];
        foreach($int_keys as $v){
            $res[$v] = $res[$v]?(int)$res[$v]:$res[$v];
        }

        $ret = $res;

END:
        $this->retReturn( $ret );
    }

    public function editUser(){
        $raw = $this->RxData;
        $ret = [];

        $keys = [
            'user', 'icon','nickname','age','status', 'dept_id', 'fid','openid', 'phone','status',
            'weight','height','cover','sex' ,'job_status'  ,'idcard' ,'age' ];
        $keys_m = [ 'fid','idcard' ];
        $keys_num = [ 'fid','idcard','age','height','weight','dept_id','fid' ,'status' ];
        foreach ($keys_num as $v) {
            if ($raw[$v] && !is_numeric($raw[$v])){
                $ret['status'] = 5;
                $ret['errstr'] = $v.' in not num';
                goto END;
            }
        }
        $data = [];
        foreach ($keys_m as $v) {
            if (!$raw[$v]){
                $ret['status'] = 5;
                goto END;
            }
        }
        foreach ($keys as $v) {
            if ($raw[$v])
                $data[$v] = $raw[$v];
        }

        if($raw['sex'] && !in_array($raw['sex'],[1,2])){
            $ret['status'] = 5;
            $ret['errstr'] = 'sex wfk?!?!@';
            goto END;
        }

        if(empty($data)){
            $ret['status'] = 5;
            $ret['errstr'] = '';
            goto END;
        }

        if($raw['fid']){
            //check access

    /*        if (!in_array($raw['fid'], $this->out['role_firm_id'])  &&  $this->out['admin_role'] != S_TRUE) {
                $ret['status'] = 14;
                $ret['errstr'] = 'u should be sm1';
                goto END;
            }*/
            $raw_dep_check = $exist_dept = $this->modelUser->findFirm('',['id'=>$raw['fid']]);
            if(!$exist_dept){
                $ret['status'] = 13;
                $ret['errstr'] = 'firm not exist';
                goto END;
            }
            $data['dept_id'] = $raw['dept_id']?$raw['dept_id']:0;
        }
        if($raw['dept_id']){
            $raw_dep_check = $exist_dept = $this->modelUser->findDept('',['id'=>$raw['dept_id']]);
            if(!$exist_dept){
                $ret['status'] = 13;
                $ret['errstr'] = 'department not exist';
                goto END;
            }
        }

        if($data['pass'])
            $data['pass'] = \Common\getRealPass($data['pass']);

        $this->modelUser->startTrans();

        if (isset($raw['uid'])){
            $exist = $this->modelUser->findUser('', ['fid'=>$raw['fid'],'idcard'=>$raw['idcard']]);
            if ($exist && $exist['uid'] != $raw['uid']) {
                $ret['status'] = 12;
                $ret['errstr'] = 'user with idcard already exist in this firm';
                goto END;
            }
            $exist = $this->modelUser->findUser('', ['uid' => $raw['uid']]);
            if (!$exist) {
                $ret['status'] = 13;
                $ret['errstr'] = 'uid user not exist';
                goto END;
            }

            $uid = $raw['uid'];
            $res = $this->modelUser->editUser($data,['uid' => $raw['uid']]);
        } else {
            $exist = $this->modelUser->findUser('', ['fid'=>$raw['fid'],'idcard'=>$raw['idcard']]);
            if ($exist) {
                $ret['status'] = 12;
                $ret['errstr'] = 'user with idcard already exist in this firm';
                goto END;
            }

            $data['atime'] = time();
            $uid = $this->modelUser->addUser($data);
            if (!$uid) {
                $this->modelUser->rollback();
                $ret['status'] = 1;
                $ret['errstr'] = 'add failed';
                goto END;
            }

        }
        $this->modelUser->commit();

        $img_keys = ['icon','cover'];
        foreach($img_keys as $v1){
            if($raw[$v1]){
                if(strstr($raw[$v1],'temp')){
                    $file_name = basename($raw[$v1]);
                    $res_move_file = \Common\ownUploadImgIndirect2($raw[$v1], C("upload_path").'client/uid_'.$uid.'/icon/'.$file_name);
                    $res_edit_img = $this->modelUser->editUser([$v1 => C("upload_path").'client/uid_'.$uid.'/icon/'.$file_name],['uid' => $uid]);

                }
            }

        }

        //人员统计
        $this->modelUser->updateDeptNum();
        $ret['status'] = 0;
        $ret['errstr'] = '';
END:
        $this->retReturn($ret);
    }

    public function delUser(){
        $raw = $this->RxData;

        $ret['status'] = 1;
        $ret['errstr'] = '';
        if (!is_array($raw['uid']))
            goto END;

        $where['uid']    = $raw['uid'];
        $exist = $this->modelUser->findUser('', $where);
        if(!$exist){
            $ret['status'] = 23;
            $ret['errstr'] = '';
            goto END;
        }
        $res  = $this->modelUser->delUser($where);
        if(!$res)
            goto END;
        $ret['status'] = 0;

END:
        $this->retReturn($ret);
    }

    //admin
    public function showAdminList(){
        $raw = $this->RxData;
        $ret = [ 'total' => 0 , 'page_start' => 0, 'page_n' => 0, 'data' => [] ];

        $page_start = ( !isset( $raw[ 'page_start' ] ) || !is_numeric( $raw[ 'page_start' ] ) )?'1':$raw[ 'page_start' ];
        $page_limit = ( !isset( $raw[ 'page_limit' ] ) || !is_numeric( $raw[ 'page_limit' ] ) )?C('PAGE_LIMIT'):$raw[ 'page_limit' ];
        $limit = ($page_start-1)*$page_limit.','.$page_limit;
        $ret['page_start'] = (int)$page_start;

        $where = [];
        $where['a.uid'] = ['neq',$this->out['uid']];
        $search_keys = ['name','nickname','phone'];
        $where['a.uid'][] = ['gt',1];
        $where['a.uid'][] = ['neq',$this->out['uid']];
        foreach($search_keys as $v)
            if( isset ( $raw[ $v ] ) && !empty( $raw[ $v ] ) )
             $where['a.'.$v] = [ 'like', '%'.$raw[ $v ].'%' ];

        if($raw['role_id'])
            $where['b.group_id']  = $raw['role_id'];

        if ($raw['time_start'] && $raw['time_end']) {
            $where['a.atime'] = [
                ['egt', $raw['time_start']],
                ['lt', $raw['time_end']]
            ];
        } elseif ($raw['time_start']) {
            $where['a.atime'] = ['egt', $raw['time_start']];
        } elseif ($raw['time_end']) {
            $where['a.atime'] = ['lt', $raw['time_end']];
            $where['a.atime'] = ['lt', $raw['time_end']];
        }


        $column = 'a.uid,a.phone,a.user,a.name,a.nickname,c.title roles,a.status,a.atime';
        $order = 'a.atime DESC';

        if($this->out['admin_role'] == S_FALSE)
            $where['a.role_firm_id'] = ['in',$this->out['role_firm_id']];
        $total = $this->m_m->listUser( 'count(*) num', $where );
        $res = $this->m_m->listUser( $column, $where, $order, $limit );

        if( $total )
            $ret['total'] = (int)$total[0]['num'];

        if( $res ){
            $ret['page_n'] = count($res);
            $ret['data'] = \Common\enforceInt($res);
        }

        $string_keys = ['user','name'];
        $int_keys = ['phone','status','atime','uid'];
        foreach($ret['data'] as &$v){
            foreach($string_keys as $v1){
                $v[$v1] = (string)$v[$v1];
            }
            foreach($int_keys as $v1){
                $v[$v1] = (int)$v[$v1];
            }
        }
        unset($v);

END:
        $this->retReturn( $ret );
    }

    public function showAdminDetail(){
        $ret = [];
        $raw = $this->RxData;

        if( !isset( $raw['uid'] ) || !is_numeric( $raw['uid'] ) )
            goto END;

        $where['a.uid'] = $raw['uid'];
        $column = 'a.uid,a.admin_role,a.role_firm_id,a.icon,a.user,a.name,a.phone,b.group_id role_id,a.status,a.rem,a.atime';

        $res = $this->m_m->getUser( $column, $where );

        $res['icon'] = \Common\getCompleteUrl($res['icon']);


        $int_keys = ['uid','phone','sex','status','dept_id','height','age'];
        foreach($int_keys as &$v){
            $res[$v] = (int)$res[$v];
        }

        if( $res )
            $ret = \Common\enforceInt($res);
END:
        $this->retReturn( $ret );
    }

    public function editAdmin(){
        $raw = $this->RxData;
        $ret = [];

        $keys = [
            'user', 'pass', 'icon', 'name', 'phone','status','group_id','admin_role','role_firm_id'
        ];

        $keys_m = [
            'role_id','user','admin_role'
        ];
        foreach ($keys_m as $v) {
            if (!$raw[$v]){
                $ret['status'] = 5;
                $ret['errstr'] = $v.' miss';
                goto END;
            }
        }
        foreach ($keys as $v) {
            if ($raw[$v])
                $data[$v] = $raw[$v];
        }

        if($data['pass'])
            $data['pass'] = \Common\getRealPass($data['pass']);

        if(!$data || $data['group_id'] == 1){
            $ret['status'] = 5;
            $ret['errstr'] = 'wrong params';
            goto END;
        }

        if($raw['admin_role'] == S_TRUE && $this->out['admin_role'] == S_TRUE){        //super admin
            $data['role_firm_id'] = 0;
        }else{
            $data['role_firm_id'] = implode(',',$raw['role_firm_id']);
            $data['admin_role'] = S_FALSE;
        }

        if (isset($raw['uid'])) {
            if(  $raw['uid'] == 1 && ( $this->out['uid'] != 1 || $raw['role_id'] != 1 ) ){
                $ret['status'] = 14;
                goto END;
            }

            $exist = $this->modelUser->findAdminUser('uid', ['uid' => $raw['uid']]);
            if (!$exist ) {
                $ret['status'] = 12;
                $ret['errstr'] = 'user not exist';
                goto END;
            }
            if($raw['phone']){
                $exist = $this->modelUser->findAdminUser('uid', ['uid' => ['neq',$raw['uid']],'phone'=>$raw['phone']]);
                if ($exist) {
                    $ret['status'] = 20013;
                    $ret['errstr'] = 'phone already exist';
                    goto END;
                }
            }
            if($raw['user']){
                $exist = $this->modelUser->findAdminUser('uid', ['uid' => ['neq',$raw['uid']],'user'=>$raw['user']]);
                if ($exist) {
                    $ret['status'] = 20012;
                    $ret['errstr'] = 'user already exis5t';
                    goto END;
                }
            }

            $uid = $raw['uid'];
            $res = $this->modelUser->editAdminUser($data,['uid' => $raw['uid']]);

        } else {
            $data['atime'] = time();
            if($raw['phone']){
                $exist = $this->modelUser->findAdminUser('', ['phone'=>$raw['phone']]);
                if ($exist) {
                    $ret['status'] = 13;
                    $ret['errstr'] = 'phone already exist';
                    goto END;
                }
            }
            if($raw['user']){
                $exist = $this->modelUser->findAdminUser('', ['user'=>$raw['user']]);
                if ($exist) {
                    $ret['status'] = 12;
                    $ret['errstr'] = 'user already exist4';
                    goto END;
                }
            }
            $uid = $this->modelUser->addAdminUser($data);
            if (!$uid) {
                $ret['status'] = 1;
                $ret['errstr'] = 'add failed';
                goto END;
            }
        }
//access data
        $group = [];
        if ($raw['role_id']) {
            $raw['role_id'] = is_array($raw['role_id'])?$raw['role_id']:[$raw['role_id']];
            $roles_access = D('AuthRule')->selectAuthGroup('', ['id' => ['in', $raw['role_id']]]);
            foreach ($roles_access as $v) {
                $roll_acess_data[] = ['uid' => $uid, 'group_id' => $v['id']];
                $group[] = $v['title'];
            }

        }

//add access
        $role_res1 = D('AuthRule')->delAuthGroupAccess(['uid' => $uid]);
        if (!empty($roll_acess_data)) {
            $role_res1 = D('AuthRule')->addAuthGroupAccessAll($roll_acess_data);
        }

        $res_group = $this->modelUser->editAdminUser(['roles'=>implode(',',$group)],['uid' => $uid]);

        $ret['id'] = $uid;
        //图片处理
        $img_keys = ['icon'];
        foreach($img_keys as $v1){
            if($raw[$v1]){
                if(strstr($raw[$v1],'temp')){
                    $file_name = basename($raw[$v1]);
                    $res_move_file = \Common\ownUploadImgIndirect2($raw[$v1], C("upload_path").'admin/uid_'.$uid.'/'.$file_name);
                    $res_edit_img = $this->modelUser->editAdminUser([$v1 => C("upload_path").'admin/uid_'.$uid.'/'.$file_name],['uid' => $uid]);
                }
            }
        }



        $ret['status'] = 0;
        $ret['errstr'] = '';
END:
        $this->retReturn($ret);
    }

    public function delAdmin(){
        $raw = $this->RxData;

        $ret['status'] = 1;
        $ret['errstr'] = '';
        if (!is_array($raw['uid']))
            goto END;

        $where['uid']    = $raw['uid'];
        $exist = $this->modelUser->findAdmin('', $where);
        if(!$exist){
            $ret['status'] = 23;
            $ret['errstr'] = '';
            goto END;
        }
        $res  = $this->modelUser->delAdmin($where);
        if(!$res)
            goto END;
        $ret['status'] = 0;

END:
        $this->retReturn($ret);
    }


    //department
    public function showDepList(){
        $this->showDeptList();
    }

    public function showDeptList(){

        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page - 1) . ',' . $num;
        $where = [];

        if($raw['name'])
            $where['name'] = ['like','%'.$raw['name'].'%'];

        //列出权限下的部门列表
        if($raw['fid'] ){
            if($this->out['admin_role'] == S_TRUE){
                $where['fid'] = $raw['fid'];
            }else{
                $where['fid'] = ['in',$this->out['role_firm_id']];
            }
        }else{
            $where['fid'] = ['in',$this->out['role_firm_id']];
        }

        $order = 'atime desc';
        $columns = '*,num user_n';
        $result = $this->modelUser->selectDept($columns, $where, $limit, $order);
        if (!$result)
            goto END;
        $int_keys = ['id','num'];
        foreach($result as &$v){
            foreach($int_keys as $vv){
                $v[$vv] = (int)$v[$vv];
            }
        }
        unset($v);


        $count = $this->modelUser->selectDept('count(*) total', $where);

        $ret['total'] = (int)$count[0]['total'];
        $ret['page_start'] = $page;
        $ret['page_n'] = count($result);



        $ret['data']   = $result;

END:
        $this->retReturn($ret);
    }

    public function showDeptDetail(){
        $raw = $this->RxData;

        if(!isset($raw['id']) || !is_numeric($raw['id']))
            goto END;


        $res = $this->modelUser->findDept('',['id'=>$raw['id']]);
        if(!$res){
            goto END;
        }

        if(!in_array($raw['fid'],$this->out['role_firm_id'])){
            goto END;
        }

        $res = $this->modelUser->findDept('',['id'=>$raw['id']]);

END:
        $int_keys = ['id'];
        foreach($int_keys as $v){
            $res[$v] = (int)$res[$v];
        }
        $ret = $res;

        $this->retReturn( $ret );
    }

    public function editDep(){
        $raw = $this->RxData;
        $ret = [];

        $keys = ['name','fid'];

        $data = [];
        foreach ($keys as $v) {
            if ($raw[$v])
                $data[$v] = $raw[$v];
        }

        if(empty($data)){
            $ret['status'] = 5;
            $ret['errstr'] = '';
            goto END;
        }
        if($raw['fid']){
            if($this->out['admin_role'] != S_TRUE){
                if(!in_array($raw['fid'],$this->out['role_firm_id'])){
                    $ret['status'] = 14;
                    goto END;
                }
            }

        }

        if (isset($raw['id'])) {
            $exist = $this->modelUser->findDept('', ['id' => $raw['id']]);

            if (!$exist) {
                $ret['status'] = 13;
                $ret['errstr'] = 'not exist';
                goto END;
            }


            if($this->out['admin_role'] != S_TRUE){
                if(!in_array($raw['fid'],$this->out['role_firm_id'])){
                    $ret['status'] = 14;
                    goto END;
                }
            }
 
            $res = $this->modelUser->editDept($data,['id' => $raw['id']]);
        } else {
            $data['atime'] = time();
            $res = $this->modelUser->addDept($data);
            if (!$res) {
                $ret['status'] = 1;
                $ret['errstr'] = 'add failed';
                goto END;
            }
            $raw['id'] = $res;
        }

        //图片处理
        $img_keys = ['cover'];
        foreach($img_keys as $v1){
            if($raw[$v1]) {
                if (strstr($raw[$v1], 'temp')) {
                    $file_name = basename($raw[$v1]);;
                    $res_move_file = \Common\ownUploadImgIndirect2($raw[$v1], C("upload_path").'depart_'.$raw['id'].'/'. $file_name);
                    $res_edit_img = $this->modelUser->editUser([$v1 => C("upload_path").'depart_'.$raw['id'].'/'.$file_name],['uid' => $uid]);

                }

            }
        }

        $ret['status'] = 0;
        $ret['errstr'] = '';
END:
        $this->retReturn($ret);
    }

    public function delDep(){
        $raw = $this->RxData;

        $ret['status'] = 1;
        $ret['errstr'] = '';
        if (!is_array($raw['id']))
            $raw['id'] = [$raw['id']];

        $where['id']    = ['in',$raw['id']];
        $exist = $this->modelUser->findDept('', $where);
        if(!$exist){
            $ret['status'] = 13;
            $ret['errstr'] = '';
            goto END;
        }

        $exist = $this->modelUser->findDept('', ['fid'=>['in',$raw['id']]]);
        if($exist){
            $ret['status'] = 12;
            $ret['errstr'] = '';
            goto END;
        }

        $exist_user = $this->modelUser->findUser('', ['dept_id'=>['in',$raw['id']],'dept_pid'=>['in',$raw['id']],'_logic'=>'or']);
        if($exist_user){
            $ret['status'] = 12;
            $ret['errstr'] = 'user still exist';
            goto END;
        }
        $res  = $this->modelUser->delDept($where);
        if(!$res)
            goto END;
        $ret['status'] = 0;
        $ret['errstr'] = '';

END:
        $this->retReturn($ret);
    }

    public function editUserDep(){
        $raw = $this->RxData;
        $ret = [];
        $ret['errstr'] = '';
        $keys_m = 'uid,dept_id';
        foreach ($keys_m as $v) {
            if (!$raw[$v]){
                $ret['status'] = 5;
                $ret['errstr'] = '';
                goto END;
            }
        }
        if(!array($raw['uid']))
            $raw['uid'] = [$raw['uid']];
        $exist_dept = $this->modelUser->findDept('id',['id'=>$raw['dept_id']]);
        if(!$exist_dept){
            $ret['status'] = 13;
            $ret['errstr'] = 'dept not exist';
            goto END;
        }
        $exist_user = $this->modelUser->selectUser('uid,dept_id',['uid'=>['in',$raw['uid']]]);

        if(count($exist_user) != count($raw['uid'])){
            $ret['status'] = 13;
            $ret['errstr'] = 'user not exist';
            goto END;
        }

        $res = $this->modelUser->editUser(['dept_id'=>$raw['dept_id']],['uid'=>['in',$raw['uid']]]);

        $ret['status'] = 0;

END:
        $this->retReturn($ret);
    }


    //firm
    public function showFirmist(){
        $this->showFirmList();
    }
    public function showFirmList(){

        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page - 1) . ',' . $num;
        $where = [];

        if($raw['name'])
            $where['name'] = ['like','%'.$raw['name'].'%'];
        if($raw['status'])
            $where['status'] = $raw['status'];

        if($this->out['admin_role'] == S_FALSE){
            $where['id'] = ['in',$this->out['role_firm_id']];
        }


        $order = 'atime desc';
        $columns = '*,num user_n';
        $result = $this->modelUser->selectFirm($columns, $where, $limit, $order);
        if (!$result)
            goto END;

        $int_keys = ['id','user_n','status','num'];
        foreach($result as &$v){
            $v['cover'] = \Common\getCompleteUrl($v['cover']);
            foreach($int_keys as $vv){
                $v[$vv] = (int)$v[$vv];
            }
        }
        unset($v);
        $count = $this->modelUser->selectFirm('count(*) total', $where);


        $ret['total'] = (int)$count[0]['total'];
        $ret['page_start'] = $page;
        $ret['page_n'] = count($result);
        $ret['data']   = $result;

END:
        $this->retReturn($ret);
    }


    public function showFirmDetail(){
        $raw = $this->RxData;

        $ret = [];
        if(!isset($raw['id']) || !is_numeric($raw['id']))
            goto END;
        if($this->out['admin_role'] != S_TRUE && !in_array($raw['id'],$this->out['role_firm_id'])){
                goto END;
        }

        $res = $this->modelUser->findFirm('',['id'=>$raw['id']]);
        if(!$res){
            goto END;
        }


        $url_keys = ['logo','cover'];
        foreach($url_keys as $v)
            $res[$v] =\Common\getCompleteUrl($res[$v]);

        $int_keys = ['id'];
        foreach($int_keys as $v){
            $res[$v] = (int)$res[$v];
        }

        $ret = $res;

END:
        $this->retReturn( $ret );
    }

    public function editFirm(){
        $raw = $this->RxData;
        $ret = [];

        if($this->out['admin_role'] == S_FALSE){
            $ret['status'] = 14;
            goto END;

        }
        $keys = ['name','intro','cover','logo','status','contact','contact_phone','contact_addr'];

        $data = [];
        foreach ($keys as $v) {
            if ($raw[$v])
                $data[$v] = $raw[$v];
        }
        $data['intro'] = $raw['intro']?$raw['intro']:'/';

        if(empty($data)){
            $ret['status'] = 5;
            $ret['errstr'] = '';
            goto END;
        }

        if (isset($raw['id'])) {
            $exist = $this->modelUser->findFirm('', ['id' => $raw['id']]);
            if($exist['fid'] == 0){
                if($this->out['admin_role'] != S_TRUE){
                    if(!in_array($raw['id'],$this->out['role_firm_id'])){
                        $ret['status'] = 14;
                        goto END;
                    }
                }
            }else{
                if($this->out['admin_role'] != S_TRUE){
                    if(!in_array($raw['fid'],$this->out['role_firm_id'])){
                        $ret['status'] = 14;
                        goto END;
                    }
                }
            }
            if (!$exist) {
                $ret['status'] = 13;
                $ret['errstr'] = 'not exist';
                goto END;
            }

            $res = $this->modelUser->editFirm($data,['id' => $raw['id']]);
        } else {
            $data['atime'] = time();
            $res = $this->modelUser->addFirm($data);
            if (!$res) {
                $ret['status'] = 1;
                $ret['errstr'] = 'add failed';
                goto END;
            }
            $raw['id'] = $res;
        }

        //图片处理
        $img_keys = ['cover','logo'];
        foreach($img_keys as $v1){
            if($raw[$v1]) {
                if (strstr($raw[$v1], 'temp')) {
                    $file_name = basename($raw[$v1]);;
                    $res_move_file = \Common\ownUploadImgIndirect2($raw[$v1], C("upload_path").'depart_'.$raw['id'].'/'. $file_name);
                    $res_edit_img = $this->modelUser->editFirm([$v1 => C("upload_path").'depart_'.$raw['id'].'/'.$file_name],['id' =>  $raw['id']]);
                }
            }
        }

        $ret['status'] = 0;
        $ret['errstr'] = '';
END:
        $this->retReturn($ret);
    }

    public function delFirm(){
        $raw = $this->RxData;

        $ret['status'] = 1;
        $ret['errstr'] = '';
        if (!is_array($raw['id']))
            $raw['id'] = [$raw['id']];

        $where['id']    = ['in',$raw['id']];
        $exist = $this->modelUser->findFirm('', $where);
        if(!$exist){
            $ret['status'] = 13;
            $ret['errstr'] = '';
            goto END;
        }

        $exist_user = $this->modelUser->findUser('', ['fid'=>['in',$raw['id']]]);
        if($exist_user){
            $ret['status'] = 12;
            $ret['errstr'] = 'user still exist';
            goto END;
        }
        $res  = $this->modelUser->delFirm($where);
        if(!$res)
            goto END;
        $ret['status'] = 0;
        $ret['errstr'] = '';

END:
        $this->retReturn($ret);
    }


    //like
    public function showLikeList(){
        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page - 1) . ',' . $num;
        $where = [];

        if($raw['nickname_from']){
            $uid_from = [];
            $user_from_data = $this->modelUser->selectUser('uid',['nickname'=>['like','%'.$raw['nickname_from'].'%']]);
            if(!$user_from_data)
                goto END;
            foreach($user_from_data as $v)
                $uid_from[] = $v['uid'];
            $where['uid'] = ['in',$uid_from];

        }
      if($raw['nickname_to']){
            $uid_to = [];
            $user_to_data = $this->modelUser->selectUser('uid,nickname',['nickname'=>['like','%'.$raw['nickname_to'].'%']]);
            if(!$user_to_data)
                goto END;
            foreach($user_to_data as $v)
                $uid_to[] = $v['uid'];
          $where['to_uid'] = ['in',$uid_to];
      }

        if ($raw['time_start'] && $raw['time_end']) {
            $where['mtime'] = [
                ['egt', $raw['time_start']],
                ['lt', $raw['time_end']]
            ];
        } elseif ($raw['time_start']) {
            $where['mtime'] = ['egt', $raw['time_start']];
        } elseif ($raw['time_end']) {
            $where['mtime'] = ['lt', $raw['time_end']];
        }

        $order = 'mtime desc';
        $res = $this->modelUser->selectUserLike('*,mtime atime',$where,$limit,$order);
        if(!$res)
            goto END;

        $uid = $to_uid = [];
        foreach($res as $v){
            $to_uid[] = $v['to_uid'];
            $uid[] = $v['uid'];
            $uid[] = $v['to_uid'];
        }
        $res_count_like = $this->modelUser->selectUserLike('id,to_uid',['to_uid'=>['in',$to_uid]]);
        $like_count = [];
        foreach($res_count_like as $v){
            if(!$like_count[$v['to_uid']])
                $like_count[$v['to_uid']] = 0;
            $like_count[$v['to_uid']] ++;
        }

        $user_info =$this->modelUser->getUserInfo($uid);
        foreach ($res as &$item) {
            $item['uid_from']       =   $item['uid'];
            $item['user_from']      =   $user_info[$item['uid']]['user'];
            $item['nickname_from'] =    $user_info[$item['uid']]['nickname'];
            $item['uid_to']         =    $item['did'];
            $item['liked_n']        =   $like_count[$item['to_uid']];
            $item['user_to']        =   $user_info[$item['to_uid']]['uid'];
            $item['nickname_to']   =    $user_info[$item['to_uid']]['nickname'];
        }

        $count = $this->modelUser->selectUserLike('count(*) total', $where);

        $ret['total'] = (int)$count[0]['total'];
        $ret['page_start'] = $page;
        $ret['page_n'] = count($res);
        $ret['data']   = $res;

END:
        $this->retReturn($ret);
    }


    //rank
    public function showRankList(){
        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $res = $this->modelAct->showUserStepRankList($raw);
        if(!$res)
            goto END;
        $ret = $res;

END:
        $this->retReturn($ret);
    }

    public function showDepRankList(){
        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];
        $res = $this->modelUser->getDeptRankList($raw);
        if(!$res)
            goto END;
        $ret = $res;
END:
        $this->retReturn($ret);
    }

    public function sortt($a,$type=false){
        //从小到大
        $len = count($a);
        for($i=1;$i<=$len;$i++){
            for($j=$len-1;$j>=$i;$j--){
                if($a[$j]['step']>$a[$j-1]['step']){
                    //如果是从大到小的话，只要在这里的判断改成if($b[$j]<$b[$j-1])就可以了
                    $tmp=$a[$j];
                    $a[$j]=$a[$j-1];
                    $a[$j-1]=$tmp;
                }
            }
        }
        return $a;
    }


}
