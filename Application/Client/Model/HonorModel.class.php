<?php
namespace Client\Model;

class HonorModel extends GlobalModel
{
    protected $tableName = THONOR; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    public function _initialize() {
        parent::_initialize();
    }

    public function findHonor( $column='', $where='', $order='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->find();

        return $re;
    }

    public function selectHonor( $column='', $where='', $order='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }


    public function getHonorInfo( $column='', $where='', $order='', $limit='', $extra=[] ){
        $extra_str = '';
        if( $extra['uid'] )
            $extra_str = ' AND b.uid = '.$extra['uid'];

        $re = $this
            ->alias('a')
            ->field( $column )
            ->join( ' LEFT JOIN '.TCLI_HONOR.' b on a.id = b.hid AND b.type = '.HT_FIXED.$extra_str )
            ->where( $where )
            ->limit( $limit )
            ->order( $order )
            ->select();

        return $re;
    }


    public function getActHonorInfo( $column='', $where='', $order='', $limit='' ){
        $re = M(TCLI_HONOR)
            ->alias('a')
            ->field( $column )
            ->join( ' INNER JOIN '.TACT.' b on a.hid = b.id AND a.type = '.HT_ACT )
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }



    public function findClientHonor( $column='', $where='', $order='' ) {
        $re = M(TCLI_HONOR)
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->find();

        return $re;
    }

    public function selectClientHonor( $column='', $where='', $order='', $group='' ) {
        $re = M(TCLI_HONOR)
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->group( $group )
            ->select();

        return $re;
    }



    public function findHonorOrder( $column='', $where='', $order='' ) {
        $re = M(THONOR_O)
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->find();

        return $re;
    }

    public function selectHonorOrder( $column='', $where='', $order='' ) {
        $re = M(THONOR_O)
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }


    public function setHonor( $uid='', $today_step=0 ){
        $t = time();
        $today = strtotime( date( 'Ymd', $t ) );
        $yesterday = $today-86400;
        $where = [ 'uid' => $uid ];

        $user_res = M(TCLIENT)
            ->field('step_n')
            ->where( $where )
            ->find();

        $step_n = $user_res['step_n'] + $today_step;

        // 更新等级
        $level_res = M(TLEVEL)
            ->field('level,step_l,step_h')
            ->select();

        $level = false;
        foreach ($level_res as $value) {
            if( $step_n >= $value['step_l'] && $step_n <= $value['step_h'] ){
                $level = $value['level'];
            }
        }
        if( $level !== false )
            M(TCLIENT)->where( $where )->save( [ 'level' => $level ] );

        // 更新成就
        $dist_res = M(TSTEP)
            ->field('sum( dist ) dist')
            ->where( $where )
            ->find();

        $honor_res = M(THONOR)
            ->field('id,type,num,step_n,dist_n')
            ->select();

        $where['type'] = HT_FIXED;
        $honor_order_res = M(THONOR_O)->field( 'id,hid,num,status,mtime' )->where( $where )->select();

        $honor_o = [];
        foreach ($honor_order_res as $value) {
            $honor_o[ $value['hid'] ] = $value;
        }

        $edit_honor_order_d = $add_honor_order_d = [];
        foreach ($honor_res as $value) {
            // 已有统计记录
            if( isset( $honor_o[ $value['id'] ] ) ){
                if( $honor_o[ $value['id'] ]['status'] == S_TRUE )
                    continue;

                $detail = $honor_o[ $value['id'] ];
                // 以步数为条件
                if( $value['step_n'] != 0 ){
                    $status = S_FALSE;
                    // 以历史累积总步数为条件
                    if( $value['type'] == H_HIS_TOTAL ){
                        // 累积步数满足条件
                        if( $step_n >= $value['step_n'] ){
                            if( $detail['num']+1 >= $value['num'] ){
                                $status = S_TRUE;
                                $finish_honor[] = $value['id'];
                            }

                            $edit_honor_order_d['d'][] = [
                                'id' => $detail['id'],
                                'num' => $detail['num']+1,
                                'status' => $status,
                                'mtime' => $t
                            ];
                        }
                    }
                    // 以当天步数为条件, 更新时间不在今天
                    else if( $today_step >= $value['step_n'] && $detail['mtime'] < $today ){
                        // 每日累计
                        if( $value['type'] == H_DAY_TOTAL ){
                            if( $detail['num']+1 >= $value['num'] ){
                                $status = S_TRUE;
                                $finish_honor[] = $value['id'];
                            }

                            $edit_honor_order_d['d'][] = [
                                'id' => $detail['id'],
                                'num' => $detail['num']+1,
                                'status' => $status,
                                'mtime' => $t
                            ];

                        }
                        // 每日连续
                        else if( $value['type'] == H_DAY_CONT ){

                            // 更新时间是在昨天
                            if( $detail['mtime'] >= $yesterday ){
                                $this_num = $detail['num']+1;
                            }else{
                                $this_num = 1;
                            }

                            if( $this_num >= $value['num'] ){
                                $status = S_TRUE;
                                $finish_honor[] = $value['id'];
                            }

                            $edit_honor_order_d['d'][] = [
                                'id' => $detail['id'],
                                'num' => $this_num,
                                'status' => $status,
                                'mtime' => $t
                            ];
                        }
                    }

                }else if( $value['dist_n'] != 0 ){
                    if( $dist_res['dist'] >= $value['dist_n'] ){
                        if( $detail['num']+1 >= $value['num'] ){
                            $status = S_TRUE;
                            $finish_honor[] = $value['id'];
                        }

                        $edit_honor_order_d['d'][] = [
                            'id' => $detail['id'],
                            'num' => $detail['num']+1,
                            'status' => $status,
                            'mtime' => $t
                        ];
                    }
                }
            }
            // 未进行过统计
            else{
                // 以步数为条件
                if( $value['step_n'] != 0 ){
                    $status = S_FALSE;
                    // 以历史累积总步数为条件
                    if( $value['type'] == H_HIS_TOTAL ){
                        // 累积步数满足条件
                        if( $step_n >= $value['step_n'] ){
                            if( 1 >= $value['num'] ){
                                $status = S_TRUE;
                                $finish_honor[] = $value['id'];
                            }

                            $add_honor_order_d[] = [
                                'type' => HT_FIXED,
                                'uid' => $uid,
                                'hid' => $value['id'],
                                'num' => 1,
                                'status' => $status,
                                'mtime' => $t,
                                'atime' => $t
                            ];
                        }
                    }
                    // 以当天步数为条件
                    else if( $today_step >= $value['step_n'] ){

                        if( 1 >= $value['num'] ){
                            $status = S_TRUE;
                            $finish_honor[] = $value['id'];
                        }

                        $add_honor_order_d[] = [
                            'type' => HT_FIXED,
                            'uid' => $uid,
                            'hid' => $value['id'],
                            'num' => 1,
                            'status' => $status,
                            'mtime' => $t,
                            'atime' => $t
                        ];
                    }

                }else if( $value['dist_n'] != 0 ){
                    if( $dist_res['dist'] >= $value['dist_n'] ){
                        if( 1 >= $value['num'] ){
                            $status = S_TRUE;
                            $finish_honor[] = $value['id'];
                        }

                        $add_honor_order_d[] = [
                            'type' => HT_FIXED,
                            'uid' => $uid,
                            'hid' => $value['id'],
                            'num' => 1,
                            'status' => $status,
                            'mtime' => $t,
                            'atime' => $t
                        ];
                    }
                }
            }
        }

        if( $edit_honor_order_d ){
            $edit_honor_order_d['key'] = 'id';
            $edit_honor_order_d['column'] = [ 'num', 'status', 'mtime' ];
            $edit_honor_order_sql = $this->SaveAllSql( $edit_honor_order_d, THONOR_O );
            $edit_honor_order_res = M(THONOR_O)->execute($edit_honor_order_sql);
        }

        if( $add_honor_order_d )
            $add_honor_order_res = M(THONOR_O)->addAll( $add_honor_order_d );

        $d = [];
        foreach ($finish_honor as $key => $value) {
            $d[] = [
                'type' => HT_FIXED,
                'uid'  => $uid,
                'hid'  => $value,
                'atime' => $t
            ];
        }
        if( !$d ) return ;

        $res = M( TCLI_HONOR )->addAll( $d );
        if( !$res )
            return false;

        return true;
    }


    public function setActHonor( $uid='', $today_step=0 ){
        $t = time();
        $today = strtotime( date( 'Ymd', $t ) );
        $yesterday = $today-86400;

        $where = [ 'status' => AS_DOING, 'step_cond' => [ 'elt', $today_step ] ];
        $act_res = M(TACT)
            ->field('id,num,num_cond,step_cond,aim_dept_id,aim_uid')
            ->where( $where )
            ->select();

        if( !$act_res ) return ;

        $user_res = M(TCLIENT)
            ->field('uid,dept_id,dept_pid,job_status')
            ->where( [ 'uid' => $uid ] )
            ->find();

        $act_id = [];
        foreach ($act_res as $value) {
            $uid_arr = explode( ',', $value['aim_uid']);
            $dept_arr = explode( ',', $value['aim_dept_id']);

            if(
                (
                    (
                        $value['aim_uid'] == -1 &&
                        (in_array( $user_res['dept_id'], $dept_arr) || in_array( $user_res['dept_pid'], $dept_arr) )
                    ) ||
                    in_array( $uid, $uid_arr )
                ) &&
                $user_res['job_status'] == S_TRUE
            ){
                $act_id[] = $value['id'];
            }
        }
        if( !$act_id ) return ;

        $act_order_res = M(THONOR_O)
            ->field('id,hid,uid,num,mtime')
            ->where( [ 'hid' => [ 'in', $act_id ], 'uid' => $uid, 'type' => HT_ACT ] )
            ->select();

        $already_act_id = $num = $mtime = [];
        foreach ($act_order_res as $key => $value) {
            $already_act_id[ $value['hid'] ] = $value['id'];
            $num[ $value['hid'] ] = $value['num'];
            $mtime[ $value['hid'] ] = $value['mtime'];
        }

        $full_honor_id = $edit_act_order_d = $add_act_order_d = [];
        foreach ($act_res as $key => $value) {

            if( in_array( $value['id'], $act_id ) ){
                if( isset( $already_act_id[ $value['id'] ] ) ){
                    if( $mtime[ $value['id'] ] < $today ){
                        if( $mtime[ $value['id'] ] >= $yesterday ){
                            $this_num = $num[ $value['id'] ]+1;
                        }else{
                            $this_num = 1;
                        }

                        $status = S_FALSE;
                        if( $num[ $value['id'] ]+1 >= $value['num'] ){
                            $full_honor_id[] = $value['id'];
                            $status = S_TRUE;
                        }

                        $edit_act_order_d['d'][] = [
                            'id' => $already_act_id[ $value['id'] ],
                            'num' => $num[ $value['id'] ]+1,
                            'status' => $status,
                            'mtime' => $t
                        ];
                    }
                }else{
                    $status = S_FALSE;
                    if( 1 >= $value['num'] ){
                        $full_honor_id[] = $value['id'];
                        $status = S_TRUE;
                    }

                    $add_act_order_d[] = [
                        'type' => HT_ACT,
                        'hid' => $value['id'],
                        'uid' => $uid,
                        'num' => 1,
                        'status' => $status,
                        'atime' => $t,
                        'mtime' => $t
                    ];
                }
            }
        }

        if( $edit_act_order_d ){
            $edit_act_order_d['key'] = 'id';
            $edit_act_order_d['column'] = [ 'num', 'status', 'mtime' ];
            $edit_act_order_sql = $this->SaveAllSql( $edit_act_order_d, THONOR_O );
            $edit_act_order_res = M(THONOR_O)->execute($edit_act_order_sql);
        }

        if( $add_act_order_d )
            $add_act_order_res = M(THONOR_O)->addAll( $add_act_order_d );

        $honor_where = ['type' => HT_ACT, 'uid' => $uid];
        $honor_order_res = M(TCLI_HONOR)->field( 'hid' )->where( $honor_where )->select();

        $already_honor = [];
        foreach ($honor_order_res as $key => $value) {
            $already_honor[] = $value['hid'];
        }

        $new_honor = array_diff( $full_honor_id, $already_honor);
        if( !$new_honor ) return ;

        $d = [];
        foreach ($new_honor as $key => $value) {
            $d[] = [
                'type' => HT_ACT,
                'uid'  => $uid,
                'hid'  => $value,
                'atime' => $t
            ];

            $set_act_id[] = $value;
        }
        if( !$d ) return ;

        if( isset( $set_act_id ) && !$set_act_id )
            M(TACT)->where( [ 'id' => [ 'in', $set_act_id ] ] )->setInc( 'user_n' );

        $res = M( TCLI_HONOR )->addAll( $d );
        if( !$res )
            return false;

        return true;
    }

}
