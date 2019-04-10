<?php
namespace Admin\Model;

class ScoreModel extends GlobalModel
{
    protected $tableName = TSTEP_SCORE; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    //act
    public function addAct($data)
    {
        $re = M(TSTEP_SCORE)
            ->add($data);

        return $re;
    }

    public function editAct($data, $where)
    {
        $re = M(TSTEP_SCORE)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function delAct($where)
    {
        $re = M(TSTEP_SCORE)
            ->where($where)
            ->delete();

        return $re;
    }

    public function selectAct($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M(TSTEP_SCORE)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function selectActWithUser($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M(TSTEP_SCORE)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN ' . TBACKEND . ' b on b.uid = a.uid')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function findAct($column = '', $where = '')
    {
        $re = M(TSTEP_SCORE)
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }

    //score
    public function selectScore($column = '', $where = '', $limit = '', $order = '', $group = '')
    {
        $re = M(TSTEP_SCORE_O)
            ->field($column)
            ->alias('b')
            ->join('INNER JOIN '.TCLIENT.' a on b.uid = a.uid')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->group($group)
            ->select();

        return $re;
    }

    public function selectScoreWithDept($column = '', $where = '', $limit = '', $order = '', $group = '')
    {
        $re = M(TSTEP_SCORE_O)
            ->field($column)
            ->alias('b')
            ->join('INNER JOIN '.TDEPT.' a on b.dept_id = a.id')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->group($group)
            ->select();

        return $re;
    }

    public function findScore($column = '', $where = '')
    {
        $re = M(TSTEP_SCORE_O)
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }

    public function addAllScoreRule( $data=[] ){
        $re = M(TSTEP_SCORE_L)
            ->addAll($data);

        return $re;
    }


    public function addScoreOrder( $data=[] ){
        $re = M(TSTEP_SCORE_O)
            ->addAll($data);

        return $re;
    }


    public function delScoreRule( $where = '' ){
        $re = M(TSTEP_SCORE_L)
            ->where($where)
            ->delete();

        return $re;
    }

    public function selectScoreRule($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M(TSTEP_SCORE_L)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    //step



    public function selectUserAlias($column = '',$where = '',$limit='',$order = ''){
        $re = M(TCLIENT)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TDEPT.' b on b.id = a.fid and b.id > 0')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    /**
     * 获取用户积分列表
     */

    public function showUserScoreList($raw){
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];
        $page = isset($raw['page_start']) ? $raw['page_start'] : 1;
        $num = isset($raw['page_limit']) ? $raw['page_limit'] : 10;
        $where = [];

        if(!$raw['id'])
            goto END;

        $today = strtotime( date( 'Ymd', time() ) );

        //act_info
        $act_info = $this->findAct('',['id'=>$raw['id']]);
        $raw['time_start'] = 0;
        if(!$raw['excel']){

            $keys = ['nickname','name'];
            foreach ($keys as $item) {
                if ($raw[$item])
                    $where['a.' . $item] = ['like', '%' . $raw[$item] . '%'];
            }
            $btime =  $raw['time_start'] &&  $raw['time_start']>=$act_info['btime'] && $raw['time_start'] <=$today?$raw['time_start']:$act_info['btime'];
            $etime =  $raw['time_end'] && $raw['time_end']<$act_info['etime']?$raw['time_end']:$act_info['etime'];
            $where['b.atime'][] = ['egt', $btime];
            $where['b.atime'][] = ['elt', $etime];

        }else{
            $btime = $act_info['btime'];
            $etime =  $act_info['etime'];
            $where['b.atime'][] = ['egt', $act_info['btime']];
            $where['b.atime'][] = ['elt', $act_info['etime']];
        }

        $where['a.fid'] = $act_info['fid'];
        $columns = 'b.step_n,b.uid,b.atime';
        $result = $this->selectStep($columns, $where,'','b.atime desc,b.step_n desc');
        for($i=0;$i<=(($etime-$btime)/86400);$i++){
            $t = strtotime(date('Y-m-d', $btime+86400*$i));
            $terms[] = $t;
        }

        if (!$result){
            if($raw['excel'])
                goto END;
            goto NO_DATA;

        }

        $uids = [];
        foreach($result as $v){
            if(!in_array($v['uid'],$uids))
                $uids[] = $v['uid'];

        }

        if($uids)
            $user_info = D('Admin/User')->getUserInfo($uids);

        $analysis = [];
        $res_by_uid = [];
        foreach($result as &$v){
            $v['atime'] = strtotime(date('Y-m-d', $v['atime']));

            if(!$analysis[$v['atime']]['time'])
                $analysis[$v['atime']]['time'] = $v['atime'] ;

            $keys_null = ['score','step_n'];
            foreach($keys_null as $vs){
                if(!isset($analysis[$v['atime']][$vs]))
                    $analysis[$v['atime']][$vs] = 0 ;
            }

            if(!$res_by_uid[$v['uid']]['nickname'])
                $res_by_uid[$v['uid']] = [
                    'uid'         =>     $v['uid'],
                    'nickname'   =>      $user_info[$v['uid']]['nickname'],
                    'phone'       =>     $user_info[$v['uid']]['phone'],
                    'dept_name'  =>      $user_info[$v['uid']]['dept_name'],
                    'fid'  =>      $user_info[$v['uid']]['fid'],
                ];

            $v['time'] = $v['atime'];
            $res_by_uid[$v['uid']]['data'][$v['atime']] = $v;
        }
        unset($v);

        foreach($res_by_uid as $k=>$v){
            $res_final[] = $v;
        }

        for($i=0;$i<=(($etime-$btime)/86400);$i++){
            $t = strtotime(date('Y-m-d', $btime+86400*$i));
            $ret['analysis'][] = [
                'time' => $t
            ];
            $terms[] = $t;
        }

        $terms = array_unique($terms);

        $user_data_each = [];
        foreach($res_final as &$v){
            $temp_each = [];
            for($count_term = 0;$count_term<count($terms);$count_term++){
                if(!isset($v['data'][$terms[$count_term]])){
                    $temp_each[] = $v['data'][$terms[$count_term]] = ['step_n'=>0,'time'=>$terms[$count_term]];
                }else{
                    $temp_each[] = $v['data'][$terms[$count_term]];
                }
            }
            $user_data_each[$v['uid']] = $temp_each;
            unset($v['data']);
        }
        unset($v);

        $res_out_put = [];
        foreach($res_final as &$v){
            $v['data'] = $user_data_each[$v['uid']];;
            $res_out_put[] = $v;
        }
        unset($v);

        goto END;

        NO_DATA://空数据填充
        for($i=0;$i<=(($etime-$btime)/86400);$i++){
            $t = strtotime(date('Y-m-d', $btime+86400*$i));
            $ret['analysis'][] = [
                'step_n' => 0,
                'time' => $t
            ];
        }

        $uid = explode(',',$act_info['aim_uid']);

        $where = [];
        $where['uid'] = ['in',array_unique($uid)];
        if($raw['dept_id'])
            $where['fid'] = $raw['dept_id'];
        $user_res = D('Admin/User')->selectUser('uid,phone,nickname,fid',$where);

        if(!$user_res)
            goto END;
        $temp_each = [];

        foreach($user_res as $v) {
            $temp_each[$v['uid']] = $v;
            for ($count_term = 0; $count_term < count($terms); $count_term++) {
                $temp_each[$v['uid']]['data'][] = ['score' => 0, 'step_n' => 0, 'time' => $terms[$count_term]];
            }
        }

        $i = 0;
        foreach($temp_each as $v){
            if($i<$num * ($page - 1)){
                $i++;
                continue;
            }
            if($i>=$num*$page-1)
                break;
            $res_final[] = $v;
            $i++;
        }
        END:

        foreach($res_final as &$v){
            $v['step_n'] = 0;
            foreach($v['data'] as $v1){
                $v['step_n'] += $v1['step_n'];
            }
        }
        unset($v);

        $ret['total'] = count($uids);
        $ret['page_start'] = $page;
        $ret['page_n'] = count($res_final);
        $ret['data'] = $res_final;
        var_dump($ret);die;
        return $ret;
    }




}
