<?php

namespace Common;

/*
* 获取json格式数据
* @ $err 错误码提示
*/
function getRawByJson( &$err ){
    $err = 0;
    //是否为上传
    if( $_FILES ){ $err = 1; return ''; }

    //参数是否为空
    $input  = file_get_contents('php://input');
    if( !$input ) return '';

    //解析是否成功
    $raw = json_decode($input,true,'512',JSON_BIGINT_AS_STRING);
    if( $raw === null || $raw === false ) $err = 2;

    return $raw;
}


function rawValidator( $action='', $raw=[] ){
    $res = [ 'status' => 0, 'errstr' => '' ];
    if( $action == '' ){
        $res['status'] = 1;
        return $res;
    }

    // 根据模块取相应的 参数格式
    $module = explode( '/', $action)[0];
    $schema_path = C('RAW_JSON_SCHEMA').$module.'.json';
    if( !is_file( $schema_path ) ) {
        $res['status'] = 1;
        return $res;
    }

    $schema = json_decode( file_get_contents( $schema_path ), true );
    if( $action && $schema[ $action ] && isset( $schema[ $action ]['params'] ) ){
        vendor( 'JsonSchemaValidation.AutoLoad' );
        $validation = new \JsonSchema\Validator();

        $json_schema = json_encode( $schema[ $action ]['params'] );
        // json_decode过程中为了方便取值，将 object 转为了 array
        // 由此会导致校检失败，因此重新编码，后解码，以确保数据不受影响
        $validation->check( json_decode( json_encode( $raw ) ), json_decode( $json_schema ) );
        if ( !$validation->isValid() ) {
            foreach ( $validation->getErrors() as $value ) {
                if( $value['constraint']['name'] == 'additionalProp' ){
                    $res['status'] = 100;
                }else if( $value['constraint']['name'] == 'required' ){
                    $res['status'] = 101;
                }else if( $value['constraint']['name'] == 'type' ){
                    $type = explode( ' ', $value['constraint']['params']['expected'])[1];
                    if( $type == 'string' ){
                        $res['status'] = 102;
                    }else if( $type == 'number' ){
                        $res['status'] = 103;
                    }else if( $type == 'integer' ){
                        $res['status'] = 104;
                    }else if( $type == 'object' ){
                        $res['status'] = 105;
                    }else if( $type == 'array' ){
                        $res['status'] = 106;
                    }
                }else if( $value['constraint']['name'] == 'minimum' ){
                    $res['status'] = 107;
                }else if( $value['constraint']['name'] == 'maximum' ){
                    $res['status'] = 108;
                }else if( $value['constraint']['name'] == 'multipleOf' ){
                    $res['status'] = 109;
                    $res['errstr'] = $value['message'];
                }else{
                    $res['status'] = 110;
                }

                break;
            }
        }
    }

    return $res;
}



/*
生成token文件，返回tokenid
*/
function genDaToken( $d=[], $module=MODULE_NAME )
{
    $prefixed = C( 'TOKEN_PREFIXED' );
    $cache_type = C( 'TOKEN_CACHE_TYPE' );

    $app_number = C('APP_NUMBER');
    $app = array_search($module,$app_number);
    if( !$app ) return false;

    $now = getMicroTime();
    $sign = md5( $now.rand(1000,9999) );
    $name = $app.$sign;
    $p = [
        'file'      => [
            'btime'     => time(),
            'etime'     => time()+C('DATOKEN_EXPIRE'),
        ],
        'data'      => $d,
    ];

    switch ($cache_type) {
        case TOKEN_TYPE_FILE:

            $dir = C( 'TOKEN_PATH' ).$module.'/';
            $file = $dir.$prefixed.$name;

            if( !is_dir( $dir ) ) @mkdir( $dir, 0777, ture );
            if( !is_file($file) ) touch($file);
            if( $p ) file_put_contents($file,json_encode( $p ));
            break;

        case TOKEN_TYPE_REDIS:
            $key = '/'.C( 'APPNAME' ).'/'.strtolower($module).C( 'TOKEN_KEY' );
            $redisClient = createRedis();

            $redisClient->hset( $key, $name, json_encode( $p ) );
            break;

        default:
            break;
    }

    $token = $name;
    return $token;
}


/*
清除token文件
*/
function delDaToken( $token, $module=MODULE_NAME )
{
    $prefixed = C( 'TOKEN_PREFIXED' );
    $dir = C( 'TOKEN_PATH' ).$module.'/';
    $file = $dir.$prefixed.$token;

    if( !is_file( $file ) )
        return true;

    $del = unlink( $file );
    if( !$del )
        return false;

    return true;
}


