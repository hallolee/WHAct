<?php
namespace Admin\Controller;

class FileController extends GlobalController {
    protected $modelUser;
    protected $modelActivity;
    protected $dataDemo;
    protected $dataDeptDemo;
    protected $limit = 10000;
    public function _initialize(){
        parent::_initialize();
        $this->modelUser = D('Admin/User');
        $this->modelActivity = D('Admin/Activity');
        $this->dataDemo = [
            [
                'uid' => ' 198',
                'idcard' => '2201245648946542121',
                'nickname' => '张敏',
                'phone' => 13696965658,
                'sex' => 2,
                'age' => 19,
                'height' => 165,
                'weight' => 47.3,
                'firm_name' => '结算与网络金融部',
                'dept_name' => '',
            ],
            [

                'idcard' => '2201245648946542121',
                'nickname' => '梅艳芳',
                'phone' => 13696965668,
                'sex' => 2,
                'age' => 20,
                'height' => 166,
                'weight' => 53,
                'firm_name' => '白云支行',
                'dept_name' => '市场发展部/中华广场支行',
            ],
        ];
        $this->dataDeptDemo = [
            ['id'=>'9910','dept_name'=>'示例海珠分行-后勤','dept_pid_name'=>'示例海珠分行'],
            ['id'=>'','dept_name'=>'示例海珠分行','dept_pid_name'=>'']
        ];

//        get 获取token 验证登录
        if( !isset( $this->out['uid'] ) || empty( $this->out['uid'] ) || !is_numeric( $this->out['uid'] ) ){
            // $ret = [ 'status' => E_TOKEN, 'errstr' => '' ];
            $raw = $this->RxData?$this->RxData:I('get.');
            if( !isset( $raw['token'] ) ){
                echo ' Invalid token('.$raw['token'].'), Please try again after login. ';
                exit();
            }else{
                \Common\validDaToken( $raw['token'], $user );
                if( !isset( $user ) || empty( $user ) || empty( $user['uid'] ) ){
                    echo ' Invalid token('.$raw['token'].'), Please try again after login. ';
                    exit();
                }else{
                    $this->out = $user;
                }
            }
        }
    }

    private function checkPhoneNum($phone){
        return preg_match('/^1([0-9]{10})/',$phone);
    }

    /*
    * 导出excel 例子
    */
    public function getData( $where=[] ){

        $data[] = [
            'ch_name'  => '例子',
            'en_name'  => 'example'
        ];

        return $data;
    }

    //department
    public function exportDepart()
    {
        $raw = I('get.');

        $ret = [];
        $where = [];

        $search_key = ['pid'];
        foreach($search_key as $v){
            if($raw[$v])
                $where[$v] = $raw[$v];
        }

        if($raw['name'])
            $where['name'] = ['like','%'.$raw['name'].'%'];

        $columns = ['name','id','num','pid','atime'];
        $data = $this->modelUser->selectDept($columns, $where,'','pid asc');
        $pids = $p_info = [];
        foreach($data as $v){
            $pids[] = $v['pid'];
        }
        if($pids){
            $p_data = $this->modelUser->selectDept('id,name', ['id'=>['in',array_unique($pids)]],'','id,pid');
            foreach($p_data as $v){
                $p_info[$v['id']] = $v['name'];
            }
        }
        foreach($data as &$v){
            $v['p_name'] = $p_info[$v['pid']];
        }

        if (!$data)
            goto END;

        $title_num = array('A', 'B', 'C', 'D', 'E');
        $title = [
            'A' => "部门id",
            'B' => "部门名称",
            'C' => "上级部门",
            'D' => "部门人数",
            'E' => "添加时间"
        ];


        $name = urlencode('部门');
        $ext = [
            'WIDTH'=>25,
            'A'=>15,
            'B'=>30,
            'C'=>30,
            'I'=>10,
            'E'=>50,
        ];
        $this->excel($data, $title_num, $title, $name, 'addExcelDep',$ext);
END:
        $this->retReturn($ret);
    }

