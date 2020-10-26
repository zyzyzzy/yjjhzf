<?php
namespace Admin\Model;



use Think\Model\RelationModel;

class UserpayapifeeModel extends RelationModel
{
    protected $_link =[
        "userpayapiclass" => [
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'userpayapiclass',
            'foreign_key' => 'userpayapiclassid',
            'relation_deep'=> ['user','payapi']

        ],

    ];
    protected  $_validate = array(   //自动验证
        ['rate','number','费率必须为数字格式',1],
    );
    public static function getUserFees($userid,$payapiid=null)
    {
       $userpayapiclassIds = UserpayapiclassModel::getIdsByUserid($userid);
        return D("userpayapifee")->relation(true)->where(['userpayapiclassid'=>['in',$userpayapiclassIds]])->select();
    }

    public static function getFee($id)
    {
        return D("userpayapifee")->relation(true)->where("id='".$id."'")->find();
    }
}