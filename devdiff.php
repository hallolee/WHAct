<?php
/*
【执行】
此版本程序为CLI命令行执行：

php devdiff.php <参数>

例如  php devdiff.php do

【结果】
然后运行，就会根据你传的参数，做相应的行为。

命令基本格式为：
php devdiff.php <path> <1|2> <show|merge>

* 参数:
    第一个参数 路径（绝对；路径）：
    /data/pp/Application/Common/Conf/dev.php（/data/pp/Application/Common）

    第二个参数 路径类型：
    1 为文件，2 为文件夹

    第三个参数 操作类型：
    show 为显示差异，merge 为显示差异与合并差异
【备注】
*/

    /*
    * 屏蔽系统干扰
    */
    // 屏蔽系统变量干扰
    $_SERVER['HTTP_HOST']   = 'HTTP_HOST';
    $_SERVER['SERVER_NAME'] = 'SERVER_NAME';
    const WX_DISPLAY = 'WX_DISPLAY';
    const COL_OPEN   = 'COL_OPEN';
    const COL_CLOSE  = 'COL_CLOSE';

    // 屏蔽TP 的大 C 方法的干扰
    function C($key){
        return $key;
    }

    // 提示语参数
    $param_err = "php devdiff.php <path> <1|2> <show|merge>\r\n";

    $not_diff = "\r\n 未检查出差异 \r\n\r\n";
    $file_not_exit = "\r\n文件匹配失败,检查路径或参数是否正确\r\n";

    $diff_head = "--------------------\r\ndev.php缺少如：\r\n\r\n";
    $dist_head = "--------------------\r\ndev.dist缺少如：\r\n\r\n";

    $merge_exit = "暂不支持\r\n";
    $merge_head = "dev.php合并后增加如：\r\n\r\n";

    $foot = " \r\n的配置 \r\n--------------------\r\n";

    /*
    * 程序正式开始位置
    */
    $path = ( isset($argv[1]) && $argv[1] )? $argv[1]:'Application/Common/Conf/dev.php';
    $type = isset( $argv[2] )?$argv[2]:'';
    $do = isset( $argv[3] )?$argv[3]:'';

    if( empty( $path ) || empty( $type ) || empty( $do ) || !in_array($do, ['show','merge']) ){
        echo $param_err;
        exit();
    }

    // 不同模式对应验证方式
    switch ($type) {
        case '1':
            // 对比 dev.php 与当前目录下的 dev.dist 
            if( $do == 'show' ){
                $res = chkFile( $path, $diff, $dist );
            }else{
                echo $merge_exit;
                exit();
                // $res = mergeFile( $path, $type, $diff );
            }

            break;

        case '2':
            // 对比当前目录中某个目录的dev.php dev.dist 
            if( $do == 'show' ){
                $res = chkDir( $path, $diff, $dist );
            }else{
                echo $merge_exit;
                exit();
                // $res = mergeDirFile( $path, $type, $diff );
            }

            break;


        default:
            echo $param_err;
            break;
    }

    // 将验证结果输出
    if( $res ){
        if( !$diff && !$dist ){
            echo $not_diff;
            exit();
        }

        // 输出 dev.php 缺失内容
        if( $diff ){

            echo $diff_head;
            printContent( $diff );
            echo $foot;
        }

        // 输出 dev.dist 缺失内容
        if( $dist ){

            echo $dist_head;
            printContent( $dist );
            echo $foot;
        }
    }else{
        echo $file_not_exit;
    }

    /*
    * 输出差异方法，递归保证多维数组仍能输出详细差异
    * $v 必须为数组
    * $pre 输出前缀
    */
    function printContent( $v, $pre=' key 值为' ){
        foreach ($v as $key => $val) {
            if( is_array( $val ) ){
                $pre = $pre.$key.' 下的 ';
                printContent( $val, $pre );
                //重置前缀
                $pre=' key 值为';

            }else if( is_numeric( $key ) ){
                echo $pre.$val." \r\n";
            }else{
                echo $pre.$key." \r\n";
            }
        }
    }

    /*
    * 检测文件夹下 是否有目录同时存在 dev.php dev.dist 文件，存在则检测差异
    * @ $dir 为目录
    * @ return bool值
    * @ $dist 为 dev.php 相对于 dev.dist 多余的内容
    * @ $diff 为 dev.php 相对于 dev.dist 缺失的内容
    */
    function chkDir( $dir='', &$diff=[], &$dist=[] ){

        $dir = $dir?$dir:'null';
        $file = getAllFile( $dir );


        if( empty( $file ) )
            return false;

        $res = chkFile( $file, $diff, $dist );

        return $res;

    }

    /*
    * 检测是否存在dev.php 同时存在 dev.dist 文件，存在则检测差异
    * @ $file 为文件
    * @ return bool值
    * @ $dist 为 dev.php 相对于 dev.dist 多余的内容
    * @ $diff 为 dev.php 相对于 dev.dist 缺失的内容
    */
    function chkFile( $file1='', &$diff=[], &$dist=[] ){
        $file1 = $file1?$file1:'null';
        $file2 = strstr($file1, '.', true).'.dist';

        if( !is_file($file1) || !is_file($file2) )
            return false;

        // dev.php
        $php_d = getContent($file1);

        //dev.dist
        $dist_d = getContent($file2);

        $res = diffKey($dist_d, $php_d, $diff);
        $dist = $php_d;

        return true;
    }


    /*
    * 单独获取文件内容、防止数据互相干扰
    * include_once、include 的内容具有全局作用域
    * 函数包含使其局部化
    */ 
    function getContent( $path ){

        $content = include_once($path);
        return $content;

    };


    /*
    * 检测两个文件匹配的键值是否一致，并返回差异
    * @ $dist_d 存储的是 dev.dist 的内容
    * @ $php_d 初始为 dev.php 的内容，结束后为 dev.php 相对于 dev.dist 多余的内容
    * @ $diff 为 dev.php 相对于 dev.dist 缺失的内容
    */
    function diffKey( $dist_d=[], &$php_d=[], &$diff=[]){

        foreach ($dist_d as $key => $val) {
            if( is_array( $val ) ){
                if( !isset( $php_d[ $key ] ) ){
                    $diff[] = $key;
                }else if( !$val && !$php_d[ $key ] ){
                    unset( $diff[$key] );
                    unset( $php_d[$key] );
                }else{
                    diffKey( $val, $php_d[$key], $diff[$key] );
                    if( empty( $diff[$key] ) ){
                        unset( $diff[$key] );
                        unset( $php_d[$key] );
                    }else if( empty( $php_d[$key] ) ){
                        unset( $php_d[ $key ] );
                    }
                }
            }else{
                if( !isset( $php_d[ $key ] ) ){
                    $diff[] = $key;
                }else{
                    unset($php_d[$key]);
                }
            }
        }

        return true;
    }


    /*
    * 检测当前目录下是否在某个目录同时存在 dev.php dev.dist
    * @ return 存在则返回所在目录，否则返回空
    */
    function getAllFile( $dir ){

        $all_files = scandir($dir);
        $file_arr = [];
        foreach($all_files as $filename){

            if(in_array($filename,array(".", ".."))){
                continue;
            }

            $full_name=$dir.'/'.$filename;
            if(is_dir($full_name)){
                $dir_arr[] = $full_name;
            }else{
                $file_arr[] = $filename;
            }
        }

        if( in_array('dev.php', $file_arr) && in_array('dev.dist', $file_arr) )
            return $dir.'/'.'dev.php';

        if( isset( $dir_arr ) )
            foreach ($dir_arr as $val) {
                $file = getAllFile( $val );
                if( $file )
                    return $file;
            }

        return '';
    }

    /*
    * 合并差异
    */
    function mergeDirFile( $dir='', &$diff=[] ){

        $dir = $dir?$dir:'null';
        $file = getAllFile( $dir );

        if( empty( $file ) )
            return false;

        $res = mergeFile( $file, $type, $diff );

        return $res;

    }

    /*
    * 合并差异
    */
    function mergeFile( $file1='', &$diff=[] ){
        $file1 = $file1?$file1:'null';
        $file2 = strstr($file1, '.', true).'.dist';

        if( !is_file($file1) || !is_file($file2) )
            return false;

        $data1 = include($file1);
        $data2 = include($file2);

        $res = diffFile( $data1, $data2, $add, $diff );

        return [ $add, $diff ];

        if( !empty( $diff ) )
            $res = file_put_contents($file2, $data2);

        return true;
    }

    /*
    * 检测差异，并返回差异信息
    */
    function diffFile( $standard=[], $target=[], &$add=[], &$diff=[] ) {

        foreach ($standard as $key => $val) {
            if( is_array( $val ) ){
                if( !isset( $target[ $key ] ) ){
                    $diff[] = $key;
                    $add[$key] = $val;
                }else{
                    mergeFile( $val, $target[$key], $add, $diff[$key] );
                    if( empty( $diff[$key] ) )
                        unset( $diff[$key] );
                }
            }else{
                if( !isset( $target[ $key ] ) ){
                    $diff[] = $key;
                    $add[$key] = $val;
                }
            }
        }

        return true;
    }


?>
