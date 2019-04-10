<?php
namespace Client\Controller;

class StepController extends GlobalController
{

    protected $user_m;
    protected $step_m;

    public function _initialize(){
        parent::_initialize();
        $this->user_m = D('Client/Profile');
        $this->step_m = D('Client/Step');
    }

    protected function getDeptUser( $dept_id=0, $type=RANK_DEPT ){
        $res = [];
        if( !$dept_id ) return $res;

        switch ($type) {
            case RANK_FIRM:

                $user_res = $this->user_m->selectClient( 'uid', [ 'fid' => $dept_id ] );
                foreach ($user_res as $key => $value) {
                    $res[] = $value['uid'];
                }
                break;

            case RANK_DEPT:
                if( $dept_id==0 )
                    return $res;

                $user_res = $this->user_m->selectClient( 'uid', [ 'dept_id' => $dept_id ] );
                foreach ($user_res as $key => $value) {
                    $res[] = $value['uid'];
                }
                break;

            default:
                break;
        }

        return $res;
    }


    protected function setRankToday( $fid=0 ){
        $t = time();
        $today = strtotime( date( 'Ymd', $t ) );

        $chk_user = $this->user_m->selectClient( 'uid', [ 'fid' => $fid ] );
        if( !$chk_user )
            return false;

        $uid_arr = [];
        foreach ($chk_user as $key => $value) {
            $uid_arr[] = $value['uid'];
        }

        $col = 'a.id,a.ranking rank';
        $rank_res = $this->step_m->getUserStepRank( $col, ['atime' => $today, 'uid' => [ 'in', $uid_arr ], 'mtime <> atime' ], [], 'step_n DESC,mtime ASC' );
        if( !$rank_res )
            return false;

        $set_d = [
            'key' => 'id',
            'column' => ['rank'],
            'd' => $rank_res
        ];
        $res = $this->step_m->setStepRank( $set_d );
        if( !$res )
            return false;

        return true;
    }


    public function showUserRankInToday(){
        $raw = $this->RxData;
        $t = time();
        $today = strtotime( date( 'Ymd', $t ) );
        $ret = [ 'total' => 0 , 'page_start' => 0, 'page_n' => 0, 'is_new' => 0, 'etime' => 0, 'data' => [] ];
        $uid = $this->out['uid'];

        $page_start = ( !isset( $raw[ 'page_start' ] ) || !is_numeric( $raw[ 'page_start' ] ) )?'1':$raw[ 'page_start' ];
        $page_limit = ( !isset( $raw[ 'page_limit' ] ) || !is_numeric( $raw[ 'page_limit' ] ) )?C('PAGE_LIMIT'):$raw[ 'page_limit' ];
        $limit = ($page_start-1)*$page_limit.','.$page_limit;
        $ret['page_start'] = (int)$page_start;

        $cache_str = 'step_rank_all_today';
        $where = [ 'a.atime' => [ [ 'egt', $today ], [ 'elt', $t ] ], 'a.atime <> a.mtime' ];
        if( isset( $raw['type'] ) && in_array( $raw['type'], [ RANK_FIRM, RANK_DEPT ] ) ){
            $chk_user = $this->user_m->findClient( 'fid,dept_id', [ 'uid' => $this->out['uid'] ] );

            $id = '';
            if( $raw['type']==RANK_FIRM ){
                $id = $chk_user['fid'];
                $cache_str = 'step_rank_firm_today_'.$id;
            }else{
                $id = $chk_user['dept_id'];
                $cache_str = 'step_rank_dept_today_'.$id;
            }

            $uid_arr = $this->getDeptUser( $id, $raw['type'] );
            if( empty( $uid_arr ) )
                $uid_arr[] = $uid;

            $where['a.uid'] = [ 'in', $uid_arr ];
        }

        $start = ($page_start-1)*$page_limit;
        $end = $page_start*$page_limit-1;
        $res = \Common\cacheRankGetByRedis( $start, $end, $uid, $cache_str );
        if( !$res || $res['etime'] <= $t ){
            $col = 'a.id,a.uid,a.step_n,b.icon,b.nickname,e.name dept_name';
            $select_res = $this->step_m->getUserStep( $col, $where, 'a.step_n DESC,a.mtime ASC' );

            \Common\cacheRankSetByRedis( $select_res, 'uid', $cache_str );
            $res = \Common\cacheRankGetByRedis( $start, $end, $uid, $cache_str );
            $ret['is_new'] = 1;
        }

        $ret['etime'] = isset($res['etime'])?$res['etime']:0;
        $ret['total'] = isset($res['total'])?$res['total']:0;
        foreach ($res['data'] as $key => $value) {
            $start++;

            $value = json_decode( $value, true );
            $value['id'] = (int)$value['id'];
            $value['uid'] = (int)$value['uid'];
            $value['step_n'] = (int)$value['step_n'];
            $value['icon'] = \Common\getCompleteUrl( $value['icon'] );
            $value['order'] = $start;

            $ret['data'][] = $value;
        }

        $ret['page_n'] = count($ret['data']);
END:
        $this->retReturn( $ret );
    }


