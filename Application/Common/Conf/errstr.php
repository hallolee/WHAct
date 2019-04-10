<?php

$c[ 'ERRSTR' ] = [
    E_OK        => 'OK',       //成功
    E_SYSTEM    => 'Failed.',       //执行过程中跳出
    E_RUN       => 'Failed',            //执行出错
    E_CONF      => 'Config error',      //配置出错
    E_MYSQL     => 'Sql Service error', //SQL出错
    5      => 'Parameter error',   //参数错误
    E_USER      => 'Invalid user',      //用户不存在
    E_PASS      => 'Password error',    //验证用户密码(登录失败)
    E_TOKEN     => 'Invalid token',     //Token不存在(登录失败)
    E_SESS      => 'Invalid session',                           //session有错(登录失败)
    E_SMSWECHAT => 'Wechat news failed to send',                //发送微信出错
    E_SMSPHONE  => 'SMS verification code failed to send',      //发送短信出错
    E_EXIST     => 'Data already exists',                       //某结果已存在
    E_NOEXIST   => 'Data not exists',                           //某结果不存在
    14        => 'Insufficient permissions',                  //权限不足
    E_STATUS    => 'The status is wrong',                       //所处状态不符
    E_UPLOAD    => 'Upload error',                              //上传文件失败
    E_SMSCODE   => 'SMS verification code error',               //短信验证码错误
    E_SMSCODE2  => 'SMS verification code timeout',             //短信验证码超时或丢失
    E_OPENID    => 'Openid error',                              //微信openid错误
    E_NOCHANGE  => 'No data changes',                           //没有数据变动
    E_DISABLE   => 'Disable login',                             //账号被禁用
    E_INVITE    => 'Invalid invitecode',                        //邀请码错误
    E_NOTENOUGH => 'Not enough',                                //不足、不够、未达标
    E_JSON      => 'Json format error',                         //json 格式错误
];

return $c;
?>
