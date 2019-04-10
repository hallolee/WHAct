<?php
namespace Client\Controller;

class ActController extends GlobalController
{

    protected $act_m;
    protected $user_m;
    protected $step_m;

    public function _initialize(){
        parent::_initialize();
        $this->step_m = D('Client/Step');
        $this->act_m = D('Client/Act');
        $this->user_m = D('Client/Profile');
    }


    public function showDetail(){
        $raw = $this->RxData;
        $ret = [];
        $uid = $this->out['uid'];
        $today = strtotime( date( 'Ymd', time() ) );

        $user_res = $this->user_m->getClient( 'a.uid,a.fid,a.dept_id,b.name firm_name', [ 'a.uid' => $uid ] );

        $where['a.id'] = $raw['id'];
        $where['fid'] = $user_res['fid'];
        $col = 'a.id,a.title,a.cover,a.descr,a.content,a.type,a.status,'.
                'a.sign,a.sign_coor,a.sign_addr,a.sign_range,a.fid,'.
                'a.admin_name,b.icon admin_icon,'.
                'a.btime,a.etime,a.atime';
        $res = $this->act_m->getActDetail( $col, $where );

        if( $res ){
            $sign_user = [
                'status' => S_FALSE,
                'coor'   => '',
                'data'   => []
            ];
            $sign = [
                'status' => (int)$res['sign'],
                'coor' => $res['sign_coor'],
                'addr' => $res['sign_addr'],
                'range' => $res['sign_range'],
            ];

            $in_act = S_FALSE;
            if( $user_res['fid'] == $res['fid'] ){
                $in_act = S_TRUE;

                if( !$res['sign_coor'] || !$res['sign_range'] )
                    $sign['status'] = S_FALSE;

                $sign_where = [ 'uid' => $uid, 'aid' => $raw['id'], ];
                $sign_res = $this->act_m->selectSign( 'id,sign_coor,atime,mtime', $sign_where );
                if( $sign_res ){
                    foreach ($sign_res as $value) {
                        if( $value['atime'] == $today ){
                            $sign_user['status'] = S_TRUE;
                            $sign_user['coor'] = $value['sign_coor'];
                        }

                        $sign_user['data'][] = [
                            'time' => $value['mtime'],
                            'coor' => $value['sign_coor']
                        ];
                    }
                }

                $res['step_today'] = [
                    'step_n' => 0,
                    'order'  => 0
                ];
                $step_res = $this->step_m->findStep( 'step_n,rank `order`,mtime', ['atime' => $today, 'uid' => $uid ] );
                if( $step_res ){
                    if( $step_res['mtime'] == $today )
                        $step_res['order'] = 0;

                    unset( $step_res['mtime'] );
                    $rank_res = \Common\cacheRankGetByRedis( 0, 0, $uid, 'step_rank_firm_today_'.$res['fid'] );
                    $res['step_today'] = [
                        'step_n' => (int)$step_res['step_n'],
                        'order'  => (int)(isset($rank_res['rank'])?$rank_res['rank']:$step_res['order'])
                    ];
                }
            }
            unset( $res['sign'] );
            unset( $res['sign_coor'] );
            unset( $res['sign_addr'] );
            unset( $res['sign_range'] );

            $res['id'] = (int)$res['id'];
            $res['type'] = (int)$res['type'];
            $res['status'] = (int)$res['status'];
            $res['firm']['name'] = $user_res['firm_name'];
            $res['in_act'] = $in_act;
            $res['sign'] = $sign;
            $res['sign_user'] = $sign_user;
            $res['cover'] = \Common\getCompleteUrl( $res['cover'] );
            $res['admin_icon'] = \Common\getCompleteUrl( $res['admin_icon'] );
            $ret = $res;
        }
END:
        $this->retReturn( $ret );
    }

    public function sign(){
        $raw = $this->RxData;
        $t = time();
        $today = strtotime( date( 'Ymd', $t ) );
        $uid = $this->out['uid'];
        $ret = [ 'status' => 1, 'errstr' => '' ];

        $keys = [ 'aid', 'data' ];
        foreach ($keys as $value) {
            if( !isset( $raw[ $value ] ) || empty( $raw[ $value ] ) )
                goto END;

            ${$value} = $raw[ $value ];
        }

        $user_res = $this->user_m->findClient( 'uid,status,fid,dept_id', [ 'uid' => $uid ] );
        $chk_act = $this->act_m->findAct( 'id,status,sign_coor,sign_range,fid', [ 'id' => $aid ] );

        if( !$chk_act || !$chk_act['sign_coor'] || !$chk_act['sign_range'] || $chk_act['status'] != AS_DOING ){
            $ret['status'] = 10021;
            goto END;
        }else if( $user_res['fid'] != $chk_act['fid'] ){
            $ret['status'] = 10022;
            goto END;
        }

        $sign_coor = explode( ',', $chk_act['sign_coor']);

        $dist = $this->getdistance( $sign_coor[0], $sign_coor[1], $data['longitude'], $data['latitude'] );
        if( $dist > $chk_act['sign_range'] ){
            $ret['dist'] = $dist;
            $ret['status'] = 10020;
            goto END;
        }

        $where = [ 'uid' => $uid, 'aid' => $aid, 'atime' => $today ];
        $chk = $this->act_m->findSign( 'id', $where );
        if( $chk ){
            $d = [
                'sign_coor' => $data['longitude'].','.$data['latitude'],
                'sign_data' => json_encode( $data ),
                'mtime' => $t
            ];

            $res = $this->act_m->saveSign( [ 'id' => $chk['id'] ], $d );
        }else{

            $d = [
                'uid' => $uid,
                'aid' => $aid,
                'sign_coor' => $data['longitude'].','.$data['latitude'],
                'sign_data' => json_encode( $data ),
                'mtime' => $t,
                'atime' => $today
            ];

            $res = $this->act_m->addSign( $d );
        }
        if( $res )
            $ret['status'] = 0;
END:
        $this->retReturn( $ret );
    }




    /**
     * 求两个已知经纬度之间的距离,单位为米
     * 
     * @param lng1 $ ,lng2 经度
     * @param lat1 $ ,lat2 纬度
     * @return float 距离，单位米
     * @author www.Alixixi.com 
     */
    protected function getdistance($lng1, $lat1, $lng2, $lat2) {
        // 将角度转为狐度
        $radLat1 = deg2rad($lat1); //deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
        return $s;
    } 



}
