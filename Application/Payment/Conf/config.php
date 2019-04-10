<?php

$c[ 'PAY_NOTIFY_CALLBACK' ] = [
    'Client' => [
        1 => [
            'type'   => 'func',
            'model'  => 'Client/Order',
            'method' => 'afterWeChatPaidToRecharge',
            'logpath' => 'Client/wepay_recharge.err',
        ],
        2 => [
            'type'   => 'func',
            'model'  => 'Client/Order',
            'method' => 'afterAliPaidToRecharge',
            'logpath' => 'Client/alipay_recharge.err',
        ],
    ]
];

return $c;
?>