    public function getImportDepartExcel()
    {
        $raw = I('get.');

        $ret = [];
        $where = [];

        $title_num = array('A', 'B', 'C');
        $title = [
            'A' => "id(新增忽略)",
            'B' => "部门名称",
            'C' => "上级部门名称（部门完整名称，不填表示一级部门）"
        ];

        $name = urlencode('部门导入Excel模板');
        $ext = [
            'WIDTH'=>25,
            'C'=>60,
        ];
        $this->excel($this->dataDeptDemo, $title_num, $title, $name, 'addExcelDept',$ext);
END:
        $this->retReturn($ret);
    }

    public function importDepart()
    {
        $ret = [];
        $head = C('PREURL');
        vendor('phpexcel.PHPExcel.Reader.Excel2007');

        $ret['status'] = 1;
        $ret['errstr'] = [];

        $field = 'excel';
        $realpath = C("upload_path").'depart/file/';

        $conf = array(
            'pre' => 'pro',
            'types' => ['jpg', 'gif', 'png', 'jpeg', 'xls', 'xlsx'],
        );


        if (!is_dir($realpath)) $z = mkdir($realpath, 0775, true);
        $upload_res = \Common\commonUpload($field, $realpath, $conf);

        if ($upload_res['status'] != 0) {
            $ret = $upload_res;
            goto END;
        }

        $path = '';
        foreach ($upload_res['file'] as $key => $value) {
            $file_path = $value['savepath'] . $value['savename'];
            $path = $realpath . $value['savename'];
        }

        $PHPReader=new \PHPExcel_Reader_Excel2007();
        //设置属性

        $objPHPExcel = $PHPReader->load($path);


        $currentSheet = $objPHPExcel->getSheet(0);
        $allRow = $currentSheet->getHighestRow();  //获取总行数
        $list = $p_dep = $new_p_dept = $new_dept_name = $excel_data = $edit_data =[];


        //检查Excel 格式
        $title = [
            'A' => "部门id(新增忽略)",
            'B' => "部门名称",
            'C' => "上级部门名称（部门完整名称，不填表示一级部门）"
        ];
        $title1 = [
            'A' => "部门id",
            'B' => "部门名称",
            'C' => "上级部门名称"
        ];;
        $title2 = [
            'A' => "id(新增忽略)",
            'B' => "部门名称",
            'C' => "上级部门名称"
        ];

        $ceils = ['A','B','C'];
        foreach($ceils as $k=>$v){
            $cell = (string)$currentSheet->getCell($v.'1')->getValue() ;
            if($cell!= $title[$v] && $cell!=$title1[$v] && $cell!=$title2[$v]){
                $ret['errstr'][] = $v."列应该是'".$title[$v]."',而你的是 '".$cell."'";
            }
        }


        //all  dept
        $all_dept_dept = $this->modelUser->selectDept('id,name,pid');
        $all_dept_data = $dept_info = [];
        if($all_dept_dept){
            foreach($all_dept_dept as $v)
                $dept_info[$v['id']] = $v;
            foreach($all_dept_dept as $v){
                $all_dept_data[] = $v['pid']?$dept_info[$v['pid']]['name'].'-'.$v['name']:$v['name'];
            }
        }

        $t = time();
        $this_excel_nam5 = $this_excel_by_name = [];
        for ($Row = 2; $Row <= $allRow; $Row++) {
            $id = $currentSheet->getCell('A' . $Row)->getValue();;
            $dept_name= (string)$currentSheet->getCell('B' . $Row)->getValue();
            $dept_pid_name= (string)$currentSheet->getCell('C' . $Row)->getValue();
            $dept_pid_name = $dept_pid_name == '/'?'':$dept_pid_name;
            if(strstr($dept_name,'示例') || (!$dept_name && !$dept_pid_name))
                continue;

            if($id){
                if(!is_numeric($id))
                    $ret['errstr'][] = '第'.$Row.'行，部门id='.$id.',不是数字';

                $edit_data[] =  ['id'=>$id,'name'=>$dept_name];
            }else{
                if(!$dept_pid_name){
                    if(in_array($dept_name,$all_dept_data) ){
                        $ret['errstr'][] = '第'.$Row.'行，部门"'.$dept_name.'"，已存在';
                    }
                    if(in_array($dept_name,$this_excel_nam5)){
                        $ret['errstr'][] = '第'.$Row.'行，部门"'.$dept_name.'"，与第'.$this_excel_by_name[$dept_name]['row'].'行重复';
                    }
                    $new_dept_name[] = ['name'=>$dept_name,'atime' => $t];
                    $this_excel_nam5[] = $dept_name;
                }else{
                    if(in_array($dept_pid_name.'-'.$dept_name,$all_dept_data)){
                        $ret['errstr'][] = '第'.$Row.'行，部门"'.$dept_pid_name.'"，下已存在子部门"'.$dept_name.'"';
                    }
                }
            }
            $this_excel_by_name[$dept_name] = $excel_data[] = ['row'=>$Row,'id'=>$id,'name'=>$dept_name,'pid_name'=>$dept_pid_name];
        }

        if($ret['errstr'])
            goto END;


        if(empty($excel_data))
            $ret['errstr'][] = 'Excel无数据';


        if(isset($edit_data)){
            foreach ($edit_data as $item) {
                $this->modelUser->editDept($item['data'],['id'=>$item['id']]);
            }
        }
        if($new_dept_name) {
            $res_add_new_1st = $this->modelUser->addDeptAll($new_dept_name);

            if(!$res_add_new_1st){
                $ret['errstr'][] = '添加新部门失败';
                goto END;
            }
        }
        //all_dept
        $all_dept = $this->modelUser->selectDept('id,name');

        if($all_dept){
            foreach($all_dept as $v) {
                $list[$v['name']]= $v;
            }
        }

        $AllErrstr = [];
        if(isset($excel_data)){
            foreach($excel_data as $v){
                if($v['id'] && $v['pid_name']){
                    $this->modelUser->editDept(['pid'=>$list[$v['pid_name']]['id']],['id'=>$v['id']]);
                }else{
                    if($v['pid_name']){
                        if(!isset($list[$v['pid_name']]) && !in_array($v['pid_name'].'"不存在',$AllErrstr)){
                            $ret['errstr'][] = '第'.$v['row'].'行，上级部门"'.$v['pid_name'].'"不存在';
                            $AllErrstr[] = $v['pid_name'].'"不存在';
                        }

                        $add_data1[] = ['name'=>$v['name'],'pid'=>$list[$v['pid_name']]['id']];

                    }
                }
            }
        }
        if($ret['errstr']){
            goto END;
        }


        if($add_data1){
            $this->modelUser->addDeptAll($add_data1);
        }

        if($ret['errstr']){
            $ret['status'] = 1;
            goto END;
        }
        $ret['status'] = 0;

END:
        if(is_array($ret['errstr']) && count($ret['errstr']) > 10)
            $ret['errstr'] = array_slice($ret['errstr'] ,0,10);

        $this->retReturn($ret);
    }

