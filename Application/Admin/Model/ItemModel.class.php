<?php
namespace Admin\Model;

class ItemModel extends GlobalModel
{
    protected $tableName = TACT; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    //item
    public function addItem($data)
    {
        $re = M('item')
            ->add($data);

        return $re;
    }


    public function findItem($column = '' ,$where = '')
    {
        $re = M('item')
            ->field($column)
            ->where($where)
            ->find();
        return $re;
    }

    public function editItem($data, $where)
    {
        $re = M('item')
            ->where($where)
            ->save($data);

        return $re;
    }

    public function delItem($where)
    {
        $re = M('item')
            ->where($where)
            ->delete();

        return $re;
    }

    public function selectItem($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M('item')
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function selectItemDetail($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M('item_detail')
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

    public function addItemDetail($data)
    {
        $re = M('item_detail')
            ->add($data);

        return $re;
    }

    public function findItemDetail($column = '' ,$where = '')
    {
        $re = M('item_detail')
            ->field($column)
            ->where($where)
            ->find();
        return $re;
    }

    public function addUser($data)
    {
        $re = M('user')
            ->add($data);

        return $re;
    }
    public function addClient($data)
    {
        $re = M('user')
            ->add($data);

        return $re;
    }

    public function findClient($column = '', $where = '')
    {
        $re = M('user')
            ->field($column)
            ->where($where)
            ->find();
        return $re;
    }
    public function saveClient($data = '', $where = '')
    {
        $re = M('user')
            ->where($where)
            ->save($data);
        return $re;
    }

}