<?php
namespace User\Model;

use Think\Model;
//整Model   2019-1-3 任梦龙创建
class UserloginModel extends Model
{
    //自动验证
    protected  $_validate = array(
        ['username','require','用户名称不能为空',1],
        ['loginpassword','require','密码不能为空',1],
    );

}