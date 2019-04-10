<?php
namespace Payment\Controller;
vendor( 'LibWechatPay.WxPay#Api' );
vendor( 'LibWechatPay.WxPay#Notify' );
use function \Common\dMsg;
use function \Common\dExp;

class WepayApiController extends GlobalController
{
    // basic_funcs begin
    protected function setWechatConf( $pf )
    {
        $ret = false;
        $wechat_conf = \Common\getWechatTuples( $pf );
        if( !$wechat_conf ) goto END;

        \WxPayConfig::$APPID = $wechat_conf[ 'app_id' ];
        \WxPayConfig::$MCHID = $wechat_conf[ 'mch_id' ];
        \WxPayConfig::$KEY   = $wechat_conf[ 'mch_secret' ];
        $ret = true;

END:
        return $ret;
    }
    // basic_funcs end



    /**
     * 初始化
     */
    public function _initialize( $check=false )
    {
        parent::_initialize( $check );
    }

    public function getJsParam()
    {
        $ret = [ 'status' => 5, 'errstr' => '' ];
        $raw = $this->RxData;

        if( !isset($raw['pf']) || !$this->setWechatConf($raw['pf']) ){
            $ret['status'] = E_PAYMENT_WXPF;
            goto END;
        }

        \Common\validDaToken( $raw['token'], $out, TOKEN_NOCHECK );
        if( !isset( $out['uid'] ) || empty( $out['uid'] ) ){
            $ret[ 'status' ] = E_TOKEN;
            $ret[ 'errstr' ] = 'Token failure!';
            goto END;
        }

        $col = C('WECHAT_CONF')[ $raw['pf'] ][ 'COL' ];
        $openid = $out[ $col ];
        if( !$openid )
        {
            $ret[ 'status' ] = E_OPENID;
            $ret[ 'errstr' ] = "Invaid $col: '$openid'!";
            goto END;
        }
        //dMsg( __FUNCTION__, "openid = '$openid'" );

        $module = $raw['module'];
        if( !$module )
        {
            $ret[ 'errstr' ] = 'Module: required!';
            goto END;
        }

        $product_desc = $raw['product_desc'];
        if( !$product_desc )
        {
            $ret[ 'errstr' ] = 'Product_desc: required!';
            goto END;
        }

        $trade_no = $raw['trade_no'];
        if( strlen($trade_no)>32 || !ctype_alnum($trade_no) )
        {
            $ret[ 'errstr' ] = "Invaid trade_no: '$trade_no'!";
            goto END;
        }
        //dMsg( __FUNCTION__, "trade_no = '$trade_no'" );

        $fee = $raw['fee'];
        if( !is_numeric($fee) )
        {
            $ret[ 'errstr' ] = "Invalide fee: '$fee!'";
            goto END;
        }

        $attach = $raw['attach'];
        if( !$attach ) $attach = '';

        $cburl = C('PREENT').MODULE_NAME.'/'.CONTROLLER_NAME.'/payNotify/'.$raw['pf'];
        //统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody( $product_desc );
        $input->SetOut_trade_no( $trade_no );
        $input->SetTotal_fee( $fee );
        $input->SetNotify_url( $cburl );
        $input->SetOpenid( $openid );
        $input->SetTrade_type( 'JSAPI' );
        $input->SetAttach( "$module!$attach" );
        //$input->SetGoods_tag( 'test' );
        //$input->SetTime_start(date("YmdHis"));
        //$input->SetTime_expire(date("YmdHis", time() + 600));

        $order = \WxPayApi::unifiedOrder( $input );
        //\Common\dExp( __FUNCTION__, $order );
        if( $order['return_code'] != 'SUCCESS' )
        {
            $ret[ 'status' ] = E_PAYMENT_RESULT;
            $ret[ 'errstr' ] = $order[ 'return_msg' ];
            goto END;
        }
        if( $order['result_code'] != 'SUCCESS' )
        {
            $ret[ 'status' ] = E_PAYMENT_RESULT;
            $ret[ 'errstr' ] = "errcode:'{$order[ 'err_code' ]}', errstr:'{$order[ 'err_code_des' ]}'";
            goto END;
        }
        $jsApiParameters = \WxPayApi::GetJsApiParameters( $order );

        $ret[ 'status' ] = E_OK;
        $ret[ 'param' ] = $jsApiParameters;

END:
        $this->retReturn( $ret );
    }

