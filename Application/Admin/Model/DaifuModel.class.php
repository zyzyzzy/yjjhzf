<?php
/**
 * 代付通道模型
 */

namespace Admin\Model;

use Think\Model;

class DaifuModel extends Model
{
    //2019-4-18 rml：添加字段，优化自动验证
    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [
                "id"
                , "zh_payname"
                , "en_payname"
                , "payapishangjiaid"
                , "status"
                , "settle_feilv"
                , 'settle_min_feilv'
            ],
        ),
    );

    protected $_validate = array(
        array('payapishangjiaid', 'require', '通道商家不能为空！', 0),
        array('zh_payname', 'require', '代付通道名称不能为空！', 0),
        array('zh_payname', 'checkName', '代付通道名称已存在！', 0, 'callback', 3),
        array('zh_payname', '2,20', '代付通道名称长度在2-20字符之间！', 0, 'length', 3),
        array('en_payname', 'require', '代付通道编码不能为空！', 0),
        array('en_payname', 'checkBianma', '代付通道编码已存在！', 0, 'callback', 3),
        array('en_payname', '2,20', '代付通道编码长度在2-20字符之间！', 0, 'length', 3),
        array('settle_feilv', 'require', '默认结算运营费率不能为空！', 0),
        array('settle_feilv', 'checkOrder', '默认结算运营费率不能大于2！', 0, 'callback', 3),
        array('settle_min_feilv', 'require', '结算单笔最低手续费不能为空！', 0),
        array('settle_min_feilv', 'checkMinOrder', '结算单笔最低手续费不能大于9999999999！', 0, 'callback', 3),
    );

    //验证代付通道名称唯一性
    protected function checkName($name)
    {
        $id = I('request.id', '');
        if ($id) {
            $count = D('daifu')->where(['zh_payname' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('daifu')->where(['zh_payname' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    //验证代付通道名称唯一性
    protected function checkBianma($name)
    {
        $id = I('request.id', '');
        if ($id) {
            $count = D('daifu')->where(['en_payname' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('daifu')->where(['en_payname' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    protected function checkOrder($settle_feilv)
    {
        if($settle_feilv > 2){
            return false;
        }
        return true;
    }

    protected function checkMinOrder($settle_min_feilv)
    {
        if($settle_min_feilv > 9999999999){
            return false;
        }
        return true;
    }

    public static function getInfo($id)
    {
        return D('daifu')->where("id='" . $id . "'")->find();
    }

    public static function getDaifuName($id)
    {
        return D('daifu')->where("id='" . $id . "'")->getField('zh_payname');
    }

    public static function getDaifu($where, $page = 1, $limit = 10)
    {
        $daifu_list = D('daifu')->page($page, $limit)->where($where)->select();
        //查询商家名称
        foreach ($daifu_list as $key => $val) {
            $shangjia = M('payapishangjia')->where('id = ' . $val['payapishangjiaid'])->getField('shangjianame');
            $daifu_list[$key]['shangjianame'] = $shangjia;
        }
        return $daifu_list;
    }

    //2019-1-14 任梦龙：修改为软删除
    public function deleteDaifu($id)
    {
        return M('daifu')->where('id = ' . $id)->setField('del', 1);
    }

    //2019-1-14 任梦龙：将修改代付状态移到模型层
    public static function editStatus($id, $status)
    {
        return M("daifu")->where("id=" . $id)->setField("status", $status);
    }

    //2019-1-14 任梦龙：添加批量删除
    public static function delAllDaifu($idstr)
    {
        $idstr = explode(',', $idstr);
        $new = '';
        foreach ($idstr as $k => $v) {
            $settle = SettleconfigModel::getInfoByDaifuid($v);
            if (!$settle) {
                $new .= $v . ',';
            }
        }
        $new = trim($new, ',');
        $where['id'] = ['in', $new];
        return M("daifu")->where($where)->setField('del', 1);
    }

    /**
     * 2019-2-19 任梦龙：获取商家下的代付通道
     */
    public static function getDaifuidList($where)
    {
        return M('daifu')->where($where)->field('id,zh_payname')->select();  //商家下的代付通道
    }

    //2019-4-18 rml：获取代付通道下的商家id
    public static function getPayapiShangjiaId($id)
    {
        return M('daifu')->where(['id' => $id])->getField('payapishangjiaid');
    }
}
