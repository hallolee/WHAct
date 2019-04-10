<?php
namespace Admin\Model;

class UserModel extends GlobalModel
{
    protected $tableName = TCLIENT; //配置表名，默认与模型名相同，若不同，可通过此来进行设置


    //admin

    public function delAdmin( $where='' ){
        $re = M(TBACKEND)
            ->where( $where )
            ->delete();

        return $re;
    }

    public function addAdminUser($data){
        $re = M(TBACKEND)
            ->add($data);

        return $re;
    }

    public function editAdminUser($data,$where){
        $re = M(TBACKEND)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function delAdminUser($where){
        $re = M(TBACKEND)
            ->where($where)
            ->delete();

        return $re;
    }

    public function selectAdminUser($column = '',$where = '',$limit='',$order = ''){
        $re = M(TBACKEND)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function findAdminUser($column = '',$where = '')
    {
        $re = M(TBACKEND)
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }

    //client
    public function addUser($data){
        $re = M(TCLIENT)
            ->add($data);

        return $re;
    }

    public function addUserAll($data){
        $re = M(TCLIENT)
            ->addAll($data);

        return $re;
    }

    public function editUser($data,$where){
        $re = M(TCLIENT)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function delUser($where){
        $re = M(TCLIENT)
            ->where($where)
            ->delete();

        return $re;
    }

    public function selectUser($column = '',$where = '',$limit='',$order = '',$group = ''){
        $re = M(TCLIENT)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->group($group)
            ->select();

        return $re;
    }


    public function selectUserAlias($column = '',$where = '',$limit='',$order = ''){
        $re = M(TCLIENT)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TDEPT.' b on b.id = a.dept_id_direct and b.id > 0')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function selectUserWithDept($column = '',$where = '',$limit='',$order = ''){
        $re = M(TCLIENT)
            ->alias('a')
            ->field($column)
            ->join('LEFT JOIN '.TDEPT.' b on b.id = a.dept_id')
            ->join('LEFT JOIN '.TDEPT.' c on c.id = b.pid')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function findUser($column = '',$where = '')
    {
        $re = M(TCLIENT)
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }

    public function findUserWithStep($column = '',$where = ''){
        $re = M(TCLIENT)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TSTEP.' b on b.uid = a.uid')
            ->where($where)
            ->find();

        return $re;
    }

    public function selectUserWithStep($column = '',$where = '',$limit='',$order = ''){
        $re = M(TCLIENT)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TSTEP.' b on b.uid = a.uid')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function getUserList($raw,$out = []){
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'status' => 0, 'data' => []];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page - 1) . ',' . $num;
        $where = [];


        if($raw['fid']) {
            if ($out['admin_role'] == S_FALSE && !in_array($raw['fid'],$out['role_firm_id'])) {
                goto END;
            }
            $where['fid'] = $raw['fid'];
        }else{
            if ($out['admin_role'] == S_FALSE)
                $where['fid'] = ['in',$out['role_firm_id']];
        }
        if($raw['dept_id'])
            $where['dept_id'] = $raw['dept_id'];


        $leke_keys = ['phone', 'nickname', 'user', 'idcard'];
        foreach ($leke_keys as $item) {
            if ($raw[$item])
                $where[$item] = ['like', '%' . $raw[$item] . '%'];
        }
        $search_keys = ['dept_id','did','sex','status','fid'];
        foreach ($search_keys as $item) {
            if ($raw[$item])
                $where[$item] = $raw[$item];
        }

        if ($raw['time_start'] && $raw['time_end']) {
            $where['atime'] = [
                ['egt', $raw['time_start']],
                ['lt', $raw['time_end']]
            ];
        } elseif ($raw['time_start']) {
            $where['atime'] = ['egt', $raw['time_start']];
        } elseif ($raw['time_end']) {
            $where['atime'] = ['lt', $raw['time_end']];
        }
        if($raw['where']){
            $where[] = $raw['where'];
            $order = 'uid';
        }else{
            $order = 'atime desc,fid desc';
        }
        $columns = '';

        $result = $this->selectUser($columns, $where, $limit, $order);
         if (!$result){
            $ret['status']   = 1;
            goto END;
        }

        $dep_info =$this->getDeptInfo();
        $firm_info =$this->getFirmInfo();

        $int_keys = ['atime','status','phone','sex','uid'];
        foreach ($result as &$item) {
            $item['dept_name'] = $dep_info[$item['dept_id']]['name']?$dep_info[$item['dept_id']]['name']:'';
            $item['firm_name'] = $firm_info[$item['fid']]['name'];
            $item['phone'] = $item['phone']?$item['phone']:0;
            foreach($int_keys as $v)
                $item[$v] = (int)$item[$v];
        }
        unset($item);

        $count = $this->selectUser('count(*) total', $where);

        $ret['total'] = (int)$count[0]['total'];
        $ret['page_start'] = $page;
        $ret['page_n'] = (int)count($result);
        $ret['data']   = $result;

END:
        return $ret;

    }



    //firm
    public function selectFirm($column = '',$where = '',$limit='',$order = ''){
        $re = M(TFIRM)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function findFirm($column = '',$where = ''){
        $re = M(TFIRM)
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }

    public function editFirm($data,$where){
        $re = M(TFIRM)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function addFirm($data){
        $re = M(TFIRM)
            ->add($data);

        return $re;
    }

    public function delFirm($where){
        $re = M(TFIRM)
            ->where($where)
            ->delete();

        return $re;
    }

    //department
    public function selectDept($column = '',$where = '',$limit='',$order = ''){
        $re = M(TDEPT)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function findDept($column = '',$where = ''){
        $re = M(TDEPT)
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }

    public function addDept($data){
        $re = M(TDEPT)
            ->add($data);
        return $re;
    }

    public function addDeptAll($data){
        $re = M(TDEPT)
            ->addAll($data);
        return $re;
    }

    public function editDept($data,$where){
        $re = M(TDEPT)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function delDept($where){
        $re = M(TDEPT)
            ->where($where)
            ->delete();

        return $re;
    }

    public function incDeptNum($data,$where){
        $info = M(TDEPT)
            ->where($where)
            ->setInc($data['column'],$data['value']);

        return $info;
    }

    public function decDeptNum($data,$where){
        $info = M(TDEPT)
            ->where($where)
            ->setDec($data['column'],$data['value']);

        return $info;
    }

    //dept_n

    public function updateDeptNum(){
        $user_info = $this->selectUser('count(*) num,dept_id','','','','dept_id');
        $newest_data = [];
        $sql = '';
        if($user_info){
            foreach($user_info as $v){
                $newest_data[] = ['id'=>$v['dept_id'],'num'=>$v['num']];
                $ids[] = $v['dept_id'];

            }
            $sql .= 'UPDATE '.TDEPT.' SET
            `num`= case';

            foreach($newest_data as $k=>$v){
                $sql .= '
                when  `id` = "'.$v['id'].'"
            then
                "'.$v['num'].'" ';

            }
            $sql .= ' else `num`  end;';

            $result = M()->execute($sql);
            $this->editDept(['num'=>0],['id'=>['not in',$ids]]);
            $this->updateFirmNum();
        }
       return true;

    }

    public function updateFirmNum(){
        $user_info = $this->selectUser('count(*) num,fid','','','','fid');
        $newest_data = [];
        $sql = '';
        if($user_info){
            foreach($user_info as $v){
                $newest_data[] = ['id'=>$v['fid'],'num'=>$v['num']];
                $ids[] = $v['fid'];
            }
            $sql .= 'UPDATE '.TFIRM.' SET
            `num`= case';

            foreach($newest_data as $k=>$v){
                $sql .= '
                when  `id` = "'.$v['id'].'"
            then
                "'.$v['num'].'" ';

            }
            $sql .= ' else `num`  end;';
            $result = M()->execute($sql);
            $this->editFirm(['num'=>0],['id'=>['not in',$ids]]);
        }


       return true;

    }

    /**
     * @return all depts
     */
    public function getDeptInfo(){
        $res = $this->selectDept('','','0,1000');
        $data = [];
        if($res)
            foreach($res as $v){
                $data[$v['id']] = $v;
            }

        return $data;
    }


    public function getFirmInfo(){
        $res = $this->selectFirm('','','0,1000');
        $data = [];
        if($res)
            foreach($res as $v){
                $data[$v['id']] = $v;
            }

        return $data;
    }



    /**
     *return users
     */

    public function getUserInfo($uid,$column = '')
    {
        $user_res = $this->selectUser($column, ['uid' => ['in', $uid]]);
        $user = [];
        if ($user_res){
            $dep = $this->getDeptInfo();
            $firm = $this->getFirmInfo();
        }
        foreach ($user_res as $val) {

            if($column){
                foreach($column as $v){
                    $user[$val['uid']][$v] = $val[$v];
                }
                $user[$val['uid']]['firm_name'] = $firm[$val['fid']]['name'];
                $user[$val['uid']]['dept_name'] = $dep[$val['dept_id']]['name'];
                $user[$val['uid']]['icon'] = \Common\getCompleteUrl($val['icon']);

            }else{
                $user[$val['uid']] = [
                    'uid'   =>  $val['uid'],
                    'user'  =>  $val['user'],
                    'idcard'  =>  $val['idcard'],
                    'nickname'  =>  $val['nickname'],
                    'phone' =>  $val['phone'],
                    'dept_id'  =>  $val['dept_id'],
                    'fid'  =>  $val['fid'],
                    'firm_name'  =>  $firm[$val['fid']]['name'],
                    'dept_name'  =>  $dep[$val['dept_id']]['name'],
                    'icon'     =>  \Common\getCompleteUrl($val['icon'])
                ];

            }
        }

        return $user;
    }


    //step
    public function selectStep($column = '',$where = '',$limit='',$order = '',$group = ''){
        $re = M(TSTEP)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->group($group)
            ->select();

        return $re;
    }

    public function findStep($column = '',$where = ''){
        $re = M(TSTEP)
            ->field($column)
            ->where($where)
            ->find();

        return $re;
    }

    public function editStep($data,$where){
        $re = M(TSTEP)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function addStep($data){
        $re = M(TSTEP)
            ->add($data);

        return $re;
    }

    public function delStep($where){
        $re = M(TSTEP)
            ->where($where)
            ->delete();

        return $re;
    }

    //champion
    public function selectChampion($column = '',$where = '',$limit='',$order = ''){
        $re = M(TCHAMPION)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }


    //step
    public function selectUserStep($column = '',$where = '',$limit='',$order = '',$group = ''){
        $re = M(TSTEP)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->group($group)
            ->select();

        return $re;
    }


    public function selectUserStepWithClient($column = '',$where = '',$limit='',$order = '',$group = ''){
        $re = M(TSTEP)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TCLIENT.' b on b.uid = a.uid')
            ->join('INNER JOIN '.TDEPT.' c on b.dept_id_direct = c.id and c.pid >0')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->group($group)
            ->select();

        return $re;
    }


    public function selectUserStepWithClient1nd($column = '',$where = '',$limit='',$order = '',$group = ''){
        $re = M(TSTEP)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TCLIENT.' b on b.uid = a.uid')
            ->join('INNER JOIN '.TDEPT.' c on b.fid= c.id')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->group($group)
            ->select();

        return $re;
    }


    public function selectStepWithClient($column = '',$where = '',$limit='',$order = '',$group = ''){
        $re = M(TCLIENT)
            ->alias('a')
            ->field($column)
            ->join('INNER JOIN '.TSTEP.' b on b.uid = a.uid')
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->group($group)
            ->select();

        return $re;
    }

    public function selectUserLike($column = '',$where = '',$limit='',$order = ''){
        $re = M(TLIKE_O)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }
    



}
