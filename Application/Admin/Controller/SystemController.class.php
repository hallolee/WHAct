<?php
namespace Admin\Controller;

class SystemController extends GlobalController{

    protected $modelUser;
    protected $m_m;
    protected $m_m1;
    protected $m_m2;
    public function _initialize(){
        parent::_initialize();
        $this->modelUser   = D('User');
        $this->m_m = D('Admin/Backend');
        $this->m_m1 = D('Admin/AuthRule');
        $this->m_m2 = D('Admin/BasicInfo');
    }

    public function upload(){
        $re = [];
        $uid = $this->out['uid'];

        $head = C('PREURL');

        $field = 'picture';
        $pre_path = C("UPLOAD_PATH");
        $realpath = $pre_path.'profile'."/".'uid_'.$uid.'/icon/';

        $conf = array(
            'pre' => 'pro',
            'types' => ['jpg', 'gif', 'png', 'jpeg'],
        );

        if( !is_dir($realpath) ) $z = mkdir( $realpath, 0775, true );
        $upload_res = \Common\commonUpload($field,$realpath,$conf);

        if( $upload_res['state'] != 0 ){
            $re =  json_encode($upload_res);
            goto END;
        }


        foreach ($upload_res['file'] as $key => $value) {

            $file_path = $value['savepath'].$value['savename'];
            $path = $realpath.$value['savename'];
        }


        $re[ 'status' ] = 0;
        $re[ 'errstr' ] = '';
        $re[ 'url' ] = $head.$path;
        $re[ 'path' ] = $path;
END:
        $this->retReturn( $re );
    }


    //roles
    public function roleList(){
        $ret = [ 'total' => 0 , 'page_start' => 0, 'page_n' => 0, 'data' => [] ];
        $raw = $this->RxData;

        $page_start = ( !isset( $raw[ 'page_start' ] ) || !is_numeric( $raw[ 'page_start' ] ) )?'1':$raw[ 'page_start' ];
        $page_limit = ( !isset( $raw[ 'page_limit' ] ) || !is_numeric( $raw[ 'page_limit' ] ) )?C('PAGE_LIMIT'):$raw[ 'page_limit' ];
        $limit = ($page_start-1)*$page_limit.','.$page_limit;
        $ret['page_start'] = (int)$page_start;

        $where['id'] = array('neq','1');

        if( isset( $raw['title'] ) && !empty( $raw['title'] ) )
            $where['title'] = [ 'like', '%'.$raw['title'].'%' ];

        if( isset( $raw['status'] ) && $raw['status'] !== '' && in_array( $raw['status'], [1,2] ) )
            $where['status'] = $raw['status'];

        $total = $this->m_m1->selectAuthGroup( 'count(id) num', $where );
        $res = $this->getAuthGroup( $where, $limit );
        foreach ($res as $val) {
            $ret['data'][] = array(
                'id'    => (int)$val['id'],
                'status'=> (int)$val['status'],
                'title' => $val['title'],
                'rem'   => $val['rem']
            );
        }

        if( $total )
            $ret['total'] = (int)$total[0]['num'];


        $ret['page_n'] = count($ret['data']);
END:
        $this->retReturn($ret);
    }


    public function ShowAuthGroupDetail(){
        $ret = [];
        $raw = $this->RxData;

        if( isset( $raw['id'] ) && is_numeric( $raw['id'] ) ){
            $where['id'] = $raw['id'];
            $group = $this->getAuthGroup( $where );
            foreach ($group as $val) {
                $ret  = array(
                    'id'    => (int)$val['id'],
                    'title' => $val['title'],
                    'role_id' => $val['group_id'],
                    'group_id' => $val['group_id'],
                    'status'=> (int)$val['status'],
                    'rem'   => $val['rem'],
                    'rule'  => explode(',',$val['rule']),
                    'dept_id'  => explode(',',$val['dept_id']),
                );
            }
        }

        $this->retReturn($ret);
    }


