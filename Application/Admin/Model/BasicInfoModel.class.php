<?php
namespace Admin\Model;

class BasicInfoModel extends GlobalModel
{
    protected $tableName = TBASIC_INFO; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    public function _initialize() {
        parent::_initialize();
    }


    public function selecBasicInfo( $column='', $where='', $order='' ){
        $re = $this
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }

    public function findBasicInfo( $column='', $where='' ){
        $re = $this
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->find();

        return $re;
    }

    public function addBasicInfo( $data='' ){
        $re = $this
            ->add( $data );

        return $re;
    }

    public function editBasicInfo( $where='', $data='' ){
        $re = $this
            ->where( $where )
            ->save( $data );

        return $re;
    }

    public function delBasicInfo( $where='' ){
        $re = $this
            ->where( $where )
            ->delete();

        return $re;
    }



}