    public function getNativeParam()
    {
        $ret = [ 'status' => 5 ];
        $raw = $this->RxData;

        if( !isset($raw['pf']) || !$this->setWechatConf($raw['pf']) ){
            $ret['status'] = E_PAYMENT_WXPF;
            goto END;
        }

        /*
        $phone = $raw[ 'phone' ];
        S( ['type' => 'memcached'] );
        $code = S( $phone );

        if( !$code )
        {
            $ret = [
                'status' => E_SMSCODE2,
                'errstr' => 'Captcha expire or lost.'
            ];
            goto END;
        }

        if( $code != $raw['code'] )
        {
            $ret = [
                'status' => E_SMSCODE,
                'errstr' => 'Captcha error.'
            ];
            goto END;
        }
        //dMsg( __FUNCTION__, "code = '$code'" );
        */

        $module = $raw[ 'module' ];
        if( !$module )
        {
            $ret[ 'errstr' ] = 'Module: required!';
            goto END;
        }

        $product_desc = $raw[ 'product_desc' ];
        if( !$product_desc )
        {
            $ret[ 'errstr' ] = 'Product_desc: required!';
            goto END;
        }

        $trade_no = $raw[ 'trade_no' ];
        if( strlen($trade_no) > 32 || !ctype_alnum($trade_no) )
        {
            $ret[ 'errstr' ] = "Invaid trade_no: '$trade_no'!";
            goto END;
        }
        //dMsg( __FUNCTION__, "trade_no = '$trade_no'" );

        $fee = $raw[ 'fee' ];
        if( !is_numeric($fee) )
        {
            $ret[ 'errstr' ] = "Invalide fee: '$fee!'";
            goto END;
        }

        $attach = $raw[ 'attach' ];
        if( !$attach ) $attach = '';

        $cburl = C('PREENT').MODULE_NAME.'/'.CONTROLLER_NAME.'/payNotify/'.$raw['pf'];

        //统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody( $product_desc );
        $input->SetOut_trade_no( $trade_no );
        $input->SetTotal_fee( $fee );
        $input->SetNotify_url( $cburl );
        $input->SetTrade_type( 'NATIVE' );
        $input->SetAttach( "$module!$attach" );
        $input->SetProduct_id( '123456789' ); // must fill in though useless!!
        //$input->SetGoods_tag( 'test' );
        //$input->SetTime_start(date("YmdHis"));
        //$input->SetTime_expire(date("YmdHis", time() + 600));

        $order = \WxPayApi::unifiedOrder( $input );
        //dExp( __FUNCTION__, $order );
        if( $order['return_code'] != 'SUCCESS' )
        {
            $ret[ 'status' ] = E_PAYMENT_RESULT;
            $ret[ 'errstr' ] = $order[ 'return_msg' ];
            goto END;
        }
        if( $order['result_code'] != 'SUCCESS' )
        {
            $ret[ 'status' ] = E_PAYMENT_RESULT;
            $ret[ 'errstr' ] = "errcode:'{$order[ 'err_code' ]}', errstr:'{$order[ 'err_code_des' ]}'";
            goto END;
        }

        $ret[ 'status' ] = E_OK;
        $ret[ 'code_url' ] = $order[ 'code_url' ];

END:
        $this->retReturn( $ret );
    }

