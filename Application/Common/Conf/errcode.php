<?php
$c[ 'ERRCODE_COMMON' ] = [
    0   => 'OK',                                        //成功
    1   => 'Failed',                                    //执行过程中跳出
    2   => 'Failed',                                    //执行出错
    3   => 'Config error',                              //配置出错
    4   => 'Sql Service error',                         //SQL出错
    5   => 'Parameter error',                           //参数错误
    6   => 'Invalid user',                              //用户不存在
    7   => 'Password error',                            //验证用户密码(登录失败)
    8   => 'Invalid token',                             //Token不存在(登录失败)
    9   => 'Invalid session',                           //session有错(登录失败)
    10  => 'Wechat news failed to send',                //发送微信出错
    11  => 'SMS verification code failed to send',      //发送短信出错
    12  => 'Data already exists',                       //某结果已存在
    13  => 'Data not exists',                           //某结果不存在
    14  => 'Insufficient permissions',                  //权限不足
    15  => 'The status is wrong',                       //所处状态不符
    16  => 'Upload error',                              //上传文件失败
    17  => 'SMS verification code error',               //短信验证码错误
    18  => 'SMS verification code timeout',             //短信验证码超时或丢失
    19  => 'Openid error',                              //微信openid错误
    20  => 'No data changes',                           //没有数据变动
    21  => 'Disable login',                             //账号被禁用
    22  => 'Invalid invitecode',                        //邀请码错误
    23  => 'Not enough',                                //不足、不够、未达标
    24  => 'Json format error',                         //json 格式错误
    25  => 'Shell doing, do not operate',               //操作未完成，用于脚本
    26  => 'the value has reached the upper limit',     //达到上限
    27  => 'Get wechat order failed',                   // 获取微信订单结果失败
    28  => 'wechat param error',                        //微信平台参数错误

    100 => 'Some param are redundant',                  // 含多余参数
    101 => 'Some param is required',                    // 缺少必须字段
    102 => 'Some param is string, not other',           // 部分字段类型应为 string
    103 => 'Some param is integer, not other',          // 部分字段类型应为 int
    104 => 'Some param is number, not other',           // 部分字段类型应为 number
    105 => 'Some param is object, not other',           // 部分字段类型应为 object
    106 => 'Some param is array, not other',            // 部分字段类型应为 array
    107 => 'Must be at least minimum',                  // 不小于 最小值
    108 => 'Must be at most maximum',                   // 不大于 最大值
    109 => 'Must be a multiple of x',                   // 必须是x的倍数
    110 => 'Some param is error',                       // 部分参数错误


    20001 => 'user not exist',                       // 用户不存在
    20002 => 'no access',                       // 部分参数错误
    20003 => 'status wrong',                       // 状态错误
    20012 => 'user exists',                       // 账号已存在
    20013 => 'phone exists',                       // 手机号已存在
    20020 => 'department not exist',                       // 部门不存在
    20021 => 'firm not exist',                       // 企业不存在
    41030 => 'page not exist',                       // 所传page页面不存在，或者小程序没有发布
    45009 => 'too frequent',                       // 调用分钟频率受限(目前5000次/分钟，会调整)，如需大量小程序码，建议预生成
    45029 => 'too frequent',                       // 生成码个数总和到达最大个数限制


];

$c[ 'ERRCODE_WK' ] = [
    10001 => 'Set to display authorization, but actually silent authorization',   // 设置的是显示授权，实际却是静默授权
    10002 => 'The unionid is turned on, but it is not actually obtained',          // 开启了 unionid，实际却没获取到
    10003 => 'Wechet into decode failed',          // 微信步数解码失败

    10007 => 'User not exists',          // 用户不存在

    10020 => 'Outside the sign range',          // 在签到范围之外
    10021 => 'Activity does not need to sign in',          // 活动无需签到
    10022 => 'User is not participating in this activity',          // 用户未参与此活动
];


$c[ 'ERRCODE_HL' ] = [];


$c[ 'ERRCODE' ] = $c['ERRCODE_COMMON'] + $c['ERRCODE_WK'] + $c['ERRCODE_HL'];


$c[ 'PARAM_JSON_ERRCODE' ] = [
    0 => 'PARAM_OK',
    1 => 'UPLOAD_FILES',
    2 => 'JSON_DECODE_ERR',
];

return $c;