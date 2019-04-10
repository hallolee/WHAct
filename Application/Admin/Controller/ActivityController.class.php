<?php
namespace Admin\Controller;

class ActivityController extends GlobalController
{

    protected $model;
    protected $modelUser;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = D('Admin/Activity');
        $this->modelUser = D('Admin/User');
    }


    //act
    public function showActList()
    {
        $raw = $this->RxData;
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $page = $raw['page_start'] ? $raw['page_start'] : 1;
        $num = $raw['page_limit'] ? $raw['page_limit'] : 10;
        $limit = $num * ($page - 1) . ',' . $num;
        $where = [];


        if ($raw['title'])
            $where['a.title'] = ['like', '%' . $raw['title'] . '%'];

        $keys = ['nickname','name'];
        foreach ($keys as $item) {
            if ($raw[$item])
                $where['b.' . $item] = ['like', '%' . $raw[$item] . '%'];
        }

        $search_key = ['num', 'type', 'status', 'fid'];
        foreach ($search_key as $v) {
            if ($raw[$v])
                $where['a.' . $v] = $raw[$v];
        }

        if ($raw['time_start'] && $raw['time_end']) {
            $where['a.btime'] = ['elt', $raw['time_end']];
            $where['a.etime'] = ['egt', $raw['time_start']];


        } elseif ($raw['time_start']) {
            $where['a.btime'] = ['egt', $raw['time_start']];
        } elseif ($raw['time_end']) {
            $where['a.btime'] = ['elt', $raw['time_end']];
        }

        if($this->out['admin_role'] == S_FALSE)
            $where['a.fid'] = ['in',$this->out['role_firm_id']];

        $columns = 'a.*,b.nickname,c.name firm_name';
        $order = 'field(a.status,2,1,3),a.atime desc';

        $result = $this->model->selectActWithUser($columns, $where, $limit, $order);

        if (!$result)
            goto END;

        $aid = [];
        $int_keys = ['id','status','btime','etime','atime'];
        foreach($result as &$v){
            $aid[] = $v['id'];
            foreach($int_keys as $vv){
                $v[$vv] = (int)$v[$vv];
            }
        }
        unset($v);

        $count = $this->model->selectActWithUser('count(*) total', $where);

        $ret['total'] = (int)$count[0]['total'];
        $ret['page_start'] = $page;
        $ret['page_n'] = count($result);
        $ret['data'] = $result;
END:
        $this->retReturn($ret);
    }

    public function showActDetail(){
        $this->showDetail();
    }

    public function showDetail()
    {
        $raw = $this->RxData;
        $ret = [];

        if (!isset($raw['id']))
            goto END;

        $columns = 'a.*,b.icon,b.uid,b.nickname,c.name firm_name';
        $res = $this->model->findActWithUser($columns, ['a.id' => $raw['id']]);
        if (!$res)
            goto END;


        $url_keys = ['admin_icon','cover','qr_code'];
        foreach($url_keys as $v)
            $res[$v] =\Common\getCompleteUrl($res[$v]);

        $int_keys = ['id','sign','btime','type','etime','atime','fid'];
        foreach($int_keys as $v){
            $res[$v] = (int)$res[$v];
        }

        $ret = $res;
END:
        $this->retReturn($ret);
    }

    public function editAct()
    {
        $raw = $this->RxData;
        $ret = [];

        $keys = [
            'title', 'content', 'cover',  'descr', 'type', 'btime', 'etime', 'honor_color', 'sign', 'sign_addr', 'sign_range', 'sign_coor',
            'honor_color', 'honor_icon', 'honor_desc', 'honor', 'uid', 'dept_id', 'type_pk', 'admin_name','num',
            'fixed_times', 'num_cond', 'step_cond','fid','admin_icon'
        ];
        $keys_m = ['title', 'type'];
        if($raw['sign'] == S_TRUE)
            $keys_m = ['fid', 'title', 'type', 'sign_addr', 'sign', 'sign_range'];

        $keys_isset = ['fid'];
        foreach($keys_isset as $v){
            if(!isset($raw[$v])){
                $ret['status'] = 5;
                $ret['errstr'] = 'wtf.'.$v;
                goto END;
            }
        }

        foreach ($keys_m as $v) {
            if (!isset($raw[$v])) {
                $ret['status'] = 5;
                $ret['errstr'] = $v . ' miss';
                goto END;
            }
        }

        $data = [];
        foreach ($keys as $v) {
            if ($raw[$v])
                $data[$v] = $raw[$v];
        }
        //check firm
        $firm_check = $this->modelUser->findFirm('id',['id'=>$raw['fid']]);
        if(!$firm_check){
            $ret['status'] = 13;
            $ret['errstr'] = 'firm not exist';
            goto END;
        }

        $data['content'] = $data['content']?$data['content']:'^-^';
        if ($data['fixed_times']) {
            if (count($data['fixed_times']) > 1) {
                $data['btime'] = explode(',', $data['fixed_times'])[0];
                $data['etime'] = end(explode(',', $data['fixed_times']));
            } else {
                $data['btime'] = explode(',', $data['fixed_times'])[0];
                $data['etime'] = explode(',', $data['fixed_times'])[0] + 86399;
            }
        }
        if (empty($data)) {
            $ret['status'] = 5;
            $ret['errstr'] = '';
            goto END;
        }
        if ($raw['id']){
            $exist = $this->model->findAct('', ['id' => $raw['id']]);
            if (!$exist) {
                $ret['status'] = 13;
                $ret['errstr'] = 'act not exist';
                goto END;
            }

        }
        $data['admin_name'] = $raw['admin_name']?$raw['admin_name']:$this->out['name'];
        $data['show_dept_id'] = implode(',',$raw['fid']);

        $data['fid'] = $raw['fid'];
        //发起部门&指定部门id

ADDATA:

        //图片处理
        $img_keys = ['cover'];
        foreach($img_keys as $v1){
            if($raw[$v1]){
                if(strstr($raw[$v1],'temp')){
                    $file_name = basename($raw[$v1]);
                    $res_move_file = \Common\ownUploadImgIndirect2($raw[$v1], C("upload_path").'Admin/'.$ret['id'].'/'.$file_name);
                    $data[$v1] = C("upload_path").'Admin/'.$ret['id'].'/'.$file_name;
                }
            }
        }

        if (isset($raw['id'])) {
            if($data['fid'])
                unset($data['fid']);
            $res = $this->model->editAct($data, ['id' => $raw['id']]);

            $ret['id '] = $raw['id'];
        } else {
            $data['atime'] = time();
            if($raw['fid'] != -1){
                $orgin_info = $this->modelUser->findDept('',['id'=>$raw['fid']]);
            }
            $data['orgin_dept_name'] = $orgin_info?$orgin_info['name']:-1;
            $data['uid'] = $this->out['uid'] ? $this->out['uid'] : 1;
            $res = $this->model->addAct($data);
            if (!$res) {
                $ret['status'] = 1;
                $ret['errstr'] = 'add failed';
                goto END;
            }
            $ret['id'] = $res;
        }

        //status
        $res = $this->model->editAct(['status' => AS_END], ['etime' => ['lt', time()], 'status' => ['neq', AS_END]]);
        $res = $this->model->editAct(['status' => AS_DOING], ['btime' => ['lt', time()], 'etime' => ['gt', time()], 'status' => ['neq', AS_DOING]]);
        $res = $this->model->editAct(['status' => AS_INIT], ['btime' => ['gt', time()], 'status' => ['neq', AS_INIT]]);


        $ret['status'] = 0;
        $ret['errstr'] = '';
END:
        $this->retReturn($ret);
    }


    public function delAct()
    {
        $raw = $this->RxData;

        $ret['status'] = 1;
        $ret['errstr'] = '';

        $where['id'] = $raw['id'];
        $exist = $this->model->findAct('', $where);
        if($exist['btime'] < time()){
            $ret['status'] =20003;
            goto END;
        }

        $res = $this->model->delAct($where);
        if (!$res)
            goto END;
        $ret['status'] = 0;
END:
        $this->retReturn($ret);
    }

    //ranklist
    public function showRankList(){

        $raw = $this->RxData;
        //act_info
        $act_info = $this->model->findAct('',['id'=>$raw['id']]);
        if(!$act_info)
            goto END;

        $sign_info = $this->model->showUserStepRankList($raw);

END:

        $this->ajaxReturn($sign_info);

    }


    public function showActUserSignList(){

        $raw = $this->RxData;
        //act_info
        $act_info = $this->model->findAct('',['id'=>$raw['id']]);
        if(!$act_info)
            goto END;

        $sign_info = $this->model->showUserSignList($raw);

END:

        $this->ajaxReturn($sign_info?$sign_info:(object)[]);

    }


    public function getQRCode(){
        $raw = $this->RxData;

        $ret = [];
        if(!$raw['id']){
            $ret['status'] = 5;
            goto END;
        }
        $d = [
            'width' => $raw['width']? $raw['width']:'500',
            'scene' => $raw['scene'],
            'page' => $raw['page']
        ];
        $path = \Common\getWechatMpQrCode($d);
        $ret['url'] =\Common\getCompleteUrl( $path );
        $this->model->editAct(['qr_code'=>$path],['id'=>$raw['id']]);
        $ret['status'] = 0;
END:
        //status
        $res = $this->model->editAct(['status' => AS_END], ['etime' => ['lt', time()], 'status' => ['neq', AS_END]]);
        $res = $this->model->editAct(['status' => AS_DOING], ['btime' => ['lt', time()], 'etime' => ['gt', time()], 'status' => ['neq', AS_DOING]]);
        $res = $this->model->editAct(['status' => AS_INIT], ['btime' => ['gt', time()], 'status' => ['neq', AS_INIT]]);


        $this->ajaxReturn($ret);
    }


}
