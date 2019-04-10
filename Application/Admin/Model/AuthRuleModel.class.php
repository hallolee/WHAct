<?php
namespace Admin\Model;

class AuthRuleModel extends GlobalModel{

    protected $tableName = TAUTH_RULE; //配置具体权限表名，默认与模型名相同，若不同，可通过此来进行设置
    protected $auth_group_access;         //配置用户对应角色组表名，
    protected $auth_group;               //配置角色组表名

    public function _initialize(){
        parent::_initialize();
        $this->auth_group_access = M( TAUTH_GROUP_ACC );
        $this->auth_group = M( TAUTH_GROUP );
    }

    public function selectAuthRule( $column='', $where='', $order='' ){
        return $this
                ->where($where)
                ->field($column)
                ->order($order)
                ->select();
    }

    public function selectAuthGroupAccess( $column='', $where='', $order='' ){
        return $this->auth_group_access
                ->where($where)
                ->field($column)
                ->order($order)
                ->select();
    }

    public function selectAuthGroup( $column='', $where='', $order='', $limit='' ){
        return $this->auth_group
                ->where($where)
                ->field($column)
                ->order($order)
                ->limit($limit)
                ->select();
    }

    public function findAuthRule( $column='', $where='' ){
        return $this
                ->where($where)
                ->field($column)
                ->find();
    }

    public function findAuthGroupAccess( $column='', $where='' ){
        return $this->auth_group_access
                ->where($where)
                ->field($column)
                ->find();
    }

    public function findAuthGroupAccessWithGroup( $column='', $where='' ){
        return $this->auth_group_access
            ->alias('a')
            ->join('INNER JOIN '.TAUTH_GROUP.' b on b.id = a.group_id')
            ->where($where)
            ->field($column)
            ->find();
    }

    public function findAuthGroup( $column='', $where='' ){
        return $this->auth_group
                ->where($where)
                ->field($column)
                ->find();
    }

    public function saveAuthRule( $where='', $data='' ){
        return $this
                ->where($where)
                ->save($data);
    }

    public function saveAuthGroupAccess( $where='', $data='' ){
        return $this->auth_group_access
                ->where($where)
                ->save($data);
    }

    public function saveAuthGroup( $where='', $data='' ){
        return $this->auth_group
                ->where($where)
                ->save($data);
    }

    public function addAuthGroupAccess( $data='' ){
        return $this->auth_group_access
                ->data($data)
                ->add();
    }

    public function addAuthGroup( $data='' ){
        return $this->auth_group
                ->data($data)
                ->add();
    }

    public function delAuthGroup( $where='' ){
        return $this->auth_group
                ->where($where)
                ->delete();
    }

    public function delAuthGroupAccess( $where ){
        return $this->auth_group_access
            ->where($where)
            ->delete();
    }

    public function addAuthGroupAccessAll( $data='' ){
        return $this->auth_group_access
            ->addAll($data);
    }

}





?>
