<?php
namespace Payment\Controller;
use function \Common\dMsg;
use function \Common\dExp;
vendor( 'LibAliPay.AlipayTradeService' );

class AlipayApiController extends GlobalController {


    // 初始化
    public function _initialize($check=false, $check_param=false){
        parent::_initialize($check, $check_param);
    }


    public function getJsParam() {
        $ret = [ 'status' => 5, 'errstr' => '' ];
        $raw = $this->RxData;
        $conf = C('ALIPAY_CONF');

        \Common\validDaToken( $raw['token'], $out, TOKEN_NOCHECK );
        if( !isset( $out['uid'] ) || empty( $out['uid'] ) ){
            $ret[ 'status' ] = E_TOKEN;
            $ret[ 'errstr' ] = 'Token failure!';
            goto END;
        }

        $module = $raw['module'];
        if( !$module )
        {
            $ret[ 'errstr' ] = 'Module: required!';
            goto END;
        }

        $subject = $raw['subject'];
        if( !$subject ) {
            $ret['errstr'] = 'subject: required!';
            goto END;
        }

        $trade_no = $raw['trade_no'];
        if( strlen($trade_no) > 64 )
        {
            $ret[ 'errstr' ] = "Invalid trade_no: '$trade_no'!";
            goto END;
        }

        $total_amount = $raw['total_amount'];
        if( !is_numeric($total_amount) ) {
            $ret[ 'errstr' ] = "Invalid total_amount: '$total_amount!'";
            goto END;
        }

        $body = $raw['body'];
        if( !$body ) $body = '';

        $return_url = $raw['return_url'];
        if( !$return_url ) $return_url = '';

        $passback = $raw['passback'];
        if( !$passback ) $passback = '';

        $biz_content = [
            //商品描述，可空
            'body'            => $body,
            //订单名称，必填
            'subject'         => $subject,
            //商户订单号，商户网站订单系统中唯一订单号，必填
            'out_trade_no'    => $trade_no,
            //超时时间
            'timeout_express' => '2m',
            //付款金额，必填
            'total_amount'    => $total_amount,
            //产品标示码，固定值：QUICK_WAP_PAY
            'productCode'     => 'QUICK_WAP_PAY',
            //公用回传参数
            'passback_params' => urlencode( "$module!$out[uid]!$passback" )
        ];

        //$return_url = C('PREENT').MODULE_NAME.'/page/Alidemo.html';
        $cburl      = C('PREENT').MODULE_NAME.'/'.CONTROLLER_NAME.'/payNotify';

        $payResponse = new \AlipayTradeService($conf);
        $result = $payResponse->wapPay(
            json_encode( $biz_content, JSON_UNESCAPED_UNICODE ),
            $return_url,
            $cburl
        );

        if( $result ){
            $ret['status'] = E_OK;
            $ret['param'] = $result;
        }
END:
        $this->ajaxReturn( $ret );
    }


    public function getAppParam() {
        $ret = [ 'status' => 5, 'errstr' => '' ];
        $raw = $this->RxData;
        $conf = C('ALIPAY_CONF');

        \Common\validDaToken( $raw['token'], $out, TOKEN_NOCHECK );
        if( !isset( $out['uid'] ) || empty( $out['uid'] ) ){
            $ret[ 'status' ] = E_TOKEN;
            $ret[ 'errstr' ] = 'Token failure!';
            goto END;
        }

        $module = $raw['module'];
        if( !$module )
        {
            $ret[ 'errstr' ] = 'Module: required!';
            goto END;
        }

        $subject = $raw['subject'];
        if( !$subject ) {
            $ret['errstr'] = 'subject: required!';
            goto END;
        }

        $trade_no = $raw['trade_no'];
        if( strlen($trade_no) > 64 )
        {
            $ret[ 'errstr' ] = "Invalid trade_no: '$trade_no'!";
            goto END;
        }

        $total_amount = $raw['total_amount'];
        if( !is_numeric($total_amount) ) {
            $ret[ 'errstr' ] = "Invalid total_amount: '$total_amount!'";
            goto END;
        }

        $body = $raw['body'];
        if( !$body ) $body = '';

        $passback = $raw['passback'];
        if( !$passback ) $passback = '';

        $biz_content = [
            //商品描述，可空
            'body'            => $body,
            //订单名称，必填
            'subject'         => $subject,
            //商户订单号，商户网站订单系统中唯一订单号，必填
            'out_trade_no'    => $trade_no,
            //超时时间
            'timeout_express' => '30m',
            //付款金额，必填
            'total_amount'    => $total_amount,
            //产品标示码，固定值：QUICK_MSECURITY_PAY
            'productCode'     => 'QUICK_MSECURITY_PAY',
            //公用回传参数
            'passback_params' => urlencode( "$module!$out[uid]!$passback" )
        ];

        $cburl      = C('PREENT').MODULE_NAME.'/'.CONTROLLER_NAME.'/payNotify';

        $payResponse = new \AlipayTradeService($conf);
        $result = $payResponse->appPay(
            json_encode( $biz_content, JSON_UNESCAPED_UNICODE ),
            $cburl
        );

        if( $result ){
            $ret['status'] = E_OK;
            $ret['param'] = $result;
        }
END:
        $this->ajaxReturn( $ret );
    }



    // call back for api
    public function payNotify() {
        $raw = $this->RxData;

        $status = 1;
        $conf = C('ALIPAY_CONF');
        $alipaySevice = new \AlipayTradeService($conf);

        // 此处返回的双引号会被 html 编码（暂不知道原因），会导致验签失败
        $raw['fund_bill_list'] = htmlspecialchars_decode( $raw['fund_bill_list'] );
        $result = $alipaySevice->check($raw);

        // dExp( __FUNCTION__, $result );
        // dExp( __FUNCTION__, $raw );
        if( !$result ) goto END;

        $chk_res = D( 'AlipayNotify' )
            ->where( [ 'tid' => $raw[ 'trade_no' ] , 'iid' => $raw[ 'out_trade_no' ], 'status' => 1 ] )
            ->find();

        list( $module, $uid, $attach ) = explode( '!', $raw['passback_params'] );
        list( $module, $seq ) = explode( '@', $module, 2 );

        // 检查以后已进行记录，后续回调处理是否成功
        if( $chk_res )
        {
            $id = $chk_res['id'];
        }
        else
        {
            $d = [
                'module'     => $module,
                'tid'        => $raw[ 'trade_no' ],
                'iid'        => $raw[ 'out_trade_no' ],
                'fee'        => $raw[ 'buyer_pay_amount' ]*100,
                'time_end'   => date( 'YmdHis', strtotime( $raw[ 'notify_time' ] ) ),
                'atime'      => time(),
                'status'     => $status,
            ];

            /*
             * must insert here to get a record,
             * cau'z maybe used in subsequent callback for business
             */
            $id = D( 'AlipayNotify' )->add( $d );
            if( !$id )
            {
                $error = D( 'AlipayNotify' )->getDbError();
                $error = explode( ':', $error );
                if( $error[0] == 1062 )
                {
                    dMsg( __FUNCTION__, 'Repeat submit trading records!' );
                }
            }
        }

        $d[ 'uid' ]    = $uid;
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
            //dExp( __FUNCTION__, $d );
            $status = D( $bi['model'] )->$bi['method']( $d );
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
            $id = D( 'AlipayNotify' )->where( ['id' => $id] )->save( $d );

            // 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
            echo "success";
        }else{
            //验证失败
            echo "fail";
        }
    }
}