    //user
    public function importUser(){
        $ret = [];
        $head = C('PREURL');
        vendor('phpexcel.PHPExcel.Reader.Excel2007');

        $ret['status'] = 0;
        $ret['errstr'] = [];
        $raw = $this->RxData;;

        if(!$raw['fid']){
            $ret['status'] = 5;
            $ret['errstr'][] = 'fid miss';
            goto END;
        }
        if($this->out['admin_role'] == S_FALSE && !in_array( $raw['fid'],$this->out['role_fiem_id'])){
            $ret['errstr'][] = '无该公司的操作权限';
            $ret['status'] = 14;
            goto END;
        }
        $field = 'excel';
        $realpath = C("upload_path").'client/file/';

        $conf = array(
            'pre' => 'pr1o',
            'types' => ['jpg', 'gif', 'png', 'jpeg', 'xls', 'xlsx'],
        );

        if (!is_dir($realpath)) $z = mkdir($realpath, 0775, true);
        $upload_res = \Common\commonUpload($field, $realpath, $conf);

        if ($upload_res['status'] != 0) {
            $ret = $upload_res;
            $ret['errstr'] = ['上传错误'];
            $ret['status'] = 1;
            goto END;
        }

        $path = '';
        foreach ($upload_res['file'] as $key => $value) {
            $file_path = $value['savepath'] . $value['savename'];
            $path = $realpath . $value['savename'];
        }

        $PHPReader=new \PHPExcel_Reader_Excel2007();
        //设置属性

        $objPHPExcel = $PHPReader->load($path);

        error_reporting(E_ALL ^ E_NOTICE);

        $currentSheet = $objPHPExcel->getSheet(0);
        $phones = $level = $dep_1st = $dep_2nd =  $dep_2nd_all = $dep_dept_all = [];

        $t = time();
        $edit_list = $list = [];
        $allRow = $currentSheet->getHighestRow();  //获取总行数
        $second_repeat = $id_card_exist_id = $first_repeat =  $id_card_exist = [];

        //检查Excel 格式
        $title = [
            'A' => "uid(新增留空)",
            'B' => "身份证号码",
            'C' => "姓名",
            'D' => "手机号(必填)",
            'E' => "性别(男/女)",
            'F' => "年龄",
            'G' => "身高(cm)",
            'H' => "体重(kg)",
        ];
        $title1 = [
            'A' => "uid",
            'B' => "身份证号码",
            'C' => "姓名",
            'D' => "手机号",
            'E' => "性别",
            'F' => "年龄",
            'G' => "身高(cm)",
            'H' => "体重(kg)",
        ];

        $title_num = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');

        foreach($title_num as $k=>$v){
            $cell = (string)$currentSheet->getCell($v.'1')->getValue() ;
            if($cell!= $title[$v] && $cell != $title1[$v]){
                $ret['errstr'][] = $v."列应该是'".$title[$v]."',而你的是 '".$cell."'";
            }
        }
        if( $ret['errstr']){
            $ret['status'] = 1;
            goto END;
        }

        //all user
        $all_data = $all_phones = [];
        $all_user = $this->modelUser->selectUser('uid,phone');
        if($all_user){
            foreach($all_user as $v){
                $all_data[$v['uid']] = $v;
                $all_phones[$v['phone']] = $v;
            }
        }

        $demo_key = ['phone','nickname','idcard'];
        $this_phone = [];
        $excel_id_card = [];
        for ($Row = 2; $Row <= $allRow; $Row++) {
            $phone_num = (string)$currentSheet->getCell('D' . $Row)->getValue();
            $phone_num = (int)$phone_num;
            $nickname = (string)$currentSheet->getCell('C' . $Row)->getValue();

            if(empty($phone_num) && empty($nickname))
                continue;

            //身份证必填
            if(empty($currentSheet->getCell('B' . $Row)->getValue())){
                $ret['errstr'][] = '第'.$Row.'行，身份证为空';
                continue;
            }
            if($phone_num && (!$this->checkPhoneNum($phone_num) || strlen($phone_num) != 11)){
                $ret['errstr'][] = '第'.$Row.'行，手机号'.$phone_num.'格式错误';
                continue;
            }

            $this_phone[] = $phone_num;

            $uid = $currentSheet->getCell('A' . $Row)->getValue();
            $id_card = (int)$currentSheet->getCell('B' . $Row)->getValue();
            $sex = (string)$currentSheet->getCell('E' . $Row)->getValue();
            $weight = $currentSheet->getCell('H' . $Row)->getValue()?$currentSheet->getCell('H' . $Row)->getValue()*1000:0;

            $excel_id_card[$id_card] = ['line'=>$Row,'nickname'=>$nickname];
            $sex = $sex == '男'?'男':'女';

            foreach($demo_key as $v){
                if(${$v} == $this->dataDemo[0][$v] || ${$v} == $this->dataDemo[1][$v])
                    continue;
            }
            //num check
            $num_line = ['F','G','H'];

            foreach($num_line as $v){
                $data_temp = $currentSheet->getCell($v . $Row)->getValue();
                if(((string)$data_temp && !is_numeric($data_temp)) || $data_temp <0 ){
                    $ret['errstr'][] = '第'.$Row.'行，'.$title[$v].'='.(string)$currentSheet->getCell($v . $Row)->getValue().'，不符合规范';
                }
            }

           
            if($uid){
                if(!is_numeric($uid))
                    $ret['errstr'][] = '第'.$Row.'行，uid="'.$uid.'",不是数字';
                $edit_list[] = [
                    'uid' => $uid,
                    'data'=> [
                        'idcard'     => $id_card?$id_card:'',
                        'nickname'   => $nickname,
                        'phone'       => $phone_num,
                        'sex'         => $sex == '男'?1:2,
                        'age'         => $currentSheet->getCell('F' . $Row)->getValue()?$currentSheet->getCell('F' . $Row)->getValue():0,
                        'height'     => $currentSheet->getCell('G' . $Row)->getValue()?$currentSheet->getCell('G' . $Row)->getValue():0,
                        'weight'     => $weight,
                        'fid'         => $raw['fid'],
                        'dept_id'       => $raw['dept_id']?$raw['dept_id']:0
                    ]
                ];
            }else{
                $new_id_card[] =  $id_card;
                $list[] = [
                    'idcard' => $id_card?$id_card:'',
                    'nickname'   => (string)$currentSheet->getCell('C' . $Row)->getValue(),
                    'phone'      => $currentSheet->getCell('D' . $Row)->getValue(),
                    'sex'         => $sex == '男'?1:2,
                    'age'        => $currentSheet->getCell('F' . $Row)->getValue()?$currentSheet->getCell('F' . $Row)->getValue():0,
                    'height'     => $currentSheet->getCell('G' . $Row)->getValue()?$currentSheet->getCell('G' . $Row)->getValue():0,
                    'weight'     => $weight,
                    'fid'     => $raw['fid'],
                    'dept_id'       => $raw['dept_id']?$raw['dept_id']:0,
                    'atime'     => $t
                    ];

            }

            if(!in_array($sex,['男','女']))
                $ret['errstr'][] = '第'.$Row.'行，性别= '.$sex.',不符合规范';

            $id_card_exist_by_phone[$phone_num] =  ['line'=>$Row,'uid'=>$uid,'phone'=>$phone_num];
      
        }
        if( $ret['errstr']){
            $ret['status'] = 12;
            goto END;
        }
        // check idcard repeat
        if($new_id_card){
            $exist_check = $this->modelUser->selectUser('uid,idcard,nickname',['idcard'=>['in',$new_id_card],'fid'=>$raw['fid']]);
            if($exist_check){
                foreach($exist_check as $v){
                        $ret['errstr'][] = '第'.$excel_id_card[$v['idcard']]['line'].'行，身份证号码-'.$v['idcard'].'-已被-'.$v['nickname'].'-注册';
                    }
                }
                if($ret['errstr']){
                    $ret['status'] = 1;
                    goto END;
                }
        }


        if( $ret['errstr']){
            $ret['status'] = 12;
            goto END;
        }
/*        if($phones){
            $exist_check = $this->modelUser->selectUser('uid,phone',['phone'=>['in',$phones]]);
            if($exist_check){
                foreach($exist_check as $v){
                    $ret['errstr'][] = '第'.$id_card_exist_by_phone[$v['phone']]['line'].'行，手机号'.$v['phone'].'已被注册';
                }
                $ret['status'] = 13;
                goto END;
            }
        }*/
        if( $ret['errstr']){
            $ret['status'] = 12;
            goto END;
        }
        //add user
        $keys = ['edit_list','list'];

        if($edit_list){
            foreach($edit_list as $v){
                $this->modelUser->editUser($v['data'],['uid'=>$v['uid']]);
            }
        }
        $this->modelUser->updateDeptNum();
        if($list){
            $res = $this->modelUser->addUserAll($list);
            if(!$res){
                $ret['status'] = 1;
                $ret['errstr'][] = '用户新增失败';
                goto END;
            }
        }

END:
        if(is_array($ret['errstr']) && count($ret['errstr']) > 1)
            $ret['errstr'] = array_slice($ret['errstr'] ,0,1);

        $this->retReturn($ret);
    }

