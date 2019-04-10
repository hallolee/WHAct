<?php
namespace Payment\Controller;
use Common\Controller\CommonController;

class PageController extends CommonController 
{
    public function _initialize( $check=true )
    {
        /*
        $openid = '';
        $datoken = I( 'request.datoken' );
        $datoken_value = $this->ValidDaTokenValue( $datoken );
        $token  = $datoken_value[ C( 'VISIT_USER' ) ]? GenTokenWrap( 'VISIT_USER' ):'';
        if( !is_weixin() ) goto END;
        $key = C( 'VISIT_USER_APPID' );
        $check_key = $datoken_value[ $key ];
        if( isset($check_key) && $check_key )
        {
            $openid = $check_key;
            goto END;
        }

        $appid = C( 'APP_ID' );
        $code = I( 'get.code' );
        //dMsg( __FUNCTION__, "code = '$code'" );
        if( !$code )
        {
            //重定向
            //触发微信返回code码
            $orig_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
            if( isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] )
                $orig_url .= '?'.$_SERVER['QUERY_STRING'];

            $orig_url = urlencode( $orig_url );
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize'.
                   "?appid=$appid&redirect_uri=$orig_url".
                   '&response_type=code&scope=snsapi_base&state='.time().
                   '#wechat_redirect';
            Redirect( $url );
            exit;
        }

        $secret = C( 'SECRET' );
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token'.
               "?appid=$appid&secret=$secret&code=$code".
               '&grant_type=authorization_code';
        $z = url_get( $url );
        //dExp( __FUNCTION__, $z );
        $z = json_decode( $z, true );

        if( isset($z['openid']) ) $openid = $z[ 'openid' ];

END:
        $this->assign( 'datoken', $datoken );
        $this->assign( 'openid', $openid );
        $this->assign( 'token', $token );
        validDaTokenWrite( [C('VISIT_USER_APPID')=>$openid], $datoken );
        // session(C('VISIT_USER_APPID'),$openid);
        */
        return;
    }

    public function ValidDaTokenValue($datoken)
    {
        $value;
        $state = \Common\validDaToken( $datoken, $value );
        if ( !$state ) {
            return false;
        }
        return $value;
    }
}
