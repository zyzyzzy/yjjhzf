<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/1/24/024
 * Time: 下午 1:11
 * 会员设置控制器
 */

namespace Admin\Controller;

use Admin\Model\UserothersetingModel;  //2019-1-24 任梦龙：添加模型

class UserSetingController extends CommonController
{
    /**
     * 2019-1-24 任梦龙：添加会员设置：指一些零碎的设置页面
     */
    public function setList()
    {
        $this->display();
    }

    /**
     * 2019-1-24 任梦龙：登陆次数限制页面
     */
    public function loginSeting()
    {
        $find = UserothersetingModel::getOtherInfo();
        if ($find) {
            $this->assign('login_count', $find[0]['login_count']);
            $this->assign('set_time', $find[0]['set_time']);
            $this->assign('exist', 1);
        }
        $this->display();
    }

    /**
     * 2019-1-24 任梦龙：修改登陆次数
     */
    public function editLoginSeting()
    {
        $info = I('post.');
        $res = UserothersetingModel::saveLoginSeting('userotherseting', $info);
        $this->ajaxReturn($res);
    }
}