    public function exportUser()
    {
        $raw = I('get.');
        $raw['page_limit'] = $raw['page_limit'] ?$raw['page_limit'] :$this->limit;
        $ret = [];
        $result = $this->modelUser->getUserList($raw,$this->out);

        if ($result['status'] != 0)
            goto END;

        $title_num = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $title = [
            'A' => "uid",
            'B' => "身份证号码",
            'C' => "姓名",
            'D' => "手机号",
            'E' => "性别",
            'F' => "年龄",
            'G' => "身高(cm)",
            'H' => "体重(kg)",
        ];
        $firm_info = $this->modelUser->findFirm('name',['id'=>$raw['fid']]);

        $name = urlencode($firm_info['name'].'-用户列表');
        $ext = [
            'WIDTH'=>25,
            'A'=>10,
            'B'=>30,
            'C'=>18,
            'D'=>20,
            'G'=>10,
            'F'=>10,
            'E'=>8,
            'H'=>10,
            'I'=>30,
            'J'=>30
        ];
        if($result['data']){
            foreach($result['data'] as &$v)
                $v['weight'] = $v['weight']?$v['weight']:0;
        }

        unset($v);
        $this->excel($result['data'], $title_num, $title, $name, 'addExcelUser',$ext);
END:
        $this->retReturn($ret);
    }

