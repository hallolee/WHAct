<?php
namespace Admin\Model;

class ScoreModel extends GlobalModel
{
    protected $tableName = 'step_score_c'; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    //act
    public function addAct($data)
    {
        $re = M('step_score_c')
            ->add($data);

        return $re;
    }

    public function editAct($data, $where)
    {
        $re = M('step_score_c')
            ->where($where)
            ->save($data);

        return $re;
    }

    public function delAct($where)
    {
        $re = M('step_score_c')
            ->where($where)
            ->delete();

        return $re;
    }

    public function selectAct($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M('step_score_c')
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function selectActWithUser($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M('step_score_c')
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
        $re = M('step_score_c')
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }

    //score
    public function selectScore($column = '', $where = '', $limit = '', $order = '', $group = '')
    {
        $re = M('step_score_order_c')
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

    public function selectScoreWithDept($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M('step_score_order_c')
            ->field($column)
            ->alias('b')
            ->join('INNER JOIN '.TDEPT.' a on b.dept_id = a.id')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function findScore($column = '', $where = '')
    {
        $re = M('step_score_order_c')
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }

    public function addAllScoreRule( $data=[] ){
        $re = M('step_score_level_c')
            ->addAll($data);

        return $re;
    }


    public function addScoreOrder( $data=[] ){
        $re = M('step_score_order_c')
            ->addAll($data);

        return $re;
    }

    public function delOrder( $where = '' ){
        $re = M('step_score_order_c')
            ->where($where)
            ->delete();

        return $re;
    }


    public function delScoreRule( $where = '' ){
        $re = M('step_score_level_c')
            ->where($where)
            ->delete();

        return $re;
    }

    public function selectScoreRule($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M('step_score_level_c')
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }



    public function selectUserAlias($column = '',$where = '',$limit='',$order = ''){
        $re = M(TCLIENT)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TDEPT.' b on b.id = a.dept_pid and b.id > 0')
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

        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $raw['excel']?'':$num * ($page - 1) . ',' . $num;
        $where = [];

        if(!$raw['id'])
            goto END;

        $today = strtotime( date( 'Ymd', time() ) );
        if ($raw['dept_id'])
            $where['b.dept_id'] = $raw['dept_id'];

        $where['b.pid'] = $raw['id'];
        $where['b.type'] = SCORE_TYPE_USER;

        $keys = ['nickname','name'];
        foreach ($keys as $item) {
            if ($raw[$item])
                $where['a.' . $item] = ['like', '%' . $raw[$item] . '%'];
        }

        //act_info
        $act_info = $this->findAct('',['id'=>$raw['id']]);

        $btime =  $raw['time_start'] &&  $raw['time_start']>=$act_info['btime']?$raw['time_start']:$act_info['btime'];
        $etime =  $raw['time_end'] && $raw['time_end']<=$today?$raw['time_end']:$today-1;
        $where['b.atime'][] = ['egt', $btime];
        $where['b.atime'][] = ['elt', $etime-1];

        $columns = 'b.uid,b.score,b.step_n,b.atime';
        $result = $this->selectScore($columns, $where, $limit);

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
        foreach($result as $v)
            $uids[] = $v['uid'];

        if($uids)
            $user_info = D('Admin/User')->getUserInfo($uids);

        $analysis = [];
        foreach($result as &$v){
            $v['atime'] = strtotime(date('Y-m-d', $v['atime']));

            if(!$analysis[$v['atime']]['time'])
                $analysis[$v['atime']]['time'] = $v['atime'] ;

            $keys_null = ['score','step_n'];
            foreach($keys_null as $vs){
                if(!$analysis[$v['atime']][$vs])
                    $analysis[$v['atime']][$vs] = 0 ;
            }

            $analysis[$v['atime']]['score'] += $v['score'];
            $analysis[$v['atime']]['step_n'] += $v['step_n'];

            $res_by_uid[$v['uid']]['data'][] = [
                'score' =>  $v['score'],
                'step_n' =>  $v['step_n'],
                'time' =>  $v['atime'],
            ];
            if(!$res_by_uid[$v['uid']]['nickname'])
                $res_by_uid[$v['uid']] = [
                    'uid'         =>     $v['uid'],
                    'nickname'   =>      $user_info[$v['uid']]['nickname'],
                    'phone'       =>     $user_info[$v['uid']]['phone'],
                    'dept_name'  =>      $user_info[$v['uid']]['dept_name'],
                    'dept_pid'   =>      $user_info[$v['uid']]['dept_pid'],
                ];

            $v['time'] = $v['atime'];
            $res_by_uid[$v['uid']]['data'][$v['time']] = $v;
        }
        unset($v);

        foreach($res_by_uid as $k=>$v){
            $res_final[] = $v;
        }

        for($i=0;$i<=(($etime-$btime)/86400);$i++){
            $t = strtotime(date('Y-m-d', $btime+86400*$i));
            $ret['analysis'][] = [
                'score' => $analysis[$t]['score']?$analysis[$t]['score']:0,
                'step_n'=> $analysis[$t]['step_n']?$analysis[$t]['step_n']:0,
                'time'  => $t
            ];
            $terms[] = $t;
        }
        $terms = array_unique($terms);

        $user_data_each = [];
        foreach($res_final as &$v){
            $temp_each = [];
            for($count_term = 0;$count_term<count($terms);$count_term++){
                if(!isset($v['data'][$terms[$count_term]])){
                    $temp_each[] = $v['data'][$terms[$count_term]] = ['score'=>0,'step_n'=>0,'time'=>$terms[$count_term]];
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
        $count =  $this->selectScore('any_value(a.uid) uid', $where, '','','uid');

        goto END;

NO_DATA://空数据填充
        for($i=0;$i<=(($etime-$btime)/86400);$i++){
            $t = strtotime(date('Y-m-d', $btime+86400*$i));
            $ret['analysis'][] = [
                'score' => 0,
                'step_n' => 0,
                'time' => $t
            ];
        }

        $uid = explode(',',$act_info['aim_uid']);
        $user_info = D('Admin/User')->getUserInfo(array_unique($uid));
        $temp_each = [];

        foreach($user_info as $v) {
            $temp_each[$v['uid']] = $v;
            for ($count_term = 0; $count_term < count($terms); $count_term++) {
                $temp_each[$v['uid']]['data'][] = ['score' => 0, 'step_n' => 0, 'time' => $terms[$count_term]];
            }
        }
        $count = array_unique($uid);
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
        $count = $temp_each;
END:
        $ret['total'] = count($count);
        $ret['page_start'] = $page;
        $ret['page_n'] = count($res_final);
        $ret['data'] = $res_final;
        return $ret;
    }




}
