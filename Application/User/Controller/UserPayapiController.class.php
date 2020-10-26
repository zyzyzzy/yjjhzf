<?php

namespace User\Controller;

use Think\Controller;
use User\Model\PayapiclassModel;
use User\Model\SettleconfigModel;

class UserPayapiController extends UserCommonController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 交易通道列表
     */
    public function payapiList()
    {
        $PayapiclassList = PayapiclassModel::getPayapiClassList();
        $this->assign("PayapiclassList", $PayapiclassList);
        $this->display();
    }

    /**
     * 加载用户交易通道列表
     */
    public function loadPayapiList()
    {
        $where = [
            'userid' => session('user_info.id'),
            'payapiid' => ['gt',0],
        ];
        $payapiclassid = I('post.payapiclassid',0);
        if($payapiclassid){
            $where['payapiclassid'] = $payapiclassid;
        }
        //读取用户开通的通道

        $datalist = D('userpayapiclass')->scope('default_scope')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('id DESC')->select();
        //用户的交易费率存在时读取用户的费率，用户的费率不存在时读取通道的费率
        foreach ($datalist as $k=>$v){
            if($v['order_feilv'] == ''){
                $datalist[$k]['order_feilv'] = PayapiclassModel::getOrderFeilv($v['payapiclassid']);
            }
            if($v['order_min_feilv'] == ''){
                $datalist[$k]['order_min_feilv'] = PayapiclassModel::getOrderMinFeilv($v['payapiclassid']);
            }
            //获取通道分类编码
            $datalist[$k]['payapiclassbm'] =PayapiclassModel::getPayapiclassBm($v['payapiclassid']);
        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功',
            'count' =>  count($datalist),
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, 'JSON');
    }

}