    public function getImportUserExcel()
    {
        $raw = I('get.');

        $ret = [];

        $title_num = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $title = [
            'A' => "uid(新增留空)",
            'B' => "身份证号码",
            'C' => "姓名",
            'D' => "手机号(必填)",
            'E' => "性别(男/女)",
            'F' => "年龄",
            'G' => "身高(cm)",
            'H' => "体重(kg)",
        ];



        $name = urlencode('用户导入Excel模板');
        $ext = [
            'WIDTH'=>15,
            'B'=>20,
            'E'=>10,
            'F'=>10,
            'G'=>10,
            'J'=>25,
            'K'=>30,
        ];

        $data = $this->dataDemo;
        $this->excel($data, $title_num, $title, $name, 'addExcelUser',$ext);
END:
        $this->retReturn($ret);
    }


    // user sign list
    public function exportActSignUser()
    {
        $raw = I('get.');

        $ret = [];
        $raw['excel'] = true;
        if(!$raw['id'])
            goto END;
        $data = $this->modelActivity->showUserSignList($raw);

        if (!$data['data'])
            goto END;

        $title = [
            'A' => "姓名",
            'B' => "身份证号码",
        ];
        $title_num = array('A', 'B');
        $title_num_data = [];
        $act_info = $this->modelActivity->findAct('',['id'=>$raw['id']]);


        $ext = [
            'WIDTH'=>7,
            'title_num_data'=>$title_num_data,
            'A'=>10,
            'B'=>30,
        ];

        $j = 4;
        foreach($data['analysis'] as $v){
            $this_num = $j>26?chr(floor($j/26.5)+63).chr(($j%26==0?26:$j%26)+63):chr($j+63);
            $title_num_data[] = $title_num[] = $this_num;
            $title[$this_num] = date("m/d",$v['time']);
            $ext['alias'][$j+63] = $this_num;
            $ext['tags'][$this_num] = $j+63;
            $j++;

        }
        $name = urlencode('活动-'.$act_info['title'].'-用户签到列表');

        $this->excel($data['data'], $title_num, $title, $name, 'addExcelUserSign',$ext);
END:
        $this->retReturn($ret);
    }

