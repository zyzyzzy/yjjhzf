<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/1/24/024
 * Time: 上午 11:21
 * 用户其他设置表的模型
 */

namespace Admin\Model;

use Think\Model;

class UserothersetingModel extends Model
{
    //自动验证
    protected $_validate = array(
        array('login_count', 'require', '限制次数不能为空！', 0),
        array('login_count', 'loginCount', '限制次数应为正整数！', 0, 'callback', 1),
        array('login_count', 'countRange', '限制次数范围在3-10之间！', 0, 'callback', 1),
        array('set_time', 'require', '等待时间不能为空！', 0),
    );

    /**
     * 验证限制次数
     */
    protected function loginCount($count)
    {
        if (!preg_match("/^[1-9][0-9]*$/", $count)) {
            return false;
        }
        return true;
    }

    /**
     * 限制次数的范围
     */
    protected function countRange($login_count)
    {
        if ($login_count < 3 || $login_count > 11) {
            return false;
        }
        return true;
    }

    /**
     * 查询数据
     */
    public static function getOtherInfo()
    {
        return M('userotherseting')->order('id DESC')->limit(1)->select();
    }

    /**
     * 修改登录设置
     * @param $tablename
     * @param $info
     * @return array
     */
    public static function saveLoginSeting($tablename, $info)
    {
        $tableobj = D($tablename);
        $return = [];
        if (!$tableobj->create()) {
            $return["status"] = 'no';
            $return["msg"] = $tableobj->getError();
        } else {
            $set_name = '';
            $time = $info['set_time'] / 60;
            switch ($time) {
                case 5:
                    $set_name = '5分钟';
                    break;
                case 10;
                    $set_name = '10分钟';
                    break;
                case 30;
                    $set_name = '30分钟';
                    break;
                case 60;
                    $set_name = '1小时';
                    break;
                case 180;
                    $set_name = '3小时';
                    break;
                default;
                    $set_name = '24小时';
                    break;
            }
            $data = [
                'login_count' => $info['login_count'],
                'set_time' => $info['set_time'],
                'set_name' => $set_name
            ];
            $find = $tableobj->select();
            if ($find) {
                $res = $tableobj->where('id=' . $find[0]['id'])->save($data);
            } else {
                $res = $tableobj->add($data);
            }

            //响应状态码
            if ($res) {
                $return["status"] = "ok";
                $return["msg"] = '修改成功';
            } else {
                $return["status"] = "no";
                $return["msg"] = '修改失败,请稍后重试！';
            }
        }
        return $return;
    }

}