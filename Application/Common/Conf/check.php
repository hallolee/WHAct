<?php

/*
* @ 项目模块检查 token 配置
*/

// 不检查登录模式的模块(CONTROLLER)
$c[ 'TOKEN_NOCHK_CONTROLLER' ]  = [
    'Client' => [
        'Index'     => false,
        'LoginReg'  => false,
        'Profile'   => true,
    ],
    'Admin' => [
        'File'  => false,
        'Login'  => false,
        'Filecc'  => false,
    ]
];

// 不检查登录模式的方法
$c[ 'TOKEN_NOCHK_ACTION' ] = [
    // 'Client/LoginReg/logout',
];

// 不检查 JSON 格式的方法
// 同时，通过 值 指定替代的获取参数方式
// 值 目前支持:'get' 'post' 'put' 'param'(千万别写错,否则后果未知）
$c[ 'PARAM_NOCHK_ACTION' ] = [
    'Payment/WepayApi/payNotify'    => 'post',
    'Payment/AlipayApi/payNotify'   => 'post'
    // 'Client/LoginReg/logout' => 'post',
];

return $c;
?>