    //act rank list
    public function exportActRankList()
    {
        $raw = I('get.');

        $ret = [];
        $raw['excel'] = true;
        $data = $this->modelActivity->showUserStepRankList($raw);

        if (!$data['data'])
            goto END;

        $title = [
            'A' => "名次",
            'B' => "姓名",
            'C' => "身份证号码",
            'D' => "总步数",
        ];
        $title_num = array('A', 'B', 'C', 'D');
        $title_num_data = [];
        $act_info = $this->modelActivity->findAct('',['id'=>$raw['id']]);


        $ext = [
            'WIDTH'=>7,
            'title_num_data'=>$title_num_data,
            'A'=>10,
            'B'=>30,
            'C'=>30,
        ];

        $j = 5;
        foreach($data['analysis'] as $v){
            $this_num = $j>26?chr(floor($j/26.5)+64).chr(($j%26==0?26:$j%26)+64):chr($j+64);
            $title_num_data[] = $title_num[] = $this_num;
            $title[$this_num] = date("m/d",$v['time']);
            $ext['alias'][$j+64] = $this_num;
            $ext['tags'][$this_num] = $j+64;
            $j++;

        }

        $name = urlencode('活动-'.$act_info['title'].'-用户步数排行榜');

        $this->excel($data['data'], $title_num, $title, $name, 'addExcelUserActRank',$ext);
        END:
        $this->retReturn($ret);
    }


