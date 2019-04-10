<?php

//Token写入方式参数
const TOKEN_APPEND = 1;
const TOKEN_COVER = 2;

//Token读取验证模式参数
const TOKEN_FULLCHECK = 1;
const TOKEN_NOCHECK = 2;

//Token 缓存参数
const TOKEN_TYPE_FILE = 1;
const TOKEN_TYPE_REDIS = 2;

// wechat authorized 授权方式
const WX_DISPLAY  = 1;
const WX_QUIET    = 2;


// wechat unionid 是否记录的状态
const COL_OPEN  = 1;
const COL_CLOSE = 2;

// action status
const ACTION_LOG = 1;
const ACTION_REG = 2;

// apple push type
const APPLE_PUSH_SSL   = 1;
const APPLE_PUSH_HTTP2 = 2;

//注册方式
const REG_PASSWD    = 1;    //账号密码
const REG_WECHAT    = 2;    //wechat openid
const REG_PHONE     = 3;    //手机验证码
const REG_WECHAT_MP = 4;    //wechat mini program/game


//数据表常量
const TACT              = 'act';
const TSIGN_O           = 'act_sign_order';
const TACT_STEP         = 'act_step';

const TAUTH_RULE            = 'auth_rule';
const TAUTH_GROUP           = 'auth_group';
const TAUTH_GROUP_ACC       = 'auth_group_access';

const TBASIC_INFO       = 'basic_info';

const TFIRM             = 'firm';
const TDEPT             = 'department';

const TBACKEND      = 'user_backend';
const TCLIENT       = 'user_client';

const TSTEP         = 'user_step';


// 通用状态
const S_DEFAULT  = 0;    // 默认
const S_TRUE     = 1;    // 是
const S_FALSE   = 2;    // 否


// sms push type -- 业务区分
const SMS_COMMON        = 0;
const SMS_REG           = 1;
const SMS_LOGIN         = 2;
const SMS_REPASS        = 3;
const SMS_USERINFO      = 4;


// act status
const AS_INIT           = 1;
const AS_DOING          = 2;
const AS_END            = 3;


// rank type
const RANK_ALL           = 1;   // 全部
const RANK_FIRM          = 2;   // 公司
const RANK_DEPT          = 3;   // 部门