<?php
namespace Admin\Controller;

class ManageController extends GlobalController {

    protected $m_m;
    protected $m_m1;
    protected $m_m2;

    public function _initialize(){
        parent::_initialize();
        $this->m_m = D('Admin/Backend');
        $this->m_m1 = D('Admin/AuthRule');
        $this->m_m2 = D('Admin/BasicInfo');
    }


    public function showManager(){
        $raw = $this->RxData;
        $ret = [ 'total' => 0 , 'page_start' => 0, 'page_n' => 0, 'data' => [] ];

        $page_start = ( !isset( $raw[ 'page_start' ] ) || !is_numeric( $raw[ 'page_start' ] ) )?'1':$raw[ 'page_start' ];
        $page_limit = ( !isset( $raw[ 'page_limit' ] ) || !is_numeric( $raw[ 'page_limit' ] ) )?C('PAGE_LIMIT'):$raw[ 'page_limit' ];
        $limit = ($page_start-1)*$page_limit.','.$page_limit;
        $ret['page_start'] = $page_start;

        $where = [];
        if( isset ( $raw[ 'realname' ] ) && !empty( $raw[ 'realname' ] ) )
            $where['a.realname'] = [ 'like', '%'.$raw[ 'realname' ].'%' ];

        if( isset ( $raw[ 'phone' ] ) && !empty( $raw[ 'phone' ] ) )
            $where['a.phone'] = [ 'like', '%'.$raw[ 'phone' ].'%' ];

        if( isset ( $raw[ 'time' ] ) && is_array( $raw[ 'time' ] ) && !empty( $raw[ 'time' ]['min'] ) && !empty( $raw[ 'time' ]['max'] ) ){
            $where['a.atime'] = [
                [ 'egt', $raw[ 'time' ]['min'] ],
                [ 'lt', $raw[ 'time' ]['max'] ]
            ];
        }

        $column = 'a.uid,a.realname,a.phone,c.title `group`,a.status rt_status,a.atime';
        $order = 'a.atime DESC';

        $total = $this->m_m->listUser( 'count(a.uid) num', $where );
        $res = $this->m_m->listUser( $column, $where, $order, $limit );

        if( $total )
            $ret['total'] = $total[0]['num'];

        if( $res ){
            $ret['page_n'] = count($res);
            $ret['data'] = \Common\enforceInt($res);
        }
END:
        $this->retReturn( $ret );
    }


    public function showManagerDetail(){
        $ret = [];
        $raw = $this->RxData;

        if( !isset( $raw['uid'] ) || !is_numeric( $raw['uid'] ) )
            goto END;

        $where['a.uid'] = $raw['uid'];
        $column = 'a.uid,a.user,a.realname,a.phone,b.group_id,a.status rt_status,a.rem,a.atime';

        $res = $this->m_m->getUser( $column, $where );

        if( $res )
            $ret = \Common\enforceInt($res);
END:
        $this->retReturn( $ret );
    }


    public function editManager(){
        $ret = [ 'status' => 1, 'errstr' => '' ];
        $raw = $this->RxData;

        $keys = [ 'user', 'pass', 'realname', 'phone', 'status', 'rem' ];
        foreach ($keys as $val) {
            
            if( isset( $raw[ $val ] ) || !empty( $raw[ $val ] ) )
                $d[$val] = $raw[ $val ];
        }
        $uid = ( isset( $raw['uid'] ) && is_numeric( $raw['uid'] ) )?$raw['uid']:'0';
        $group_id = ( isset( $raw['group_id'] ) && is_numeric( $raw['group_id'] ) )?$raw['group_id']:'0';
        
        if( $group_id == 1 ){
            $ret['status'] = 14;
            goto END;
        }
        if( empty( $d ) && $group_id == '0' ){
            $ret['status'] = 5;
            goto END;
        }

        $chk_w[] = [ 'user' => $d['user'], '_logic' => 'or','phone' => $d['phone'] ];
        if( $uid ) $chk_w[ 'uid' ] = [ 'neq', $uid ];
        $chk = $this->m_m->findUser( 'uid', $chk_w );
        // $ret['sql'] = M()->GetlastSql();
        if( $chk ){
            $ret['status'] = E_EXIST;
            goto END;
        }

        if( isset( $d['pass'] ) )
            $d['pass'] = \Common\GetRealPass( $d['pass'] );

        if( $uid ){
            if(  $uid == 1 && ( $this->out['uid'] != 1 || $group_id != '0' ) ){
                $ret['status'] = 14;
                goto END;
            }

            $res = $this->m_m->saveUser( [ 'uid' => $uid ], $d );

            if( $res === false )
                goto END;

            if( $group_id )
                $this->m_m1->saveAuthGroupAccess( [ 'uid' => $uid ], [ 'group_id' => $group_id ] );

        }else{
            $d['atime'] = time();
            if( empty( $d['user'] ) || empty( $d['pass'] ) ){
                $ret['status'] = 5;
                goto END;
            }

            $res = $this->m_m->addUser( $d );

            if( !$res )
                goto END;

            $this->m_m1->addAuthGroupAccess( [ 'uid' => $res, 'group_id' => $group_id ] );
        }

        $ret['status'] = 0;

END:
        $this->retReturn( $ret );
    }


    public function ShowAuthGroup(){
        $ret = [ 'total' => 0 , 'page_start' => 0, 'page_n' => 0, 'data' => [] ];
        $raw = $this->RxData;

        $page_start = ( !isset( $raw[ 'page_start' ] ) || !is_numeric( $raw[ 'page_start' ] ) )?'1':$raw[ 'page_start' ];
        $page_limit = ( !isset( $raw[ 'page_limit' ] ) || !is_numeric( $raw[ 'page_limit' ] ) )?C('PAGE_LIMIT'):$raw[ 'page_limit' ];
        $limit = ($page_start-1)*$page_limit.','.$page_limit;
        $ret['page_start'] = $page_start;

        $where['id'] = array('neq','1');

        if( isset( $raw['title'] ) && !empty( $raw['title'] ) )
            $where['title'] = [ 'like', '%'.$raw['title'].'%' ];

        if( isset( $raw['rt_status'] ) && $raw['rt_status'] !== '' && in_array( $raw['rt_status'], [1,2] ) )
            $where['status'] = $raw['rt_status']==2?0:1;

        $total = $this->m_m1->selectAuthGroup( 'count(id) num', $where );
        $res = $this->getAuthGroup( $where, $limit );
        foreach ($res as $val) {
            $ret['data'][] = array(
                'id'    => $val['id'],
                'rt_status'=> $val['status'],
                'title' => $val['title'],
                'rem'   => $val['rem']
                );
        }

        if( $total )
            $ret['total'] = $total[0]['num'];

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
                    'id'    => $val['id'],
                    'title' => $val['title'],
                    'rt_status'=> $val['status'],
                    'rem'   => $val['rem'],
                    'rule'  => explode(',',$val['rule']),
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
                        'id'    => $value['id'],
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
                    'id'    => $value['id'],
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
                "id"        => $value['id'],
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


    public function getAuthGroup($where='', $limit=''){              //内部方法，获取角色分组信息

        $group = $this->m_m1->selectAuthGroup( '', $where, '', $limit );
        foreach ($group as $key => $value) {
            $data[] = array(
                'id'    => $value['id'],
                'title' => $value['title'],
                'status'=> $value['status'],
                'rem'   => $value['rem'],
                'rule'  => $value['rules'],
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