    public function showUserRankInSometime(){
        $raw = $this->RxData;
        $t = time();
        $today = strtotime( date( 'Ymd', $t ) );
        $ret = [ 'total' => 0 , 'page_start' => 0, 'page_n' => 0, 'data' => [] ];
        $uid = $this->out['uid'];

        $page_start = ( !isset( $raw[ 'page_start' ] ) || !is_numeric( $raw[ 'page_start' ] ) )?'1':$raw[ 'page_start' ];
        $page_limit = ( !isset( $raw[ 'page_limit' ] ) || !is_numeric( $raw[ 'page_limit' ] ) )?C('PAGE_LIMIT'):$raw[ 'page_limit' ];
        $limit = ($page_start-1)*$page_limit.','.$page_limit;
        $ret['page_start'] = (int)$page_start;

        $where = [ 'a.atime' => [ [ 'egt', $today ], [ 'elt', $t ] ] ];
        if( isset ( $raw[ 'time' ] ) && is_array( $raw[ 'time' ] ) && !empty( $raw[ 'time' ]['min'] ) && !empty( $raw[ 'time' ]['max'] ) ){
            $where['a.atime'] = [
                [ 'egt', $raw[ 'time' ]['min'] ],
                [ 'elt', $raw[ 'time' ]['max'] ]
            ];
        }

        if( isset( $raw['type'] ) && in_array( $raw['type'], [ RANK_FIRM, RANK_DEPT ] ) ){
            $chk_user = $this->user_m->findClient( 'fid,dept_id', [ 'uid' => $this->out['uid'] ] );

            $uid_arr = $this->getDeptUser( ($raw['type']==RANK_FIRM)?$chk_user['fid']:$chk_user['dept_id'], $raw['type'] );
            if( empty( $uid_arr ) )
                $uid_arr[] = $uid;

            $where['a.uid'] = [ 'in', $uid_arr ];
        }

        $total = $this->step_m->getUserStep( 'count(*) num', $where, '', '', 'a.uid' );

        $col = 'a.uid,sum(a.step_n) step_n,b.icon,b.nickname,e.name dept_name';
        $res = $this->step_m->getUserStep( $col, $where, 'step_n DESC', $limit, 'a.uid' );

        if( $total )
            $ret['total'] = (int)$total[0]['num'];

        if( $res ){
            $start = ($page_start-1)*$page_limit;
            foreach ($res as $value) {
                $start++;

                $value['uid'] = (int)$value['uid'];
                $value['step_n'] = (int)$value['step_n'];
                $value['icon'] = \Common\getCompleteUrl( $value['icon'] );
                $value['order'] = $start;

                $ret['data'][] = $value;
            }

            $ret['page_n'] = count($ret['data']);
        }

END:
        $this->retReturn( $ret );
    }


    public function setInfoByMP(){
        $raw = $this->RxData;
        $ret = [ 'status' => 1, 'errstr' => '' ];
        $uid = $this->out['uid'];
        $session_key = $this->out['session_key'];
        $t = time();
        $today = strtotime( date( 'Ymd', $t ) );

        $keys = ['encryptedData', 'iv'];
        foreach ($keys as $value) {
            if( !isset( $raw[ $value ] ) ){
                $ret['status'] = 5;
                goto END;
            }

            ${$value} = $raw[ $value ];
        }

        $get_code = 'no';
        if( isset( $raw['code'] ) && !empty( $raw['code'] ) ){
            $wechat = \Common\getOpenidByMP( $raw['code'] );
            if( !$wechat['session_key'] ){
                $ret['status'] = 19;
                goto END;
            }

            $session_key = $wechat['session_key'];
            \Common\validDaTokenWrite( [ 'session_key' => $session_key ], $raw['token'], TOKEN_APPEND );

            $get_code = 'yes';
        }

        $decrypt_res = \Common\decryptData( $encryptedData, $iv, $session_key, $step_res );
        if( $decrypt_res != 'true' ){
            $ret['test'] = [ 'encryptedData' => $encryptedData, 'iv' => $iv, 'sessionKey' => $session_key, 'step_res' => $step_res, 'getCode' => $get_code , 'code' => isset( $raw['code'] )?$raw['code']:'', 'wechat' => isset( $wechat )?$wechat:'' ];
            $ret['status'] = 10003;
            $ret['errstr'] = $decrypt_res;
            goto END;
        }

        $step_n = 0;
        foreach ($step_res['stepInfoList'] as $key => $value) {
            if( $value['timestamp'] == $today )
                $step_n = $value['step'];
        }

        $num = ceil(($t - $today)/3600);
        $step_time = ceil($step_n/$num);

        $start = $today;
        $data = [];
        $step_cache = $step_n;
        for ($i=1; $i <= $num; $i++) {
            $now = $start+3600;
            $end = ($now > $t)?$t:$now;

            if( $step_time > $step_cache )
                $step_time = $step_cache;

            $data[] = [
                'btime' => $start,
                'etime' => $end,
                'step_n' => $step_time
            ];

            $start = $end;
            $step_cache-=$step_time;
        }

        $d = [
            'step_n' => $step_n,
            'dist' => 0,
            'cal' => 0,
            'extra' => json_encode( [ 'func' => ACTION_NAME, 'step' => $data, 'ua' => $_SERVER['HTTP_USER_AGENT'] ] ),
            'mtime' => time()
        ];

        $chk_res = $this->step_m->findStep( 'id', [ 'uid' => $uid, 'atime' => $today ] );
        if( $chk_res ){
            $res = $this->step_m->saveStep( [ 'id' => $chk_res['id'] ], $d );
            if( $res === false )
                goto END;
        }else{
            $d['uid'] = $uid;
            $d['atime'] = $today;

            $res = $this->step_m->addStep( $d );
            if( !$res )
                goto END;
        }

        $ret['status'] = 0;

        $ret['step_today'] = [
            'step_n' => $d['step_n']
        ];

        $this->setRankToday( $this->out['fid'] );
END:
        $this->retReturn( $ret );
    }



}