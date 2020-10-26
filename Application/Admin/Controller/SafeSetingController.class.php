<?php
/**
 * 风控设置控制器
 */

namespace Admin\Controller;

use Admin\Model\BlackipModel;
use Admin\Model\BlackdomainModel;
use Admin\Model\BlacktelModel;
use Admin\Model\BlackidcardModel;
use Admin\Model\BlackbanknumModel;

//2019-4-19 rml：优化代码
class SafeSetingController extends CommonController
{
    /**
     *风控设置
     */
    public function safeSeting()
    {
        $this->display();
    }

    /**
     * IP黑名单页面
     */
    public function blackIp()
    {
        $this->display();
    }

    /**
     * 加载IP黑名单
     */
    public function loadBlackIp()
    {
        $where = [];
        $ip = I('post.ip', '', 'trim');
        if ($ip <> '') {
            $where['ip'] = $ip;
        }
        $this->ajaxReturn(PageDataLoad('blackip', $where));
    }

    /**
     * 添加ip页面
     */
    public function addBlackIp()
    {
        $this->display();
    }

    /**
     * 确认添加ip
     */
    public function blackIpAdd()
    {
        $msg = '添加IP黑名单:' . I('post.ip', '', 'trim');
        $return = AddSave('blackip', 'add', '添加IP黑名单');
        $this->addAdminOperate($msg . ',' . $return['msg']);
        $this->ajaxReturn($return, 'JSON');
    }

