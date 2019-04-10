<?php
/*
【执行】
此版本程序为CLI命令行执行：

php mysqldiff.php <参数>

例如  php mysqldiff.php do

【结果】
然后运行，就会根据你传的参数，做相应的行为。

命令基本格式为：
php mysqldiff.php 

* 参数:
    第一个参数 操作类型：
    defect 为显示缺失字段，surplus 为显示多余字段，all 同时显示缺失、多余字段(默认全部)


【备注】
*/

    // 提示语参数
    $param_err = "php mysqldiff.php <defect|surplus|all>\r\n";

    $not_diff = "\r\n 未检查出差异 \r\n\r\n";

    $d_head = "--------------------\r\n当前basic_info 缺少 字段：\r\n\r\n";
    $s_head = "--------------------\r\n当前basic_info 多余 字段：\r\n\r\n";

    $config = [
        'itrend' => [
            'hostname'  => 'localhost',
            'username'  => 'lbys',
            'password'  => '123456',
            'database'  => 'test_travelpp'
        ],
        'devpom' => [
            'hostname'  => 'dev.pompom-social.com',
            'username'  => 'wk',
            'password'  => '924137',
            'database'  => 'travelpp'
        ],
        'pom' => [
            'hostname'  => 'pompom-social.com',
            'username'  => 'wk',
            'password'  => '924137',
            'database'  => 'travelpp'
        ],
    ];

    $base_config = $config[ 'devpom' ];
    $target_config = $config[ 'pom' ];

    /*
    * 程序正式开始位置
    */
    $do = isset( $argv[1] )?$argv[1]:'all';

    if( !in_array( $do, ['defect','surplus', 'all'] ) ){
        echo $param_err;
        exit();
    }

    $dev_col = getSqlCol( $target_config );
    $deploy_col = getSqlCol( $base_config );

    $surplus_diff = array_diff( $deploy_col, $dev_col );
    $defect_diff = array_diff( $dev_col, $deploy_col );

    switch ($do) {
        case 'defect':

            echo $d_head;
            if( $defect_diff ){
                printContent( $defect_diff );
            }else{
                echo $not_diff;
            }

            break;

        case 'surplus':

            echo $s_head;
            if( $surplus_diff ){
                printContent( $surplus_diff );
            }else{
                echo $not_diff;
            }

            break;

        case 'all':

            echo $d_head;
            if( $defect_diff ){
                printContent( $defect_diff );
            }else{
                echo $not_diff;
            }

            echo $s_head;
            if( $surplus_diff ){
                printContent( $surplus_diff );
            }else{
                echo $not_diff;
            }

            break;

        default:
            exit();
    }


    function getSqlCol( $config=[] ){

        $sql = 'SELECT * FROM `basic_info`';

        $connect = new connect( $config );
        $res = $connect->select( $sql );

        $col = [];
        foreach ($res as $key => $value) {
            $col[] = $value['field'].','.$value['module'];
        }

        return $col;
    }


    /*
    * @param $v 必须为数组
    */
    function printContent( $v ){
        foreach ($v as $key => $val) {
            $cache = explode( ',', $val );

            echo "field : '$cache[0]' in '$cache[1]' \r\n";
        }
        echo " \r\n";
    }




class connect{
    protected $mysqli;
    protected $config;

    function __construct( $config=[] ){
        $this->config = $config;
        $this->connect();
    }

    /**
    * 连接数据库
    * @param array $config 数据库配置数组
    */
    public function connect(){
        if( !empty( $this->config ) ){
            $this->mysqli = new mysqli($this->config['hostname'],$this->config['username'],$this->config['password'],$this->config['database']);
            if($this->mysqli->connect_error){
                echo "数据库连接错误";
                exit();
            }
            $this->query("set names utf8");
        }else{
            return false;
        }
    }

    /**
    * 释放查询结果
    * @access public
    */
    public function free() {
        $this->mysqli->close();
        $this->mysqli = null;
    }

    /**
    *数据表操作
    * @access public
    */
    public function query($sql){
        $result = $this->mysqli->query($sql);
        if($result === false){
           echo $this->mysqli->error;
           die;
        }
        return $result;
    }

    /**
    *数据表批量sql语句操作
    * @access public
    */
    public function multi_query($sql){
        $result = $this->mysqli->multi_query($sql);
        if($result === false){
           echo $this->mysqli->error;
           die;
        }
        return $result;
    }

    /**
    * 释放 multi_query 查询结果
    * @access public
    */
    public function free_multi()
    {
        while( $this->mysqli->more_results() && $this->mysqli->next_result() )
        {
            /*
            $rs = $this->mysqli->store_result();
            var_dump( $rs );
             */
        }
    }

    /**
    *返回上一步 INSERT 操作产生的 ID
    * @access public
    */
    public function insert_id() {
        return $this->mysqli->insert_id ;
    }

    /**
    *数据表查询
    * @access public
    */
    public function select($sql) {
        $rs = $this->query($sql);
        $list = array();
        while($row = $rs->fetch_assoc()) {
            $list[] = $row;
        }
        $this->free();
        return $list;
    }

}

?>
