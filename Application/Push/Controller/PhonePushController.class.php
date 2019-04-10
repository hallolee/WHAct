<?php
namespace Push\Controller;

class PhonePushController extends GlobalController {

    protected $m_m;
    public function __construct() { }

    public function apple( $data=[] ){
        $ret = E_SYSTEM;
        $type = C('APPLE_PUSH_TYPE');

        if( empty( $type ) || $type == APPLE_PUSH_SSL ){
            $ret = $this->appleBySsl( $data );
        }else if( $type == APPLE_PUSH_HTTP2 ){
            $ret = $this->appleByHttp2( $data );
        }

        return $ret;
    }


    public function appleBySsl( $data=[] ){
        $ret = E_SYSTEM;

        $apnsHost = $data['conf']['apns_host'];
        $apnsPort = $data['conf']['apns_port'];
        $apnsCert = $data['conf']['apns_cert'];
        $apnsPass = $data['conf']['apns_pass'];

        $aData = [];
        if( isset( $data['data']['src'] ) && isset( $data['data']['args'] ) ){
            $aData[ 'aps' ] = [
                'alert' => [
                    'loc-key' => (string)$data['data']['src'],  //src loc-key必须是字符串
                    'loc-args' => $data['data']['args']  //约定处理
                ]
            ];
        }else if( isset( $data['data']['message'] ) ){
            //约定处理
            $aData[ 'aps' ] = [
                'alert' => [
                    'title' => isset($data['data']['title'])?$data['data']['title']:'',
                    'body' => isset($data['data']['message'])?$data['data']['message']:''
                ],
                'badge' => isset($data['data']['badge'])?$data['data']['badge']:'',
                'sound' => isset($data['data']['sound'])?$data['data']['sound']:'defalut'
            ];
        }
        if( !$aData ) return $ret;

        $payload = json_encode($aData);

        $streamContext = stream_context_create();
        stream_context_set_option($streamContext, 'ssl', 'local_cert', $apnsCert);

        if( $apnsPass )
            stream_context_set_option($streamContext, 'ssl', 'passphrase', $apnsPass);

        $apns = stream_socket_client(
            'tls://' . $apnsHost . ':' . $apnsPort,
            $err,
            $errstr,
            60,
            STREAM_CLIENT_CONNECT,
            $streamContext
        );
        if (!$apns) {
            \Common\dExp( __FUNCTION__, [ 'err' => $err, 'errstr' => $errstr ] );
            goto END;
        }else{
            $deviceToken = $data['basic']['device_token'];
            $apnsMessage = $this->_getBinaryNotification( $deviceToken, $payload, 12 );

            $result = fwrite($apns, $apnsMessage);
            if ($result) {
                $ret = E_OK;
            }
            fclose($apns);
        }
END:
        return $ret;
    }


    protected function _getBinaryNotification($sDeviceToken, $sPayload, $nMessageID = 0, $nExpire = 30)
    {
        $nTokenLength = strlen($sDeviceToken);
        $nPayloadLength = strlen($sPayload);

        $sRet  = pack('CNNnH*', 1, $nMessageID, $nExpire > 0 ? time() + $nExpire : 0, 32, $sDeviceToken);
        $sRet .= pack('n', $nPayloadLength);
        $sRet .= $sPayload;

        return $sRet;
    }



    public function appleByHttp2( $data=[] ) {
        $ret = [ "status" => E_SYSTEM, "token" => [] ];

        $apnsHost = $data['conf']['apns_host'];
        $apnsPort = $data['conf']['apns_port'];
        $apnsCert = $data['conf']['apns_cert'];
        $apnsPass = $data['conf']['apns_pass'];
        $apnsTopic = $data['conf']['apns_topic'];

        $aData = [];
        if( isset( $data['data']['src'] ) && isset( $data['data']['args'] ) ){
            $aData[ 'aps' ] = [
                'alert' => [
                    'loc-key' => (string)$data['data']['src'],  //src loc-key必须是字符串
                    'loc-args' => $data['data']['args']  //约定处理
                ]
            ];
        }else if( isset( $data['data']['message'] ) ){
            //约定处理
            $aData[ 'aps' ] = [
                'alert' => [
                    'title' => isset($data['data']['title'])?$data['data']['title']:'',
                    'body' => isset($data['data']['message'])?$data['data']['message']:''
                ],
                'badge' => isset($data['data']['badge'])?$data['data']['badge']:'',
                'sound' => isset($data['data']['sound'])?$data['data']['sound']:'defalut'
            ];
        }
        if( !$aData ) return $ret;
        $payload = json_encode($aData);

        $deviceToken = $data['basic']['device_token'];
        if( !is_array( $deviceToken ) ) return $ret;

        $headers = array(
            'apns-topic: '.$apnsTopic,
            // 'apns-priority:10',
            // 'apns-expiration:0'
        );

        $hArr = $hSocket = array();

        if (!defined('CURL_HTTP_VERSION_2_0')) {
            define('CURL_HTTP_VERSION_2_0', 3);
        }

        // 创建批处理cURL句柄
        $mh = curl_multi_init();
        foreach ($deviceToken as $key => $value) {
            $hSocket[$key] = curl_init();

            curl_setopt($hSocket[$key], CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
            curl_setopt($hSocket[$key], CURLOPT_SSLCERT, $apnsCert);
            curl_setopt($hSocket[$key], CURLOPT_SSLCERTPASSWD, $apnsPass);
            curl_setopt($hSocket[$key], CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($hSocket[$key], CURLOPT_TIMEOUT, 30);
            curl_setopt($hSocket[$key], CURLOPT_URL, 'https://'.$apnsHost.':'.$apnsPort.'/3/device/'.$value);
            curl_setopt($hSocket[$key], CURLOPT_POSTFIELDS, $payload);
            curl_setopt($hSocket[$key], CURLOPT_HTTPHEADER, $headers);
            curl_setopt($hSocket[$key], CURLOPT_RETURNTRANSFER, 1);

            if (!$hSocket[$key]) {
                \Common\dExp( __FUNCTION__, [ 'sck' => $hSocket[$key]] );
            } else {
                array_push($hArr, $hSocket[$key]);
                curl_multi_add_handle($mh,$hSocket[$key]);
            }
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        foreach ($hArr as $h) {
            $info = curl_getinfo($h);
            if( $info['http_code'] != 200 ){
                $response_errors = json_decode( curl_multi_getcontent($h), true);
                $error_tokens[ substr( $info['url'], -64) ] = $response_errors['reason']?$response_errors['reason']:'failed';
            }
            curl_multi_remove_handle($mh, $h);
        }

        curl_multi_close($mh);
        $ret['status'] = E_OK;
        if( isset( $error_tokens ) ){
            \Common\dExp( __FUNCTION__, [ 'error_tokens' => $error_tokens ] );
            $ret['token'] = $error_tokens;
        }
END:
        return $ret;
    }


}