    public function ShowAuthRule(){
        $ret = [];

        $where = [];
        $res = $this->getAuthRule($where);

        // 将标题一项抽出来
        foreach ($res as $key => $value) {
            $class = $value['class'];
            $category= $value['category'];
            if($category=='title'){
                $tip[ $value['order'] ] = [ 'title' => $value['title'], 'id' => $value['id'] ];
                $order[ $class ] = $value['order'];
                if( !is_numeric( $value['name'] ) ){
                    $cache['page'][$value['order']][] = array(
                        'id'    => (int)$value['id'],
                        'title' => $value['title'],
                    );
                }
            }
        }

        foreach ($res as $key => $value) {
            $class = $value['class'];
            $category= $value['category'];
            if($category!='title'){
                $num = $order[$class];
                $cache[$category][$num][] = array(
                    'id'    => (int)$value['id'],
                    'title' => $value['title'],
                );
            }
        }

        foreach ($tip as $key => $value) {
            // $ret['api'][] = [
            //     "id"        => $value['id'],
            //     "title"     => $value['title'],
            //     "data"      => $cache['api'][$key]?$cache['api'][$key]:[]
            // ];

            $ret[] = [
                "id"        => (int)$value['id'],
                "title"     => $value['title'],
                "data"      => $cache['page'][$key]?$cache['page'][$key]:[ 'title' => $value['title'], 'id' => $value['id'] ]
            ];
        }

        $this->retReturn($ret);
    }


    public function editAuthGroup(){
        $ret = [ 'status' => 1, 'errstr' => '' ];
        $raw = $this->RxData;

        $keys = [ 'title', 'rules', 'status', 'rem' ];
        foreach ($keys as $val) {
            if( isset( $raw[ $val ] ) && $raw[ $val ] !== '' )
                $d[$val] = $raw[ $val ];
        }
        $d['dept_id'] = $raw['dept_id'];

        $id = ( isset( $raw['id'] ) && is_numeric( $raw['id'] ) )?$raw['id']:'0';

        if( !$d ){
            $ret['status'] = 5;
            goto END;
        }

        if( $id ){
            if( $id == 1 ){
                $ret['status'] = 14;
                goto END;
            }

            $res = $this->m_m1->saveAuthGroup( [ 'id' => $id ], $d );

            if( $res === false )
                goto END;
        }else{
            if( $rules || $title ) goto END;

            $res = $this->m_m1->addAuthGroup( $d );

            if( !$res )
                goto END;

            $ret['id'] = $res;
        }

        $ret['status'] = 0;
END:
        $this->retReturn($ret);
    }

    public function delAuthGroup(){
        $raw = $this->RxData;

        $ret['status'] = 1;
        $ret['errstr'] = '';
        if (!is_array($raw['id']))
            $raw['id'] = [$raw['id']];

        $where['id']    = ['in',$raw['id']];
        $exist = $this->m_m1->findAuthGroup('', $where);
        if(!$exist){
            $ret['status'] = 13;
            $ret['errstr'] = '';
            goto END;
        }
        $exist_user = $this->m_m1->findAuthGroupAccess('', ['group_id'=>['in',$raw['id']]]);
        if($exist_user){
            $ret['status'] = 12;
            $ret['errstr'] = 'user still exist';
            goto END;
        }
        $res  = $this->m_m1->delAuthGroup($where);
        if(!$res)
            goto END;
        $ret['status'] = 0;
        $ret['errstr'] = '';

END:
        $this->retReturn($ret);
    }

    public function getAuthGroup($where='', $limit=''){              //内部方法，获取角色分组信息

        $group = $this->m_m1->selectAuthGroup( '', $where, 'id desc', $limit );
        foreach ($group as $key => $value) {
            $data[] = array(
                'id'    => $value['id'],
                'title' => $value['title'],
                'status'=> $value['status'],
                'rem'   => $value['rem'],
                'rule'  => $value['rules'],
                'group_id'  => $value['group_id'],
                'dept_id'  => $value['dept_id']
            );
        }

        return $data;
    }


    public function getAuthRule($where=''){               //内部方法，获取所有权限节点信息

        $order = ['`order` asc'];
        $rule = $this->m_m1->selectAuthRule( '', $where, $order );

        foreach ($rule as $key => $value) {
            $data[] = array(
                'id'    => $value['id'],
                'name'  => $value['name'],
                'title' => $value['title'],
                'status' => $value['status'],
                'category' => $value['category'],
                'class' => $value['class'],
                'order' => $value['order'],
            );
        }

        return $data;
    }






}