<?php
namespace Payment\Model;
use Think\Model\RelationModel;
class WepayNotifyModel extends RelationModel{
        protected $trueTableName = TPAYLOG_WECHAT; // 与角色多对多关联
        protected $_link = array(

        );

    }
?>
