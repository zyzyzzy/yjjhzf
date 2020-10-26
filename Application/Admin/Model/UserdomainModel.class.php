<?php

namespace Admin\Model;

use Think\Model;

class UserdomainModel extends Model{


    protected $_auto = array(

        array('created_at','time',1,'function'),

        array('updated_at','getTime',1,'callback'),

    );



    protected  $_validate = array(   //自动验证

        array('userid','require','用户不能为空！',1),

        array('domain','require','域名不能为空！',1),

    );

    protected function getTime(){
        return date('Y-m-d H:i:s');
    }

    /**
     * @param $userid
     * @return mixed
     */
    public static function getDomainIds($userid)
    {
        return  M('userdomain')->where("userid='".$userid."'")->getField('domainid',true);
    }

    public static function getDomains($userid)
    {
        $domainIds =static::getDomainIds($userid);
        $domains =[];
        if(count($domainIds)>0){
           foreach($domainIds as $domainId){
             $domains[]=static::getDomain($domainId);
           }
        }
        return $domains;
    }

    public function getDomain($id)
    {
        return M('domain')->find($id);
    }

    public static function delDomain($domainid,$userid)
    {
        return M('userdomain')->where("userid='".$userid."' and domainid='".$domainid."'")->delete();
    }

    public static function addDomain($domain,$userid)
    {
        $domainid = M('domain')->add([
            'domain'=>$domain,
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s'),
        ]);
        return M('userdomain')->add([
            'domainid'=>$domainid,
            'userid'=>$userid,
        ]);
    }

}

