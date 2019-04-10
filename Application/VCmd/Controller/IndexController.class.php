<?php
namespace VCmd\Controller;

class IndexController extends GlobalController {

    protected $m_m;

    public function _initialize() {
        $this->m_m = D('VCmd/Index');

        // 判断上一次调用整个流程是否已经执行完毕, 已完毕则将执行状态设为执行中
        if( !$this->setFuncStatus( ACTION_NAME ) )
            $this->ajaxReturn( [ 'status' => 25, 'errstr' => '' ] );
    }

    /*
    * 设置方法调用状态，1为执行中
    * 防止上一次未执行完即再次调用
    */
    protected function setFuncStatus( $func='' ){
        if( !$func ) return false;

        // 判断上一次调用整个流程是否已经执行完毕
        S( ['type'=>'memcached'] );
        if( S( $func ) ) return false;

        // 执行状态设为执行中
        S( $func, 1, C('VCMD_FUNC_TIME') );
        return true;
    }

    /*
    * 设置方法调用状态，置为执行完毕
    */
    protected function delFuncStatus( $func='' ){
        S( ['type'=>'memcached'] );
        S( $func, null );
    }



    public function setAct(){
        $ret = [ 'status' => 1, 'errstr' => '' ];
        $t = time();
        $today = strtotime( date( 'Ymd', $t ) );

        $where = [
            'status' => [ 'neq', AS_END ]
        ];
        $res = $this->m_m->selectAct( 'id,status,btime,etime', $where );

        $set_act_d = [];
        foreach ($res as $val) {
            if( $val['btime'] <= $today && $val['etime'] >= $today ){
                if( $val['status'] != AS_DOING ){
                    $set_act_d[] = [
                        'id' => $val['id'],
                        'status' => AS_DOING
                    ];
                }
            }else if( $val['etime'] < $today ){
                $set_act_d[] = [
                    'id' => $val['id'],
                    'status' => AS_END
                ];
            }
        }

        if( $set_act_d ){
            $set_d = [
                'key' => 'id',
                'column' => ['status'],
                'd' => $set_act_d
            ];

            $res = $this->m_m->setAct( $set_d );
            if( !$res )
                goto END;
        }

        $ret['status'] = 0;
END:
        // 流程结束
        $this->delFuncStatus( ACTION_NAME );
        $this->ajaxReturn( $ret );
    }


    public function delTemp(){
        $path = C('img_temp_path');
        $ret['status'] = 0;
        $ret['errstr'] = 'delete success';
        $res = \Common\delDir($path,C('file_expire_time'),true);
        if($res){
            $ret['errstr'] = 'delete failed';
            $ret['status'] = 1;

        }

        $this->delFuncStatus( ACTION_NAME );
        $this->ajaxReturn( $ret );
    }


}
