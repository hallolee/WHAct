<?php
namespace Admin\Model;

class QaModel extends GlobalModel
{
    protected $tableName = TQA_QUES; //配置表名，默认与模型名相同，若不同，可通过此来进行设置

    //level
    public function addQuestion($data)
    {
        $re = M(TQA_QUES)
            ->add($data);

        return $re;
    }

    public function editQuestion($data, $where)
    {
        $re = M(TQA_QUES)
            ->where($where)
            ->save($data);

        return $re;
    }

    public function findQuestion($column = '',$where = '', $order = '')
    {
        $re = M(TQA_QUES)
            ->field($column)
            ->where($where)
            ->order($order)
            ->find();

        return $re;
    }

    public function delQuestion($where)
    {
        $re = M(TQA_QUES)
            ->where($where)
            ->delete();

        return $re;
    }

    public function selectQuestion($column = '', $where = '', $limit = '', $order = '')
    {
        $re = M(TQA_QUES)
            ->field($column)
            ->where($where)
            ->limit($limit)
            ->order($order)
            ->select();

        return $re;
    }

}