/*
* @ $verify 检查token所在模块与当前模块 TOKEN_FULLCHECK 全查 ，NO_CHECK 不查
取出token文件
*/
function validDaToken( $token, &$out='', $verify=TOKEN_FULLCHECK, $module=MODULE_NAME )
{
    $prefixed = C( 'TOKEN_PREFIXED' );
    $cache_type = C( 'TOKEN_CACHE_TYPE' );
    $key = substr($token, 0,3);

    $app_number = C('APP_NUMBER');
    $token_module = isset($app_number[ $key ])?$app_number[ $key ]:'';

    if( $verify == TOKEN_NOCHECK )
    {
        $module = $token_module;
    }else if( !$token_module || $token_module != $module ){
        return false;
    }

    switch ($cache_type) {
        case TOKEN_TYPE_FILE:

            $dir = C( 'TOKEN_PATH' ).$module.'/';
            $file = $dir.$prefixed.$token;

            if( !is_file( $file ) )
                return false;

            $token_body = json_decode( file_get_contents( $file ), true );

            if( $token_body['file']['etime'] < time() ){
                unlink($file);
                return false;
            }
            break;

        case TOKEN_TYPE_REDIS:

            $key = '/'.C( 'APPNAME' ).'/'.strtolower($module).C( 'TOKEN_KEY' );
            $redisClient = createRedis();

            if ( 0 == $redisClient->hexists( $key, $token )) {
              return false;
            }

            $token_body = json_decode( $redisClient->hget( $key, $token ), true );

            if( $token_body['file']['etime'] < time() ){
                $redisClient->hdel( $key, $token );
                return false;
            }
            break;

        default:
            break;
    }

    $out = $token_body['data'];
    return true;
}


/*
* @ $d 存储数据
* @ $token 文件标识
* @ $write 写入方式 TOKEN_APPEND 追加，TOKEN_COVER 覆盖
* @ $verify 检查token所在模块与当前模块 TOKEN_FULLCHECK 全查 ，NO_CHECK 不查
*取出token文件,写入文件
*/
function validDaTokenWrite($d = [], $token, $write=TOKEN_COVER, $verify=TOKEN_FULLCHECK, $module=MODULE_NAME )
{
    $prefixed = C( 'TOKEN_PREFIXED' );
    $cache_type = C( 'TOKEN_CACHE_TYPE' );
    $key = substr($token, 0,3);

    $app_number = C('APP_NUMBER');
    $token_module = isset($app_number[ $key ])?$app_number[ $key ]:'';

    if( $verify == TOKEN_NOCHECK )
    {
        $module = $token_module;
    }else if( !$token_module || $token_module != $module ){
        return false;
    }

    switch ($cache_type) {
        case TOKEN_TYPE_FILE:
            $dir = C( 'TOKEN_PATH' ).$module.'/';
            $file = $dir.$prefixed.$token;

            if( !is_file( $file ) )
                return false;

            $token_body = json_decode( file_get_contents( $file ), true );

            if( $token_body['file']['etime'] < time() ){
                unlink($file);
                return false;
            }

            if( $write == TOKEN_COVER ){
                $token_body['data'] = $d;
            }else if( $write == TOKEN_APPEND ){
                $token_body['data'] = array_merge($token_body['data'],$d);
            }

            if( empty( $token_body['data'] ) ){
                unlink($file);
            }else{
                $status = file_put_contents( $file, json_encode( $token_body ) );
            }
            break;

        case TOKEN_TYPE_REDIS:

            $key = '/'.C( 'APPNAME' ).'/'.strtolower($module).C( 'TOKEN_KEY' );
            $redisClient = createRedis();

            if ( 0 == $redisClient->hexists( $key, $token )) {
              return false;
            }

            $token_body = json_decode( $redisClient->hget( $key, $token ), true );

            if( $token_body['file']['etime'] < time() ){
                $redisClient->hdel( $key, $token );
                return false;
            }

            if( $write == TOKEN_COVER ){
                $token_body['data'] = $d;
            }else if( $write == TOKEN_APPEND ){
                $token_body['data'] = array_merge($token_body['data'],$d);
            }

            if( empty( $token_body['data'] ) ){
                $redisClient->hdel( $key, $token );
            }else{
                $status = $redisClient->hset( $key, $token, json_encode( $token_body ));
            }
            break;

        default:
            break;
    }

    if ( !$status ) {
        return false;
    }
    return true;
}


/*
* 读取token文件
* @ $token 文件标识
* @ $module 当前所在模块
* @ $verify 检查token所在模块与当前模块 TOKEN_FULLCHECK 全查 ，NO_CHECK 不查
*/
function readDaToken( $token, $module, $verify )
{

    $prefixed = C( 'TOKEN_PREFIXED' );
    $cache_type = C( 'TOKEN_CACHE_TYPE' );
    $key = substr($token, 0,3);

    $app_number = C('APP_NUMBER');
    $token_module = isset($app_number[ $key ])?$app_number[ $key ]:'';

    if( $verify == TOKEN_NOCHECK )
    {
        $module = $token_module;
    }else if( !$token_module || $token_module != $module ){
        return false;
    }

    switch ($cache_type) {
        case TOKEN_TYPE_FILE:

            $prefixed = C( 'TOKEN_PREFIXED' );
            $dir = C( 'TOKEN_PATH' ).$module.'/';
            $file = $dir.$prefixed.$token;

            if( !is_file( $file ) )
                return false;

            $token_body = json_decode( file_get_contents( $file ), true );

            if( $token_body['file']['etime'] < time() ){
                unlink($file);
                return false;
            }
            break;

        case TOKEN_TYPE_REDIS:

            $key = '/'.C( 'APPNAME' ).'/'.strtolower($module).C( 'TOKEN_KEY' );
            $redisClient = createRedis();
            if( 0 == $redisClient->hexists( $key, $token) )
                return false;

            $token_body = json_decode( $redisClient->hget( $key, $token ), true );

            if( $token_body['file']['etime'] < time() ){
                $redisClient->hdel( $key, $token );
                return false;
            }
            break;

        default:
            break;
    }

    return $token_body;
}


