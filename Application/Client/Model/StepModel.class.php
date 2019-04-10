<?php
namespace Client\Model;

class StepModel extends GlobalModel
{
    protected $tableName = TSTEP; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    public function _initialize() {
        parent::_initialize();
    }

    public function findStep( $column='', $where='', $order='', $group='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->group( $group )
            ->find();

        return $re;
    }

    public function selectStep( $column='', $where='', $order='', $limit='', $group='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->group( $group )
            ->limit( $limit )
            ->select();

        return $re;
    }

    public function saveStep( $where='', $data='' ) {
        $re = $this
            ->where( $where )
            ->save( $data );

        return $re;
    }

    public function setStepRank( $d=[] ){
        if( !$d || !$d['d'] ) return ;

        $sql = $this->SaveAllSql( $d, TSTEP );
        $re = M(TSTEP)->execute($sql);

        return $re;
    }

    public function setStepInc( $where='', $column='', $num=1 ) {
        $re = $this
            ->where( $where )
            ->setInc( $column, $num );

        return $re;
    }

    public function setStepDec( $where='', $column='', $num=1 ) {
        $re = $this
            ->where( $where )
            ->setDec( $column, $num );

        return $re;
    }

    public function addStep( $data='' ) {
        $re = $this
            ->add( $data );

        return $re;
    }


    public function addAllStep( $data=[] ) {
        $re = $this
            ->addAll( $data );

        return $re;
    }

    public function getStepRank( $column='', $where_table='', $where='', $order='' ){
        $table = M()
            ->table( TSTEP.',( SELECT (@ranknum := 0) ) b' )
            ->field( '*,(@ranknum :=@ranknum + 1) ranking' )
            ->where( $where_table )
            ->order( $order )
            ->BuildSql();

        $re = M()
            ->alias('a')
            ->table( $table )
            ->field( $column )
            ->where( $where )
            ->find();

        return $re;
    }

    public function getUserStepRank( $column='', $where_table='', $where='', $order='', $limit='', $group='' ){

        $table = M()
            ->table( TSTEP.',( SELECT (@ranknum := 0) ) b' )
            ->field( '*,(@ranknum :=@ranknum + 1) ranking' )
            ->where( $where_table )
            ->order( $order )
            ->BuildSql();

        $re = M()
            ->alias('a')
            ->table( $table )
            ->field( $column )
            ->join( ' INNER JOIN '.TCLIENT.' b on a.uid = b.uid ' )
            ->where( $where )
            ->group( $group )
            ->limit( $limit )
            ->select();

        return $re;
    }


    public function getUserStep( $column='', $where='', $order='', $limit='', $group='' ) {
        $re = $this
            ->alias('a')
            ->field( $column )
            ->join( ' INNER JOIN '.TCLIENT.' b on a.uid = b.uid ' )
            ->join( ' LEFT JOIN '.TFIRM.' d on b.fid = d.id ' )
            ->join( ' LEFT JOIN '.TDEPT.' e on b.dept_id = e.id ' )
            ->where( $where )
            ->order( $order )
            ->group( $group )
            ->limit( $limit )
            ->select();

        return $re;
    }


}
