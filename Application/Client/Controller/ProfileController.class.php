<?php
namespace Client\Controller;

class ProfileController extends GlobalController
{

    protected $user_m;
    protected $step_m;

    public function _initialize(){
        parent::_initialize();
        $this->step_m = D('Client/Step');
        $this->user_m = D('Client/Profile');
    }


    protected function setRankToday( $fid=0 ){
        $t = time();
        $today = strtotime( date( 'Ymd', $t ) );

        $chk_user = $this->user_m->selectClient( 'uid', [ 'fid' => $fid ] );
        if( !$chk_user )
            return false;

        $uid_arr = [];
        foreach ($chk_user as $key => $value) {
            $uid_arr[] = $value['uid'];
        }

        $col = 'a.id,a.ranking rank';
        $rank_res = $this->step_m->getUserStepRank( $col, ['atime' => $today, 'uid' => [ 'in', $uid_arr ], 'mtime <> atime' ], [], 'step_n DESC,mtime ASC' );
        if( !$rank_res )
            return false;

        $set_d = [
            'key' => 'id',
            'column' => ['rank'],
            'd' => $rank_res
        ];
        $res = $this->step_m->setStepRank( $set_d );
        if( !$res )
            return false;

        return true;
    }

    /*
    * 用户信息
    */
    public function showInfo(){
        $raw = $this->RxData;
        $ret = [];
        // 用户信息所需字段
        $keys = [ 'a.uid', 'a.nickname','a.icon','a.idcard','a.fid','b.name firm_name','c.name dept_name' ];
        $res = $this->user_m->getClient( $keys, [ 'a.uid' => $this->out['uid'] ] );

        if( $res ){
            foreach ($res as $key => $value) {
                if( $key == 'icon' )
                    $value = \Common\getCompleteUrl( $value );

                if( in_array($key, [ 'uid', 'fid' ]) )
                    $value = (int)$value;

                $ret[ $key ] = ( $value || $value==='0' )?$value:'';
            }
        }

        $t = time();
        $today = strtotime( date( 'Ymd', $t ) );
        $step_res = $this->step_m->findStep( 'id', ['atime' => $today, 'uid' => $this->out['uid'] ] );
        if( !$step_res ){
            $this->step_m->addStep( [ 'uid' => $this->out['uid'], 'atime' => $today, 'extra' => '[]', 'mtime' => $t ] );
            $this->setRankToday($res['fid']);
        }

        $this->retReturn( $ret );
    }


    /*
    * 修改用户信息 by wechat mini program
    */
    public function editInfo(){
        $raw = $this->RxData;
        $ret = [ 'status' => 1, 'errstr' => '' ];

        $keys = [ 'code', 'encryptedData', 'iv' ];
        foreach ($keys as $val) {
            if( !isset( $raw[ $val ] ) || empty( $raw[ $val ] ) )
                goto END;

            ${$val} = $raw[ $val ];
        }

        $wechat = \Common\getOpenidByMP( $code );
        if( $wechat['session_key'] == '' ){
            goto END;
        }

        $wechat_user = \Common\getWeChatInfoByMP( $encryptedData, $iv, $wechat['session_key'] );
        $d = [];
        if( $wechat_user ){
            // $d[ 'nickname' ] = $wechat_user['nickname']?urlencode( $wechat_user['nickname'] ):'';
            $d[ 'icon' ] = $wechat_user['headimgurl']?$wechat_user['headimgurl']:'';
        }

        $res = $this->user_m->saveClient( [ 'uid' => $this->out['uid'] ], $d );

        // 当用户未修改信息提交，也算修改成功
        if( $res !== false )
            $ret['status'] = 0;
END:
        $this->retReturn( $ret );
    }



    /*
    * 修改用户信息 by user
    */
    public function editInfoByUser(){
        $raw = $this->RxData;
        $ret = [ 'status' => 1, 'errstr' => '' ];

        $key = [ 'nickname','sex','birthday','city','phone' ];
        foreach ($key as $val) {
            if( isset( $raw[ $val ] ) && $raw[ $val ] !== '' )
                $d[ $val ] = $raw[ $val ];
        }

        if( !$d ) goto END;

        $res = $this->user_m->saveClient( [ 'uid' => $this->out['uid'] ], $d );

        // 当用户未修改信息提交，也算修改成功
        if( $res !== false )
            $ret['status'] = 0;
END:
        $this->retReturn( $ret );
    }

    /*
    * 修改用户头像
    */
    public function iconUpload(){
        $ret = [ 'status' => 1, 'errstr' => '' ];
        $uid = $this->out['uid'];

        $field = 'picture';
        $pre_path = C("UPLOAD_PATH");
        $realpath = $pre_path."profile/icon/u_$uid/";
        $conf = array(
            'pre' => 'icon',
            'types' => ['jpg', 'gif', 'png', 'jpeg'],
        );

        if( !is_dir($realpath) ) $z = mkdir( $realpath, 0775, true );

        // 上传图片
        $upload_res = \Common\commonUpload($field,$realpath,$conf);
        if( $upload_res['status'] != 0 ){
            $ret =  $upload_res;
            goto END;
        }

        foreach ($upload_res['file'] as $key => $value) {
            //未压缩图片路径
            $path = $realpath.$value['savename'];

            //进行图片压缩
            $file_path = $value['savepath'].$value['savename'];
            $thumb = \Common\imgThumb($file_path,$value['savename']);
            if( $thumb['status'] != 0 ){
                $ret =  $thumb;
                goto END;
            }

            //压缩后的图片路径
            $thumbpath = $realpath.$thumb['savename'];

            break;
        }

        //修改用户头像（当前只保存压缩图）
        $data['icon'] = $thumbpath;
        $res = $this->user_m->saveClient( [ 'uid' => $uid ], $data );

        //未保存成功，清除上传图片
        if( $res === false ){
            unlink($path);
            unlink($thumbpath);
            goto END;
        }

        $ret[ 'status' ] = 0;
        $ret[ 'errstr' ] = '';
        $ret[ 'img_url' ] = \Common\getCompleteUrl( $thumbpath );

END:
        $this->retReturn( $ret );
    }


}