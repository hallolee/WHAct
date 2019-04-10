<?php
namespace Common\Controller;
use Think\Controller;
/**
*
*/
class CommonController extends Controller
{
    protected $RxData;
    protected $out;
    protected $config;

    public function _initialize()
    {
        if( C('ALLOW_CORS') )
            header('Access-Control-Allow-Origin:*');

        $ret = [ 'status' => 8, 'errstr' => '' ];
        $chk = $chk_param = true;
        $action = MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;

        // 获取访问模块 token检查配置
        $token_chk_status = C('TOKEN_NOCHK_CONTROLLER');
        if(
            isset( $token_chk_status[ MODULE_NAME ][ CONTROLLER_NAME ] )
            && $token_chk_status[ MODULE_NAME ][ CONTROLLER_NAME ] == false
        ){
            $chk = false;
        }

        // 检测方法是否为无需验证 token
        $token_action = C('TOKEN_NOCHK_ACTION');
        if( in_array( $action, $token_action ) )
            $chk = false;

        // 检测方法是否为无需参数格式
        $param_action = C('PARAM_NOCHK_ACTION');
        if( isset( $param_action[ $action ] ) )
            $chk_param = false;

        $err = 0;
        $this->RxData = \Common\getRawByJson( $err );
        switch ($err) {
            case 1:
                //当前为文件上传
                $this->RxData =I("post.");
                break;

            case 2:
                //json 解析失败
                if( $chk_param ){
                    $this->retReturn( [ 'status' => 24, 'errstr' => '' ] );
                }else{
                    $this->RxData =I($param_action[ $action ].".");
                }
                break;

            default:
                break;
        }

        if( isset($this->RxData['token']) && $this->RxData['token'] ){

            \Common\validDaToken( $this->RxData['token'], $user );
            $this->out = $user;
        }

        // 验证 token 是否符合条件
        if( $chk && ( !isset( $user ) || empty( $user ) || empty( $user['uid'] ) ) ){
            $this->retReturn( $ret );
        }

        // 验证参数是否符合规范
        $ret = \Common\rawValidator( $action, $this->RxData );
        // if( $ret['status'] != 0 )
            // $this->retReturn( $ret );
    }


    /*
    *@  自定义封装结果返回
    */
    public function retReturn( $ret ){
        if( ( is_array( $ret ) || is_object( $ret ) ) && isset( $ret['status'] ) && isset( $ret['errstr'] ) && empty( $ret['errstr'] ) )
            \Common\genStatusStr( $ret['status'], $ret );

        $this->ajaxReturn( $ret );
    }



}