/*
* 取出token文件
* @ $token 文件标识
* @ $verify 检查token所在模块与当前模块 TOKEN_FULLCHECK 全查 ，NO_CHECK 不查
* @ $module 当前所在模块
*/
function checkDaToken( $token, &$out='', $verify=TOKEN_FULLCHECK, $module=MODULE_NAME )
{

    $token_body = \Common\readDaToken( $token, $module, $verify );

    if( $token_body === false )
        return false;

    $out['uid'] = $token_body['data']['uid'];
    $out['etime'] = $token_body['file']['etime'];
    return true;
}


/*
* 更新token文件
* @ $token 文件标识
* @ $verify 检查token所在模块与当前模块 TOKEN_FULLCHECK 全查 ，NO_CHECK 不查
* @ $module 当前所在模块
*/
function replaceDaToken( $token, &$out='', $verify=TOKEN_FULLCHECK, $module=MODULE_NAME )
{
    $prefixed = C( 'TOKEN_PREFIXED' );
    $cache_type = C( 'TOKEN_CACHE_TYPE' );
    $dir = C( 'TOKEN_PATH' ).$module.'/';
    $token_body = \Common\readDaToken( $token, $module, $verify );

    if( $token_body === false )
        return false;

    $token_new = \Common\genDaToken( $token_body['data'] );

    if( !$token_new )
         return false;

    switch ($cache_type) {
        case TOKEN_TYPE_FILE:

            unlink($dir.$prefixed.$token);

            $file = $dir.$prefixed.$token_new;

            if( !is_file( $file ) )
                return false;

            $token_body = json_decode( file_get_contents( $file ), true );
            break;

        case TOKEN_TYPE_REDIS:

            $key = '/'.C( 'APPNAME' ).'/'.strtolower($module).C( 'TOKEN_KEY' );
            $redisClient = createRedis();

            if( 0 == $redisClient->hexists( $key, $token) )
                return false;

            $token_body = json_decode( $redisClient->hget( $key, $token ), true );
            break;

        default:
            break;
    }

    $out['etime'] = $token_body['file']['etime'];
    return $token_new;
}


/**
* 创建 redis 连接
*/
function createRedis( $database='' ){

    if( $database==='' )
        $database = C( 'TOKEN_DATABASE_INDEX' );

    $options = array (
        'host'          => C('REDIS_HOST') ? : '127.0.0.1',
        'port'          => C('REDIS_PORT') ? : 6379,
        'timeout'       => C('DATA_CACHE_TIMEOUT') ? : false,
        'persistent'    => false,
    );

    $options['expire'] =  isset($options['expire'])?  $options['expire']  :   C('DATA_CACHE_TIME');
    $options['prefix'] =  isset($options['prefix'])?  $options['prefix']  :   C('DATA_CACHE_PREFIX');
    $options['length'] =  isset($options['length'])?  $options['length']  :   0;
    $func = $options['persistent'] ? 'pconnect' : 'connect';
    $handler  = new \Redis();
    $options['timeout'] === false ?
        $handler->$func($options['host'], $options['port']) :
        $handler->$func($options['host'], $options['port'], $options['timeout']);

    $handler->select( $database );
    return $handler;
}



/*
* 校检手机验证码
*/
function checkPhoneCode( $phone, $phonecode, $type=SMS_COMMON )
{
    S( ['type'=>'memcached'] );
    $code = S( $phone.$type );
    if( C('IS_TEST') && $phonecode == C('TEST_DATA.PHONE_TEST_CODE') ){
        // 测试环境使用的测试机制
        $code = 'true';
    }
    else if( !$code ){
        //验证码已过期或已失效
        return 18;
    }
    else if( $code != $phonecode ){
        //验证码错误
        return 17;
    }
    S( $phone.$type , null );

    return 0;
}



/*
* 获取毫秒级时间戳
*/
function getMicroTime()
{
    $micro = microtime( true );
    return floor( $micro * 1000 );
}

/*
* 补全返回完整链接
* @ $url 需要判断的链接
*/
function getCompleteUrl( $url='', $pre='IMG_PREURL' ){

    // 空判断
    if( empty( $url ) )
        return '';

    // 判断 http 是否存在及所在位置是否为头部
    if( stripos( $url, 'http' ) === 0 )
        return $url;

    return C($pre).$url;
}


/*
@公共上传
*/
function commonUpload( $module='', $path='', $conf='' ){                              //公共上传方法
    $module = $module == "" ? 'file' : $module; //未知模块将存入file文件夹

    if (!is_dir($path)) @mkdir( $path, 0775, true );
    import("ORG.Net.UploadFile");
    $upload = new \Think\Upload();
    $upload->rootPath = $path;
    $upload->maxSize = 9145728;
    $upload->autoSub  = false;           //是否创建时间子目录
    $upload->allowExts = $conf['types'];

    if(!empty($conf['savename'])){
        $upload->saveName = $conf['savename'];       // 保存自定义名称
    }
    else if(!empty($conf['pre'])){
        $upload->saveName = array('uniqid',$conf['pre']);       //图片名前缀
    }
    else {
        $upload->saveName = '';       // 保存默认名称
    }
    $upload->savePath = '';
    $upload->uploadReplace = true;

    $res = $upload->upload();

    if (!$res) {

        $info = array(
            "status" => 16,
            "msg" => $upload->getError()
        );
        return $info;
    } else {

        foreach ($res as $value) {
            $value['savepath'] = $path.$value['savepath'];
            $cache[] = $value;
        }

        $info['file'] = $cache;
        $info['status'] = 0;

        return $info;
    }
}