    //upload
    public function upload(){
        $ret = [];
        $head = C('PREURL');

        $ret['status'] = 0;
        $ret['errstr'] = '';
        $conf = array(
            'pre' => 'img',
        );
        $upload_res = \Common\ownUploadImgIndirect1(true,false,$conf);

        if ($upload_res['status'] != 0) {
            $ret = $upload_res;
            goto END;
        }
        $ret['status'] = 0;
        $ret['errstr'] = '';
        $ret['url'] = $head . $upload_res['thumb_path'];

        $ret['path'] = $upload_res['thumb_path'];

END:
        $this->retReturn($ret);
    }

    public function addExcelDep( &$objExcel, $data=[] ) {
        $i=2;
        foreach($data as $value) {
            /*----------写入内容-------------*/
            $objExcel->getActiveSheet()->setCellValue('a'.$i, $value['id']);
            $objExcel->getActiveSheet()->setCellValue('b'.$i, $value['name']);
            $objExcel->getActiveSheet()->setCellValue('c'.$i, $value['p_name']?$value['p_name']:'/');
            $objExcel->getActiveSheet()->setCellValue('d'.$i, $value['num']);
            $objExcel->getActiveSheet()->setCellValue('e'.$i, date('Y-m-d H:i:s',$value['atime']));

            $i++;
        }
    }


    public function addExcelUser( &$objExcel, $data=[] ) {
        $i=2;
        foreach($data as $value) {
            /*----------写入内容-------------*/


            $objExcel->getActiveSheet()->setCellValue('a'.$i, $value['uid']);
            $objExcel->getActiveSheet()->setCellValueExplicit ('b'.$i, $value['idcard']);
            $objExcel->getActiveSheet()->setCellValue('c'.$i, $value['nickname']);
            $objExcel->getActiveSheet()->setCellValue('d'.$i, $value['phone'] );
            $objExcel->getActiveSheet()->setCellValue('e'.$i, $value['sex'] ==1 ?'男':'女');
            $objExcel->getActiveSheet()->setCellValue('f'.$i, $value['age']);
            $objExcel->getActiveSheet()->setCellValue('g'.$i, $value['height']);
            $objExcel->getActiveSheet()->setCellValue('h'.$i, $value['weight']);


        $i++;
        }
    }

    public function addExcelDept( &$objExcel, $data=[] ) {
        $i=2;
        foreach($data as $value) {
            /*----------写入内容-------------*/

            $objExcel->getActiveSheet()->setCellValue('a'.$i, $value['id']);
            $objExcel->getActiveSheet()->setCellValue('b'.$i, $value['dept_name']);
            $objExcel->getActiveSheet()->setCellValue('c'.$i, $value['dept_pid_name']);

        $i++;
        }
    }


    public function addExcelUserSign( &$objExcel, $data=[] ,$title = []) {
        $i=2;
        foreach($data[0] as $value) {
            /*----------写入内容-------------*/

            $objExcel->getActiveSheet()->setCellValue('a'.$i, $value['nickname']);
            $objExcel->getActiveSheet()->setCellValue('b'.$i, $value['idcard']);
            $j = 67;
            foreach($value['data'] as $v){
                $objExcel->getActiveSheet()->setCellValue($title[$j].$i, $v['sign'] == S_TRUE?'√':'');
                $j++;
            }
            $i++;
        }
    }

