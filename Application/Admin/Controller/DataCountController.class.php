<?php
/**
 * Created by PhpStorm.
 * User: 汪桂芳
 * Date: 2018/12/7
 * Time: 13:16
 */

namespace Admin\Controller;

use Admin\Model\DatatemplateModel;
use Think\Controller;


//2019-03-21汪桂芳:添加操作记录
class DataCountController extends CommonController
{
    /**
     * 统计模板
     */
    //统计模板页面
    public function dataTemplateList()
    {
        $this->display();
    }

    //加载统计模板记录
    public function loadDataTemplateList()
    {
        $where[1] = 'del=0';
        $i = 2;
        $title = I("post.title", "");
        if ($title <> "") {
            $where[$i] = "(title like '%" . $title . "%')";
            $i++;
        }
        $admin_user = I("post.type", "");
        if ($admin_user <> "") {
            $where[$i] = "admin_user = '" . $admin_user . "'";
            $i++;
        }
        $this->ajaxReturn(PageDataLoad('datatemplate', $where), 'JSON');
    }

    //模板添加
    public function dataTemplateAdd()
    {
        $this->display();
    }

    //模板添加处理程序
    public function addDataTemplate()
    {
        $post = I('post.');
        $msg = "添加统计模板:";
        //上传文件
        $date_time = date('YmdHis');
        $save_name = $date_time . rand(1000, 9999) . $post['action'];
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 2097152; // 设置附件上传大小
//        $upload->exts = array('xls'); // 设置附件上传类型jpg|png|gif|bmp|jpeg
        $upload->rootPath = C('DATATEMPLATE_PATH'); // 设置附件上传目录
        $upload->saveName = $save_name;   //文件名
        $upload->subName = date('Y-m-d');   //子目录创建方式，以账号id命名
        // 上传文件
        $info = $upload->uploadOne($_FILES['file']);
        if (!$info) { // 上传错误提示错误信息
            $this->addAdminOperate($msg.'图片上传有误,'.$upload->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);
        } else { // 上传成功
            $all_path = C('DATATEMPLATE_PATH') . $info['savepath'] . $info['savename'];
            $data = [
                'admin_user' => $post['admin_user'],
                'img_name' => $all_path,
                'title' => $post['title'],
                'action' => $post['action'],
                'default' => $post['defaultval']
            ];
            $res = DatatemplateModel::addTemplate($data);
            if ($res) {
                $this->addAdminOperate($msg.'统计模板['.$post['title'].']添加成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => "统计模板添加成功"]);
            } else {
                unlink($all_path);
                $this->addAdminOperate($msg.'统计模板['.$post['title'].']添加失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '统计模板添加失败']);
            }
        }
    }

    //模板编辑
    public function dataTemplateEdit()
    {
        $id = I('get.id');
        $info = DatatemplateModel::getOneTemplate($id);
        $this->assign('info', $info);
        $this->display();
    }

    //模板编辑处理程序(点击过上传图片,先传图片)
    public function editDataTemplate()
    {
        $post = I('post.');
        //获取统计模板名称
        $template_name = DatatemplateModel::getTemplateName(I('id'));
        $msg = "修改统计模板[".$template_name."]";
        //上传文件
        $date_time = date('YmdHis');
        $save_name = $date_time . rand(1000, 9999) . $post['action'];
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 2097152; // 设置附件上传大小
//        $upload->exts = array('xls'); // 设置附件上传类型jpg|png|gif|bmp|jpeg
        $upload->rootPath = C('DATATEMPLATE_PATH'); // 设置附件上传目录
        $upload->saveName = $save_name;   //文件名
        $upload->subName = date('Y-m-d');   //子目录创建方式，以账号id命名
        // 上传文件
        $info = $upload->uploadOne($_FILES['file']);
        if (!$info) { // 上传错误提示错误信息
            $this->addAdminOperate($msg.'修改失败,图片上传有误,'.$upload->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);
        } else { // 上传成功
            $old = DatatemplateModel::getOneTemplate($post['id']);
            $all_path = C('DATATEMPLATE_PATH') . $info['savepath'] . $info['savename'];
            $data = [
                'admin_user' => $post['admin_user'],
                'img_name' => $all_path,
                'title' => $post['title'],
                'action' => $post['action'],
                'default' => $post['defaultval']
            ];
            $res = DatatemplateModel::saveTemplate($post['id'], $data);
            if ($res) {
                unlink($old['img_name']);
                $this->addAdminOperate($msg.'修改成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => "统计模板修改成功"]);
            } else {
                unlink($all_path);
                $this->addAdminOperate($msg.'修改失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '统计模板修改失败']);
            }
        }
    }

    //模板编辑处理程序(未点击上传图片,直接修改其他内容)
    public function editTemplate()
    {
        $post = I('post.');
        $template_name = DatatemplateModel::getTemplateName(I('id'));
        $msg = "修改统计模板[".$template_name."]";
        $data = [
            'admin_user' => $post['admin_user'],
            'title' => $post['title'],
            'action' => $post['action'],
            'default' => $post['defaultval']
        ];
        $res = DatatemplateModel::saveTemplate($post['id'], $data);
        if ($res) {
            $this->addAdminOperate($msg.'修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => "统计模板修改成功"]);
        } else {
            $this->addAdminOperate($msg.'修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '统计模板修改失败']);
        }
    }

    //模板单条删除
    public function templateDel()
    {
        $id = I("id");
        $template_name = DatatemplateModel::getTemplateName(I('id'));
        $msg = "删除统计模板[".$template_name."]";
        $res = DatatemplateModel::delTemplate($id);
        if ($res) {
            $this->addAdminOperate($msg.'删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate($msg.'删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败，请重试']);
        }

    }

    //模板批量删除
    public function delAll()
    {
        $idstr = I("post.id_str", "");
        if ($idstr == "") {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择统计模板']);
        }

        $r = DatatemplateModel::delAllTemplate($idstr);
        if ($r) {
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败，请重试']);
        }
    }


    /**
     * 资金变动记录
     */
    //资金变动记录表
    public function moneyChangeList()
    {
        $payapis = M('payapi')->field('id,zh_payname')->select();
        $this->assign('payapis', $payapis);
        //添加代付通道的查询
        $daifus = M('daifu')->field('id,zh_payname')->select();
        $this->assign('daifus', $daifus);
        $accounts = M('payapiaccount')->field('id,bieming')->select();
        $this->assign('accounts', $accounts);
        $types = C('MONEY_CHANGE_TYPE');
        $this->assign('types', $types);
        $this->display();
    }

    //加载资金变动记录
    public function loadMoneyChangeList()
    {
        //读取表前缀
        $pre = C('DB_PREFIX');
        $where_sql = '';
        //商户号
        $memberid = I("post.memberid", "", 'trim');
        if ($memberid <> "") {
            $where_sql .= " AND (" . $pre . "secretkey.memberid ='" . $memberid . "')";
        }
        $transid = I("post.transid", "", 'trim');
        if ($transid <> "") {
            $where_sql .= " AND (transid ='" . $transid . "')";
        }
        $payapiid = I("post.payapiid", "");
        if ($payapiid <> "") {
            $where_sql .= " AND payapiid = " . $payapiid;
        }
        $daifuid = I("post.daifuid", "");
        if ($daifuid <> "") {
            $where_sql .= " AND payapiid = " . $daifuid;
        }
        $accountid = I("post.accountid", "");
        if ($accountid <> "") {
            $where_sql .= " AND accountid = " . $accountid;
        }
        $type = I("post.type", "");
        if ($type <> "") {
            $where_sql .= " AND changetype = " . $type;
        }
        $start = I("post.start", "");
        if ($start <> "") {
            $where_sql .= " AND DATEDIFF('" . $start . "',datetime) <= 0";
        }
        $end = I("post.end", "");
        if ($end <> "") {
            $where_sql .= " AND DATEDIFF('" . $end . "',datetime) >= 0";
        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => 0, //总页数
            'data' => [
            ]
        ];
        $count_sql = "SELECT COUNT(*) AS tp_count FROM `" . $pre . "moneychange` ";  //查询的sql
        $datalist_sql = "SELECT * FROM `" . $pre . "moneychange` ";   //获取分页数据的sql
        if ($memberid) {
            $count_sql = "SELECT COUNT(*) AS tp_count FROM `" . $pre . "moneychange` LEFT JOIN " . $pre . "secretkey on " . $pre . "moneychange.userid = " . $pre . "secretkey.userid ";
            $datalist_sql = "SELECT " . $pre . "moneychange.*," . $pre . "secretkey.memberid FROM `" . $pre . "moneychange` LEFT JOIN " . $pre . "secretkey on " . $pre . "moneychange.userid = " . $pre . "secretkey.userid ";
        }

        //搜索条件
        if ($where_sql) {
            $where_sql_new = substr($where_sql, 4);
            $count_sql .= "WHERE " . $where_sql_new;
            $datalist_sql .= "WHERE " . $where_sql_new;
        }
        $count = M()->query($count_sql);
        $count = $count[0]['tp_count'];
        $page = I('post.page', 0);
        $limit = I("post.limit", 10);
        $row = ($page - 1) * $limit;
        $datalist_sql .= " ORDER BY " . $pre . "moneychange.datetime DESC LIMIT " . $row . ", " . $limit;
        $datalist = M()->query($datalist_sql);
        //查询类型
        $types = C('MONEY_CHANGE_TYPE');
        foreach ($datalist as $key => $val) {
            $datalist[$key]['type'] = $types[$val['changetype']];
            $datalist[$key]['memberid'] = $val['userid'] != '' ? $this->getMemberid($val['userid']) : '';
            $pay_type = [2,3,4,7];//交易有关,查询交易通道表
            if(in_array($val['changetype'],$pay_type)){
                $datalist[$key]['payapiname'] = $val['payapiid'] != '' ? $this->getPayapiName($val['payapiid']) : '';
            }
            $settle_type = [5,6,8];//结算有关,查询代付通道表
            if(in_array($val['changetype'],$settle_type)){
                $datalist[$key]['payapiname'] = $val['payapiid'] != '' ? $this->getDaifuName($val['payapiid']) : '';
            }
            $datalist[$key]['accountname'] = $val['accountid'] != '' ? $this->getAccountName($val['accountid']) : '';
            $datalist[$key]['tcusername'] = $val['tcuserid'] != '' ? $this->getUserName($val['tcuserid']) : '';
        }
        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $datalist;
        $this->ajaxReturn($ReturnArr, 'JSON');
    }

    //导出列表
    public function downloadChangeList()
    {
        //读取表前缀
        $pre = C('DB_PREFIX');
        $where_sql = '';
        //商户号
        $memberid = I("get.memberid", "", 'trim');
        if ($memberid <> "") {
            $where_sql .= " AND (" . $pre . "secretkey.memberid = '" . $memberid . "')";
        }
        $transid = I("get.transid", "", 'trim');
        if ($transid <> "") {
            $where_sql .= " AND (transid = '" . $transid . "')";
        }
        $payapiid = I("get.payapiid", "");
        if ($payapiid <> "") {
            $where_sql .= " AND payapiid = " . $payapiid;
        }
        $daifuid = I("get.daifuid", "");
        if ($daifuid <> "") {
            $where_sql .= " AND payapiid = " . $daifuid;
        }
        $accountid = I("get.accountid", "");
        if ($accountid <> "") {
            $where_sql .= " AND accountid = " . $accountid;
        }
        $type = I("get.type", "");
        if ($type <> "") {
            $where_sql .= " AND changetype = " . $type;
        }
        $start = I("get.start", "");
        if ($start <> "") {
            $where_sql .= " AND DATEDIFF('" . $start . "',datetime) <= 0";
        }
        $end = I("get.end", "");
        if ($end <> "") {
            $where_sql .= " AND DATEDIFF('" . $end . "',datetime) >= 0";
        }

        $datalist_sql = "SELECT * FROM `" . $pre . "moneychange` ";   //获取分页数据的sql
        if ($memberid) {
            $datalist_sql = "SELECT " . $pre . "moneychange.*," . $pre . "secretkey.memberid FROM `" . $pre . "moneychange` LEFT JOIN " . $pre . "secretkey on " . $pre . "moneychange.userid = " . $pre . "secretkey.userid ";
        }
        //搜索条件
        if ($where_sql) {
            $where_sql_new = substr($where_sql, 4);
            $datalist_sql .= "WHERE " . $where_sql_new;
        }
        $datalist_sql .= " ORDER BY " . $pre . "moneychange.datetime DESC";
        $datalist = M()->query($datalist_sql);

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
            $datalist[$key]['accountname'] = $val['accountid'] != '' ? $this->getAccountName($val['accountid']) : '';
            $datalist[$key]['username'] = $val['userid'] != '' ? $this->getUserName($val['userid']) : '';
            $datalist[$key]['memberid'] = $val['userid'] != '' ? $this->getMemberid($val['userid']) : '';     //20192-21 任梦龙：获取商户号
            $datalist[$key]['tcusername'] = $val['tcuserid'] != '' ? $this->getUserName($val['tcuserid']) : '';
        }
        $title = '资金变动记录表';
        $menu_zh = array('商户号', '订单号', '类型', '原金额', '变动金额', '变动后金额', '变动时间', '通道名称', '账号名称', '提成用户名', '提成等级', '备注');
        $menu_en = array('memberid', 'transid', 'type', 'oldmoney', 'changemoney', 'nowmoney', 'datetime', 'payapiname', 'accountname', 'tcusername', 'tcdengji', 'remarks');
        $config = array('RowHeight' => 25, 'Width' => 20);
        $this->addAdminOperate("管理员[".session('admin_info.user_name')."]导出了资金变动记录表");
        DownLoadExcel($title, $menu_zh, $menu_en, $datalist, $config);
    }

    //获取用户名
    public function getUserName($id)
    {
        return D("user")->where('id=' . $id)->getField('username');
    }

    //获取通道名称
    public function getPayapiName($id)
    {
        return D("payapi")->where('id=' . $id)->getField('zh_payname');
    }

    //获取代付通道名称
    public function getDaifuName($id)
    {
        return D("daifu")->where('id=' . $id)->getField('zh_payname');
    }

    //获取账号名称
    public function getAccountName($id)
    {
        return D("payapiaccount")->where('id=' . $id)->getField('bieming');
    }

    //获取商户号
    public function getMemberid($id)
    {
        return M('secretkey')->where('userid=' . $id)->getField('memberid');
    }
}