/*
@生成缩略图
*/
function imgThumb( $file_path='', $file_name='', $width='150', $height='150' ){ //生成缩略图的公共方法

    $image = new \Think\Image();
    $image->open($file_path);

    $name = str_replace( strstr($file_name, '.') ,"" ,$file_name );
    $path = str_replace($file_name,"",$file_path);

    $type = substr(strrchr($file_name, '.'), 1);
    $new_name = $name."_thumb.".$type;
    list($width_s, $height_s) = getimagesize($file_path);

    $height_t = floor($height_s*$width/$width_s);
    // 生成150*150的缩略图并保存为thumb.***
    if($type == 'png' || $type == 'gif'){        //png 保持背景透明

        //绘制透明底图
        $img_null = imagecreatetruecolor($width, $height_t);
        $color=imagecolorallocate($img_null,255,255,255);
        imagefill($img_null,0,0,$color);

        //背景填充
        $img_o = imagecreatefromstring(file_get_contents($file_path));
        $c = imagecolorat($img_o, 1, 1);
        imagecolortransparent($img_null, $c);
        imagecopyresampled($img_null, $img_o, 0, 0, 0, 0, $width, $height_t, imagesx($img_o), imagesy($img_o));
        $res = imagepng($img_null,$path.$new_name);
        imagedestroy($img_null);
    }else{
        $res = $image->thumb($width,$height_t)->save("$path/".$new_name);
    }
    if (!$res) {
        $info = array(
            "status" => 16,
            "msg" => $image->getError()
        );
        return $info;
    } else {

        $info['path'] = "$path/".$new_name;
        $info[ 'savename' ] = $new_name;
        $info['status'] = 0;

        return $info;
    }
}


/**
 * 去除小数点
 * @param $raw 浮点数
 * @param $decimals 保留位数
 * @param $sys_decimals 系统保留位数
 * @return $ret
 */
function getNumFromUser2Sys( $raw, $decimals=0, $sys_decimals='' )
{

    // 系统保留位数
    if( !$sys_decimals )
        $sys_decimals = C('DECIMALS');

    // 用户定义保留位数大于系统保留位数
    if( $decimals > $sys_decimals )
        $decimals = $sys_decimals;

    // 根据用户需求截取相应位数
    $tmp = sprintf( "%.{$decimals}f", $raw );

    // 系统保留位数
    $base = 1;
    for ($i=0; $i < $sys_decimals; $i++) {
        $base*=10;
    }
    $ret = $tmp*$base;
    return $ret;
}


/**
 * 增加小数点
 * @param $raw      原始数据，目前要求是整数
 * @param $decimals 保留位数
 * @param $sys_decimals 系统保留位数
 * @return $ret
 */
function getNumFromSys2User( $raw, $decimals=0, $sys_decimals='' )
{
    // 系统保留位数
    if( !$sys_decimals )
        $sys_decimals = C('DECIMALS');

    // 用户定义保留位数大于系统保留位数
    if( $decimals > $sys_decimals )
        $decimals = $sys_decimals;

    // 根据系统保留位数还原
    $base = 1;
    for ($i=0; $i < $sys_decimals; $i++) {
        $base*=10;
    }
    $tmp = $raw/$base;

    // 截取获取相应位数结果
    $ret = sprintf( "%.{$decimals}f", $tmp );
    return $ret;
}


/**
 * 生成对应状态提示
 * @param $errcode,$ret
 * @return $ret
 */
function genStatusStr( $errcode, &$ret )
{
    $errstrArr = C('ERRCODE');
    $errstr = isset( $errstrArr[ $errcode ] )?$errstrArr[ $errcode ]:'No desc';

    $ret['status'] = $errcode;
    $ret['errstr'] = $errstr;

    return true;
}

/*
生成存入数据库的password
*/
function getRealPass($password) {
    $pass = md5(C("PSD_SALT").$password);
    return $pass;
}

/**
* 随机字符
* @param number $length 长度
* @param string $type 类型
* @param number $convert 转换大小写
* @return string
*/
function random($length=6, $type='string', $convert=0) {
    $config = array(
        'number'=>'1234567890',
        'letter'=>'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'string'=>'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789',
        'all'=>'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
    );

    if(!isset($config[$type])) $type = 'string';
    $string = $config[$type];
    $code = '';
    $strlen = strlen($string) -1;
    for($i = 0; $i < $length; $i++){
        $code .= $string{mt_rand(0, $strlen)};
    }
    $code = $convert == 0 ? $code : ( $convert ==1 ? strtolower($code) : strtoupper($code) );
    return $code;
}




