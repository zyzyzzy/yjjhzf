<?php
/**
 * 接口版本设置模型
 */

namespace Admin\Model;

use Think\Model;

class VersionModel extends Model
{
    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [
                "id"
                , "numberstr"
                , "bieming"
                , "actionname"
                , "status"
                , "date_time"
                , "all_use"   //通用开关
            ],
        ),
    );
    //自动完成
    protected $_auto = array(
        ['date_time', 'YmdHis', 1, 'function'],
    );
    //自动验证
    //2019-4-18 rml：修改验证唯一性
    protected $_validate = array(
        //2019-2-25 任梦龙:将版本号修改为接口版本号
        ['numberstr', 'require', '接口版本号不能为空', 0],
        ['numberstr', 'checkName', '接口版本号已被注册！', 0, 'callback', 3],
        ['numberstr', '2,100', '接口版本号在2-100字符之间！', 1, 'length', 3],
        ['bieming', 'require', '接口版本别名不能为空', 0],
        ['bieming', '2,100', '接口版本别名在2-100字符之间！', 1, 'length', 3],
        ['actionname', 'require', '控制器名称不能为空', 0],
        ['actionname', 'checkAction', '控制器名称已存在！', 0, 'callback', 3],
        ['actionname', '2,100', '控制器名称在2-100字符之间！', 1, 'length', 3],
    );

    //2019-4-18 rml：验证接口版本号唯一性
    protected function checkName($name)
    {
        $id = I('request.id', 0);
        if ($id) {
            $count = D('version')->where(['numberstr' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('version')->where(['numberstr' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    //2019-4-18 rml：验证控制器名称唯一性
    protected function checkAction($name)
    {
        $id = I('request.id', 0);
        if ($id) {
            $count = D('version')->where(['actionname' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('version')->where(['actionname' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    /**
     * 查询版本号列表
     */
    public static function getVersionList($where)
    {
        return D('version')->where($where)->select();
    }

    /**
     * 获取单条记录
     */
    public static function getVersionInfo($id)
    {
        return D('version')->where(['id' => $id])->find();
    }

    /**
     * 删除单条记录：软删除
     */
    public static function delVersionInfo($id)
    {
        return D('version')->where(['id' => $id])->setField('del', 1);
    }

    /**
     * 修改状态开关
     */
    public static function editStatus($id, $status)
    {
        return D('version')->where(['id' => $id])->setField('status', $status);
    }

    /**
     * 修改通用开关
     */
    public static function editAlluse($id, $all_use)
    {
        return D('version')->where(['id' => $id])->setField('all_use', $all_use);
    }

    /**
     * 2019-3-13 任梦龙：根据id获取名称
     */
    public static function getNumberstr($id)
    {
        return M('version')->where(['id' => $id])->getField('numberstr');
    }

}