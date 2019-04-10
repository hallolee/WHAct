<?php
namespace Common\Model;
use Think\Model;

class CommonModel extends Model
{
    public function _initialize(){
        
    }

    /*
    * 暂只支持 简单条件 如：id in (1,2,3,..), 同时修改多个字段数据
    * @ $db 数据库名
    * @ $table 数据表名
    * @ $d 数据
    * @ $d['key']  case 字段名  key
    * @ $d['column']  set 字段名 [ '1', '2' ]
    * @ $d['d'] 需要修改的数据 [ [ $d['key'] => $key_v, $d['column'][1] => $col_v1, $d['column'][2] => $col_v2, ... ], ... ]
    * set $d['column'][1] = case $d['key']
    */
    public function SaveAllSql( $d=[], $table='', $db='' ){
        //未指定使用默认数据库
        if( !$db ) $db = C('DB_NAME');
        //未指定使用引用类的配置
        if( !$table ) $table = $this->tableName;

        //拼接 sql 语句
        $sql = ' update ';

        $sql.= $db.'.'.$table.' SET ';

        $updatekey = $d['key'];
        $column = $d['column'];
        $data = $d['d'];

        // 抽出 where 条件数据
        foreach ($data as $v) {
            $updatekeyval[] = $v[ $updatekey ];
        }
        // 根据 column 字段，将数据整理拼接
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