    public function addExcelUserActRank( &$objExcel, $data=[] ,$title = []) {
        $i=2;
        foreach($data as $value) {
            /*----------写入内容-------------*/

            $objExcel->getActiveSheet()->setCellValue('a'.$i, $value['rank']);
            $objExcel->getActiveSheet()->setCellValue('b'.$i, $value['nickname']);
            $objExcel->getActiveSheet()->setCellValueExplicit('c'.$i, $value['idcard']);
            $objExcel->getActiveSheet()->setCellValue('d'.$i, $value['step_n']);
            $j = 69;
            foreach($value['data'] as $v){
                $objExcel->getActiveSheet()->setCellValue($title[$j].$i, $v['step_n']?$v['step_n']:0);
                $j++;
            }
            $i++;
        }
    }



    /*
    * 导出共用函数
    */
    protected function excel( $data=[], $title_num=[], $title=[], $name='', $fun='excelAddData' ,$ext){

        vendor( 'phpexcel.PHPExcel' );
        vendor( 'phpexcel/PHPExcel.IOFactory' );
        $objExcel = new \PHPExcel();
        //设置属性


        $objExcel->getProperties()->setCreator('System');
        $objExcel->getProperties()->setLastModifiedBy('System');
        $objExcel->getProperties()->setTitle("excel");
        $objExcel->getProperties()->setSubject("excel");
        $objExcel->getProperties()->setDescription("excel");
        $objExcel->getProperties()->setKeywords("excel");
        $objExcel->getProperties()->setCategory("data");
        $objExcel->setActiveSheetIndex(0);

        // title
        foreach ($title_num as $k=>$val) {
            if(isset($ext['special'] )) {
                if(isset($ext['tags'][$val]) && $ext['tags'][$val] >=68){
                        $objExcel->getActiveSheet()->setCellValue( $val.'1', $title[ $val ] );
                }
            }else{
                $objExcel->getActiveSheet()->setCellValue( $val.'1', $title[ $val ] );
            }
            //字体大小
            $objExcel->getActiveSheet()->getStyle($val.'1')->getFont()->setSize(12);
            //字体加粗
            $objExcel->getActiveSheet()->getStyle($val.'1')->getFont()->setBold(true);
        }

        //设置标题填充颜色以及字体居中
        foreach ($title_num as $value) {
            $objExcel->getActiveSheet()->getStyle($value.'1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
            $objExcel->getActiveSheet()->getStyle($value.'1')->getFill()->getStartColor()->setARGB('DCDCDC');
            $objExcel->getActiveSheet()->getStyle($value.'1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objExcel->getActiveSheet()->getStyle($value.'1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

            if(isset($ext['special'] )){

                $objExcel->getActiveSheet()->getStyle($value.'2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                $objExcel->getActiveSheet()->getStyle($value.'2')->getFill()->getStartColor()->setARGB('DCDCDC');

                $objExcel->getActiveSheet()->getStyle($value.'2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objExcel->getActiveSheet()->getStyle($value.'2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

            }
        }


        // write
        $this->$fun( $objExcel, $data, $ext['alias'] );

        // 高置列的宽度
        foreach ($title_num as $value) {
            $width = $ext[$value]?$ext[$value]:$ext['WIDTH'];
            $objExcel->getActiveSheet()->getColumnDimension($value)->setWidth($width);
        }

        // 设置第一行行高
        // $objExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);

        $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
        $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');

        $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        //设置页方向和规模
        $objExcel->getActiveSheet()->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        $objExcel->getActiveSheet()->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        $objExcel->setActiveSheetIndex(0);
        $timefmt = date('Y-m-d');
        $name = $name?$name:'excel';
        $ex = '2007';
        if($ex == '2007') { //导出excel2007文档
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$name.'.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
            $objWriter->save('php://output');
            exit;
        } else {  //导出excel2003文档
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$name.'[export with '.$timefmt.'].xls"');
            header('Cache-Control: max-age=0');
            $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
            $objWriter->save('php://output');
            exit;
        }
    }


}
