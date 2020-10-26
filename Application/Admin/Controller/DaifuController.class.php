<?php

namespace Admin\Controller;

use Admin\Model\DaifuModel;
use Admin\Model\SettleconfigModel;
use Admin\Model\PayapishangjiaModel;
use Think\Controller;

class DaifuController extends CommonController
{
    //代付通道列表页面
    public function Daifulist()
    {
        //2019-4-18 rml：写入模型
        $PayapishangjiaList = PayapishangjiaModel::getShangjiaList(['del' => 0]);
        $this->assign("PayapishangjiaList", $PayapishangjiaList);
        $this->display();
    }

    //加载代付通道列表数据
    //2019-4-18 rml：优化
    public function loadList()
    {
        $where = [];
        $i = 0;
        $where[$i] = 'del = 0';
        $i++;
        $zh_payname = I("post.zh_payname", "", 'trim');
        if ($zh_payname) {
            $where[$i] = "zh_payname like '%" . $zh_payname . "%'";
            $i++;
        }
        $en_payname = I("post.en_payname", "", 'trim');
        if ($en_payname) {
            $where[$i] = "en_payname like '%" . $en_payname . "%'";
            $i++;
        }
        $payapishangjiaid = I("post.payapishangjiaid", "");
        if ($payapishangjiaid) {
            $where[$i] = "payapishangjiaid =" . $payapishangjiaid;
            $i++;
        }
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "status =" . $status;
            $i++;
        }
        $count = D('daifu')->where($where)->count();
        $datalist = D('daifu')->scope('default_scope')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('id DESC')->select();
        foreach ($datalist as $key => $val) {
            $datalist[$key]['shangjianame'] = PayapishangjiaModel::getShangjiaName($val['payapishangjiaid']);
        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功',
            'count' => $count,
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, "json");

    }

    //添加的页面
    public function ShowCreateForm()
    {
        //2019-4-18 rml：写入模型
        $PayapishangjiaList = PayapishangjiaModel::getShangjiaList(['del' => 0]);
        $this->assign("PayapishangjiaList", $PayapishangjiaList);//商家列表

        //查询系统结算设置的费率
        $sys = SettleconfigModel::getSettleConfig(['user_id' => 0, 'user_type' => 0]);
        $this->assign("settle_feilv", $sys['settle_feilv']);
        $this->assign("settle_min_feilv", $sys['settle_min_feilv']);
        $this->display();
    }

    //确认添加
    public function CreateDaifu()
    {
        $msg = "添加代付通道[" . I('post.zh_payname','','trim') . "]:";
        $return = AddSave('daifu', 'add', '添加代付通道');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //修改页面
    public function ShowEditForm()
    {
        $find = DaifuModel::getInfo(I('id'));
        $PayapishangjiaList = PayapishangjiaModel::getShangjiaList(['del' => 0]);
        $this->assign("PayapishangjiaList", $PayapishangjiaList);
        $this->assign("find", $find);
        $this->display();
    }

    //确认修改
    public function EditDaifu()
    {
        $msg = "修改代付通道[" . I('post.zh_payname','','trim') . "]:";
        $return = AddSave('daifu', 'save', '修改代付通道');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //删除代付通道
    public function delDaifu()
    {
        $id = I('id');
        $daifu_name = DaifuModel::getDaifuName($id);
        $msg = "删除代付通道[" . $daifu_name . "]:";
        //判断是否被使用
        $settle = SettleconfigModel::getInfoByDaifuid($id);
        if ($settle) {
            $this->addAdminOperate($msg . '代付通道正在被使用,不能删除');
            $this->ajaxReturn(['status' => 'no', 'msg' => '代付通道正在被使用,不能删除']);
        } else {
            $res = DaifuModel::deleteDaifu($id);
            if ($res) {
                $this->addAdminOperate($msg . '删除成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
            } else {
                $this->addAdminOperate($msg . '删除失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
            }
        }
    }

    //修改代付状态
    public function daifuStatus()
    {
        $id = I("post.id", "");
        $daifu_name = DaifuModel::getDaifuName($id);
        $msg = "修改代付通道[" . $daifu_name . "]状态:";
        $status = I("post.status", "");
        if ($status == 1) {
            $stat = "修改为启用";
        } else {
            $stat = "修改为禁用";
        }
        $r = DaifuModel::editStatus($id, $status);
        if ($r) {
            $this->addAdminOperate($msg . $stat . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg . $stat . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //2019-1-14 任梦龙：添加批量删除
    public function delAll()
    {
        $msg = "批量删除代付通道:";
        $idstr = I("post.idstr", "");
        if ($idstr == "") {
            $this->addAdminOperate($msg . '未选择通道');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择通道']);
        }
        $r = DaifuModel::delAllDaifu($idstr);
        if ($r) {
            $this->addAdminOperate($msg . '未被使用的代付通道删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '已删除成功未被使用的代付通道']);
        } else {
            $this->addAdminOperate($msg . '所选通道都在被使用,删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '所选通道都在被使用,删除失败']);
        }
    }
}