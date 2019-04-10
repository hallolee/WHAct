<?php
/* *
 * 功能：支付宝手机网站alipay.trade.close (统一收单交易关闭接口)业务参数封装
 * 版本：2.0
 * 修改日期：2018-5-22
 */

require_once __DIR__.'/aop/AopClient.php';

/**
 * 说明：
 * 以下需要用到哪些类，直接引入相关类文件。
*/
require_once __DIR__.'/aop/request/AlipayTradeWapPayRequest.php';
require_once __DIR__.'/aop/request/AlipayTradeAppPayRequest.php';
require_once __DIR__.'/aop/request/AlipayTradeQueryRequest.php';
require_once __DIR__.'/aop/request/AlipayTradeRefundRequest.php';
require_once __DIR__.'/aop/request/AlipayTradeCloseRequest.php';
require_once __DIR__.'/aop/request/AlipayTradeFastpayRefundQueryRequest.php';
require_once __DIR__.'/aop/request/AlipayDataDataserviceBillDownloadurlQueryRequest.php';

class AlipayTradeService {

    //支付宝网关地址
    public $gateway_url = "https://openapi.alipay.com/gateway.do";

    //支付宝公钥
    public $alipay_public_key;

    //商户私钥
    public $private_key;

    //应用id
    public $appid;

    //编码格式
    public $charset = "UTF-8";

    public $token = NULL;

    //返回数据格式
    public $format = "json";

    //签名方式
    public $signtype = "RSA";

    // $alipay_config：外部调用实例化类的时候传递
    function __construct( $alipay_config ) {
        $this->gateway_url = $alipay_config['gateway_url'];
        $this->appid = $alipay_config['app_id'];
        $this->private_key = $alipay_config['merchant_private_key'];
        $this->alipay_public_key = $alipay_config['alipay_public_key'];
        $this->charset = $alipay_config['charset'];
        $this->signtype=$alipay_config['sign_type'];

        if(empty($this->appid)||trim($this->appid)==""){
            throw new Exception("appid should not be NULL!");
        }
        if(empty($this->private_key)||trim($this->private_key)==""){
            throw new Exception("private_key should not be NULL!");
        }
        if(empty($this->alipay_public_key)||trim($this->alipay_public_key)==""){
            throw new Exception("alipay_public_key should not be NULL!");
        }
        if(empty($this->charset)||trim($this->charset)==""){
            throw new Exception("charset should not be NULL!");
        }
        if(empty($this->gateway_url)||trim($this->gateway_url)==""){
            throw new Exception("gateway_url should not be NULL!");
        }

    }
    function AlipayWapPayService($alipay_config) {
        $this->__construct($alipay_config);
    }

    /**
     * alipay.trade.wap.pay
     * @param $biz_content 业务参数。
     * @param $return_url 同步跳转地址，公网可访问
     * @param $notify_url 异步通知地址，公网可以访问
     * @return $response 支付宝返回的信息
    */
    function wapPay($biz_content,$return_url,$notify_url) {
        //打印业务参数
        $this->writeLog($biz_content);

        $request = new AlipayTradeWapPayRequest();

        $request->setNotifyUrl($notify_url);
        $request->setReturnUrl($return_url);
        $request->setBizContent ( $biz_content );

        // 首先调用支付api
        $response = $this->aopclientRequestExecute ($request,true);
        // $response = $response->alipay_trade_wap_pay_response;
        return $response;
    }

    function aopclientRequestExecute($request,$ispage=false) {

        $aop = new AopClient ();
        $aop->gatewayUrl = $this->gateway_url;
        $aop->appId = $this->appid;
        $aop->rsaPrivateKey =  $this->private_key;
        $aop->alipayrsaPublicKey = $this->alipay_public_key;
        $aop->apiVersion ="1.0";
        $aop->postCharset = $this->charset;
        $aop->format= $this->format;
        $aop->signType=$this->signtype;
        // 开启页面信息输出
        $aop->debugInfo=true;
        if($ispage)
        {
            $result = $aop->pageExecute($request,"post");
            // echo $result;
        }
        else
        {
            $result = $aop->Execute($request);
        }

        //打开后，将报文写入log文件
        $this->writeLog("response: ".var_export($result,true));
        return $result;
    }

    public function appPay($biz_content,$notify_url) {
        //打印业务参数
        $this->writeLog($biz_content);

        $request = new AlipayTradeAppPayRequest();

        $request->setNotifyUrl($notify_url);
        $request->setBizContent($biz_content);

        // 首先调用支付api
        $response = $this->aopclientRequestExecuteByApp ($request);
        return $response;
    }


