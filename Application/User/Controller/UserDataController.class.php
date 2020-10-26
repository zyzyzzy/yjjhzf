<?php
/**
 * Created by PhpStorm.
 * User: 汪桂芳
 * Date: 2019/01/03
 * Time: 16:05
 */
namespace User\Controller;
use Think\Controller;
use User\Model\DatatemplateModel;
use User\Model\DatatemplateuserModel;

class UserDataController extends UserCommonController

{
    //选择统计模板页面
    public function selectTemplate()
    {
        //只针对主用户选择统计模板,子用户应用主用户的选择;如果主用户给了子用户选择的权限,子用户修改的是主用户的选择
        $user_id = session('user_id');
        //查询该账号的模板
        $user_imgs = DatatemplateuserModel::getAllTemplate($user_id);
        $count  = count($user_imgs);
        $this->assign('count', $count);

        //查询所有模板
        $all_imgs = DatatemplateModel::getAllTemplate();
        if($all_imgs){
            foreach ($all_imgs as $k => $v) {
                $all_imgs[$k]['src'] = '/' . $v['img_name'];
                foreach ($user_imgs as $key=>$val){
                    if($v['id']==$val['datatemplate_id']){
                        $all_imgs[$k]['select'] = 1;
                    }
                }
            }
        }
        $this->assign('all_imgs', $all_imgs);

        $this->display();
    }

    //选择统计模板处理程序
    public function confirmTeplate()
    {
        $user_id = session('user_info.id');
        //读取原来用户的模板
        $all = DatatemplateuserModel::getAllTemplate($user_id);
        $old = [];//存储原始数据的数组
        foreach ($all as $k=>$v){
            $old[] = $v['datatemplate_id'];
        }
        $templateid = I('post.templateid');
        if(!$templateid)
        {
            $this->ajaxReturn(['status' => 'no','msg'=>'请选择统计模板']);
        }
        $templateid = rtrim($templateid,',');
        $template = explode(',',$templateid);
        $now = [];//存储一直存在的数据的数据
        foreach ($template as $k=>$v){
            //存在就不管,不存在就添加,多余就删除
            if(!in_array($v,$old)){
                DatatemplateuserModel::addTemplate($user_id,$v);
            }else{
                $now[] = $v;
            }
        }
        //删除多余数据,即从原始数据里去掉一直存在的数据,剩下的就是要删掉的数据
        $del = array_diff($old,$now);
        if($del){
            $del = implode(',',$del);
            DatatemplateuserModel::deleteTemplate($user_id,$del);
        }
        $this->addUserOperate("用户重新选择了统计模板");
        $this->ajaxReturn(['status' => 'ok','msg'=>'选择成功']);
    }

    //资金变动记录表
    public function moneyChangeList()
    {
        $payapis = M('payapi')->select();
        $this->assign('payapis',$payapis);

        $daifus = M('daifu')->field('id,zh_payname')->select();
        $this->assign('daifus', $daifus);

        $accounts = M('payapiaccount')->select();
        $this->assign('accounts',$accounts);

        $types = C('MONEY_CHANGE_TYPE');
        $this->assign('types',$types);

        $this->display();
    }

    //加载资金变动记录
    public function loadMoneyChangeList()
    {
        $where = [];
        $i=1;
        $user_id = session('user_info.id');
        if ($user_id <> "") {
            $where[$i] = "userid = ".$user_id;
            $i++;
        }

        $transid = I("post.transid", "",'trim');
        if ($transid <> "") {
            $where[$i] = "(transid like '" . $transid . "%')";
            $i++;
        }

        $payapiid = I("post.payapiid", "");
        if ($payapiid <> "") {
            $where[$i] = "payapiid = ".$payapiid;
            $i++;
        }

        $daifuid = I("post.daifuid", "");
        if ($daifuid <> "") {
            $where[$i] = " AND payapiid = " . $daifuid;
        }

        $type = I("post.type", "");
        if ($type <> "") {
            $where[$i] = "(changetype like '%" . $type . "%')";
            $i++;
        }
        $start = I("post.start", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',datetime) <= 0";
            $i++;
        }
        $end = I("post.end", "");
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',datetime) >= 0";
            $i++;
        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => 0, //总页数
            'data' => [
            ]
        ];

