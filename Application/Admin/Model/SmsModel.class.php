<?php
namespace Admin\Model;
use Think\Model;
class SmsModel extends Model
{
    protected $_auto = [
        ['created_at','YmdHis',1,'function']
    ];
    protected  $_validate = array(   //自动验证
        ['app_name','require','短信接口名不能为空',1],
        ['app_key','require','短信接口key不为空',1],
        ['app_secret','require','短信接口秘钥不为空',1],
        ['mode_code','require','短信接口模板号不为空',1],
    );
    public static function getSms($app_name=null,$page=1,$limit=10)
    {
        if($app_name){
            return D('sms')->where("app_name like '%".$app_name."%'")->page($page,$limit)->select();
        }
        return D('sms')->page($page,$limit)->select();
    }
    public static function getInfo($id)
    {
        return D('sms')->where("id='".$id."'")->find();
    }
}