    public function aopclientRequestExecuteByApp($request){

        $aop = new AopClient;
        $aop->gatewayUrl = $this->gateway_url;
        $aop->appId = $this->appid;
        $aop->rsaPrivateKey = $this->private_key;
        $aop->format = $this->format;
        $aop->charset = $this->charset;
        $aop->signType = $this->signtype;
        $aop->alipayrsaPublicKey = $this->alipay_public_key;
        //这里和普通的接口调用不同，使用的是sdkExecute

        $response = $aop->sdkExecute($request);

        //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
        // return htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
        return $response;
    }



    /**
     * alipay.trade.query (统一收单线下交易查询)
     * @param $biz_content 业务参数
     * @return $response 支付宝返回的信息
    */
    function Query($biz_content){
        //打印业务参数
        $this->writeLog($biz_content);
        $request = new AlipayTradeQueryRequest();
        $request->setBizContent ( $biz_content );

        // 首先调用支付api
        $response = $this->aopclientRequestExecute ($request);
        $response = $response->alipay_trade_query_response;
        var_dump($response);
        return $response;
    }

    /**
     * alipay.trade.refund (统一收单交易退款接口)
     * @param $biz_content 业务参数
     * @return $response 支付宝返回的信息
     */
    function Refund($biz_content){
        //打印业务参数
        $this->writeLog($biz_content);
        $request = new AlipayTradeRefundRequest();
        $request->setBizContent ( $biz_content );

        // 首先调用支付api
        $response = $this->aopclientRequestExecute ($request);
        $response = $response->alipay_trade_refund_response;
        var_dump($response);
        return $response;
    }

    /**
     * alipay.trade.close (统一收单交易关闭接口)
     * @param $biz_content 业务参数
     * @return $response 支付宝返回的信息
     */
    function Close($biz_content){
        //打印业务参数
        $this->writeLog($biz_content);
        $request = new AlipayTradeCloseRequest();
        $request->setBizContent ( $biz_content );

        // 首先调用支付api
        $response = $this->aopclientRequestExecute ($request);
        $response = $response->alipay_trade_close_response;
        var_dump($response);
        return $response;
    }

    /**
     * 退款查询   alipay.trade.fastpay.refund.query (统一收单交易退款查询)
     * @param $biz_content 业务参数
     * @return $response 支付宝返回的信息
     */
    function refundQuery($biz_content){
        //打印业务参数
        $this->writeLog($biz_content);
        $request = new AlipayTradeFastpayRefundQueryRequest();
        $request->setBizContent ( $biz_content );

        // 首先调用支付api
        $response = $this->aopclientRequestExecute ($request);
        var_dump($response);
        return $response;
    }
    /**
     * alipay.data.dataservice.bill.downloadurl.query (查询对账单下载地址)
     * @param $biz_content 业务参数
     * @return $response 支付宝返回的信息
     */
    function downloadurlQuery($biz_content){
        //打印业务参数
        $this->writeLog($biz_content);
        $request = new alipaydatadataservicebilldownloadurlqueryRequest();
        $request->setBizContent ( $biz_content );

        // 首先调用支付api
        $response = $this->aopclientRequestExecute ($request);
        $response = $response->alipay_data_dataservice_bill_downloadurl_query_response;
        var_dump($response);
        return $response;
    }

    /**
     * 验签方法
     * @param $arr 验签支付宝返回的信息，使用支付宝公钥。
     * @return boolean
     */
    function check($arr){
        $aop = new AopClient();
        $aop->alipayrsaPublicKey = $this->alipay_public_key;
        $result = $aop->rsaCheckV1($arr, $this->alipay_public_key, $this->signtype);
        return $result;
    }

    //请确保项目文件有可写权限，不然打印不了日志。
    function writeLog($text) {
        // $text=iconv("GBK", "UTF-8//IGNORE", $text);
        //$text = characet ( $text );
        file_put_contents ( __DIR__."/../../../../Application/Runtime/Logs/Payment/alilog.txt", date ( "Y-m-d H:i:s" ) . "  " . $text . "\r\n", FILE_APPEND );
    }


    /** *利用google api生成二维码图片
     * $content：二维码内容参数
     * $size：生成二维码的尺寸，宽度和高度的值
     * $lev：可选参数，纠错等级
     * $margin：生成的二维码离边框的距离
     */
    function create_erweima($content, $size = '200', $lev = 'L', $margin= '0') {
        $content = urlencode($content);
        $image = '<img src="http://chart.apis.google.com/chart?chs='.$size.'x'.$size.'&amp;cht=qr&chld='.$lev.'|'.$margin.'&amp;chl='.$content.'"  widht="'.$size.'" height="'.$size.'" />';
        return $image;
    }
}

?>
