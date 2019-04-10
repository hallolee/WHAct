<?php
namespace Client\Model;

class ProfileModel extends GlobalModel
{
    protected $tableName = TCLIENT; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    public function _initialize() {
        parent::_initialize();
    }


    public function findClient( $column='', $where='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->find();

        return $re;
    }

    public function selectClient( $column='', $where='', $order='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }

    public function getClient( $column='', $where='', $order='' ){
        $re = $this
            ->alias('a')
            ->field( $column )
            ->join( ' LEFT JOIN '.TFIRM.' b on a.fid = b.id ' )
            ->join( ' LEFT JOIN '.TDEPT.' c on a.dept_id = c.id ' )
            ->where( $where )
            ->order( $order )
            ->find();

        return $re;
    }

    public function saveClient( $where='', $data='' ){
        $re = $this
            ->where( $where )
            ->save( $data );

        return $re;
    }

    public function addClient( $d=[] ){
        list( $user, $inviteuser ) = $d;
        $t = time();
        $re = [];

        $m = M();
        $m->startTrans();

        //添加用户
        $user['atime'] = $t;
        $add_user_res = $m
            ->table(TCLIENT)
            ->add( $user );

        // 生成用户自己的邀请码
        $invitecode = 'c'.rand(10000, 99999).$add_user_res;
        $edit_invitecode_res = $m
            ->table(TCLIENT)
            ->where([ 'uid' => $add_user_res ])
            ->save( [ 'invitecode' => $invitecode ] );

        if( !$add_user_res || !$edit_invitecode_res ){
            $m->rollback();
        }else{
            $m->commit();
            $re['uid'] = $add_user_res;
            $re['invitecode'] = $invitecode;
        }

        return $re;
    }



    public function findFirm( $column='', $where='' ) {
        $re = M(TFIRM)
            ->field( $column )
            ->where( $where )
            ->find();

        return $re;
    }

    public function selectFirm( $column='', $where='', $order='' ) {
        $re = M(TFIRM)
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }



    public function findDept( $column='', $where='' ) {
        $re = M(TDEPT)
            ->field( $column )
            ->where( $where )
            ->find();

        return $re;
    }

    public function selectDept( $column='', $where='', $order='' ) {
        $re = M(TDEPT)
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->select();

        return $re;
    }

}
?>