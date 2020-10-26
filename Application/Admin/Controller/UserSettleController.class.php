<?php
/**
 * 用户的结算设置
 */

namespace Admin\Controller;

use Admin\Model\SettleconfigModel;
use Admin\Model\DaifuModel;
use Admin\Model\UserModel;

class UserSettleController extends CommonController
{
    //结算设置
    //2019-4-18 rml：修改，添加验证码
    //2019-5-6 rml：修改逻辑：进入页面时，先去判断数据库中有没有用户的数据,如果没有，则使用系统的数据;如果存在,还需要判断是属于哪一个类型的
    public function userSettleSet()
    {
        $id = I('get.userid');
        //读取用户的结算基本设置数据
        $user_config = SettleconfigModel::getSettleconfig(['user_id' => $id]);
        $config_type = 0;  //初始默认系统
        if ($user_config) {
            if ($user_config['user_type'] == 1) {
                $config_type = 1;
                $config = $user_config;
            } else {
                $config = SettleconfigModel::getSettleconfig(['user_id' => 0, 'user_type' => 0]);
            }
        }else {
            $config = SettleconfigModel::getSettleconfig(['user_id' => 0, 'user_type' => 0]);
        }
        $this->assign('config_type', $config_type);
        $this->assign('config', $config);

        //查询代付通道下可用账号
        $account = $this->getDaifuAccount($config['daifu_id']);
        $this->assign('account', $account);

        //读取所有代付通道
        $daifu = DaifuModel::getDaifuidList(['status' => 1, 'del' => 0]);
        $this->assign('daifu', $daifu);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //用户结算设置类型修改
    //2019-4-18 rml：修改逻辑
    public function userSettleTypeEdit()
    {
        $type = I('get.type');
        $user_id = I('get.user_id');
        //利用$type的值判断是应用系统还在自定义
        $user_config = SettleconfigModel::getSettleconfig(['user_id' => $user_id]);
        //点在应用系统上
        if ($type == 0) {
            //如果用户还没有设置
            if (!$user_config) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '用户还未设置,已应用系统',]);
            }
            $user_name = UserModel::getUserName($user_id);
            $msg = '修改用户[' . $user_name . ']的结算设置:修改为应用系统';
            //如果用户已经设置了，此时要应用系统的
            if ($user_config['user_type'] == 0) {
                $res = true;
            } else {
                $res = SettleconfigModel::editSettleConfig(['id' => $user_config['id']], ['user_type' => 0]);
            }
        }
        //点在自定义上
        if ($type == 1) {
            $user_name = UserModel::getUserName($user_id);
            $msg = '修改用户[' . $user_name . ']的结算设置:修改为自定义';
            //如果用户原来没有设置过,则需要添加一条数据
            if (!$user_config) {
                $data = [
                    'user_id' => $user_id,
                    'user_type' => 1,
                ];
                $res = SettleconfigModel::addSettleConfig($data);
            } else {
                //如果已经设置过，需要将类型修改
                $res = SettleconfigModel::editSettleConfig(['id' => $user_config['id']], ['user_type' => 1]);
            }
        }
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //代付通道选择事件,查出相应的账号
    //2019-4-18 rml：修改
    public function getAccount()
    {
        $account = $this->getDaifuAccount(I('daifu_id'));
        $this->ajaxReturn($account, 'json');
    }

    //2019-4-18 rml：封装获取代付通道下可用账号
    public function getDaifuAccount($daifu_id)
    {
        $shangjia_id = DaifuModel::getPayapiShangjiaId($daifu_id);
        //查询商家所有可用账号
        $where = [
            'payapishangjiaid' => $shangjia_id,
            'status' => 1,
            'del' => 0,
            'user_id' => 0,  //系统的账号
        ];
        $account = M('payapiaccount')->where($where)->field('id,bieming')->select();
        return $account;
    }

    //结算设置修改
    //2019-5-6 rml:完善逻辑
    public function userSettleEdit()
    {
        $user_id = I('user_id');
        $data = I('post.');
        unset($data['user_type']);
        //如果是第一次添加,需要判断是否为空的情况
        if (!$data['day_start'] || !$data['day_end']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '结算时间不得为空']);
        }
        if ($data['day_start'] > $data['day_end']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '开始时间大于结束时间']);
        }
        if ($data['min_money'] > $data['max_money']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '单笔最小金额不能超过单笔最大金额']);
        }
        if ($data['max_money'] > $data['day_maxmoney']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '单笔最大金额不能超过当日最大金额']);
        }
        if ($data['day_maxmoney'] > 9999999999) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '当日提款最大金额不得超过9999999999']);
        }

        if (!$data['day_maxnum']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '当日提款最大次数不得为空']);
        }

        //当日提款最大次数不得超过9999
        if ($data['day_maxnum'] > 9999) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '当日提款最大次数不得超过9999']);
        }

        //默认结算运营费率不得大于1
        if ($data['settle_feilv'] >= 1) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '默认结算运营费率不得大于1']);
        }

        //单笔最小手续费不得超过9999999999
        if ($data['settle_min_feilv'] > 9999999999) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '单笔最小手续费不得超过9999999999']);
        }

        $user_name = UserModel::getUserName($user_id);
        $msg = '修改用户[' . $user_name . ']的结算设置:';
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $res = SettleconfigModel::editSettleConfig(['user_id' => $user_id, 'user_type' => 1], $data);
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }

    }
}