/* Log Begin */
function dMsg( $func, $msg )
{
    $info = "===== In $func(): $msg =======";
    trace( $info, '', C('DEV_LOG_LEVEL') );
}

function dExp( $func, $msg )
{
    $info = "===== In $func(): =======\n";
    $info .= var_export( $msg, true );

    trace( $info, '', C('DEV_LOG_LEVEL') );
}
/* Log End */


/*
* post method
*/
function post( $url, $postdata ) {

    $httph =curl_init($url);
    curl_setopt($httph, CURLOPT_POST, 1);//设置为POST方式
    curl_setopt($httph, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($httph, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($httph, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($httph, CURLOPT_TIMEOUT, 1);   //超时退出，新增1
    curl_setopt($httph, CURLOPT_HEADER,0);
    $result=curl_exec($httph);
    //\Think\Log::write( $result );
    if (curl_errno($httph)) {      //错误提示，新增2
       $result =  'Error-'.curl_error($httph);
    }
    curl_close($httph);

    return $result;
}


/*
* get method
*/
function urlGet($url, $param=array()){
    if(!is_array($param)){
        throw new Exception("参数必须为array");
    }
    $httph =curl_init($url);
    curl_setopt($httph, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($httph, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($httph,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($httph, CURLOPT_HEADER,0);
    $rst=curl_exec($httph);
    curl_close($httph);


    return $rst;
}


/**
 * 发送短信
 */
function sendSms( $d )
{
    // if( !C('push') ) return;

    $url  = C( 'SMS_API' );
    if( !isset($d['major']) ) $d[ 'major' ] = 1;
    $data = json_encode( $d );

    //dMsg( __FUNCTION__, $url );
    $result = post( $url, $data );
    //$res = json_decode($result,true);
    $res = $result;

    return $res;
}


/*
* 强制装换数字类型
*/
function enforceInt( $d=[] ){
    // 强制装换数字类型
    foreach ($d as &$val) {
        if( is_array( $val ) ){
            $val = enforceInt( $val );
        }else if( is_numeric( $val ) ){
            $val = (int)$val;
        }
    }
    unset( $val );

    return $d;
}


/*
* Get wechat openid
*/
function getOpenid( $code='', $platform=1 ){
    $ret = [];
    if( !$code ) return $ret;

    $m_wechat_conf = getWechatTuples( $platform );

    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$m_wechat_conf['app_id']."&secret=".$m_wechat_conf['app_secret']."&code=".$code."&grant_type=authorization_code";
    $result_str = urlGet($url);
    $re_array = json_decode($result_str,true);

    if( !isset($re_array['openid']) )
    {
        dExp( __FUNCTION__, $re_array );
        return $ret;
    }

    $ret = $re_array;

    return $ret;
}



/*
* Get wechat user info
*/
function getWeChatInfo( $access_token='',$openid='' ){
    $ret = [];
    if( !$access_token || !$openid ) return $ret;

    $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";

    $result_str = urlGet($url);
    $re_array = json_decode($result_str,true);

    if( !isset($re_array['openid']) )
    {
        dExp( __FUNCTION__, $re_array );
        return $ret;
    }

    // if( $re_array['headimgurl'] ) Download($re_array['headimgurl'],'download/file','icon_','jpg');
    $ret = $re_array;

    return $ret;
}



/*
* Get wechat openid and sessionkey by mini program
*/
function getOpenidByMP( $code='', $platform=3, $conf_name='WECHAT_CONF' ){
    $ret = [];
    $wechat_conf = \Common\getWechatTuples( $platform, $conf_name );

    $sessionApiUrl = "https://api.weixin.qq.com/sns/jscode2session?appid={$wechat_conf['app_id']}&secret={$wechat_conf['app_secret']}&js_code={$code}&grant_type=authorization_code";
    $sessionData = json_decode(urlGet($sessionApiUrl),true);
    if(!isset($sessionData['session_key']))
    {
        dExp( __FUNCTION__, $sessionData );
        return $ret;
    }

    $ret = $sessionData;
    return $ret;
}

/**
 * 检验数据的真实性，并且获取解密后的明文.
 * @param $encryptedData string 加密的用户数据
 * @param $iv string 与用户数据一同返回的初始向量
 * @param $sessionkey string 用户在小程序登录后获取的会话密钥
 * @param $platform number 支持多平台
 *
 * @return object
 * Get wechat user info by mini program
*/
function getWeChatInfoByMP( $encryptedData='', $iv='', $sessionkey='', $platform=3, $conf_name='WECHAT_CONF' ){
    $ret = [];
    if( !$encryptedData || !$iv || !$sessionkey ){
        dMsg( __FUNCTION__, 'encryptedData、iv、sessionkey must be not null ' );
        return $ret;
    }

    if (strlen($sessionkey) != 24 || strlen($iv) != 24) {
        dMsg( __FUNCTION__, 'strlen sessionkey is '.strlen($sessionkey).', strlen iv is '.strlen($iv) );
        return $ret;
    }

    $wechat_conf = \Common\getWechatTuples( $platform, $conf_name );
    $aesKey=base64_decode($sessionkey);
    $aesIV=base64_decode($iv);
    $aesCipher=base64_decode($encryptedData);
    $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
    $data=json_decode( $result, true );
    if( $data == NULL ) {
        dMsg( __FUNCTION__, 'decrypt data is null or worng :'.$result );
        return $ret;
    }

    if( $data['watermark']['appid'] != $wechat_conf['app_id'] ) {
        dMsg( __FUNCTION__, 'appid is wrong:'.$data['watermark']['appid'] );
        return $ret;
    }

    $userinfo = [];
    foreach ($data as $key => $value) {
        if( $key == 'avatarUrl' )
            $key = 'headimgurl';

        $userinfo[ strtolower( $key ) ] = $value;
    }

    if( !isset($userinfo['openid']) )
    {
        dMsg( __FUNCTION__, 'data not openid' );
        return $ret;
    }

    $ret = $userinfo;
    return $ret;
}



/*
* Get wechat config params in url mode
*/
function getWechatConfFromUrlByName( $public_name )
{
    $ret = [];
    if( !$public_name ) goto END;

    $url = C( 'WXCONF_URL' );
    $d = [
        'name' => $public_name,
    ];
    $d = json_encode( $d, JSON_UNESCAPED_UNICODE );
    $z = post( $url, $d );
    $z = json_decode( $z, true );

    if( !$z || !isset($z['access_token']) || !$z['access_token'] )
    {
        dExp( __FUNCTION__, $z );
        goto END;
    }
    $ret = $z;

END:
    return $ret;
}


/**
* Get wechat 4 tuples in either way
* @param conf_name 配置名
* @param platform 配置子选项
*/
function getWechatTuples( $platform=1, $conf_name='WECHAT_CONF' )
{
    $ret = '';
    $conf = C( $conf_name );
    $atfile = $conf[ $platform ]['TUPLE_FILE'];
    if( !$atfile )
    {
        dMsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }

    if( $atfile[0] == '!' )
    {
        // url mode
        $public_name = substr( $atfile, 1 );
        $z = getWechatConfFromUrlByName( $public_name );

        if( !$z ) goto END;
        $ret = [
            'app_id'     => $z[ 'appid' ],
            'app_secret' => $z[ 'appsecret' ],
            'mch_id'     => $z[ 'mchid' ],
            'mch_secret' => $z[ 'mchsecret' ],
        ];

        goto END;
    }

    // file mode as default
    if( !is_file($atfile) )
    {
        dMsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }
    $ret = file_get_contents( $atfile );

END:
    return $ret;
}

/*
* Get wechat access token & js ticket in either way
*/
function getWechatTT( $platform=1, $conf_name='WECHAT_CONF' )
{
    $ret = '';
    $conf = C( $conf_name );
    $atfile = $conf[ $platform ]['ACCESSTOKEN_FILE'];
    if( !$atfile )
    {
        dMsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }

    if( $atfile[0] == '!' )
    {
        // url mode
        $public_name = substr( $atfile, 1 );
        $z = getWechatConfFromUrlByName( $public_name );

        if( !$z ) goto END;
        $ret = [
            'access_token' => $z[ 'access_token' ],
            'js_ticket'    => $z[ 'js_ticket' ],
        ];

        goto END;
    }

    // file mode as default
    if( !is_file($atfile) )
    {
        dMsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }
    $ret = file_get_contents( $atfile );

END:
    return $ret;
}


/*
* Get wechat access token in either way
*/
function getWechatAccessToken( $platform=1, $conf_name='WECHAT_CONF' )
{
    $ret = '';
    $conf = C( $conf_name );
    $atfile = $conf[ $platform ]['ACCESSTOKEN_FILE'];
    if( !$atfile )
    {
        dMsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }

    if( $atfile[0] == '!' )
    {
        // url mode
        $public_name = substr( $atfile, 1 );
        $z = getWechatConfFromUrlByName( $public_name );

        if( !$z ) goto END;
        $ret = $z[ 'access_token' ];

        goto END;
    }

    // file mode as default
    if( !is_file($atfile) )
    {
        dMsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }
    $ret = file_get_contents( $atfile );

END:
    return $ret;
}

/*
* Get wechat js ticket in either way
*/
function getWechatJsTicket( $platform=1, $conf_name='WECHAT_CONF' )
{
    $ret = '';
    $conf = C( $conf_name );
    $atfile = $conf[ $platform ]['ACCESSTOKEN_FILE'];
    if( !$atfile )
    {
        dMsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }

    if( $atfile[0] == '!' )
    {
        // url mode
        $public_name = substr( $atfile, 1 );
        $z = getWechatConfFromUrlByName( $public_name );

        if( !$z ) goto END;
        $ret = $z[ 'js_ticket' ];

        goto END;
    }

    // file mode as default
    if( !is_file($atfile) )
    {
        dMsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }
    $ret = file_get_contents( $atfile );

END:
    return $ret;
}


/*
 * Create necessary config params for using wechat js api.
 * In either way, all from url or all from local
 */
function getWechatJsApiConf( $url, $platform=1, $conf_name='WECHAT_CONF' )
{
    $ret = [];

    if( !$url ) goto END;

    $conf = C( $conf_name );
    $atfile = $conf[ $platform ]['ACCESSTOKEN_FILE'];
    if( !$atfile )
    {
        dMsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }

    if( $atfile[0] == '!' )
    {
        // url mode
        $public_name = substr( $atfile, 1 );
        $z = getWechatConfFromUrlByName( $public_name );
        if( !$z ) goto END;
        dExp( __FUNCTION__, $z );

        $timestamp = time();
        $noncestr  = random(16,'all');
        $ticket    = $z[ 'js_ticket' ];

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$ticket&noncestr=$noncestr&timestamp=$timestamp&url=$url";
        $signature = sha1( $string );

        $ret = [
            'app_id'    => $z[ 'appid' ],
            'timestamp' => $timestamp,
            'nonceStr'  => $noncestr,
            'signature' => $signature,
        ];

        goto END;
    }

    // file mode as default
    if( !is_file($atfile) )
    {
        dMsg( __FUNCTION__, "Token file '$atfile' not exist!" );
        goto END;
    }
    $ret = file_get_contents( $atfile );

END:
    return $ret;
}


/*
*
*/
function getWechatApi( $url, $data, $platform=1, $conf_name='WECHAT_CONF' )
{
    $ret = [];

    $token = getWechatAccessToken( $platform, $conf_name );
    if( !$token ) goto END;
    $url = C( 'WECHAT_API_URL' )."$url?access_token=$token";
    $d = json_encode( $data, JSON_UNESCAPED_UNICODE );
    $z = post( $url, $d );
    $z = json_decode( $z, true );

    if( isset($z['errcode']) && isset($z['errmsg']) )
    {
        dMsg( __FUNCTION__, "Get url '$url' failure!" );
        dExp( __FUNCTION__, $z );
        goto END;
    }
    $ret = $z;

END:
    return $ret;
}

/*
*
*/
function getWechatMpQrCode( $data, $platform=3, $conf_name='WECHAT_CONF' )
{
    $ret = [];

    $token = getWechatAccessToken( $platform, $conf_name );
    if( !$token ) goto END;

    $url = C( 'WXCONF_QRCODE_URL' ).$token;
    $d = json_encode( $data, JSON_UNESCAPED_UNICODE );
    $z = post( $url, $d );
    $ret = changePicture($z,str_replace('/','-',$data['page']));
END:
    return $ret;
}

function changePicture($imgs,$name = ''){
    $path = C('upload_path').'act/';
    if(!is_dir($path)){
        mkdir($path);
    }
    $new_file = $path . ($name?$name:time()).".jpg"; //生成图片的名字
    if(!empty($imgs)){
        $file = fopen($new_file,"w");//打开文件准备写入
        fwrite($file,$imgs);//写入
        fclose($file);//关闭
    }
    return $new_file;
}

/**
 * @param $org_path
 * @param $new_path
 * @return bool
 */
function moveFile($org_path,$new_path){
    if(!is_file($org_path) || empty($new_path))
        return false;
    if( substr($new_path, -1) == '/' )
        $new_path = $new_path.basename($org_path);
    $new_dir_path = dirname($new_path);
    if (!is_dir($new_dir_path)) $z = mkdir($new_dir_path, 0775, true);
    return  rename($org_path,$new_path) ;
}


/**
 * @param string $org_path
 * @param string $new_path
 */
function moveImage( $org_path = '',$new_path = ''){
    moveFile($org_path,$new_path);
}



/**通用图片上传
 * @param string $path
 * @param bool|false $thumb
 * @param bool|true $keep_org
 * @param array $conf
 *                       savename/  保存自定义名称
 *                       pre/       文件名前缀
 * @return array
 */
function ownUploadImg($path = '' ,$thumb = false, $keep_org = true,$conf = []){
    $conf['types'] = ['jpg', 'gif', 'png', 'jpeg'];
    $info = commonUpload('',$path,$conf);

    $ret = [];
    //压缩图片
    if($info['status'])
        return $info;

    $image = new \Think\Image();
    foreach ($info['file'] as $key => $value) {
        $path = $path . $value['savename'];
        $ret['path'] = $path;
        if($thumb){
            //按照原图的比例生成一个宽度150的缩略图并保存为thumb.jpg
            $image->open($path);
            $res_thumb = imgThumb($path,$value['savename']);
            $ret['thumb_path'] = $res_thumb['path'];
        }
        //删除原图
        if(isset($keep_org) && $keep_org == false )
            unlink($path);
    }

    $ret['status'] = 0;
    return $ret;
}



/** 一步上传图片
 * @param string $path
 * @param bool|true $keep_org
 * @param bool|false $thumb
 * @param array $conf
 *                       savename/  保存自定义名称
 *                       pre/       文件名前缀
 * @return array
 */
function ownUploadImgDirect( $path = '' ,$thumb = false,$keep_org = true,$conf = []){
    return ownUploadImg($path,$thumb,$keep_org,$conf);
}


/**两步上传图片之一，先上传到临时目录
 * @param bool|false $thumb
 * @param bool|true $keep_org
 * @param array $conf
 * @return array
 */
function ownUploadImgIndirect1( $thumb = false,$keep_org = true,$conf = []){
    return ownUploadImg(C('img_temp_path'),$thumb,$keep_org,$conf);
}

/**两步上传图片之二，移动到指定目录
 * @param string $org_path
 * @param string $new_path
 */
function ownUploadImgIndirect2( $org_path = '',$new_path = ''){
    moveImage($org_path,$new_path);
}

/**删除文件夹
 * @param $dir
 * @param $mtime 删除$mtime 之前数据
 * @param bool|false $del_empty_path
 * @return bool
 */
function delDir( $dir , $mtime = 0 ,$del_empty_path = false ){
    $t = time();
    //先删除目录下的文件：
    $dh=opendir( $dir );
    while ( $file = readdir( $dh )) {
        if( $file !="." && $file !="..") {
            $fullpath = $dir."/".$file;
            if(!is_dir( $fullpath )) {
                if($mtime ) {
                    if($t > (filemtime($fullpath)+$mtime))   unlink( $fullpath );
                }else {
                    unlink( $fullpath );
                }
            } else {
                if(count(scandir($fullpath)) == 2 && $del_empty_path) {//目录为空,=2是因为.和..存在
                    rmdir($fullpath);// 删除空目录
                }else{
                    delDir( $fullpath,$mtime,$del_empty_path );
                }

            }
        }
    }
    closedir( $dh );

    return true;

}



/**
 * 检验数据的真实性，并且获取解密后的明文.
 * @param $encryptedData string 加密的用户数据
 * @param $iv string 与用户数据一同返回的初始向量
 * @param $sessionKey string 用户在小程序登录后获取的会话密钥
 * @param $data string 解密后的原文
 *
 * @return int 成功true，失败false
 */
function decryptData( $encryptedData, $iv, $sessionKey, &$data ) {
    if (strlen($sessionKey) != 24) {
        return 'sessionKey false';
    }
    $aesKey=base64_decode($sessionKey);

    if (strlen($iv) != 24) {
        return 'iv  false';
    }
    $aesIV=base64_decode($iv);

    $aesCipher=base64_decode($encryptedData);

    $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

    $data=json_decode( $result, true );
    if( !$data || !$result ) {
        return 'decode false';
    }
    return 'true';
}



/**
* 更新排行数据
*/
function setRank( $d=[], $main_key='uid', $redis_key='/common/rank', $cache_type='' )
{
    $t = time();
    // 排行榜存储数量，当前为主从两个
    $rank_cache_num = 2;

    if( !$cache_type )
        $cache_type = C('DATA_CACHE_TYPE');

    switch ( strtolower( $cache_type ) ) {
        case 'redis':

            $redisClient = createRedis();

            // 判断更新状态
            for ($i=1; $i <= $rank_cache_num; $i++) { 
                if( $redisClient->hexists( $redis_key.'/'.$i.'/status', 'setting' ) )
                    return false;
            }

            for ($i=1; $i <= $rank_cache_num; $i++) { 

                // 设置更新缓存状态
                $redisClient->hset( $redis_key.'/'.$i.'/status', 'setting', $t );

                // 清空上一次排行榜数据
                $redisClient->del( $redis_key.'/'.$i );
                $redisClient->del($redis_key.'/'.$i.'/order');
                // 往 redis 添加数据
                $rank_order=1;
                foreach ($d as $key => $value) {
                    $set_res = $redisClient->rPush( $redis_key.'/'.$i, json_encode( $value ) );
                    if( !$set_res )
                        return false;

                    $redisClient->hset( $redis_key.'/'.$i.'/order', $value[$main_key], $rank_order++ );
                }

                $redisClient->hset( $redis_key.'/'.$i.'/life_cycle', 'btime', $t+($i-1)*10 );
                $redisClient->hset( $redis_key.'/'.$i.'/life_cycle', 'etime', $t+($i-1)*10+C('RANK_LIFE_CYCLE') );

                // 删除更新缓存状态
                $redisClient->hdel( $redis_key.'/'.$i.'/status', 'setting' );
            }
            break;

        case 'memcached':
            return false;
            break;

        case 'file':
            return false;
            break;

        default:
            return false;
    }

    return true;
}


/**
* 读取排行数据
*/
function getRank( $start=0, $end=-1, $main_body='', $redis_key='/common/rank', $cache_type='' )
{
    $res = [];
    // 排行榜存储数量，当前为主从两个
    $rank_cache_num = 2;

    if( !$cache_type )
        $cache_type = C('DATA_CACHE_TYPE');

    switch ( strtolower( $cache_type ) ) {
        case 'redis':

            $redisClient = createRedis();

            $rank_sm = '';
            for ($i=1; $i <= $rank_cache_num; $i++) { 
                if( !$redisClient->hexists( $redis_key.'/'.$i.'/status', 'setting' ) ){
                    $rank_sm = $i;
                    break;
                }
            }

            if( $rank_sm ){
                // 取出数据
                $res = [
                    'key' => $rank_sm,
                    'rank' => $redisClient->hget( $redis_key.'/'.$rank_sm.'/order', $main_body ),
                    'btime' => $redisClient->hget( $redis_key.'/'.$rank_sm.'/life_cycle', 'btime' ),
                    'etime' => $redisClient->hget( $redis_key.'/'.$rank_sm.'/life_cycle', 'etime' ),
                    'total' => $redisClient->lLen( $redis_key.'/'.$rank_sm ),
                    'data' => $redisClient->lRange( $redis_key.'/'.$rank_sm, $start, $end )
                ];
            }
            break;

        case 'memcached':

            break;

        case 'file':

            break;

        default:
            break;
    }

    return $res;
}