    public function getAppParam()
    {
        $ret = [ 'status' => 5, 'errstr' => '' ];
        $raw = $this->RxData;

        if( !isset($raw['pf']) || !$this->setWechatConf($raw['pf']) ){
            $ret['status'] = E_PAYMENT_WXPF;
            goto END;
        }

        \Common\validDaToken( $raw['token'], $out, TOKEN_NOCHECK );
        if( !isset( $out['uid'] ) || empty( $out['uid'] ) ){
            $ret[ 'status' ] = E_TOKEN;
            $ret[ 'errstr' ] = 'Token failure!';
            goto END;
        }

        /*
        $openid = $out['openid'];
        if( !$openid )
        {
            $ret[ 'status' ] = E_OPENID;
            $ret[ 'errstr' ] = "Invaid openid: '$openid'!";
            goto END;
        }
         */
        //dMsg( __FUNCTION__, "openid = '$openid'" );

        $module = $raw['module'];
        if( !$module )
        {
            $ret[ 'errstr' ] = 'Module: required!';
            goto END;
        }

        $product_desc = $raw['product_desc'];
        if( !$product_desc )
        {
            $ret[ 'errstr' ] = 'Product_desc: required!';
            goto END;
        }

        $trade_no = $raw['trade_no'];
        if( strlen($trade_no)>32 || !ctype_alnum($trade_no) )
        {
            $ret[ 'errstr' ] = "Invaid trade_no: '$trade_no'!";
            goto END;
        }
        //dMsg( __FUNCTION__, "trade_no = '$trade_no'" );

        $fee = $raw['fee'];
        if( !is_numeric($fee) )
        {
            $ret[ 'errstr' ] = "Invalide fee: '$fee!'";
            goto END;
        }

        $attach = $raw['attach'];
        if( !$attach ) $attach = '';

        $cburl = C('PREENT').MODULE_NAME.'/'.CONTROLLER_NAME.'/payNotify/'.$raw['pf'];
        //统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody( $product_desc );
        $input->SetOut_trade_no( $trade_no );
        $input->SetTotal_fee( $fee );
        $input->SetNotify_url( $cburl );
        //$input->SetOpenid( $openid );
        $input->SetTrade_type( 'APP' );
        $input->SetAttach( "$module!$attach" );
        //$input->SetGoods_tag( 'test' );
        //$input->SetTime_start(date("YmdHis"));
        //$input->SetTime_expire(date("YmdHis", time() + 600));

        $order = \WxPayApi::unifiedOrder( $input );
        //dExp( __FUNCTION__, $order );
        if( $order['return_code'] != 'SUCCESS' )
        {
            $ret[ 'status' ] = E_PAYMENT_RESULT;
            $ret[ 'errstr' ] = $order[ 'return_msg' ];
            goto END;
        }
        if( $order['result_code'] != 'SUCCESS' )
        {
            $ret[ 'status' ] = E_PAYMENT_RESULT;
            $ret[ 'errstr' ] = "errcode:'{$order[ 'err_code' ]}', errstr:'{$order[ 'err_code_des' ]}'";
            goto END;
        }
        $apParameters = \WxPayApi::GetAppParameters( $order );

        $ret[ 'status' ] = E_OK;
        $ret[ 'param' ] = $apParameters;

END:
        $this->retReturn( $ret );
    }

    public function getMpParam()
    {
        $ret = [ 'status' => 5, 'errstr' => '' ];
        $raw = $this->RxData;

        if( !isset($raw['pf']) || !$this->setWechatConf($raw['pf']) ){
            $ret['status'] = E_PAYMENT_WXPF;
            goto END;
        }


        \Common\validDaToken( $raw['token'], $out, TOKEN_NOCHECK );
        if( !isset( $out['uid'] ) || empty( $out['uid'] ) ){
            $ret[ 'status' ] = E_TOKEN;
            $ret[ 'errstr' ] = 'Token failure!';
            goto END;
        }

        $col = C('WECHAT_CONF')[ $raw['pf'] ][ 'COL' ];
        $openid = $out[ $col ];
        if( !$openid )
        {
            $ret[ 'status' ] = E_OPENID;
            $ret[ 'errstr' ] = "Invaid $col: '$openid'!";
            goto END;
        }
        //dMsg( __FUNCTION__, "$col = '$openid'" );
        //dMsg( __FUNCTION__, "uid = '$out[uid]'" );

        $module = $raw['module'];
        if( !$module )
        {
            $ret[ 'errstr' ] = 'Module: required!';
            goto END;
        }

        $product_desc = $raw['product_desc'];
        if( !$product_desc )
        {
            $ret[ 'errstr' ] = 'Product_desc: required!';
            goto END;
        }

        $trade_no = $raw['trade_no'];
        if( strlen($trade_no)>32 || !ctype_alnum($trade_no) )
        {
            $ret[ 'errstr' ] = "Invaid trade_no: '$trade_no'!";
            goto END;
        }
        //dMsg( __FUNCTION__, "trade_no = '$trade_no'" );

        $fee = $raw['fee'];
        if( !is_numeric($fee) )
        {
            $ret[ 'errstr' ] = "Invalide fee: '$fee!'";
            goto END;
        }

        $attach = $raw['attach'];
        if( !$attach ) $attach = '';

        $cburl = C('PREENT').MODULE_NAME.'/'.CONTROLLER_NAME.'/payNotify/'.$raw['pf'];
        //统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody( $product_desc );
        $input->SetOut_trade_no( $trade_no );
        $input->SetTotal_fee( $fee );
        $input->SetNotify_url( $cburl );
        $input->SetOpenid( $openid );
        $input->SetTrade_type( 'JSAPI' );
        $input->SetAttach( "$module!$attach" );
        //$input->SetGoods_tag( 'test' );
        //$input->SetTime_start(date("YmdHis"));
        //$input->SetTime_expire(date("YmdHis", time() + 600));

        $order = \WxPayApi::unifiedOrder( $input );
        //\Common\dExp( __FUNCTION__, $order );
        if( $order['return_code'] != 'SUCCESS' )
        {
            $ret[ 'status' ] = E_PAYMENT_RESULT;
            $ret[ 'errstr' ] = $order[ 'return_msg' ];
            goto END;
        }
        if( $order['result_code'] != 'SUCCESS' )
        {
            $ret[ 'status' ] = E_PAYMENT_RESULT;
            $ret[ 'errstr' ] = "errcode:'{$order[ 'err_code' ]}', errstr:'{$order[ 'err_code_des' ]}'";
            goto END;
        }
        $jsApiParameters = \WxPayApi::GetJsApiParameters( $order );

        $ret[ 'status' ] = E_OK;
        $ret[ 'param' ] = $jsApiParameters;

END:
        $this->retReturn( $ret );
    }


