<?php
namespace Payment\Model;
use Think\Model\RelationModel;
class AlipayNotifyModel extends RelationModel{
        protected $trueTableName = TPAYLOG_ALI; // 与角色多对多关联
        protected $_link = array(

        );

    }
?>
