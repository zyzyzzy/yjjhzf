<?php

namespace Admin\Model;

use Think\Model;

class DomainModel extends Model
{


    //2019-3-29 任梦龙：修改
    protected $_auto = array(

        array('created_at', 'YmdHis', 1, 'function'),

    );


    protected $_validate = array(   //自动验证

        array('userid', 'require', '用户不能为空！', 1),
        array('domain', 'require', '域名不能为空！', 1),
        array('domain', 'regDomain', '域名或者ip格式错误', 1, 'function'),
        // array('domain','#(((\d{1,2})|(1\d{1,2})|(2[0-4]\d)|(25[0-5]))\.){3}((\d{1,2})|(1\d{1,2})|(2[0-4]\d)|(25[0-5]))(:\d{0,9})?#','域名或者ip格式错误',1,'regex'),
        // array('domain','#^((http://)|(https://))?([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}(:\d{0,9})?#','域名格式错误',1,'regex'),

    );

    //2019-3-29 任梦龙：修改
    public static function delDomain($id)
    {
        return M('domain')->where(['id' => $id])->delete();
    }

    public static function getDomains($userid)
    {
        return M('domain')->where('userid=' . $userid)->select();
    }

    public static function addDomain($domain, $userid)
    {
        $domainid = M('domain')->add([
            'domain' => $domain,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return M('userdomain')->add([
            'domainid' => $domainid,
            'userid' => $userid,
        ]);
    }

    //2019-3-29 任梦龙：根据id获取域名
    public static function getDomain($id)
    {
        return M('domain')->where(['id' => $id])->getField('domain');
    }


}

