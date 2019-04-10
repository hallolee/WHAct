<?php
namespace Push\Controller;

class PushController extends GlobalController {

    protected $RxData;
    protected $err;
    protected $m_m;

    public function _initialize() {
        $this->RxData = \Common\getRawByJson( $this->err );
    }


    protected function pushCore( $param=[] ){
        $ret = E_SYSTEM;
        $keys = [ 'major', 'minor', 'push_id', 'data' ];
        foreach ($keys as $value) {
            ${$value} = $param[ $value ];
        }

        // 获取推送配置
        $dir = C('PUSH_DATA_PATH');
        $all_files = scandir($dir);
        foreach ($all_files as $filename) {
            $real_path = $dir.'/'.$filename;
            if( !is_file( $real_path ) )
                continue;

            $basic_d = json_decode( file_get_contents( $real_path ), true );

            foreach ($basic_d as $key => $value) {
                $basic_info[ $key ] = $value;
            }
        }

        // 是否存在相应的推送配置
        if( !isset( $basic_info[ $major ]['children'][ $minor ][ $push_id ] ) )
            goto END;

        $fun = $basic_info[ $major ]['children'][ $minor ]['fun'];
        $class = $basic_info[ $major ]['class'];

        $config = $basic_info[ $major ]['children'][ $minor ][ $push_id ];

        $push_d['conf'] = $config;
        unset($push_d['conf']['basic']);
        unset($push_d['conf']['data']);

        // 推送基础信息
        foreach ($config['basic'] as $key => $value) {
            if( !isset( $data[ $key ] ) || empty( $data[ $key ] ) )
                goto END;

            $push_d['basic'][ $key ] = $data[ $key ];
            unset( $data[ $key ] );
        }

        // 推送内容信息
        foreach ($config['data'] as $key => $value) {
            if( !isset( $data[ $key ] ) || empty( $data[ $key ] ) )
                goto END;

            $push_d['data'][ $key ] = $data[ $key ];
            unset( $data[ $key ] );
        }

        $push = new $class();
        $ret = $push->$fun( $push_d );
END:
        return $ret;
    }

    public function exPush(){
        $ret = [ 'status' => E_SYSTEM, 'errstr' => '' ];
        $raw = $this->RxData;

        // 校检基本参数
        $keys = [ 'major', 'minor', 'push_id', 'data' ];
        foreach ($keys as $value) {
            if( !isset( $raw[ $value ] ) || empty( $raw[ $value ] ) )
                goto END;
        }

        $res = $this->pushCore( $raw );
        $ret['status'] = $res;
END:
        \Common\genStatusStr( $ret['status'], $ret );
        $this->ajaxReturn( $ret );
    }


    public function inPush( $raw=[] ){
        $ret = E_SYSTEM;

        // 校检基本参数
        $keys = [ 'major', 'minor', 'push_id', 'data' ];
        foreach ($keys as $value) {
            if( !isset( $raw[ $value ] ) || empty( $raw[ $value ] ) )
                goto END;
        }

        $ret = $this->pushCore( $raw );
END:
        return $ret;
    }





}
