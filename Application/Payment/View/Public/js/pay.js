/**
 * Created by David@2018.2.13
 */

    function DumpObj( o )
    {
        var s = '';
        for( var property in o )
        {
            s += '\n '+property + ': ' + o[property];
        }
        alert(s);
    }
    
    function WepayDo( pay_param, PayCb )
    {
        WeixinJSBridge.invoke(
            'getBrandWCPayRequest',
            pay_param,
            function( res ) {
                var msgs = {
                    'get_brand_wcpay_request:ok':     'ok',
                    'get_brand_wcpay_request:cancel': 'cancel',
                    'get_brand_wcpay_request:fail':   'fail',
                };
                var msg = msgs[ res.err_msg ] || 'fail';
                //WeixinJSBridge.log( msg );
                PayCb( msg );
            }
        );
    }

    function PayCall( pay_param, PayCb )
    {
        //DumpObj( pay_param );
        if( typeof WeixinJSBridge != "undefined" )
        {
            WepayDo( pay_param, PayCb );
            return;
        }

        if( document.addEventListener )
        {
            document.addEventListener(
                'WeixinJSBridgeReady',
                function() { WepayDo( pay_param, PayCb ); },
                false
            );
            return;
        }

        if( document.attachEvent )
        {
            document.attachEvent(
                'WeixinJSBridgeReady',
                function() { WepayDo( pay_param, PayCb ); }
            );
            document.attachEvent(
                'onWeixinJSBridgeReady',
                function() { WepayDo( pay_param, PayCb ); }
            );
            return;
        }
    }

    function checkTel(phone){
        var re = /^1\d{10}$/;
        return ( phone && re.test(phone) );
    }
