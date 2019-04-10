<?php
namespace VCmd\Model;

class IndexModel extends GlobalModel{

    protected $tableName = TBASIC_INFO;

    public function _initialize() {}

    public function selectBasicInfo( $column='', $where='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->select();

        return $re;
    }

    /*
    * act
    */
    public function findAct( $column='', $where='', $order='', $group='' ) {
        $re = M(TACT)
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->group( $group )
            ->find();

        return $re;
    }

    public function selectAct( $column='', $where='', $order='', $limit='', $group='' ) {
        $re = M(TACT)
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->group( $group )
            ->limit( $limit )
            ->select();

        return $re;
    }

    public function setAct( $d=[] ){
        if( !$d['d'] ) return ;

        $sql = $this->SaveAllSql( $d, TACT );
        $re = M(TACT)->execute($sql);

        return $re;
    }


}
?>