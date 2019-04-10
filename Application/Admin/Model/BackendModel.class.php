<?php
namespace Admin\Model;

class BackendModel extends GlobalModel
{
    protected $tableName = TBACKEND; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    public function _initialize() {
        parent::_initialize();
    }


    public function findUser( $column='', $where='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->find();

        return $re;
    }

    public function selectUser( $column='', $where='', $order='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }


    public function addUser( $data='' ){
        $re = $this
            ->add( $data );

        return $re;
    }


    public function saveUser( $where='', $data='' ){
        $re = $this
            ->where( $where )
            ->save( $data );

        return $re;
    }

    public function delUser( $where='' ){
        $re = $this
            ->where( $where )
            ->delete();

        return $re;
    }



    public function listUser( $column='', $where='', $order='', $limit='0,10' ) {
        $re = $this
            ->alias('a')
            ->field( $column )
            ->join( ' LEFT JOIN '.TAUTH_GROUP_ACC.' b on a.uid = b.uid ' )
            ->join( 'LEFT JOIN '.TAUTH_GROUP.' c on b.group_id = c.id ' )
            ->where( $where )
            ->order( $order )
            ->limit( $limit )
            ->select();

        return $re;
    }


    public function getUser( $column='', $where='' ) {
        $re = $this
            ->alias('a')
            ->field( $column )
            ->join( ' LEFT JOIN '.TAUTH_GROUP_ACC.' b on a.uid = b.uid ' )
            ->where( $where )
            ->find();

        return $re;
    }


}
?>