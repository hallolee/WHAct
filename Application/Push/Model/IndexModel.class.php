<?php
namespace VCmd\Model;

class IndexModel extends GlobalModel{

    protected $tableName = TBASIC_INFO;

    public function _initialize() {}

    public function getBasicInfo( $module='' ){
        static $_basic = [];    // 保存已获取的basic_info 信息

        if( isset( $_basic[ $module ] ) ){
            return $_basic[ $module ];
        }

        // 获取相应的basic_info 信息
        $basic_res = $this->field('field,value')->where( ['module' => $module] )->select();
        $basic = [];
        foreach ($basic_res as $val) {
            $basic[ $val['field'] ] = $val['value'];
        }

        $_basic[ $module ] = $basic;

        return $basic;
    }

    public function selectBasicInfo( $column='', $where='' ) {
        $re = $this
            ->field( $column )
            ->where( $where )
            ->select();

        return $re;
    }

    public function selectQBOrder( $column='', $where='', $order='', $limit='' ) {
        $re = M( TQB_O )
            ->field( $column )
            ->where( $where )
            ->order( $order )
            ->limit( $limit )
            ->select();

        return $re;
    }

    public function saveQBOrder( $where='', $data='' ) {
        $re = M( TQB_O )
            ->where( $where )
            ->save( $data );

        return $re;
    }

}
?>