<?php

    function CheckAuth( $uid='0', $page='' ){
        $AUTH = new \Think\Auth();  //类库位置应该位于ThinkPHP\Library\Think\
        if(!$AUTH->check( $page, $uid, 1 )){
            return 14;
        }
        return 0;
    }

    function CheckAuthBtn($name='', $userid='0'){
        $ret = 1;

        $where['uid'] = $userid;
        $group = M(TAUTH_GROUP_ACC)->where($where)->find();

        $admin = M(TAUTH_GROUP)->field('id')->where([ 'rules' => '0' ])->find();

        if( $group['group_id'] == $admin['id'] ){
            $ret = 0;
        }else{
            $AUTH = new \Think\Auth();
            if($AUTH->check($name, $userid)){
                $ret = 0;
            }
        }

        return $ret;
    }


?>
