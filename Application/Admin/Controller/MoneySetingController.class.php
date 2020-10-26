<?php

namespace Admin\Controller;

use Admin\Model\MoneytypeModel;
use Admin\Model\MoneytypeclassModel;

class MoneySetingController extends CommonController
{
    //到账方案列表
    public function moneyClassList()
    {
        $this->display();
    }

    //加载到账方案列表
    public function loadMoneyClassList()
    {
        $where = [];
        $i = 0;
        $where[$i] = 'del=0';
        $i++;
        //到账方案名称
        $moneyclassname = I('post.moneyclassname', '', 'trim');
        if ($moneyclassname <> '') {
            $where[$i] = "moneyclassname = '" . $moneyclassname . "'";
            $i++;
        }
        $this->ajaxReturn(PageDataLoad('moneytypeclass', $where), 'JSON');
    }

    //添加到账方案页面
    public function addMoneyTypeClassName()
    {
        $this->display();
    }

    //确认添加到账方案
    public function moneyTypeClassNameAdd()
    {
        $msg = '添加到账方案:' . I('post.moneyclassname', '', 'trim') . ',';
        $return = AddSave('moneytypeclass', 'add', '方案名称新增');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //修改到账方案页面
    public function editMoneyTypeClassName()
    {
        $id = I('get.id');
        $edit_info = MoneytypeclassModel::getMoneyClassInfo($id);
        $this->assign('edit_info', $edit_info);
        $this->display();
    }

    //确认修改到账方案
    public function moneyTypeClassNameEdit()
    {
        $msg = '修改到账方案:' . I('post.moneyclassname', '', 'trim') . ',';
        $return = AddSave('moneytypeclass', 'save', '方案名称修改');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //删除到账方案
    public function delMoneyTypeClass()
    {
        $id = I("post.id", "");
        $money_class_name = MoneytypeclassModel::getMoneyClassName($id);
        $msg = '删除到账方案:' . $money_class_name . ',';
        //删除余额方案前，先查询该方案是否被账号或者用户账号用到，如果用到，则提示该方案正在使用，不能被删除
        $on_user = MoneytypeclassModel::useMoneyClass($id);
        if ($on_user) {
            $this->addAdminOperate($msg . ',该到账方案正在使用中,暂不能删除!');
            $this->ajaxReturn(['status' => 'on_use', 'msg' => '该到账方案正在使用中,暂不能删除!']);
        }
        $del = MoneytypeclassModel::delMoneyClass($id); //2019-1-9 任梦龙：添加软删除
        if ($del) {
            //添加判断，当到账方案被删除时，对应的冻结方案也应该删除
            MoneytypeclassModel::delMoneyType($id);
            $this->addAdminOperate($msg . '删除成功!');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功!']);
        } else {
            $this->addAdminOperate($msg . '删除失败!');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败!']);
        }
    }

    //批量删除
    public function delAllMoneyClass()
    {
        $msg = '批量删除到账方案:';
        $idstr = I("post.idstr", "");
        if ($idstr == "") {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请先选择到账方案!']);
        }
        $money_class_id = explode(',', $idstr);
        $new_idstr = "";
        foreach ($money_class_id as $val) {
            $on_user = MoneytypeclassModel::useMoneyClass($val);
            if (!$on_user) {
                $new_idstr .= $val . ',';
            }
        }
        $new_idstr = trim($new_idstr, ',');
        $new_money_class_id = explode(',', $new_idstr);
        if (!$new_idstr) {
            $this->addAdminOperate($msg . '所选择的到账方案都在被使用中,删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '所选择的到账方案都在被使用中,删除失败']);
        } else {
            $r = MoneytypeclassModel::delAllMoneyClass($new_idstr);
            if ($r) {
                foreach ($new_money_class_id as $vo) {
                    MoneytypeclassModel::delMoneyType($vo);
                }
                $this->addAdminOperate($msg . '成功删除所有未被使用的到账方案');
                $this->ajaxReturn(['status' => 'ok', 'msg' => '成功删除所有未被使用的到账方案']);
            } else {
                $this->addAdminOperate($msg . '删除失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
            }
        }
    }

    //查看到账方案的使用账号情况
    public function seeMoneyClassInfo()
    {
        $money_class_id = I('get.money_class_id');
        $this->assign('money_class_id', $money_class_id);
        $this->display();
    }

    //加载方案使用情况的列表
    //2019-4-9 任梦龙：1. 查找通道账号中有没有使用; 2. 查找用户账号中有没有使用
    public function loadMoneyClassInfo()
    {
        $money_class_id = I('get.money_class_id');
        $count_account = M('payapiaccount')->where(['del' => 0, 'moneytypeclassid' => $money_class_id])->field('id,bieming')->select();
        $count_useraccount = M('usermoneyclass')->alias('a')
            ->where('a.moneytypeclass_id=' . $money_class_id)
            ->join('__PAYAPIACCOUNT__ b ON b.id=a.payapiaccountid')
            ->join('__USER__ c ON c.id=a.userid')
            ->field('b.id,b.bieming,c.username')
            ->select();
        foreach ($count_useraccount as $key => $val) {
            $count_account[] = $val;
        }
        foreach ($count_account as $k => $vo) {
            if (!isset($vo['username'])) {
                $count_account[$k]['username'] = '-';
            }
        }
        $this->ajaxReturn([
            'code' => 0,
            'msg' => '数据加载成功',
            'count' => count($count_account),
            'data' => $count_account
        ], 'json');

    }

    //冻结方案列表
    public function MoneyTypeList()
    {
        $money_class_id = I('get.id');
        $this->assign("money_class_id", $money_class_id);
        $this->display();
    }

    //加载冻结方案列表
    public function LoadMoneyList()
    {
        $moneytypeclassid = I("get.moneytypeclassid", "");
        $page = I('page', 1);
        $limit = I('limit', 10);
        $count = MoneytypeModel::getMoneyTypeCount($moneytypeclassid);  //2018-12-26 先前做的没有算总条数 任梦龙增加
        $money_type = MoneytypeModel::getMoneyTypePage($moneytypeclassid, $page, $limit);
        //在后台将到账比例的值转换为整数，前台直接输出，解决了前台如果为29%时，出现不等于29%的情况
        foreach ($money_type as $key => $val) {
            $money_type[$key]['dzbl'] = $val['dzbl'] * 100;
        }
        $this->ajaxReturn([
            'code' => 0,
            'msg' => '数据加载成功',//响应结果
            'count' => $count,//总页数
            'data' => $money_type
        ], "json");
    }

    //添加冻结方案页面
    public function AddMoneyType()
    {
        $this->assign('moneytypeclassid', I('get.moneytypeclassid'));
        $this->display();
    }

    //确认添加冻结方案
    public function MoneyTypeAdd()
    {
        $msg = '添加冻结方案:' . I('post.moneytypename', '', 'trim') . ',';
        $return = AddSave('moneytype', 'add', '冻结方案新增');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //修改冻结方案页面
    public function EditMoneyType()
    {
        $id = I("get.id", "");
        $find = MoneytypeModel::getTypeInfo($id);
        $this->assign("find", $find);
        $this->display();
    }

    //确认修改冻结方案
    public function MoneyTypeEdit()
    {
        $msg = '修改冻结方案:' . I('post.moneytypename', '', 'trim') . ',';
        $return = AddSave('moneytype', 'save', '冻结方案修改');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //删除冻结方案，该方案对应的到账方案正在使用，则不能删除
    public function DelMoneytype()
    {
        $id = I("post.id", "");
        $moneytype_info = MoneytypeModel::getTypeInfo($id);
        $msg = '删除冻结方案:' . $moneytype_info['moneytypename'] . ',';
        $r = MoneytypeModel::delMoneyType($id);
        if ($r) {
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功!']);
        }
        $this->addAdminOperate($msg . '删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败!']);
    }

}