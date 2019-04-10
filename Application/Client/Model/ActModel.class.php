<?php
namespace Client\Model;

class ActModel extends GlobalModel
{
    protected $tableName = TACT; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    public function _initialize() {
        parent::_initialize();
    }

    public function findAct( $column='', $where='', $order='', $group='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->group( $group )
            ->find();

        return $re;
    }

    public function selectAct( $column='', $where='', $order='', $limit='', $group='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->group( $group )
            ->limit( $limit )
            ->select();

        return $re;
    }


    public function getActDetail( $column='', $where='', $order='', $group='' ) {
        $re = $this
            ->alias('a')
            ->field( $column )
            ->join( ' INNER JOIN '.TBACKEND.' b on a.uid = b.uid ' )
            ->where( $where )
            ->order( $order )
            ->group( $group )
            ->find();

        return $re;
    }


    public function selectSign( $column='', $where='', $order='' ) {
        $re = M(TSIGN_O)
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }

    public function findSign( $column='', $where='' ) {
        $re = M(TSIGN_O)
            ->field( $column )
            ->where( $where )
            ->find();

        return $re;
    }


    public function addSign( $d=[] ){
        if( !$d ) return false;

        $re = M(TSIGN_O)
            ->add( $d );

        return $re;
    }

    public function saveSign( $where='', $data='' ){
        $re = M(TSIGN_O)
            ->where( $where )
            ->save( $data );

        return $re;
    }

    public function delSign( $where=[] ){
        $re = M(TSIGN_O)
            ->where( $where )
            ->delete();

        return $re;
    }


}