    /**
     * 删除IP
     */
    public function delBlackIp()
    {
        $id = I('post.id', 0, 'intval');
        $ip = BlackipModel::getIpinfo($id);
        $msg = '删除IP黑名单:' . $ip;
        $res = BlackipModel:: blackIpDel($id);
        if ($res) {
            $this->addAdminOperate($msg . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . ',删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }

    /**
     * 批量删除IP
     */
    public function delAllIp()
    {
        $msg = '批量删除IP黑名单:';
        $id_str = I('post.idstr', '', 'trim');
        if (!$id_str) {
            $this->addAdminOperate($msg . '未选择记录');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请先选择记录']);
        }
        $res = BlackipModel::blackIpDelAll($id_str);
        if ($res) {
            $this->addAdminOperate($msg . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . ',删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);

    }


    /**
     * 域名黑名单页面
     */
    public function blackDomain()
    {
        $this->display();
    }

    /**
     * 加载域名页面
     */
    public function loadBlackDomain()
    {
        $where = [];
        $domain = I('post.domain', '', 'trim');
        if ($domain <> '') {
            $where['domain'] = $domain;
        }
        $this->ajaxReturn(PageDataLoad('blackdomain', $where));
    }

    /**
     * 添加域名黑名单页面
     */
    public function addBlackDomain()
    {
        $this->display();
    }

    /**
     * 确认添加域名黑名单
     */
    public function blackDomainAdd()
    {
        $msg = '添加域名黑名单:' . I('post.domain', '', 'trim');
        $return = AddSave('blackdomain', 'add', '添加域名黑名单');
        $this->addAdminOperate($msg . ',' . $return['msg']);
        $this->ajaxReturn($return, 'JSON');
    }

    /**
     * 删除域名黑名单
     */
    public function delBlackDomain()
    {
        $id = I('post.id', 0, 'intval');
        $domain = BlackdomainModel::getDomaininfo($id);
        $msg = '删除域名黑名单:' . $domain;
        $res = BlackdomainModel:: blackDomainDel($id);
        if ($res) {
            $this->addAdminOperate($msg . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . ',删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }

    /**
     * 批量删除域名
     */
    public function delAllDomain()
    {
        $msg = '批量删除域名黑名单:';
        $id_str = I('post.idstr', '', 'trim');
        if (!$id_str) {
            $this->addAdminOperate($msg . '未选择记录');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请先选择记录']);
        }
        $res = BlackdomainModel::blackDomainDelAll($id_str);
        if ($res) {
            $this->addAdminOperate($msg . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . ',删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);

    }


    /**
     * 手机号黑名单页面
     */
    public function blackTel()
    {
        $this->display();
    }

    /**
     * 加载手机号号列表
     */
    public function loadBlackTel()
    {
        $where = [];
        $tel = I('post.tel', '', 'trim');
        if ($tel <> '') {
            $where['tel'] = $tel;
        }
        $this->ajaxReturn(PageDataLoad('blacktel', $where));
    }

    /**
     * 添加手机号页面
     */
    public function addBlackTel()
    {
        $this->display();
    }

    /**
     * 确认添加手机号
     */
    public function blackTelAdd()
    {
        $msg = '添加手机号黑名单:' . I('post.tel', '', 'trim');
        $return = AddSave('blacktel', 'add', '添加手机号黑名单');
        $this->addAdminOperate($msg . ',' . $return['msg']);
        $this->ajaxReturn($return, 'JSON');
    }

    /**
     * 删除手机号
     */
    public function delBlackTel()
    {
        $id = I('post.id', 0, 'intval');
        $tel = BlacktelModel::getTelinfo($id);
        $msg = '删除手机号黑名单:' . $tel;
        $res = BlacktelModel:: blackTelDel($id);
        if ($res) {
            $this->addAdminOperate($msg . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . ',删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }

    /**
     * 批量删除手机号
     */
    public function delAllTel()
    {
        $msg = '批量删除手机号黑名单:';
        $id_str = I('post.idstr', '', 'trim');
        if (!$id_str) {
            $this->addAdminOperate($msg . '未选择记录');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请先选择记录']);
        }
        $res = BlacktelModel::blackTelDelAll($id_str);
        if ($res) {
            $this->addAdminOperate($msg . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . ',删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);

    }


    /**
     * 身份证黑名单页面
     */
    public function blackIdCard()
    {
        $this->display();
    }

    /**
     * 加载身份证列表
     */
    public function loadBlackIdCard()
    {
        $where = [];
        $idcard = I('post.idcard', '', 'trim');
        if ($idcard <> '') {
            $where['idcard'] = $idcard;
        }
        $this->ajaxReturn(PageDataLoad('blackidcard', $where));
    }

    /**
     * 添加身份证黑名单页面
     */
    public function addBlackIdCard()
    {
        $this->display();
    }

    /**
     * 确认添加身份证
     */
    public function blackIdCardAdd()
    {
        $msg = '添加身份证黑名单:' . I('post.idcard', '', 'trim');
        $return = AddSave('blackidcard', 'add', '添加身份证黑名单');
        $this->addAdminOperate($msg . ',' . $return['msg']);
        $this->ajaxReturn($return, 'JSON');
    }

    /**
     * 删除身份证
     */
    public function delBlackIdCard()
    {
        $id = I('post.id', 0, 'intval');
        $idcard = BlackidcardModel::getIdCardinfo($id);
        $msg = '删除身份证黑名单:' . $idcard;
        $res = BlackidcardModel:: blackIdCardDel($id);
        if ($res) {
            $this->addAdminOperate($msg . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . ',删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }

    /**
     * 批量删除身份证
     */
    public function delAllIdCard()
    {
        $msg = '批量删除身份证黑名单:';
        $id_str = I('post.idstr', '', 'trim');
        if (!$id_str) {
            $this->addAdminOperate($msg . '未选择记录');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请先选择记录']);
        }
        $res = BlackidcardModel::blackIdCardDelAll($id_str);
        if ($res) {
            $this->addAdminOperate($msg . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . ',删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);

    }


    /**
     * 银行卡号黑名单页面
     */
    public function blackBankNum()
    {
        $this->display();
    }

    /**
     * 加载银行卡号列表
     */
    public function loadBlackBankNum()
    {
        $where = [];
        $bank_num = I('post.bank_num', '', 'trim');
        if ($bank_num <> '') {
            $where['bank_num'] = $bank_num;
        }
        $this->ajaxReturn(PageDataLoad('blackbanknum', $where));
    }

    /**
     * 添加银行卡号页面
     */
    public function addBlackBankNum()
    {
        $this->display();
    }

    /**
     * 确认添加
     */
    public function BlackBankNumAdd()
    {
        $msg = '添加银行卡号黑名单:' . I('post.bank_num', '', 'trim');
        $return = AddSave('blackbanknum', 'add', '添加银行卡号黑名单');
        $this->addAdminOperate($msg . ',' . $return['msg']);
        $this->ajaxReturn($return, 'JSON');
    }

    /**
     * 删除
     */
    public function delBlackBankNum()
    {
        $id = I('post.id', 0, 'intval');
        $bank_num = BlackbanknumModel::getBankNuminfo($id);
        $msg = '删除银行卡号黑名单:' . $bank_num;
        $res = BlackbanknumModel:: blackBankNumDel($id);
        if ($res) {
            $this->addAdminOperate($msg . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . ',删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }

    /**
     * 批量删除银行卡号
     */
    public function delAllBankNum()
    {
        $msg = '批量删除银行卡号黑名单:';
        $id_str = I('post.idstr', '', 'trim');
        if (!$id_str) {
            $this->addAdminOperate($msg . '未选择记录');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请先选择记录']);
        }
        $res = BlackbanknumModel::blackBankNumDelAll($id_str);
        if ($res) {
            $this->addAdminOperate($msg . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . ',删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }

}