<?php

/**

 * 系统银行

 */



namespace Admin\Controller;



use Admin\Model\SystembankModel;



class BankSetingController extends CommonController

{

    public function __construct()

    {

        parent::__construct();

    }



    //系统银行页面

    public function bankSeting()

    {

        $this->display();

    }



    //加载系统银行列表

    public function LoadBankList()

    {

        $where = [];

        $i = 0;

        $where[$i] = 'del=0';

        $i++;

        $bankname = I("post.bankname", "", 'trim');

        if ($bankname) {

            $where[$i] = "bankname like '%" . $bankname . "%'";

            $i++;

        }

        $bankcode = I("post.bankcode", "", 'trim');

        if ($bankcode) {

            $where[$i] = "bankcode like '%" . $bankcode . "%'";

            $i++;

        }

        $jiaoyisearch = I("post.jiaoyisearch", "");

        if ($jiaoyisearch != "") {

            $where[$i] = "jiaoyi = " . $jiaoyisearch;

            $i++;

        }

        $jiesuansearch = I("post.jiesuansearch", "");

        if ($jiesuansearch != "") {

            $where[$i] = "jiesuan = " . $jiesuansearch;

            $i++;

        }

        $this->ajaxReturn(PageDataLoad('systembank', $where), 'JSON');

    }



    //添加系统银行页面

    public function AddBankCard()

    {

        $this->display();

    }



    //确认添加系统银行

    //2019-4-19 rml：先去验证数据,再上传图片

    public function BankCardAdd()