    // call back for jsapi
    public function payNotify()
    {
        $pf = I( 'path.2' );
        if( !$pf || !$this->setWechatConf($pf) )
        {
            dMsg( __FUNCTION__, 'No "pf"!' );
            return;
        }

        $notify = new PayNotifyCallBack();
        $notify->SetData( 'l_pf', $pf );
        $notify->Handle( false );

        $code = $notify->GetReturn_code();
        if( $code == 'FAIL' )
            \Common\dMsg( __FUNCTION__, $notify->GetReturn_msg() );
    }
}

class PayNotifyCallBack extends \WxPayNotify
{
    //重写回调处理函数
    public function NotifyProcess( $data, &$msg )
    {
        $status = 1;

        $ret = parent::NotifyProcess( $data, $msg );
        if( !$ret ) { $ret = false; goto END; }
        list( $module, $attach ) = explode( '!', $data['attach'], 2 );
        list( $module, $seq ) = explode( '@', $module, 2 );
        $pf = $this->values[ 'l_pf' ];
        $col = C('WECHAT_CONF')[ $pf ][ 'COL' ];

        $d = [
//            'module'   => $module,
            'module'     => $data['attach'],
            'tid'        => $data[ 'transaction_id' ],
            'iid'        => $data[ 'out_trade_no' ],
            'fee'        => $data[ 'total_fee' ],
            'col_openid' => $col,
            'openid'     => $data[ 'openid' ],
            'time_end'   => $data[ 'time_end' ],
            'atime'      => time(),
            'status'     => $status,
        ];

        /*
         * must insert here to get a record,
         * cau'z maybe used in subsequent callback for business
         */
        $id = D( 'WepayNotify' )->add( $d );
        if( !$id )
        {
            $error = D( 'WepayNotify' )->getDbError();
            $error = explode( ':', $error );
            if( $error[0] == 1062 )
            {
                \Common\dMsg( __FUNCTION__, 'Repeat submit trading records!' );
            }
        }

        $d[ 'attach' ] = $attach;
        $bi = C( 'PAY_NOTIFY_CALLBACK' )[ $module ];
        if( !isset($bi[$seq]) ) goto END;
        $bi = $bi[ $seq ];

        if( $bi['type'] == 'url' )
        {
            $d[ 'datoken' ] = 'xxxx'; // Todo: confirm how to set internal token
            //dExp( $d, __FUNCTION__ );

            $result = post( $url, $d );
            //dMsg( "result = '$result'", __FUNCTION__ );
            $result = json_decode( $result );
            $status = ( isset($result->state) && $result->state == 0 )? 0:1;

            unset( $d['datoken'] );
            goto END;
        }

        if( $bi['type'] == 'func' )
        {
            $d[ 'logpath' ] = $bi[ 'logpath' ];
            $status = D( $bi['model'] )->$bi['method']( $d );
//            $status = D('Merchant/Wallet')->WeChatPayNotify( $d );
//            $status = D('Merchant/Dragon')->WeChatPayNotify( $d );
            unset( $d['logpath'] );
            goto END;
        }

END:
        if( $status == 0 && $id )
        {
            $d = [
                'atime'  => time(),
                'status' => $status,
            ];
            $id = D( 'WepayNotify' )->where( ['id' => $id] )->save( $d );
        }

        return $ret;
    }
}
