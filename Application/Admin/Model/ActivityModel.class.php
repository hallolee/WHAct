<?php
namespace Admin\Model;

class ActivityModel extends GlobalModel
{
    protected $tableName = TACT; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    //act
    public function addAct($data)
    {
        $re = M(TACT)
            ->add($data);

        return $re;
    }

    public function editAct($data, $where)
    {
        $re = M(TACT)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function delAct($where)
    {
        $re = M(TACT)
            ->where($where)
            ->delete();

        return $re;
    }

    public function selectAct($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M(TACT)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function selectActWithUser($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M(TACT)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN ' . TBACKEND . ' b on b.uid = a.uid')
            ->join('LEFT JOIN ' . TFIRM . ' c on c.id = a.fid')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function addAllActOrder( $data=[] ){
        $re = M(THONOR_O)
            ->addAll($data);

        return $re;
    }

    public function selectActOrder($column = '', $where = '', $limit = '', $order = '',$group = '')
    {
        $re = M(TCLI_HONOR)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->group($group)
            ->select();

        return $re;
    }

    public function selectActOrderWithStep($column = '', $where = '', $limit = '', $order = '',$group = '')
    {
        $re = M(TCLI_HONOR)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '.TSTEP.' b on b.uid = a.uid ' )
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->group($group)
            ->select();

        return $re;
    }

    public function findActWithUser($column = '', $where = '')
    {
        $re = M(TACT)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN ' . TBACKEND . ' b on b.uid = a.uid')
            ->join('LEFT JOIN ' . TFIRM . ' c on c.id = a.fid')
            ->where($where)
            ->find();

        return $re;
    }

    public function findAct($column = '', $where = '')
    {
        $re = M(TACT)
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }

    public function selectActDone($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M(TACT)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN ' . THONOR_O . ' b on b.hid = a.id and b.type = 2')
//            ->join('LEFT JOIN ' . TACT_STEP . ' c on c.uid = b.uid')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }



    //sign
    public function selectSign($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M(TSIGN_O)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    //act done
    public function getActUesrDoneList($raw = '',$limit = '',$page = '',$out = ''){
        $exist = $this->findAct('',['id'=>$raw['id']]);
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];
        $where = [];
        if(!$exist)
            goto END;

        $raw['job_coding'] = $raw['job_num'];
        $keys = ['name','nickname','phone','job_coding'];
        $where_user = [];
        foreach ($keys as $item) {
            if ($raw[$item])
                $where_user[$item] = ['like', '%' . $raw[$item] . '%'];

        }
        if($where_user){
            $uid_data = D('Admin/User')->selectUser('uid',$where_user);
            if(!$uid_data)
                goto END;

            $uids = [];
            foreach($uid_data as $v){
                $uids[] = $v['uid'];
            }
            $where['a.uid'] = ['in',$uids];
        }

        if ($raw['time_start'] && $raw['time_end']) {
            $where['a.atime'] = [
                ['egt', $raw['time_start']],
                ['lt', $raw['time_end']]
            ];
        } elseif ($raw['time_start']) {
            $where['a.atime'] = ['egt', $raw['time_start']];
        } elseif ($raw['time_end']) {
            $where['a.atime'] = ['lt', $raw['time_end']];
        }



        $columns = 'any_value(a.atime) atime,any_value(a.uid) uid,sum(b.step_n) step_n';
        $order = 'atime asc,step_n desc';
        $where['a.hid'] = $raw['id'];
        $where['b.atime'] = ['egt',$exist['btime']];
        $where['b.mtime'] = ['lt',$exist['etime']];
        $result = $this->selectActOrderWithStep($columns, $where, $limit, $order,'a.uid');

        if (!$result)
            goto END;

        $uids = [];
        foreach($result as $v)
            $uids[] = $v['uid'];
        if($uids)
            $user_info = D('Admin/User')->getUserInfo($uids,['uid','idcard','nickname','fid','dept_id']);

        foreach($result as &$v){
            $v['nickname'] = $user_info[$v['uid']]['nickname'];
            $v['job_num'] = $user_info[$v['uid']]['job_num'];
            $v['phone'] = $user_info[$v['uid']]['phone'];
            $v['user'] = $user_info[$v['uid']]['user'];
            $v['name'] = $user_info[$v['uid']]['name'];
            $v['dep'] = $user_info[$v['uid']]['dept_name'];
            $v['name'] = $user_info[$v['uid']]['name'];
            $v['icon'] = $user_info[$v['uid']]['icon'];
        }
        unset($v);
        $count = $this->selectActOrderWithStep('a.uid', $where, '', '','a.uid');;

        $ret['total'] = count($count);
        $ret['page_start'] = $page;
        $ret['page_n'] = count($result);
        $ret['data'] = $result;

END:
        return $ret;
    }

    //user step rank list
    public function showUserStepRankList2($raw){
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];
        $page = isset($raw['page_start']) ? $raw['page_start'] : 1;
        $num = isset($raw['page_limit']) ? $raw['page_limit'] : 10;
        $where = [];

        if(!$raw['id'])
            goto END;

        $today = strtotime( date( 'Ymd', time() ) );

        //act_info
        $act_info = $this->findAct('',['id'=>$raw['id']]);
        $all_user = D('Admin/User')->selectUser('uid',['fid'=>$act_info['fid']]);
        if($all_user){
            foreach($all_user as $v){
                $all_user_uid[] = $v['uid'];
            }
            $ret['total'] = count($all_user);

        }else{
            goto END;
        }

        $ret['total'] = count($all_user);

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

        $columns = '';
        $result = D('Admin/User')->selectStep($columns, ['uid'=>['in',$all_user_uid]],'','atime desc,step_n desc');
        for($i=0;$i<=(($etime-$btime)/86400);$i++){
            $t = strtotime(date('Y-m-d', $btime+86400*$i));
            $terms[] = $t;
            $analysis[] = ['time'=>$t];
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
            $user_info = D('Admin/User')->getUserInfo($uids,['uid','idcard','nickname','fid','dept_id']);

        $res_by_uid = [];
        foreach($result as &$v){
            $v['atime'] = strtotime(date('Y-m-d', $v['atime']));
            if(!$res_by_uid[$v['uid']]['nickname'])
                $res_by_uid[$v['uid']] = $user_info[$v['uid']];

            $vv['time'] = $v['atime'];
            $vv['step_n'] = $v['step_n'];
            $res_by_uid[$v['uid']]['data'][$v['atime']] = $vv;
        }
        unset($v);

        foreach($res_by_uid as $k=>$v){
            $res_final[] = $v;
        }

        $count_step_n = [];
        $user_data_each = [];
        foreach($res_final as &$v){
            $temp_each = [];
            $step_n = 0;
            for($count_term = 0;$count_term<count($terms);$count_term++){
                if(!isset($v['data'][$terms[$count_term]])){
                    $temp_each[] = $v['data'][$terms[$count_term]] = ['step_n'=>0,'time'=>$terms[$count_term]];
                }else{
                    $temp_each[] = $v['data'][$terms[$count_term]];
                    $step_n += $v['data'][$terms[$count_term]]['step_n'];
                }
            }
            $user_data_each[$v['uid']] = $temp_each;
            $count_step_n[$v['uid']] = $step_n;
            unset($v['data']);
        }
        unset($v);

        $res_out_put = [];
        foreach($res_final as &$v){
            $v['step_n'] = $count_step_n[$v['uid']];
            $v['data'] = $user_data_each[$v['uid']];;
            $res_out_put[] = $v;
        }
        unset($v);

NO_DATA://空数据填充

        $uids = $uids?$uids:[];
        if(count($uids) == count($all_user))
            goto END;

        $all_user_uid = $uids?array_diff($all_user_uid,$uids):$all_user_uid;

        $user_res = D('Admin/User')->getUserInfo($all_user_uid,['uid','idcard','nickname','fid','dept_id']);
        if(!$user_res)
            goto END;
        $temp_each = [];

        foreach($user_res as $v) {
            $temp_each[$v['uid']] = $v;
            for ($count_term = 0; $count_term < count($terms); $count_term++) {
                $temp_each[$v['uid']]['data'][] = ['score' => 0, 'step_n' => 0, 'time' => $terms[$count_term]];
            }
        }
        foreach($temp_each as $v){
            $res_final[] = $v;
        }
END:
        foreach($res_final as &$v){
            $v['step_n'] = 0;
            foreach($v['data'] as $v1){
                $v['step_n'] += $v1['step_n'];
            }
        }
        unset($v);
        $res_final = $this->getRank($res_final);

        foreach($res_final as $k=>&$v){
            $v['rank'] = $k+1;
        }
        unset($v);
        if($raw['excel']){
            $ret['data'][] =$res_final;
        }else{
            for($i = ($page-1)*$num;$i<$page*$num;$i++){
                $ret['data'][] = $res_final[$i];
            }
        }
        $ret['page_n'] = count($ret['data']);
        $ret['page_start'] = $page;
        $ret['analysis'] = $analysis;
        return $ret;
    }

    public function showUserStepRankList($raw){
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];
        $page = isset($raw['page_start']) ? $raw['page_start'] : 1;
        $num = isset($raw['page_limit']) ? $raw['page_limit'] : 10;
        $where = [];
        $rank = 0;
        if(!$raw['id'])
            goto END;
        if($raw['excel']){
            $page = 1;
            $num = 999999;
        }

        $today = strtotime( date( 'Ymd', time() ) );

        //act_info
        $act_info = $this->findAct('',['id'=>$raw['id']]);
        $all_user = D('Admin/User')->selectUser('uid',['fid'=>$act_info['fid']]);
        if($all_user){
            foreach($all_user as $v){
                $all_user_uid[] = $v['uid'];
            }
            $ret['total'] = count($all_user);

        }else{
            goto END;
        }

        $raw['time_start'] = 0;
        if(!$raw['excel']){
            $keys = ['nickname','name'];
            foreach ($keys as $item) {
                if ($raw[$item])
                    $where['a.' . $item] = ['like', '%' . $raw[$item] . '%'];
            }
            $btime =  $raw['time_start'] &&  $raw['time_start']>=$act_info['btime'] && $raw['time_start'] <=$today?$raw['time_start']:$act_info['btime'];
            $etime =  $raw['time_end'] && $raw['time_end']<$act_info['etime']  && $raw['time_start'] <=$today?$raw['time_end']:$act_info['etime'];
            $where['atime'][] = ['egt', $btime];
            $where['atime'][] = ['elt', $etime];

        }else{
            $btime = $act_info['btime'];
            $etime =  $act_info['etime'];
            $where['atime'][] = ['egt', $act_info['btime']];
            $where['atime'][] = ['elt', $act_info['etime']];
        }

        $btime = $btime<$today?$btime:$today;
        $etime = $btime<$today?$etime:$today;

        $columns = 'sum(step_n) step_n,any_value(uid) uid';
        $limit = ($page-1)*$num.','.$num;

        //date
        for($i=0;$i<=(($etime-$btime)/86400);$i++){
            $t = strtotime(date('Y-m-d', $btime+86400*$i));
            $terms[] = $t;
            $analysis[] = ['time'=>$t];
        }
        //final base
        $res_base = D('Admin/User')->selectStep($columns, $where,$limit,'sum(step_n) desc','uid');
        if (!$res_base){
            if($raw['excel'])
                goto END;
            $uids = [];
            goto NO_DATA;
        }
        foreach($res_base as $k=>&$v){
            $uids[] = $v['uid'];
            $rank = $v['rank'] = ($page-1)*$num+1+$k;
            $res_final[] = $v;
        }
        unset($v);


        //all_data
        $where['uid'] = ['in',$uids];
        $res_all = D('Admin/User')->selectStep('',$where);

        $user_info = D('Admin/User')->getUserInfo($uids,['uid','idcard','nickname','fid','dept_id']);
        $res_by_uid = [];
        foreach($res_all as &$v){
            $v['atime'] = strtotime(date('Y-m-d', $v['atime']));

            if(!$res_by_uid[$v['uid']]['nickname'])
                $res_by_uid[$v['uid']] = $user_info[$v['uid']];

            $vv['time'] = $v['atime'];
            $vv['step_n'] = $v['step_n'];
            $res_by_uid[$v['uid']]['data'][$v['atime']] = $vv;
        }
        unset($v);

        //user daily data
        $user_data_each = [];
        foreach($res_by_uid as &$v){
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

        foreach($res_final as &$v){

            $v= array_merge($user_info[$v['uid']],$v);
            $v['data'] = $user_data_each[$v['uid']];;
        }
        unset($v);

        if(((count($res_final) == $num && !$raw['excel']) || $ret['total'] < $num) && !$raw['excel'])
            goto END;

        //add data lack
NO_DATA:
        $uids = $uids?$uids:[];
        if(count($uids) == $num)
            goto END;

        if($uids){
            $lack_num = $num - count($uids);
            $lack_user_info = D('Admin/User')->selectUser('uid',['fid'=>$act_info['fid'],'uid'=>['not in',$uids]],'0,'.$lack_num,'uid');


        }else{
            $res_base = D('Admin/User')->selectStep($columns, $where,$limit,'','uid');
            if($res_base){            //user with data in this page
                foreach($res_base as $v){
                    $uids[] = $v['uid'];
                }
                $limit = ($num*($page-1)+count($res_base)).','.($num-count($res_base));
                $lack_user_info = D('Admin/User')->selectUser('',['fid'=>$act_info['fid'],'uid'=>['not in',$uids]],$limit,'uid');
            }else{//user without data in this page
                $res_base = D('Admin/User')->selectStep($columns, $where,'','','uid');
                $limit = $num*($page-1)-count($res_base).','.$num;
                $lack_user_info = D('Admin/User')->selectUser('',['fid'=>$act_info['fid']],$limit,'uid');
            }

        }

        if(!$lack_user_info)
            goto END;

        foreach($lack_user_info as $v){
            $lack_uid[] = $v['uid'];
        }
        $lack_user_info = D('Admin/User')->getUserInfo($lack_uid,['uid','idcard','nickname','fid','dept_id']);

        $rank = $rank?$rank:$num*($page-1);

        if(!$lack_user_info)
            goto END;
        $temp_each = [];

        foreach($lack_user_info as &$v) {
            $rank++;
            $v['step_n'] = 0;
            $v['rank'] = $rank;
            $temp_each[$v['uid']] = $v;
            for($count_term = 0;$count_term<count($terms);$count_term++){
                $v['data'][]  = ['step_n'=>0,'time'=>$terms[$count_term]];
            }
        }
        unset($v);

        if($res_final){
            foreach($lack_user_info as $v)
                $res_final[] = $v;
        }else{
            $res_final = $lack_user_info;
        }
END:

        $data = [];
        if($res_final)
            foreach($res_final as $v)
                $data[] = $v;
        $ret['data'] = $data?$data:[];
        $ret['page_n'] = count($ret['data']);
        $ret['page_start'] = $page;
        $ret['analysis'] = $analysis;


        return $ret;
    }

    //user sign list
    public function showUserSignList($raw){
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];
        $page = isset($raw['page_start']) ? $raw['page_start'] : 1;
        $num = isset($raw['page_limit']) ? $raw['page_limit'] : 10;
        $where = [];

        if(!$raw['id'])
            goto END;

        $today = strtotime( date( 'Ymd', time() ) );

        //act_info
        $act_info = $this->findAct('',['id'=>$raw['id']]);
        $all_user = D('Admin/User')->selectUser('uid',['fid'=>$act_info['fid']]);
        $ret['total'] = count($all_user);

        $raw['time_start'] = 0;
        if(!$raw['excel']){
            $btime =  $raw['time_start'] &&  $raw['time_start']>=$act_info['btime'] && $raw['time_start'] <=$today?$raw['time_start']:$act_info['btime'];
            $etime =  $raw['time_end'] && $raw['time_end']<$act_info['etime']?$raw['time_end']:$act_info['etime'];
            $where['atime'][] = ['egt', $btime];
            $where['atime'][] = ['elt', $etime];

        }else{
            $btime =  $raw['time_start'] &&  $raw['time_start']>=$act_info['btime'] && $raw['time_start'] <=$today?$raw['time_start']:$act_info['btime'];
            $etime =  $raw['time_end'] && $raw['time_end']<$act_info['etime']?$raw['time_end']:$act_info['etime'];
            $where['atime'][] = ['egt', $btime];
            $where['atime'][] = ['elt', $etime];
        }
        $btime = $btime<$today?$btime:$today;
        $etime = $btime<$today?$etime:$today;
        $columns = '';
        $where['aid'] = $raw['id'];
        $result = $this->selectSign('uid,atime', $where,'');
        for($i=0;$i<=(($etime-$btime)/86400);$i++){
            $t = strtotime(date('Y-m-d', $btime+86400*$i));
            $terms[] = $t;
            $analysis[] = ['time'=>$t];
        }

        if (!$result){
            goto NO_DATA;
        }

        $uids = [];
        foreach($result as $v){
            if(!in_array($v['uid'],$uids))
                $uids[] = $v['uid'];

        }

        if($uids)
            $user_info = D('Admin/User')->getUserInfo($uids,['uid','idcard','nickname','fid','dept_id']);

        $res_by_uid = [];
        foreach($result as &$v){
            $v['atime'] = strtotime(date('Y-m-d', $v['atime']));

            if(!$res_by_uid[$v['uid']]['nickname'])
                $res_by_uid[$v['uid']] = $user_info[$v['uid']];

            $vv['time'] = $v['atime'];
            $vv['sign'] = S_TRUE;
            $res_by_uid[$v['uid']]['data'][$v['atime']] = $vv;
        }
        unset($v);

        foreach($res_by_uid as $k=>$v){
            $res_final[] = $v;
        }

        for($i=0;$i<=(($etime-$btime)/86400);$i++){
            $t = strtotime(date('Y-m-d', $btime+86400*$i));
            $terms[] = $t;
        }

        $terms = array_unique($terms);

        $user_data_each = [];
        foreach($res_final as &$v){
            $temp_each = [];
            for($count_term = 0;$count_term<count($terms);$count_term++){
                if(!isset($v['data'][$terms[$count_term]])){
                    $temp_each[] = $v['data'][$terms[$count_term]] = ['sign'=>S_FALSE,'time'=>$terms[$count_term]];
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
        if((count($res_final) == $num && !$raw['excel']) || $ret['total'] < $num)
            goto END;

NO_DATA://空数据填充

        $uids = $uids?$uids:[];
        if(count($uids) == count($all_user))
            goto END;

        foreach($all_user as $v){
            $all_user_uid[] = $v['uid'];
        }
        $all_user_uid = array_diff($all_user_uid,$uids);

        for($i=0;$i<=(($etime-$btime)/86400);$i++){
            $t = strtotime(date('Y-m-d', $btime+86400*$i));
            $ret['analysis'][] = [
                'time' => $t
            ];
        }

        $where = [];
        $where['uid'] = ['in',array_unique($all_user_uid)];
        if($raw['fid'])
            $where['fid'] = $raw['fid'];
        $user_res = D('Admin/User')->getUserInfo($all_user_uid,['uid','idcard','nickname','fid','dept_id']);

        if(!$user_res)
            goto END;
        $temp_each = [];

        foreach($user_res as $v) {
            $temp_each[$v['uid']] = $v;
            for ($count_term = 0; $count_term < count($terms); $count_term++) {
                $temp_each[$v['uid']]['data'][] = ['sign' => S_FALSE, 'time' => $terms[$count_term]];
            }
        }

        foreach($temp_each as $v){
            $res_final[] = $v;
        }
END:

        if($raw['excel'] || $ret['total'] < $num){
            $ret['data'][] = $res_final;
        }else{
            for($i = ($page-1)*$num;$i<$page*$num;$i++){
                $ret['data'][] = $res_final[$i];
            }
        }



        foreach($ret['data'] as &$v)
            $v['uid'] = (int)$v['uid'];

        unset($v);
        $ret['page_n'] = count($ret['data']);
        $ret['page_start'] = $page;
        return $ret;
    }

    //get final order
    public function getRank($b){
        $len = count($b)+1;
        for($k=1;$k<$len;$k++)
        {
            for($j=0;$j<$len-$k;$j++){
                if($b[$j]['step_n']>$b[$j+1]['step_n']){
                    $temp =$b[$j+1];
                    $b[$j+1] =$b[$j] ;
                    $b[$j] = $temp;
                }
            }
        }
        return $b;
    }

}
