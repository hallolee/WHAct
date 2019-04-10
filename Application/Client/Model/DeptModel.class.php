<?php
namespace Client\Model;

class DeptModel extends GlobalModel
{
    protected $tableName = TDEPT; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    public function _initialize() {
        parent::_initialize();
    }

    public function findDept( $column='', $where='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->find();

        return $re;
    }

    public function selectDept( $column='', $where='', $order='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }
}