    {

        $bankname = I('post.bankname', '', 'trim');

        $msg = '添加系统银行[' . $bankname . ']:';

        $sysbank = D('systembank');

        if (!$sysbank->create()) {

            $this->addAdminOperate($msg . $sysbank->getError());

            $this->ajaxReturn(['status' => 'no', 'msg' => $sysbank->getError()]);

        }

        //上传文件

        //判断目录是否存在

        if (!file_exists(C('SYSTEMBANK_PATH'))) {

            mkdir(C('SYSTEMBANK_PATH'), 0777, true);

        }

        $date_time = date('YmdHis');

        $save_name = $date_time . rand(1000, 9999);

        $upload = new \Think\Upload(); // 实例化上传类

        $upload->maxSize = 2097152; // 设置附件上传大小

        $upload->exts = array('jpg', 'png', 'gif', 'bmp', 'jpeg'); // 设置附件上传类型jpg|png|gif|bmp|jpeg

        $upload->rootPath = C('SYSTEMBANK_PATH'); // 设置附件上传目录

        $upload->saveName = $save_name;   //文件名

        $upload->subName = date('Y-m-d');

        // 上传文件

        $msg = '添加系统银行:' . $bankname;

        $info = $upload->uploadOne($_FILES['file']);

        if (!$info) {

            $this->addAdminOperate($msg . $upload->getError());

            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);

        } else {

            $all_path = C('SYSTEMBANK_PATH') . $info['savepath'] . $info['savename'];

            $sysbank->img_url = $all_path;  //修改或者增加数据对象的值

            $res = $sysbank->add();

            if ($res) {

                $this->addAdminOperate($msg . '添加成功');

                $this->ajaxReturn(['status' => 'ok', 'msg' => "银行添加成功"]);

            } else {

                unlink($all_path);

                $this->addAdminOperate($msg . '添加失败');

                $this->ajaxReturn(['status' => 'no', 'msg' => '银行添加失败']);

            }

        }

    }



    //修改系统银行页面

    public function editBankCard()

    {

        $id = I('get.id');

        $info = SystembankModel::getBankInfo($id);

        $this->assign('info', $info);

        $this->display();

    }



    //确认修改系统银行(未点击图片时)

    public function updateBankCard()

    {

        $id = I('id');

        $bankname = I('post.bankname', '', 'trim');

        $bankcode = I('post.bankcode', '', 'trim');

        $info = SystembankModel::getBankInfo($id);

        if ($bankname == $info['bankname'] && $bankcode == $info['bankcode']) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '未做任何修改,请确认']);

        } else {

            $msg = '修改系统银行:' . $bankname . ',未点击图片时,';

            $sysbank = D('systembank');

            if (!$sysbank->create()) {

                $this->addAdminOperate($msg . $sysbank->getError());

                $this->ajaxReturn(['status' => 'no', 'msg' => $sysbank->getError()]);

            }

            $res = $sysbank->save();

            if ($res) {

                $this->addAdminOperate($msg . '修改成功');

                $this->ajaxReturn(['status' => 'ok', 'msg' => "银行修改成功"]);

            } else {

                $this->addAdminOperate($msg . '修改失败');

                $this->ajaxReturn(['status' => 'no', 'msg' => '银行修改失败']);

            }

        }

    }



    //确认修改系统银行(点击过图片时)

    public function updateBankCardImg()

    {

        $id = I('id');

        $bankname = I('post.bankname', '', 'trim');

        $msg = '修改系统银行:' . $bankname . ':点击图片时,';

        $sysbank = D('systembank');

        if (!$sysbank->create()) {

            $this->addAdminOperate($msg . $sysbank->getError());

            $this->ajaxReturn(['status' => 'no', 'msg' => $sysbank->getError()]);

        }

        //上传文件

        //判断目录是否存在

        if (!file_exists(C('SYSTEMBANK_PATH'))) {

            mkdir(C('SYSTEMBANK_PATH'), 0777, true);

        }

        $date_time = date('YmdHis');

        $save_name = $date_time . rand(1000, 9999);

        $upload = new \Think\Upload(); // 实例化上传类

        $upload->maxSize = 2097152; // 设置附件上传大小

//        $upload->exts = array('xls'); // 设置附件上传类型jpg|png|gif|bmp|jpeg

        $upload->rootPath = C('SYSTEMBANK_PATH'); // 设置附件上传目录

        $upload->saveName = $save_name;   //文件名

        $upload->subName = date('Y-m-d');   //子目录创建方式，以账号id命名

        // 上传文件

        $info = $upload->uploadOne($_FILES['file']);

        if (!$info) {

            $this->addAdminOperate($msg . $upload->getError());

            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);

        } else {

            $old = SystembankModel::getBankInfo($id);

            $all_path = C('SYSTEMBANK_PATH') . $info['savepath'] . $info['savename'];

            $sysbank->img_url = $all_path;

            $res = $sysbank->save();

            if ($res) {

                unlink($old['img_url']);

                $this->addAdminOperate($msg . '修改成功');

                $this->ajaxReturn(['status' => 'ok', 'msg' => "银行修改成功"]);

            } else {

                unlink($all_path);

                $this->addAdminOperate($msg . '修改失败');

                $this->ajaxReturn(['status' => 'no', 'msg' => '银行修改失败']);

            }

        }

    }



    //开启或关闭系统银行交易应用

    public function jiaoyiedit()

    {

        $id = I("post.id", "");

        $jiaoyi = I("post.jiaoyi", "");

        if ($id == "" || $jiaoyi == "") {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        $find = SystembankModel::getInfo($id);

        if (!$find) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        if ($jiaoyi == 1) {

            $msgs = '修改为开启';

        } else {

            $msgs = '修改为关闭';

        }

        $msg = '修改系统银行[' . $find['bankname'] . ']的交易应用状态:' . $msgs;

        $r = SystembankModel::editInfo($id, 'jiaoyi', $jiaoyi);

        if ($r) {

            $this->addAdminOperate($msg . ',修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);

        } else {

            $this->addAdminOperate($msg . ',修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

        }

    }



    //开启或关闭系统银行结算应用

    public function jiesuanedit()

    {

        $id = I("post.id", "");

        $jiesuan = I("post.jiesuan", "");

        if ($id == "" || $jiesuan == "") {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        $find = SystembankModel::getInfo($id);

        if (!$find) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        if ($jiesuan == 1) {

            $msgs = '修改为开启';

        } else {

            $msgs = '修改为关闭';

        }

        $msg = '修改系统银行[' . $find['bankname'] . ']的结算应用状态:' . $msgs;

        $r = SystembankModel::editInfo($id, 'jiesuan', $jiesuan);

        if ($r) {

            $this->addAdminOperate($msg . ',修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);

        } else {

            $this->addAdminOperate($msg . ',修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

        }

    }



    /**

     *  删除单个系统银行

     */

    //2019-3-22 任梦龙：修改，添加操作记录

    //2019-3-25 任梦龙：当删除单个系统银行时，那么对应的用户银行卡也需要被删除，不然会冲突

    public function DelBankCard()

    {

        $id = I("post.id", "");

        if ($id == "") {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        $find = SystembankModel::getInfo($id);

        if (!$find) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        $msg = '删除系统银行:' . $find['bankname'];

        //2019-4-19 rml：只吧系统银行图片删除,修改为软删除

        $r = SystembankModel::editInfo($id, 'del', 1);

        if ($r) {

            unlink($find['img_url']);

//            M('userbankcard')->where("bankname='" . $find['bankname'] . "'")->setField('del', 1);

            $this->addAdminOperate($msg . ',删除成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);

        } else {

            $this->addAdminOperate($msg . ',删除失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败,请重试']);

        }

    }



    /**

     * 批量删除系统银行

     */

    //2019-4-19 rml：系统银行的图片也应该删除

    public function DelAllBankCard()

    {

        $idstr = I("post.idstr", "");

        if ($idstr == "") {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请先选择记录']);

        }

        $msg = '批量删除系统银行:';

        $r = SystembankModel::delAll($idstr);

        if ($r) {

            $id_arr = explode(',', $idstr);

            foreach ($id_arr as $val) {

                $img_path = SystembankModel::getImgurl($val);

                if (file_exists($img_path)) {

                    unlink($img_path);

                }

            }

            $this->addAdminOperate($msg . '删除成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);

        }

        $this->addAdminOperate($msg . '删除失败');

        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败,请重试']);

    }



    /**

     * @throws \Exception

     * 导出银行列表EXCEL

     */

    //2019-3-22 任梦龙：添加操作记录

    public function DownloadBank()

    {

        $tablename = I("get.tablename");

        $menu_zh = array('银行名称', '银行编码');

        $menu_en = array('bankname', 'bankcode');

        $list = M($tablename)->where('del = 0')->field("bankname,bankcode")->select();

        $config = array('RowHeight' => 25, 'Width' => 35);

        if ($tablename == 'jybank') {

            $name = '交易银行';

        } else {

            $name = '结算银行';

        }

        $msg = '导出' . $name;

        $this->addAdminOperate($msg);

        $title = $name . '列表';

        DownLoadExcel($title, $menu_zh, $menu_en, $list, $config);

    }



    /****************************************************************/

    //2019-1-14 任梦龙：添加回收站页面，删除，批量删除 --真实删除数据

    //回收站系统银行页面

    public function recoveryBank()

    {

        $this->display();

    }



    //加载系统银行回收站列表

    public function loadRecoveryList()

    {

        $where = [];

        $i = 0;

        $where[$i] = 'del = 1';  //del=1：表示被软删除了的数据

        $i++;

        $bankname = I("post.bankname", "");

        if ($bankname) {

            $where[$i] = "bankname like '%" . $bankname . "%'";

            $i++;

        }

        $bankcode = I("post.bankcode", "");

        if ($bankcode) {

            $where[$i] = "bankcode like '%" . $bankcode . "%'";

            $i++;

        }

        $this->ajaxReturn(PageDataLoad('systembank', $where), 'JSON');

    }



    //真实删除单条记录

    public function delActualBank()

    {

        $id = I("post.id", "");

        if ($id == "") {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作！']);

        }

        $count = M("systembank")->where("id=" . $id)->count();

        if ($count == 0) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '该记录不存在！']);

        }

        $r = SystembankModel::delInfo($id);

        $this->recoveryReturn($r, $this->del);

    }



    //真实批量删除

    public function delAllActualBank()

    {

        $idstr = I("post.idstr", "");

        if ($idstr == "") {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择银行！']);

        }

        $r = SystembankModel::delAllInfo($idstr);

        $this->recoveryReturn($r, $this->del);

    }



    //2019-1-15 任梦龙：恢复单条记录

    public function recoveryInfo()

    {

        $id = I("post.id", "");

        if ($id == "") {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作！']);

        }

        $r = SystembankModel::regainInfo($id);

        $this->recoveryReturn($r, $this->recovery);

    }



    //2019-1-15 任梦龙：恢复多条记录

    public function recoveryAll()

    {

        $idstr = I("post.idstr", "");

        if ($idstr == "") {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择银行！']);

        }

        $r = SystembankModel::regainAllData($idstr);

        $this->recoveryReturn($r, $this->recovery);

    }



    /****************************************************************/

}