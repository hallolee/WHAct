<?php
namespace Admin\Controller;

class FileController extends GlobalController {
    protected $modelUser;
    protected $modelActivity;
    protected $modelScore;
    protected $dataDemo;
    protected $dataDeptDemo;
    protected $limit = 10000;
    public function _initialize(){
        parent::_initialize();
        $this->modelUser = D('Admin/User');
        $this->modelActivity = D('Admin/Activity');
        $this->modelScore = D('Admin/Score');

        $this->dataDemo = [
            [
                'uid' => ' 198',
                'job_num' => 'ce0w01',
                'nickname' => '张敏',
                'phone' => 13696965658,
                'sex' => 2,
                'age' => 19,
                'height' => 165,
                'weight' => 47.3,
                'target_step_n' => 1165,
                'dept_pid_real_name' => '结算与网络金融部',
                'dept_real_name' => '',
            ],
            [
                'job_num' => 'ce0w02',
                'nickname' => '梅艳芳',
                'phone' => 13696965668,
                'sex' => 2,
                'age' => 20,
                'height' => 166,
                'weight' => 53,
                'target_step_n' => 1005,
                'dept_pid_real_name' => '白云支行',
                'dept_real_name' => '市场发展部/中华广场支行',
            ],
        ];
        $this->dataDeptDemo = [
            ['id'=>'9910','dept_name'=>'示例海珠分行-后勤','dept_pid_name'=>'示例海珠分行'],
            ['id'=>'','dept_name'=>'示例海珠分行','dept_pid_name'=>'']
        ];

//        get 获取token 验证登录
        if( !isset( $this->out['uid'] ) || empty( $this->out['uid'] ) || !is_numeric( $this->out['uid'] ) ){
            // $ret = [ 'status' => E_TOKEN, 'errstr' => '' ];
            $raw = I('get.');
            if( !isset( $raw['token'] ) ){
                echo ' Invalid token('.$raw['token'].'), Please try again after login. ';
                exit();
            }else{
                \Common\ValidDaTokenFile( $raw['token'], $user );
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
        $upload_res = \Common\_Upload($field, $realpath, $conf);

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
        $raw = I('get.');

        $field = 'excel';
        $realpath = C("upload_path").'client/file/';

        $conf = array(
            'pre' => 'pr1o',
            'types' => ['jpg', 'gif', 'png', 'jpeg', 'xls', 'xlsx'],
        );

        if (!is_dir($realpath)) $z = mkdir($realpath, 0775, true);
        $upload_res = \Common\_Upload($field, $realpath, $conf);

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
        //dep_info
        $dep_info  = $this->modelUser->getDeptInfo();
        if($dep_info){
            foreach($dep_info as $v){
                $dep[$v['pid_name']] = $v;
                if($v['pid'] == 0){
                    $dep_dept_all[] = $v['pid_name'];
                }else{
                    $dep_2nd_all[] = $v['pid_name'];
                }
            }
        }
        $dep['未分配'] = ['id'=>0,'name'=>'未分配'];
        $dep_dept_all[] = '未分配';
        $dep_2nd_all[] = '未分配';

        $t = time();
        $edit_list = $list = [];
        $allRow = $currentSheet->getHighestRow();  //获取总行数
        $second_repeat = $phones_exist_id = $first_repeat =  $phones_exist = [];

        //检查Excel 格式
        $title = [
            'A' => "uid(新增留空)",
            'B' => "工号",
            'C' => "姓名",
            'D' => "手机号(必填)",
            'E' => "性别(男/女)",
            'F' => "年龄",
            'G' => "身高(cm)",
            'H' => "体重(kg)",
            'I' => "目标步数",
            'J' => "一级部门",
            'K' => "二级部门"
        ];
        $title1 = [
            'A' => "uid",
            'B' => "工号",
            'C' => "姓名",
            'D' => "手机号",
            'E' => "性别",
            'F' => "年龄",
            'G' => "身高(cm)",
            'H' => "体重(kg)",
            'I' => "目标步数",
            'J' => "一级部门",
            'K' => "二级部门"
        ];

        $title_num = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L');

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

        $demo_key = ['phone','nickname','job_num'];
        $this_phone = [];
        $this_phone_haha = [];
        for ($Row = 2; $Row <= $allRow; $Row++) {
            $phone_num = (string)$currentSheet->getCell('D' . $Row)->getValue();
            $phone_num = (int)$phone_num;
            $nickname = (string)$currentSheet->getCell('C' . $Row)->getValue();

            var_dump($Row);
            foreach($title_num as $k=>$v){
                var_dump((string)$currentSheet->getCell($v . $Row)->getValue());
            }
            if($Row == 5)
                die;
            if(empty($phone_num) && empty($nickname))
                continue;

            //手机号必填
            if(empty($currentSheet->getCell('D' . $Row)->getValue())){
                $ret['errstr'][] = '第'.$Row.'行，手机号为空';
                continue;
            }
            if(!$this->checkPhoneNum($phone_num) || strlen($phone_num) != 11){
                $ret['errstr'][] = '第'.$Row.'行，手机号'.$phone_num.'格式错误';
                continue;
            }

            if(in_array($phone_num,$this_phone)){
                $ret['errstr'][] = '第'.$Row.'行手机号与'.$this_phone_haha[$phone_num].'行重复';
                continue;
            }
            $this_phone_haha[$phone_num] = $Row;
            $this_phone[] = $phone_num;


            $uid = $currentSheet->getCell('A' . $Row)->getValue();
            $first_dep = (string)$currentSheet->getCell('J' . $Row)->getValue();
            $second_dep = (string)$currentSheet->getCell('K' . $Row)->getValue();
            $sex = (string)$currentSheet->getCell('E' . $Row)->getValue();
            $sex = $sex == '男'?'男':'女';
            $weight = $currentSheet->getCell('H' . $Row)->getValue()?$currentSheet->getCell('H' . $Row)->getValue()*1000:0;

            $job_coding = $currentSheet->getCell('B' . $Row)->getValue();


            foreach($demo_key as $v){
                if(${$v} == $this->dataDemo[0][$v] || ${$v} == $this->dataDemo[1][$v])
                    continue;
            }
            //num check
            $num_line = ['F','G','H','I'];

            foreach($num_line as $v){
                $data_temp = $currentSheet->getCell($v . $Row)->getValue();
                if(((string)$data_temp && !is_numeric($data_temp)) || $data_temp <0 ){
                    $ret['errstr'][] = '第'.$Row.'行，'.$title[$v].'='.(string)$currentSheet->getCell($v . $Row)->getValue().'，不符合规范';
                }
            }


            if($first_dep == '/' || $first_dep == '')
                $first_dep = '';
            if($second_dep == '/' || $second_dep == '')
                $second_dep = '';

            if($first_dep != '未分配' && $second_dep)
                $second_dep = $first_dep.'-'.(string)$currentSheet->getCell('K' . $Row)->getValue();

            if($first_dep  && $second_dep){
                $direct_dept_id = $dep[$second_dep]?$dep[$second_dep]['id']:$dep[$first_dep]['id'];
            }elseif($first_dep){
                $direct_dept_id = $dep[$first_dep]['id'];
            }elseif($second_dep){
                $direct_dept_id = $dep[$second_dep]['id'];
            }else{
                $direct_dept_id = 0;
            }

            if($uid){
                if(!is_numeric($uid))
                    $ret['errstr'][] = '第'.$Row.'行，uid="'.$uid.'",不是数字';

                $phones_exist[$uid] =  $phone_num;
                $phones_exist_id[$uid] =  ['line'=>$Row,'uid'=>$uid,'phone'=>$phone_num];
                if($phone_num != $all_data[$uid]['phone']){
                    if(isset($all_phones[$phone_num]))
                        $ret['errstr'][] = '第'.$Row.'行，手机号'.$phone_num.'已被注册';
                }
                $edit_list[] = [
                    'uid' => $uid,
                    'data'=> [
                        'job_coding' => $job_coding?$job_coding:'',
                        'nickname'   => $nickname,
                        'phone'       => $phone_num,
                        'sex'         => $sex == '男'?1:2,
                        'age'         => $currentSheet->getCell('F' . $Row)->getValue()?$currentSheet->getCell('F' . $Row)->getValue():0,
                        'height'     => $currentSheet->getCell('G' . $Row)->getValue()?$currentSheet->getCell('G' . $Row)->getValue():0,
                        'weight'     => $weight,
                        'step_aim'     => $currentSheet->getCell('I' . $Row)->getValue()?$currentSheet->getCell('I' . $Row)->getValue():0,
                        'dept_pid'     => $dep[$first_dep]['id']?$dep[$first_dep]['id']:0,
                        'dept_id'       => $dep[$second_dep]['id']?$dep[$second_dep]['id']:0,
                        'dept_id_direct'   => $direct_dept_id
                    ]
                ];
            }else{
                $phones[] =  $currentSheet->getCell('D' . $Row)->getValue();
                $list[] = [
                    'job_coding' => $job_coding?$job_coding:'',
                    'nickname'   => (string)$currentSheet->getCell('C' . $Row)->getValue(),
                    'phone'      => $currentSheet->getCell('D' . $Row)->getValue(),
                    'sex'         => $sex == '男'?1:2,
                    'age'        => $currentSheet->getCell('F' . $Row)->getValue()?$currentSheet->getCell('F' . $Row)->getValue():0,
                    'height'     => $currentSheet->getCell('G' . $Row)->getValue()?$currentSheet->getCell('G' . $Row)->getValue():0,
                    'weight'     => $weight,
                    'step_aim'     => $currentSheet->getCell('I' . $Row)->getValue()?$currentSheet->getCell('I' . $Row)->getValue():0,
                    'dept_pid'   => $dep[$first_dep]['id']?$dep[$first_dep]['id']:0,
                    'dept_id'   => $dep[$second_dep]['id']?$dep[$second_dep]['id']:0,
                    'dept_id_direct'   => $direct_dept_id,
                    'atime'     => $t
                    ];

            }

            if(!in_array($sex,['男','女']))
                $ret['errstr'][] = '第'.$Row.'行，性别= '.$sex.',不符合规范';

            $phones_exist_by_phone[$phone_num] =  ['line'=>$Row,'uid'=>$uid,'phone'=>$phone_num];

            if($dep[$first_dep]['id'] && !in_array( $dep[$first_dep]['id'],$this->out['role_dept_id'])){
                $ret['errstr'][] = '第'.$Row.'行，您没有部门*'.$first_dep.'*的权限';
                $ret['status'] = 1;
                goto END;

            }
            if($second_dep && !in_array($second_dep,$dep_2nd_all))
                $ret['errstr'][] = '第'.$Row.'行，二级部门*'.$second_dep.'*不存在';
            if($first_dep && !in_array($first_dep,$dep_dept_all))
                $ret['errstr'][] =  '第'.$Row.'行，一级部门*'.$first_dep.'*不存在';

        }
        if( $ret['errstr']){
            $ret['status'] = E_EXCEL_NO_EXIST;
            goto END;
        }
        // check phone repeat
        if($phones_exist){
            $exist_check = $this->modelUser->selectUser('uid,phone',['phone'=>['in',$phones_exist]]);
            if($exist_check){
                foreach($exist_check as $v){
                    if($v['phone'] != $phones_exist[$v['uid']]){
                        $exist_phone_check = true;
                        $ret['errstr'][] = '第'.$phones_exist_id[$v['uid']]['line'].'行，手机号'.$phones_exist[$v['uid']].'已被注册';
                    }
                }
                if($exist_phone_check){
                    $ret['status'] = 1;
                    goto END;
                }
            }
        }

        if( $ret['errstr']){
            $ret['status'] = E_EXCEL_NO_EXIST;
            goto END;
        }
        if($phones){
            $exist_check = $this->modelUser->selectUser('uid,phone',['phone'=>['in',$phones]]);
            if($exist_check){
                foreach($exist_check as $v){
                    $ret['errstr'][] = '第'.$phones_exist_by_phone[$v['phone']]['line'].'行，手机号'.$v['phone'].'已被注册';
                }
                $ret['status'] = E_STATUS;
                goto END;
            }
        }
        if( $ret['errstr']){
            $ret['status'] = E_EXCEL_NO_EXIST;
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
        if(is_array($ret['errstr']) && count($ret['errstr']) > IMPORT_LIMIT)
            $ret['errstr'] = array_slice($ret['errstr'] ,0,IMPORT_LIMIT);

        $this->retReturn($ret);
    }

    public function exportUser()
    {
        $raw = I('get.');

        $raw['page_limit'] = $this->limit;
        $ret = [];
        $where = [];

        $result = $this->modelUser->getUserList($raw,$this->out);

        if ($result['status'] != 0)
            goto END;

        $dep_id = [];

        $dept_info = $this->modelUser->getDeptInfo();

        foreach($result['data'] as &$v){
            $v['dept_pid_real_name'] = $dept_info[$v['dept_pid']]?$dept_info[$v['dept_pid']]['name']:'未分配';
            $v['dept_real_name'] = $dept_info[$v['dept_id']]?$dept_info[$v['dept_id']]['name']:'/';
        }
        unset($v);

        $title_num = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L');
        $title = [
            'A' => "uid",
            'B' => "工号",
            'C' => "姓名",
            'D' => "手机号",
            'E' => "性别",
            'F' => "年龄",
            'G' => "身高(cm)",
            'H' => "体重(kg)",
            'I' => "目标步数",
            'J' => "一级部门",
            'K' => "二级部门"
        ];

        $name = urlencode('用户列表');
        $ext = [
            'WIDTH'=>25,
            'A'=>20,
            'B'=>15,
            'C'=>18,
            'D'=>20,
            'G'=>10,
            'F'=>10,
            'E'=>20,
            'H'=>10,
            'I'=>10,
            'J'=>20,
            'K'=>20,
        ];
        if($result['data']){
            foreach($result['data'] as &$v)
                $v['weight'] = $v['weight']?$v['weight']/1000:0;
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

        $title_num = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L');
        $title = [
            'A' => "uid(新增留空)",
            'B' => "工号",
            'C' => "姓名",
            'D' => "手机号(必填)",
            'E' => "性别(男/女)",
            'F' => "年龄",
            'G' => "身高(cm)",
            'H' => "体重(kg)",
            'I' => "目标步数",
            'J' => "一级部门",
            'K' => "二级部门"
        ];



        $name = urlencode('用户导入Excel模板');
        $ext = [
            'WIDTH'=>15,
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

    //ranklist
    public function exportRankList(){
        $raw = I('get.');

        $ret = [];
        $where = [];
        $raw['page_limit'] = $this->limit;

        if(!$raw['type'])
            $raw['type'] = 1;
        if($raw['type'] == 1){
            $result = $this->modelUser->getUserRankList($raw);
            $title = [
                'A' => "名次",
                'B' => "姓名",
                'C' => "部门",
                'D' => "等级",
                'E' => "员工平均步数",
                'F' => "累计冠军次数"
            ];
            $title_num = array('A', 'B', 'C', 'D', 'E', 'F');
            $name = urlencode('员工排行榜');
            $fun = 'addExcelUserRank';


        }else{
            $result = $this->modelUser->getDeptRankList($raw);
            $title = [
                'A' => "名次",
                'B' => "部门",
                'C' => "部门人数",
                'D' => "员工平均步数",
                'E' => "累计冠军次数"
            ];
            $title_num = array('A', 'B', 'C', 'D', 'E');

            $name = urlencode('一级部门排行榜');
            if($raw['type'] == 3)
                $name = urlencode('二级部门排行榜');

            $fun = 'addExcelDeptRank';

        }

        $ext = [
            'WIDTH'=>25,
            'A'=>10,
            'B'=>25,
            'C'=>25,
            'D'=>25,
            'E'=>25,
            'F'=>25,
        ];




        $this->excel($result['data'], $title_num, $title, $name, $fun,$ext);
    END:
        $this->retReturn($ret);
}

    //actRanklist
    public function exportActRankList(){
        $raw = I('get.');
        $raw['page_limit'] = $this->limit;

        $ret = [];
        $act_exist = $this->modelActivity->findAct('title',['id'=>$raw['id']]);
        if(!$act_exist)
            goto END;
        $ret = $this->modelActivity->getActUesrDoneList($raw,'0,5000',1);
        $title = [
            'A' => "名次",
            'B' => "姓名",
            'C' => "手机号",
            'D' => "所属部门",
            'E' => "步数",
            'F' => "完成时间"
        ];
        $title_num = array('A', 'B', 'C', 'D', 'E', 'F');
        $name = urlencode('活动"'.$act_exist['title'].'"完成员排行榜');
        $fun = 'addExcelAcrDoneUserRank';

        $ext = [
            'WIDTH'=>25,
            'A'=>10,
            'B'=>25,
            'C'=>25,
            'D'=>25,
            'E'=>20,
            'F'=>25,
        ];

        if($ret['data']){
            $i = 1;
            foreach($ret['data'] as &$v){
                $v['rank'] = $i;
                $i++;
            }
        }


        $this->excel($ret['data'], $title_num, $title, $name, $fun,$ext);
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
        $upload_res = \Common\_OwnUploadImgIndirect1(true,false,$conf);

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

    //act Done
    public function addExcelAcrDoneUserRank( &$objExcel, $data=[] ) {
        $i=2;
        foreach($data as $value) {
            /*----------写入内容-------------*/
            $objExcel->getActiveSheet()->setCellValue('a'.$i, $value['rank']);
            $objExcel->getActiveSheet()->setCellValue('b'.$i, $value['nickname']);
            $objExcel->getActiveSheet()->setCellValue('c'.$i, $value['phone']);
            $objExcel->getActiveSheet()->setCellValue('d'.$i, $value['dep']);
            $objExcel->getActiveSheet()->setCellValue('e'.$i, $value['step_n']);
            $objExcel->getActiveSheet()->setCellValue('f'.$i, date('Y-m-d H:i:s',$value['atime']));

            $i++;
        }
    }

    //rank
    public function addExcelUserRank( &$objExcel, $data=[] ) {
        $i=2;
        foreach($data as $value) {
            /*----------写入内容-------------*/
            $objExcel->getActiveSheet()->setCellValue('a'.$i, $value['rank']);
            $objExcel->getActiveSheet()->setCellValue('b'.$i, $value['nickname']);
            $objExcel->getActiveSheet()->setCellValue('c'.$i, $value['dep_name']);
            $objExcel->getActiveSheet()->setCellValue('d'.$i, $value['level_name']);
            $objExcel->getActiveSheet()->setCellValue('e'.$i, $value['champion_n']?$value['champion_n']:0);

            $i++;
        }
    }

    public function addExcelDeptRank( &$objExcel, $data=[] ) {
        $i=2;
        foreach($data as $value) {
            /*----------写入内容-------------*/
            $objExcel->getActiveSheet()->setCellValue('a'.$i, $value['rank']);
            $objExcel->getActiveSheet()->setCellValue('b'.$i, $value['dep_name']);
            $objExcel->getActiveSheet()->setCellValue('c'.$i, $value['num']);
            $objExcel->getActiveSheet()->setCellValue('d'.$i, $value['step_n']);
            $objExcel->getActiveSheet()->setCellValue('e'.$i, $value['champion_n']?$value['champion_n']:0);

            $i++;
        }
    }

    public function addExcelUser( &$objExcel, $data=[] ) {
        $i=2;
        foreach($data as $value) {
            /*----------写入内容-------------*/


            $objExcel->getActiveSheet()->setCellValue('a'.$i, $value['uid']);
            $objExcel->getActiveSheet()->setCellValue('b'.$i, $value['job_num']);
            $objExcel->getActiveSheet()->setCellValue('c'.$i, $value['nickname']);
            $objExcel->getActiveSheet()->setCellValue('d'.$i, $value['phone'] );
            $objExcel->getActiveSheet()->setCellValue('e'.$i, $value['sex'] ==1 ?'男':'女');
            $objExcel->getActiveSheet()->setCellValue('f'.$i, $value['age']);
            $objExcel->getActiveSheet()->setCellValue('g'.$i, $value['height']);
            $objExcel->getActiveSheet()->setCellValue('h'.$i, $value['weight']);
            $objExcel->getActiveSheet()->setCellValue('i'.$i, $value['target_step_n']);
//            $objExcel->getActiveSheet()->setCellValue('j'.$i, $value['dept_id']);
            $objExcel->getActiveSheet()->setCellValue('j'.$i, $value['dept_pid_real_name']);
            $objExcel->getActiveSheet()->setCellValue('k'.$i, $value['dept_real_name']);


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

    public function addExcelUserScore( &$objExcel, $data=[] ) {
        $i=3;
        foreach($data as $value) {
            /*----------写入内容-------------*/

            $objExcel->getActiveSheet()->setCellValue('a'.$i, $value['nickname']);
            $objExcel->getActiveSheet()->setCellValue('b'.$i, $value['phone']);
            $objExcel->getActiveSheet()->setCellValue('c'.$i, $value['dept_name']);
            $j = 68;
            foreach($value['data'] as $v){
                $c = $j+1;
                if(($j-68)%4){
                    $objExcel->getActiveSheet()->getStyle(chr($j).$i)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                    $objExcel->getActiveSheet()->getStyle(chr($c).$i)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);

                    $objExcel->getActiveSheet()->getStyle(chr($j).$i)->getFill()->getStartColor()->setARGB('DCDCDC');
                    $objExcel->getActiveSheet()->getStyle(chr($c).$i)->getFill()->getStartColor()->setARGB('DCDCDC');

                }
                $objExcel->getActiveSheet()->setCellValue(chr($j).$i, $v['step_n']?$v['step_n']:0);
                $objExcel->getActiveSheet()->setCellValue(chr($c).$i, $v['score']?$v['score']:0);
                $j+=2;
            }
        $i++;
        }
    }


    public function addExcelDeptScore( &$objExcel, $data=[] ) {
        $i=2;
        foreach($data as $value) {
            /*----------写入内容-------------*/

            $objExcel->getActiveSheet()->setCellValue('a'.$i, $value['dept_name']);
            $objExcel->getActiveSheet()->setCellValue('b'.$i, $value['user_n']);
            $objExcel->getActiveSheet()->setCellValue('c'.$i, $value['step_n']);
            $objExcel->getActiveSheet()->setCellValue('d'.$i, $value['score']);
        $i++;
        }
    }


    // user score
    public function exportUserScoreList()
    {
        $raw = I('get.');

        $ret = [];
        $raw['excel'] = true;
        $data = $this->modelScore->showUserScoreList($raw);


        if (!$data['data'])
            goto END;

        $title = [
            'A' => "姓名",
            'B' => "手机号",
            'C' => "部门名称"
        ];
        $title_num = array('A', 'B', 'C');

        $act_info = $this->modelScore->findAct('',['id'=>$raw['id']]);

        $j = 68;
        foreach($data['analysis'] as $v){
            $title_num[] = chr(2*$j-68);
            $title_num[] = chr(2*$j-67);
            $title[chr(2*$j-68)] = date("m/d",$v['time']);
            $j++;
        }

        $name = urlencode('活动-'.$act_info['title'].'-用户积分统计');
        $ext = [
            'WIDTH'=>7,
            'special'=>true,
            'A'=>10,
            'B'=>30,
            'C'=>30,
        ];
        $this->excel($data['data'], $title_num, $title, $name, 'addExcelUserScore',$ext);
END:
        $this->retReturn($ret);
    }

    //dept score

    public function exportDeptScoreList()
    {
        $raw = I('get.');

        $ret = [];
        $raw['excel'] = true;
        $data = $this->showDeptScoreList1($raw);


        foreach($data['data'] as $k=>$v){
            if($k==10)
                break;
            $did[] = $v['dept_id'];
        }
        $act_info = $this->modelScore->findAct('',['id'=>$raw['id']]);

        $this->modelUser->selectUserWithDept('a.nickname,a.sex,a.phone,b.name',
            ['dept_pid'=>['in',$did],'uid'=>['in',explode(',',$act_info['aim_uid'])]],
        '',
            'field(`b.dept_pid`,'.implode(',',$did).')');

        var_dump(M()->getLastSql());
        die;
        if (!$data['data'])
            goto END;



        $title = [
            'A' => "部门",
            'B' => "参与人数",
            'C' => "步数",
            'D' => "积分"
        ];
        $title_num = array('A', 'B', 'C', 'D');


        //act_info
        $name = urlencode('活动-'.$act_info['title'].'-部门积分统计');
        $ext = [
            'WIDTH'=>10,
            'A'=>30,
            'B'=>10,
            'C'=>10,
            'D'=>10,
        ];
        $this->excel($data['data'], $title_num, $title, $name, 'addExcelDeptScore',$ext);
END:
        $this->retReturn($ret);
    }

    public function showDeptScoreList($raw){
        $ret = ['total' => 0, 'page_start' => 0, 'page_n' => 0, 'data' => []];

        $limit = '';
        $where = [];

        if(!$raw['id'])
            goto END;

        if ($raw['dept_id'])
            $where['b.dept_id'] = $raw['dept_id'];

        //act_info
        $act_info = $this->modelScore->findAct('',['id'=>$raw['id']]);
        $columns = 'a.name dept_name,b.dept_id,b.user_n,b.score,b.step_n,b.atime';

        $result = $this->modelScore->selectScoreWithDept($columns, $where, $limit);

        if (!$result)
            goto END;
        $ret['data'] = $result;
END:
        return $ret;
    }

    public function showDeptScoreList1($raw){

        $page =  1;
        $limit = '';
        $where = [];

        if(!$raw['id'])
            goto END;
        $where['b.pid'] = $raw['id'];
        $where['b.type'] = SCORE_TYPE_DEPT;

        if ($raw['dept_id'])
            $where['b.dept_id'] = $raw['dept_id'];

        $keys = ['nickname','name'];
        foreach ($keys as $item) {
            if ($raw[$item])
                $where['a.'.$item] = ['like', '%' . $raw[$item] . '%'];
        }
        //act_info
        $act_info = $this->modelScore->findAct('',['id'=>$raw['id']]);
        if(!$act_info)
            goto END;
        $columns = 'any_value(a.name) dept_name,any_value(b.id) id,any_value(b.dept_id) dept_id,any_value(b.user_n) user_n,format(sum(b.score),2) score,sum(b.step_n) step_n';

        $result = $this->modelScore->selectScoreWithDept($columns, $where, $limit,'sum(b.score) desc','b.dept_id');

        if (!$result)
            goto END;
        $res_count =  $this->modelScore->selectScoreWithDept($columns, $where, '','','b.dept_id');


        //总部门数
        $aim_dept_id = explode(',',$act_info['aim_dept_id']);
        $aim_dept_info = $this->modelUser->selectDept('',['id'=>['in',$aim_dept_id],'pid'=>0]);
        $aim_dept_id = [];
        foreach($aim_dept_info as $v)
            $aim_dept_id[] = $v['id'];

        $count = count($aim_dept_id);
        //无数据部门填充
        if($count != count($res_count)){
            foreach($res_count as $v){
                $res_dept_id[] = $v['dept_id'];
            }

            $empty_dept_id = array_diff($aim_dept_id,$res_dept_id);
            $dept_info = $this->modelUser->selectDept('',['id'=>['in',$empty_dept_id],'pid'=>0]);

            //用户统计
            $empty_user_info = $this->modelUser->getUserInfo(explode(',',$act_info['aim_uid']));
            $dept_user_n = [];
            foreach($empty_user_info as $v){
                if(!$dept_user_n[$v['dept_pid']])
                    $dept_user_n[$v['dept_pid']] = 0;
                $dept_user_n[$v['dept_pid']]++;
            }
            $empty_data = [];
            $key_start = count($res_count)+1;
            foreach ($dept_info as $item) {
                $empty_data[$key_start] = [
                    'dept_id'    => $item['id'],
                    'dept_name'    => $item['name'],
                    'user_n'    => $dept_user_n[$item['id']],
                    'score'    => 0,
                    'step_n'    => 0,
                ];
                $key_start ++;
            }
        }
        if(count($result) != ($count-$page*$raw['page_limit'])%$raw['page_limit']){
            $start = ($page-1)*$raw['page_limit']+count($result)+1;
            $end = $raw['page_limit']*$page+1;
            for($i = $start;$i<=$end;$i++){
                if(!isset($empty_data[$i]))
                    break;
                $result[] = $empty_data[$i];
            }
        }
        $ret['total'] = $count;
        $ret['page_start'] = $page;
        $ret['page_n'] = count($result);
        $ret['data'] = $result;
END:
        return $ret;
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
        foreach ($title_num as $val) {

            if(isset($ext['special'] )) {
                if(ord($val)>=68){
                    if(!(ord($val) % 2) &&(ord($val))> 67){
                        $objExcel->getActiveSheet()->setCellValue( $val.'1', $title[ $val ] );

                        $objExcel->getActiveSheet()->mergeCells($val."1:".(chr(ord($val)+1))."1");// 指定第1行 相邻的列
                        $objExcel->getActiveSheet()->setCellValue( $val.'2', '步数' );

                    }else{
                        $objExcel->getActiveSheet()->setCellValue( $val.'2', '分数' );
                    }
                }else{
                    $objExcel->getActiveSheet()->mergeCells($val."1:".$val."2");// 指定第1行 相邻的列
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
        $this->$fun( $objExcel, $data, $title_num );

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
