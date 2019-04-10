<?php
namespace VCmd\Model;
use Think\Model;
class GlobalModel extends Model{

    public function _initialize() {}


    /*
    * @ $db 数据库名
    * @ $table 数据表名
    * @ $d 数据
    * @ $d['key']  case 字段名  key
    * @ $d['column']  set 字段名 [ '1', '2' ]
    * @ $d['d'] 需要修改的数据 [ [ $d['key'] => $key_v, $d['column'][1] => $col_v1, $d['column'][2] => $col_v2, ... ], ... ]
    * set $d['column'][1] = case $d['key']
    */
    public function SaveAllSql( $d=[], $table='', $db='' ){

        if( !$db ) $db = C('DB_NAME');
        if( !$table ) $table = $this->tableName;

        $sql = ' update ';

        $sql.= $db.'.'.$table.' SET ';

        $updatekey = $d['key'];
        $column = $d['column'];
        $data = $d['d'];

        foreach ($data as $v) {
            $updatekeyval[] = $v[ $updatekey ];
        }


        foreach ($column as $val) {
            $sql.= ' `'.$val.'` = CASE `'.$updatekey.'`';
            foreach ($data as $v) {
                if( isset( $v[ $val ] ) ){
                    $sql.= ' WHEN \''.$v[ $updatekey ].'\' THEN \''.$v[ $val ].'\'';
                }
            }
            $sql.= ' END,';
        }

        $sql = substr($sql,0,strlen($sql)-1);

        $sql.= ' WHERE `'.$updatekey.'` IN ('.implode(',', $updatekeyval).')';

        return $sql;
    }





}