        //总页数
        $count = M('moneychange')->where($where)->count();
        //分页页面展示设置,得到数据库里的数据（del=0）二维数组
        $datalist = M('moneychange')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('datetime DESC')->select();
        //查询类型
        $types = C('MONEY_CHANGE_TYPE');
        foreach ($datalist as $key => $val) {
            $datalist[$key]['type'] = $types[$val['changetype']];
            $pay_type = [2,3,4,7];//交易有关,查询交易通道表
            if(in_array($val['changetype'],$pay_type)){
                $datalist[$key]['payapiname'] = $val['payapiid'] != '' ? $this->getPayapiName($val['payapiid']) : '';
            }
            $settle_type = [5,6,8];//结算有关,查询代付通道表
            if(in_array($val['changetype'],$settle_type)){
                $datalist[$key]['payapiname'] = $val['payapiid'] != '' ? $this->getDaifuName($val['payapiid']) : '';
            }
            $datalist[$key]['username'] = $val['userid']!=''?$this->getUserName($val['userid']):'';
            $datalist[$key]['tcusername'] = $val['tcuserid']!=''?$this->getUserName($val['tcuserid']):'';
        }
        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $datalist;
        $this->ajaxReturn($ReturnArr, 'JSON');
    }

    //导出列表
    public function downloadChangeList()
    {
        //搜索
        $where = [];
        $i=1;
        $user_id = session('user_info.id');
        if ($user_id <> "") {
            $where[$i] = "userid = ".$user_id;
            $i++;
        }

        $transid = I("get.transid", "",'trim');
        if ($transid <> "") {
            $where[$i] = "(transid like '" . $transid . "%')";
            $i++;
        }

        $payapiid = I("get.payapiid", "");
        if ($payapiid <> "") {
            $where[$i] = "payapiid = ".$payapiid;
            $i++;
        }
        $daifuid = I("get.daifuid", "");
        if ($daifuid <> "") {
            $where[$i] = " AND payapiid = " . $daifuid;
        }
        $type = I("get.type", "");
        if ($type <> "") {
            $where[$i] = "(changetype like '%" . $type . "%')";
            $i++;
        }
        $start = I("get.start", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',datetime) <= 0";
            $i++;
        }
        $end = I("get.end", "");
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',datetime) >= 0";
            $i++;
        }
        //查询数据
        $datalist = M('moneychange')->where($where)->order('datetime DESC')->select();
        //查询类型
        $types = C('MONEY_CHANGE_TYPE');
        foreach ($datalist as $key => $val) {
            $datalist[$key]['type'] = $types[$val['changetype']];
            $pay_type = [2,3,4,7];//交易有关,查询交易通道表
            if(in_array($val['changetype'],$pay_type)){
                $datalist[$key]['payapiname'] = $val['payapiid'] != '' ? $this->getPayapiName($val['payapiid']) : '';
            }
            $settle_type = [5,6,8];//结算有关,查询代付通道表
            if(in_array($val['changetype'],$settle_type)){
                $datalist[$key]['payapiname'] = $val['payapiid'] != '' ? $this->getDaifuName($val['payapiid']) : '';
            }
            $datalist[$key]['username'] = $val['userid']!=''?$this->getUserName($val['userid']):'';
            $datalist[$key]['tcusername'] = $val['tcuserid']!=''?$this->getUserName($val['tcuserid']):'';
        }
        $title = '资金变动记录表';
        $menu_zh = array('用户名','订单号',  '类型', '原金额', '变动金额', '变动后金额', '变动时间', '通道名称', '提成用户名', '提成等级', '备注');
        $menu_en = array('username','transid', 'type',  'oldmoney', 'changemoney', 'nowmoney', 'datetime', 'payapiname', 'tcusername', 'tcdengji', 'remarks');
        $config = array('RowHeight' => 25, 'Width' => 20);
        $this->addUserOperate('用户['.session('user_info.username').']导出了资金变动记录');
        DownLoadExcel($title, $menu_zh, $menu_en, $datalist, $config);

    }

    //获取用户名
    public function getUserName($id)
    {
        return D("user")->where('id='.$id)->getField('username');
    }

    //获取交易通道名称
    public function getPayapiName($id)
    {
        return D("payapi")->where('id='.$id)->getField('zh_payname');
    }

    //获取代付通道名称
    public function getDaifuName($id)
    {
        return D("daifu")->where('id='.$id)->getField('zh_payname');
    }

}