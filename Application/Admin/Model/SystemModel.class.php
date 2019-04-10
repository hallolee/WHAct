<?php
namespace Admin\Model;

class SystemModel extends GlobalModel
{
    protected $tableName = TLEVEL; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    //level
    public function addLevel($data)
    {
        $re = M(TLEVEL)
            ->add($data);

        return $re;
    }

    public function editLevel($data, $where)
    {
        $re = M(TLEVEL)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function findLevel($column = '',$where = '', $order = '')
    {
        $re = M(TLEVEL)
            ->field($column)
            ->where($where)
            ->order($order)
            ->find();

        return $re;
    }

    public function delLevel($where)
    {
        $re = M(TLEVEL)
            ->where($where)
            ->delete();

        return $re;
    }

    public function selectLevel($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M(TLEVEL)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

}