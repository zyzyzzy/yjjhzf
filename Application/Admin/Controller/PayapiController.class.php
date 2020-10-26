<?php



namespace Admin\Controller;



use Admin\Model\AdvtemplateModel;

use Admin\Model\PayapijumpModel;

use Admin\Model\PayapiaccountModel;

use Admin\Model\SystembankModel;

use Admin\Model\ShangjiabankcodeModel;

use Admin\Model\PayapiclassModel;

use Admin\Model\PayapiModel;

use Admin\Model\PayapiaccountkeystrModel;

use Admin\Model\PayapishangjiaModel;

use Admin\Model\PayapishangjiapayapiclassModel;

use Admin\Model\QrcodetemplateModel;

use Admin\Model\DaifuModel;

use Admin\Model\UserModel;

use Admin\Model\MoneytypeclassModel;

use Admin\Model\TongdaozhanghaoModel;

use Admin\Model\UserpayapiaccountModel;

use Admin\Model\PayapiaccountloopsModel;

use Admin\Model\MoneytypeModel;

use Admin\Model\UserpayapiclassModel;



class PayapiController extends CommonController

{

    public function __construct()

    {

        parent::__construct();

    }



    //通道列表页面

    //2019-4-4 任梦龙：修改

    public function PayapiList()

    {

        $PayapishangjiaList = PayapishangjiaModel::getShangjiaList(['del' => 0]);

        $this->assign("PayapishangjiaList", $PayapishangjiaList);

        $PayapiclassList = PayapiclassModel::getPayapiClass(['del' => 0]);

        $this->assign("PayapiclassList", $PayapiclassList);

        $this->display();

    }



    //加载通道列表

    public function LoadPayapiList()

    {

        $where = [];

        $i = 0;

        $where[$i] = "del=0";

        $i++;

        $zh_payname = I("post.zh_payname", "", 'trim');

        if ($zh_payname) {

            $where[$i] = "zh_payname like '" . $zh_payname . "%'";

            $i++;

        }

        $en_payname = I("post.en_payname", "", 'trim');

        if ($en_payname) {

            $where[$i] = "en_payname like '" . $en_payname . "%'";

            $i++;

        }

        $bieming_zh_payname = I("post.bieming_zh_payname", "", 'trim');

        if ($bieming_zh_payname) {

            $where[$i] = "bieming_zh_payname like '" . $bieming_zh_payname . "%'";

            $i++;

        }

        $bieming_en_payname = I("post.bieming_en_payname", "", 'trim');

        if ($bieming_en_payname) {

            $where[$i] = "bieming_en_payname like '" . $bieming_en_payname . "%'";

            $i++;

        }

        $payapishangjiaid = I("post.payapishangjiaid", "");

        if ($payapishangjiaid) {

            $where[$i] = "payapishangjiaid =" . $payapishangjiaid;

            $i++;

        }

        $payapiclassid = I("post.payapiclassid", "");

        if ($payapiclassid) {

            $where[$i] = "payapiclassid =" . $payapiclassid;

            $i++;

        }

        $status = I("post.status", "");

        if ($status <> "") {

            $where[$i] = "status =" . $status;

            $i++;

        }



        $count = D('payapi')->where($where)->count();

        //分页页面展示设置,得到数据库里的数据（del=0）二维数组

        $datalist = D('payapi')->scope('default_scope')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('id DESC')->select();

        foreach ($datalist as $key => $val) {

            //查询该通道的通道分类下是否有模板

            $class_template = QrcodetemplateModel::getClassQrcodeList($val['payapiclassid']);

            if ($class_template) {

                $datalist[$key]['template'] = 1;

            }

        }

        $ReturnArr = [

            'code' => 0,

            'msg' => '数据加载成功',

            'count' => $count,

            'data' => $datalist

        ];

        $this->ajaxReturn($ReturnArr, 'JSON');

    }



    //添加通道的页面

    public function AddPayapiList()

    {

        $PayapishangjiaList = PayapishangjiaModel::getShangjiaList(['del' => 0]);

        $this->assign("PayapishangjiaList", $PayapishangjiaList);

        $this->display();

    }



    //选择商家时，显示对应的分类列表

    public function LoadPayapiclassSelect()

    {

        $id = I("post.id", "");

        if (!$id) {

            $this->ajaxReturn(['str' => '']);

        }

        $jsonstr = PayapishangjiaModel::getClassJson($id);

        $arr = json_decode($jsonstr, true);

        $optionstr = "<option value=''></option>";

        foreach ($arr as $k) {

            if ($k["status"] == "1") {

                $class_name = PayapiclassModel::getPayapiClassname($k["payapiclassid"]);

                $optionstr .= "<option value='" . $k["payapiclassid"] . "'>" . $class_name . "</option>";

            }

        }

        $this->ajaxReturn(['str' => $optionstr]);

    }



    //确认添加通道

    public function PayapiListAdd()

    {

        $msg = '添加通道[' . I('post.zh_payname', '', 'trim') . ']:';

        $return = AddSave('payapi', 'add', '添加通道');

        $this->addAdminOperate($msg . $return['msg']);

        $this->ajaxReturn($return, "json");

    }



    //改变通道的状态

    public function PayapiStatus()

    {

        $id = I("post.id", "");

        $payapi_name = PayapiModel::getPayapiName($id);

        $status = I("post.status", "");

        $r = PayapiModel::editPayapiStatus($id, $status);

        $msg = "修改通道[" . $payapi_name . "]状态:";

        if ($status == 1) {

            $stat = "修改为启用";

        } else {

            $stat = "修改为禁用";

        }

        if ($r) {

            $this->addAdminOperate($msg . $stat . ',修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);

        } else {

            $this->addAdminOperate($msg . $stat . ',修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

        }

    }



    //修改通道页面

    public function EditPayapiList()

    {

        $id = I("get.id", "");

        if (!$id) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请不要非法操作']);

        }

        $find = PayapiModel::getPayapiInfo($id);

        if (!$find) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请不要非法操作']);

        }

        $find['payapishangjianame'] = PayapishangjiaModel::getShangjiaName($find['payapishangjiaid']);

        $find['payapi_class_name'] = PayapiclassModel::getPayapiClassname($find['payapiclassid']);

        $this->assign("find", $find);

        $this->display();

    }



    //确认修改通道

    public function PayapiListEdit()

    {

        $id = I("post.id", "");

        $payapi_name = PayapiModel::getPayapiName($id);

        $msg = '编辑通道[' . $payapi_name . ']:';

        $return = AddSave('payapi', 'save', '通道编辑');

        $this->addAdminOperate($msg . $return['msg']);

        $this->ajaxReturn($return, "json");

    }



    //删除通道

    //2019-4-4 任梦龙：1. 判断该通道是否被用户使用 2. 判断该通道下面是否有账号在使用

    public function DelPayapiList()

    {

        $id = I("post.id", "");

        if (!$id) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        $find = PayapiModel::getPayapiInfo($id);

        if (!$find) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '该记录不存在']);

        }

        $msg = '删除通道[' . $find['zh_payname'] . ']:';

        //判断该通道是否被用户使用

        $user_payapi = UserpayapiclassModel::getUserpayapi($id);

        if ($user_payapi) {

            $this->addAdminOperate($msg . '该通道正在被用户使用，暂不能删除');

            $this->ajaxReturn(['status' => 'on_use', 'msg' => '该通道正在被使用，暂不能删除']);

        }

        //判断该通道下面是否有账号

        $payapi_account = TongdaozhanghaoModel::getTongdaoZhanghao($id);

        if ($payapi_account) {

            $this->addAdminOperate($msg . '该通道下有账号在使用，暂不能删除');

            $this->ajaxReturn(['status' => 'on_use', 'msg' => '该通道下有账号在使用，暂不能删除']);

        }

        $d = PayapiModel::delPayapi($id);

        if ($d) {

            $this->addAdminOperate($msg . '删除成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);

        } else {

            $this->addAdminOperate($msg . '删除失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);

        }

    }



    //显示账号设置页面

    //2019-4-4 任梦龙：修改

    public function Showpayapiaccount()

    {

        $payapi_id = I("get.id", "");

        $zh_payname = PayapiModel::getPayapiName($payapi_id);

        $this->assign("payapi_id", $payapi_id);

        $this->assign("zh_payname", $zh_payname);

        $this->display();

    }



    //加载账号设置列表页面

    //2019-4-4 任梦龙：只需要系统的账号 (user_id=0:系统)

    public function loadShowpayapiaccount()

    {

        $payapi_id = I('get.payapiid');

        $payapishangjiaid = PayapiModel::getPayapiField($payapi_id, 'payapishangjiaid');

        //得到通道的默认账号

//        $defaultpayapiaccount_id = M('userpayapiclass')->where('userid = 0 and payapiid = ' . $payapi_id)->getField('defaultpayapiaccountid');

        $default_account = UserpayapiclassModel::getDefaultAccount(['userid' => 0, 'payapiid' => $payapi_id]);

        $where = array(

            'payapishangjiaid' => $payapishangjiaid,

            'status' => 1,

            'del' => 0,

            'user_id' => 0

        );

        $count = M("payapiaccount")->where($where)->count();

        //这个通道对应的账号列表

        $list = M("payapiaccount")->field(['id', 'bieming', 'user_id'])->where($where)->page(I('post.page', 1), I('post.limit', 10))->order('id DESC')->select();

        //被选择中的账号

        $checked_list = TongdaozhanghaoModel::getCheckAccount(['payapiid' => $payapi_id, 'userid' => 0]);

        //in_array判断账号列表中是否存在选择的账号

        foreach ($list as $k => $v) {

            $list[$k]['payapi_id'] = $payapi_id;

            if (in_array($v['id'], $checked_list)) {

                $list[$k]['check'] = 1;

            }

            //如果默认账号的id存在于账号列表中。给默认值 1

            if ($default_account['defaultpayapiaccountid'] == $v['id']) {

                $list[$k]['default'] = 1;

            }

        }

        $return = [

            'code' => 0,

            'msg' => '数据加载成功',

            'count' => $count,

            'data' => $list

        ];

        $this->ajaxReturn($return, 'json');

    }



    //选择某个支付通道的某个通道账号的状态

    public function TongdaoZhanghao()

    {

        $payapiid = I("post.payapiid", "");

        $payapi_name = PayapiModel::getPayapiName($payapiid);

        $payapiaccountid = I("post.payapiaccountid", "");

        $checked = I("post.checked", "");

        $data = [];

        if ($payapiaccountid == "" || $payapiid == "" || $checked == "") {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        $account_name = PayapiaccountModel::getAccountName($payapiaccountid);

        $msg = "设置支付通道[" . $payapi_name . "]的通道账号[" . $account_name . "]的状态:";

        //状态关闭时,先去判断该账号是否正在被其它用户所使用，如果有，则提示暂不能删除(由账号id在userpayapiaccount表中查询)

        if ($checked == 0) {

            $str = '修改为关闭,';

            $user_account = UserpayapiaccountModel::isExistUserAccount($payapiaccountid);

            if ($user_account) {

                $this->ajaxReturn(['status' => 'no', 'msg' => '该账号被用户使用,暂不能关闭']);

            }

            $res = TongdaozhanghaoModel::delTongdaoZhanghao(['payapiid' => $payapiid, 'payapiaccountid' => $payapiaccountid, 'userid' => 0]);

        } else {

            $str = '修改为开启,';

            $data = [

                'payapiid' => $payapiid,

                'payapiaccountid' => $payapiaccountid,

                'userid' => 0,

            ];

            $res = TongdaozhanghaoModel::addTongdaoZhanghao($data);

        }

        if ($res) {

            $this->addAdminOperate($msg . $str . '修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);

        } else {

            $this->addAdminOperate($msg . $str . '修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

        }

    }



    //设置默认的账号

    public function defaultongdaoZhanghao()

    {

        $payapiid = I("post.payapiid", "");

        $payapiaccountid = I("post.payapiaccountid", "");

        $checked = I("post.checked", "");

        $data = [];

        if ($payapiaccountid == "" || $payapiid == "" || $checked == "") {

            $data["status"] = "no";

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        $payapi_name = PayapiModel::getPayapiName($payapiid);

        $account_name = PayapiaccountModel::getAccountName($payapiaccountid);

        $msg = "设置支付通道[" . $payapi_name . "]下的通道账号[" . $account_name . "]的默认状态:";

        $payapiclass_id = PayapiModel::getPayapiclassid($payapiid);

        $where = [

            'payapiclassid' => $payapiclass_id,

            'userid' => 0,

            'payapiid' => $payapiid

        ];

        //2019-4-4 任梦龙：当设置为默认时，添加一条数据,当取消默认时，删除数据

        $old_default = UserpayapiclassModel::getDefaultAccount($where);

        if ($checked == 0) {

            $str = '取消默认状态,';

            $res = UserpayapiclassModel::delDefaultAccount($where);

        } else {

            $str = '设置为默认状态,';

            if ($old_default) {

                $res = UserpayapiclassModel::editDefaultAccount($where, ['defaultpayapiaccountid' => $payapiaccountid]);

            } else {

                $data = [

                    'payapiclassid' => $payapiclass_id,

                    'userid' => 0,

                    'payapiid' => $payapiid,

                    'defaultpayapiaccountid' => $payapiaccountid

                ];

                $res = UserpayapiclassModel::addDefaultAccount($data);

            }

        }

        if ($res) {

            $this->addAdminOperate($msg . $str . '修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);

        } else {

            $this->addAdminOperate($msg . $str . '修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

        }

    }



    //批量启用账号

    public function addAllTongdaoZhanghao()

    {

        $idstr = I('post.idstr', '');  //账号id序列

        $payapi_id = I('get.payapi_id', '');

        $payapi_name = PayapiModel::getPayapiName($payapi_id);

        $msg = "批量启用支付通道[" . $payapi_name . "]的通道账号:";

        $arr = explode(',', $idstr);

        if (!($payapi_id && $arr)) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '非法操作']);

        }

        foreach ($arr as $val) {

            //如果有重复的，则跳过

            $count = M('tongdaozhanghao')->where('payapiid = ' . $payapi_id . ' and payapiaccountid = ' . $val)->count();

            if ($count <= 0) {

                $data = [

                    'payapiid' => $payapi_id,

                    'payapiaccountid' => $val,

                    'userid' => 0

                ];

                M('tongdaozhanghao')->add($data);

            }

        }

        $this->addAdminOperate($msg . '批量启用成功');

        $this->ajaxReturn(['status' => 'ok', 'msg' => '批量启用成功!'], 'json');

    }



    //批量关闭账号

    public function delAllTongdaoZhanghao()

    {

        $idstr = I('post.idstr', '');  //账号id序列

        $payapi_id = I('get.payapi_id', '');

        $payapi_name = PayapiModel::getPayapiName($payapi_id);

        $msg = "批量关闭支付通道[" . $payapi_name . "]的通道账号:";

        $arr = explode(',', $idstr);  //得到选中的账号id数组(即被包含在通道账号列表中)

        if (!($payapi_id && $arr)) {

            $this->addAdminOperate($msg . '非法操作');

            $this->ajaxReturn(['status' => 'no', 'msg' => '非法操作']);

        }

        foreach ($arr as $val) {

            //如果该账号正在被使用，则跳过

            $count = M('userpayapiaccount')->where('accountid = ' . $val)->count();

            if ($count <= 0) {

                M('tongdaozhanghao')->where('payapiid = ' . $payapi_id . ' and payapiaccountid = ' . $val)->delete();

            }

        }

        $this->addAdminOperate($msg . '批量关闭成功');

        $this->ajaxReturn(['status' => 'ok', 'msg' => '批量关闭成功!'], 'json');

    }



    //查看当前通道当前账号被哪些用户所使用的页面

    public function seePayapiAccountInfo()

    {

        $payapi_id = I("get.payapi_id", ""); //通道id

        $payapiaccount_id = I("get.payapiaccount_id", "");  //账号id

        $this->assign('payapi_id', $payapi_id);

        $this->assign('payapiaccount_id', $payapiaccount_id);

        $this->display();

    }



    //加载当前通道当前账号被哪些用户所使用的列表数据

    public function loadSeePayapiAccountInfo()

    {

        //读取表前缀

        $payapiaccount_id = I('get.payapiaccountid');

        $bieming = M('payapiaccount')->where('id = ' . $payapiaccount_id)->getField('bieming');

        //由账号id，通过userpayapiaccount表查询出该账号被哪些用户所使用

        $userpayapiclass_arr = M('userpayapiclass')->alias('a')

            ->join('__USERPAYAPIACCOUNT__ b ON a.id = b.userpayapiclassid')

            ->where('b.accountid = ' . $payapiaccount_id)

            ->select();

        foreach ($userpayapiclass_arr as $k => $v) {

            $user_name = M('user')->where('id = ' . $v['userid'])->getField('username');  //查出用户

            $zh_payname = M('payapi')->where('id = ' . $v['payapiid'])->getField('zh_payname');  //查出通道

            $class_name = M('payapiclass')->where('id = ' . $v['payapiclassid'])->getField('classname');  //查出通道分类名称

            $userpayapiclass_arr[$k]['user_name'] = $user_name;

            $userpayapiclass_arr[$k]['zh_payname'] = $zh_payname;

            $userpayapiclass_arr[$k]['class_name'] = $class_name;

            $userpayapiclass_arr[$k]['accountid'] = $payapiaccount_id;  //将账号id存入

            $userpayapiclass_arr[$k]['bieming'] = $bieming;  //将账号别名存入

        }

        $return_data = [

            'code' => 0,

            'msg' => '数据加载成功',//响应结果

            'count' => count($userpayapiclass_arr),//总页数

            'data' => $userpayapiclass_arr  //获取到的数据

        ];

        $this->ajaxReturn($return_data, 'json');

    }



    //删除该账号下某一个用户的账号,与用户账号相关联的数据也应该删除,关联数据：用户账号限额（tongdaozhanghao），用户账号费率（feilv）,用户设置到账方案（usermoneyclass）

    public function seePayapiAccountInfoDel()

    {

        $id = I('post.id');   //用户账号id （userpayapiaccount表）

        $user_id = I('post.user_id');   //用户id

        $user_name = UserModel::getUserName($user_id);

        $payapi_id = I('post.payapi_id');   //通道id

        $payapi_name = PayapiModel::getPayapiName($payapi_id);

        $payapiaccount_id = I('post.payapiaccount_id');   //账号id

        $account_name = PayapiaccountModel::getAccountName($payapiaccount_id);

        $msg = "删除用户[" . $user_name . "]在支付通道[" . $payapi_name . "]下开启的通道账号[" . $account_name . "]:";

        if (!$id) {

            $this->addAdminOperate($msg . '非法操作');

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        M('userpayapiaccount')->where('id = ' . $id)->delete();  //删除用户账号信息

        $where_quota = [

            'payapiid' => $payapi_id,

            'payapiaccountid' => $payapiaccount_id,

            'userid' => $user_id

        ];

        //如果用户设置了限额，则删除用户账号限额信息

        $user_quota = M('tongdaozhanghao')->where($where_quota)->count();

        if ($user_quota > 0) {

            M('tongdaozhanghao')->where($where_quota)->delete();

        }

        //如果用户设置了到账方案，则删除

        $where_set = [

            'payapiaccountid' => $payapiaccount_id,

            'userid' => $user_id

        ];

        $user_moneyclass = M('usermoneyclass')->where($where_set)->count();

        if ($user_moneyclass > 0) {

            M('usermoneyclass')->where($where_set)->delete();

        }

        $this->addAdminOperate($msg . '删除成功');

        $this->ajaxReturn(['status' => 'ok', 'msg' => '操作成功']);

    }



    //通道的跳转设置页面

    public function editPayapiJump()

    {

        $payapi_id = I('get.id');

        $this->assign('payapi_id', $payapi_id);

        //查询通道的设置

        $jump_type = PayapiModel::getPayapiJump($payapi_id);

        $this->assign('jump_type', $jump_type);

        if ($jump_type == 1) {

            //查询排除的用户信息

            $remove_users = PayapijumpModel::getPayapiJumpRemove($payapi_id);

            $this->assign('remove_users', $remove_users);

            //查询所有的广告模板

            $all_template = AdvtemplateModel::getAdvTemplateList();

            $this->assign('all_template', $all_template);

            //查询选择的广告模板

            $template = PayapiModel::getPayapiAdvTempalte($payapi_id);

            $this->assign('template', $template);

        }

        $this->display();

    }



    //用户支付成功后是否跳转到广告页面的设置

    public function payapiJumpEdit()

    {

        $payapi_id = I('get.payapi_id');

        $payapi_name = PayapiModel::getPayapiName($payapi_id);

        $msg = "支付通道[" . $payapi_name . "]的同步跳转设置:";

        $jump_type = I('get.jump_type');

        if ($jump_type == 1) {

            $stat = "设置为同步跳转前加入广告页面";

        } else {

            $stat = "设置为直接同步跳转到用户回调地址";

        }

        $res = PayapiModel::setPayapiJump($payapi_id, $jump_type);

        if ($res) {

            $this->addAdminOperate($msg . $stat . ',修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功'], 'json');

        } else {

            $this->addAdminOperate($msg . $stat . ',修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败'], 'json');

        }

    }



    //选择广告模板

    public function selectPayapiAdv()

    {

        $payapi_id = I('payapi_id');

        $adv_templateid = I('adv_templateid');

        $payapi_name = PayapiModel::getPayapiName($payapi_id);

        $adv_name = AdvtemplateModel::getTemplateName($adv_templateid);

        $msg = "选择通道[" . $payapi_name . "]的广告模板为[" . $adv_name . "]:";

        $res = PayapiModel::setPayapiAdvTempalte($payapi_id, $adv_templateid);

        if ($res) {

            $this->addAdminOperate($msg . '修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功'], 'json');

        } else {

            $this->addAdminOperate($msg . '修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败'], 'json');

        }

    }



    //排除用户的搜索

    public function jumpUserSearch()

    {

        $user_name = I('user_name');

        $payapi_id = I('payapi_id');

        $res = UserModel::getUserByName($user_name);

        if ($res) {

            foreach ($res as $k => $v) {

                //查询是否已添加

                if (PayapijumpModel::getPayapiUserJump($payapi_id, $v['userid'])) {

                    $res[$k]['jump'] = 1;

                }

                $res[$k]['payapi_id'] = $payapi_id;

            }

            $this->ajaxReturn(['status' => 'ok', 'data' => $res], 'json');

        } else {

            $this->ajaxReturn(['status' => 'no', 'msg' => '无搜索记录'], 'json');

        }

    }



    //排除的用户的删除

    public function jumpUserDelete()

    {

        $id = I('get.id');

        if (!$id) {

            $this->addAdminOperate("通道广告跳转设置排除用户:非法操作");

            $this->ajaxReturn(['status' => 'no', 'msg' => '非法操作'], 'json');

        }

        $info = PayapijumpModel::getPayapiUserJumpById($id);

        if (!$info) {

            $this->addAdminOperate("通道广告跳转设置排除用户:记录不存在");

            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败'], 'json');

        }

        $user_name = UserModel::getUserName($info['user_id']);

        $payapi_name = PayapiModel::getPayapiName($info['payapi_id']);

        $msg = "支付通道[" . $payapi_name . "]的广告跳转设置排除的用户[" . $user_name . "]删除:";

        $res = PayapijumpModel::payapiJumpDelete($id);

        if ($res) {

            $this->addAdminOperate($msg . '删除成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功'], 'json');

        } else {

            $this->addAdminOperate($msg . '删除失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败'], 'json');

        }

    }



    //排除的用户的添加

    public function jumpUserAdd()

    {

        $user_id = I('get.user_id');

        $payapi_id = I('get.payapi_id');

        $user_name = UserModel::getUserName($user_id);

        $payapi_name = PayapiModel::getPayapiName($payapi_id);

        $msg = "支付通道[" . $payapi_name . "]的广告跳转设置添加排除用户[" . $user_name . "]:";

        $res = PayapijumpModel::payapiJumpAdd($payapi_id, $user_id);

        if ($res) {

            $ret['id'] = $res;

            $ret['user_id'] = $user_id;

            $ret['user_name'] = M('user')->where('id=' . $user_id)->getField('username');

            $ret['member_id'] = M('secretkey')->where('userid=' . $user_id)->getField('memberid');

            $this->addAdminOperate($msg . '添加成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '添加成功', 'data' => $ret], 'json');

        } else {

            $this->addAdminOperate($msg . '添加失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '添加失败'], 'json');

        }

    }



    //选择扫码模板页面

    public function selectTemplate()

    {

        $payapiid = I('get.id');

        if (!$payapiid) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请不要非法操作']);

        }

        //查询该账号的模板

        $qrcodeid = PayapiModel::getQrcode($payapiid);

        $this->assign('qrcodeid', $qrcodeid);

        $this->assign('payapiid', $payapiid);



        //查询所有模板

        $payapicalss_id = PayapiModel::getPayapiclassid($payapiid);

        $allImgs = QrcodetemplateModel::getClassQrcodeList($payapicalss_id);

        $this->assign('allImgs', $allImgs);



        $this->display();

    }



    //确认选择的模板

    public function confirmTeplate()

    {

        $payapiid = I('post.payapiid');

        $payapi_name = PayapiModel::getPayapiName($payapiid);

        $templateid = I('post.templateid');

        //查询该账号的模板

        $qrcodeid = PayapiModel::getQrcode($payapiid);

        if ($templateid) {

            $template_name = QrcodetemplateModel::getTemplateName($templateid);

            $msg = "支付通道[" . $payapi_name . "]选择扫码模板[" . $template_name . "]:";

        } else {

            $template_name = QrcodetemplateModel::getTemplateName($qrcodeid);

            $msg = "支付通道[" . $payapi_name . "]取消扫码模板[" . $template_name . "]:";

        }

        $res = true;

        if ($qrcodeid != $templateid) {

            //修改账号表记录

            $data['qrcodeid'] = $templateid;

            $res = PayapiModel::savePayapiInfo($payapiid, $data);

        }

        if ($res) {

            $this->addAdminOperate($msg . '设置成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '扫码模板选择成功',]);

        } else {

            $this->addAdminOperate($msg . '设置失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '扫码模板选择失败',]);

        }

    }





    /**

     * 通道商家

     */

    //通道商家页面

    public function PayapiShangjia()

    {

        $payapiclasslist = PayapiclassModel::getPayapiClass(['del' => 0, 'status' => 1]);

        $this->assign("payapiclasslist", $payapiclasslist);

        $this->display();

    }



    //加载通道商家页面

    public function LoadPayapiShangjia()

    {

        $this->ajaxReturn(PageDataLoad('payapishangjia', ['del' => 0]), 'JSON');

    }



    //添加通道商家页面

    public function AddPayapiShangjia()

    {

        $this->display();

    }



    //确认添加通道商家，同时将商家-分类关系存入表

    //2019-4-18 rml：添加模型自动验证

    public function PayapiShangjiaAdd()

    {

        $msg = '添加通道商家:' . I('post.shangjianame', '', 'trim') . ',';

        $return = AddSave('payapishangjia', 'add', '添加通道商家');

        if ($return["status"] == "ok") {

            $payapiclasslist = PayapiclassModel::getPayapiClass(['del' => 0, 'status' => 1]);

            $shangjiaclass = M("payapishangjiapayapiclass");

            foreach ($payapiclasslist as $k) {

                $shangjiaclass->add(['payapishangjiaid' => $return["id"], 'payapiclassid' => $k["id"]]);

                payapishangjiaclassjson($return["id"]);

            }

        }

        $this->addAdminOperate($msg . $return['msg']);

        $this->ajaxReturn($return, "json");

    }



    //修改通道商家页面

    public function EditPayapiShangjia()

    {

        $id = I("get.id", "");

        if (!$id) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请不要非法操作']);

        }

        $find = PayapishangjiaModel::getShangjiaInfo($id);

        if ($find) {

            $this->assign("find", $find);

            $this->display();

        } else {

            $this->ajaxReturn(['status' => 'no', 'msg' => '该记录不存在']);

        }

    }



    //确认修改通道商家

    public function PayapiShangjiaEdit()

    {

        $msg = '修改通道商家:' . I('post.shangjianame', '', 'trim') . ',';

        $return = AddSave('payapishangjia', 'save', '通道商家编辑');

        $this->addAdminOperate($msg . $return['msg']);

        $this->ajaxReturn($return, "json");

    }



    //删除通道商家

    public function DelPayapiShangjia()

    {

        $msg = '删除通道商家:';

        $id = I("post.id", "");

        if (!$id) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        $find = PayapishangjiaModel::getShangjiaInfo($id);

        if (!$find) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '该记录不存在']);

        }



        //删除商家前先去查询该商家下的账号中有没有被使用的，否则拦截

        $shangjia_account_id = PayapiaccountModel::getAccountidList(['del' => 0, 'payapishangjiaid' => $id]);  //商家下的账号

        $shangjai_payapi_id = PayapiModel::getPayapiidList(['del' => 0, 'payapishangjiaid' => $id]);  //商家下的交易通道

        $shangjai_daifu_id = DaifuModel::getDaifuidList(['del' => 0, 'payapishangjiaid' => $id]); //商家下的代付通道



        //分别得到商家下的账号和通道，如果两者之一存在，则拦截，否则表示该商家没有进行任何操作，可以删除

        if ($shangjia_account_id || $shangjai_payapi_id || $shangjai_daifu_id) {

            $this->addAdminOperate($msg . $find['shangjianame'] . ',该商家正在被使用，暂不能删除');

            $this->ajaxReturn(['status' => 'on_use', 'msg' => '该商家正在被使用，暂不能删除']);

        } else {

            $d = PayapishangjiaModel::delShangjia($id, 1);

            if ($d) {

                PayapishangjiapayapiclassModel::delToShangjiaId($id, 1);

                $this->addAdminOperate($msg . $find['shangjianame'] . ',删除成功');

                $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);

            } else {

                $this->addAdminOperate($msg . $find['shangjianame'] . ',删除失败');

                $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);

            }



        }

    }



    //修改通道商家列表中分类状态

    public function PayapiShangjiaClassEdit()

    {

        $payapishangjiaid = I("post.payapishangjiaid", 0);

        $shangjia_name = PayapishangjiaModel::getShangjiaName($payapishangjiaid);

        $payapiclassid = I("post.payapiclassid", 0);

        $payapiclass_name = PayapiclassModel::getPayapiClassname($payapiclassid);

        $msg = '修改通道商家[' . $shangjia_name . ']的通道分类[' . $payapiclass_name . ']状态:';

        $status = I("post.status");

        if (!$payapishangjiaid || !$payapiclassid) {

            $this->addAdminOperate($msg . '非法操作');

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        $find = PayapishangjiapayapiclassModel::getShangjiaClassInfo($payapishangjiaid, $payapiclassid);

        if (!$find) {

            $this->addAdminOperate($msg . '该记录不存在');

            $this->ajaxReturn(['status' => 'no', 'msg' => '该记录不存在']);

        }

        $r = PayapishangjiapayapiclassModel::editShangjiaClassStatus($payapishangjiaid, $payapiclassid, $status);

        if ($status == 1) {

            $stat = "修改为开通";

        } else {

            $stat = "修改为关闭";

        }

        if ($r) {

            payapishangjiaclassjson($payapishangjiaid);

            $this->addAdminOperate($msg . $stat . ',修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);

        } else {

            $this->addAdminOperate($msg . $stat . ',修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

        }

    }



    //银行编码设置页面（交易和结算通用）

    public function shangjiaBankcode()

    {

        $shangjiaid = I('get.id');

        $type = I('get.type');

        //查询所有系统银行

        if ($type == 1) {

            //查询所有启用的交易银行

            $allBanks = SystembankModel::getAllTrueBankInfo('jiaoyi');

        } else {

            $allBanks = SystembankModel::getAllTrueBankInfo('jiesuan');

        }

        //查询该商家是否有银行编码

        foreach ($allBanks as $k => $v) {

            $shangjia_bank = ShangjiabankcodeModel::getShangjiaBankInfo($v['id'], $shangjiaid);

            if ($type == 1) {

                $allBanks[$k]['shangjia_bankcode'] = $shangjia_bank['trans_bankcode'] != '' ? $shangjia_bank['trans_bankcode'] : '';

            } else {

                $allBanks[$k]['shangjia_bankcode'] = $shangjia_bank['settle_bankcode'] != '' ? $shangjia_bank['settle_bankcode'] : '';

            }

        }

        $this->assign('allBanks', $allBanks);

        $this->assign('shangjiaid', $shangjiaid);

        $this->assign('type', $type);

        $this->display();

    }



    //银行编码修改处理程序

    public function shangjiaBankcodeEdit()

    {

        $type = I('post.type');

        $shangjia_id = I('post.shangjia_id');

        $shangjia_name = PayapishangjiaModel::getShangjiaName($shangjia_id);

        $msg = "设置通道商家[" . $shangjia_name . "]的银行编码:";

        $allBanks = I('post.bank');

        $res = true;

        foreach ($allBanks as $k => $v) {

            $shangjia_bank = ShangjiabankcodeModel::getShangjiaBankInfo($k, $shangjia_id);

            //如果该商家有该银行的编码，直接修改

            if ($shangjia_bank) {

                $type == 1 ? $data['trans_bankcode'] = $v : $data['settle_bankcode'] = $v;

                ShangjiabankcodeModel::editShangjiaBankInfo($k, $shangjia_id, $data);

            } else {

                //如果该商家没有该银行的编码，而且编码不为空，则添加

                if ($v != '') {

                    $data['systembank_id'] = $k;

                    $data['shangjia_id'] = $shangjia_id;

                    $type == 1 ? $data['trans_bankcode'] = $v : $data['settle_bankcode'] = $v;

                    $res = ShangjiabankcodeModel::addShangjiaBank($data);

                }

            }

        }

        if ($type == 1) {

            $stat = "设置交易银行编码,";

        } else {

            $stat = "设置结算银行编码,";

        }

        if ($res) {

            $this->addAdminOperate($msg . $stat . '设置成功');

            $this->ajaxReturn([

                'status' => 'ok',

                'msg' => '修改成功'

            ]);

        } else {

            $this->addAdminOperate($msg . $stat . '设置失败');

            $this->ajaxReturn([

                'status' => 'no',

                'msg' => '修改失败，请稍后重试'

            ]);

        }

    }





    /**

     * 通道分类

     */

    //通道分类页面

    public function PayapiClass()

    {

        $this->display();

    }



    //加载通道分类列表

    //2019-4-18 rml:添加搜索条件

    public function LoadPayapiClass()

    {

        $where = [];

        $i = 0;

        $where[$i] = 'del=0';

        $i++;

        $classname = I("post.classname", "", 'trim');

        if ($classname) {

            $where[$i] = "classname like '%" . $classname . "%'";

            $i++;

        }

        $classbm = I("post.classbm", "", 'trim');

        if ($classbm) {

            $where[$i] = "classbm like '%" . $classbm . "%'";

            $i++;

        }

        $status = I("post.status", "");

        if ($status <> "") {

            $where[$i] = "status =" . $status;

            $i++;

        }

        $this->ajaxReturn(PageDataLoad('payapiclass', $where), 'JSON');

    }



    //导出通道分类

    public function DownloadPayapiClass()

    {

        $title = '通道分类列表';

        $menu_zh = array('通道分类', '分类编码', '默认运营费率', '单笔最低手续费（元）');

        $menu_en = array('classname', 'classbm', 'order_feilv', 'order_min_feilv');

        $list = PayapiclassModel::exportPayapiClass(['del' => 0]);

        $config = array('RowHeight' => 25, 'Width' => 35);

        $this->addAdminOperate('导出通道分类');

        DownLoadExcel($title, $menu_zh, $menu_en, $list, $config);

    }



    //添加通道分类页面

    public function AddPayapiClass()

    {

        $this->display();

    }



    //确认添加通道分类

    //2019-4-18 rml：添加模型自动验证，代替手动验证,并且是在上传图片之前验证，避免上传图片后验证又错了

    public function PayapiClassAdd()

    {

        $post = I('post.');

        $msg = '添加通道分类:' . $post['classname'] . ',';

        $tablename = D('payapiclass');

        if (!$tablename->create($post)) {

            $this->addAdminOperate($msg . $tablename->getError());

            $this->ajaxReturn(['status' => 'no', 'msg' => $tablename->getError()]);

        }

        //上传文件

        if (!file_exists(C('PAYAPICLASS_PATH'))) {

            mkdir(C('PAYAPICLASS_PATH'), '0777', true);

        }

        $date_time = date('YmdHis');

        $save_name = $date_time . rand(1000, 9999);

        $upload = new \Think\Upload(); // 实例化上传类

        $upload->maxSize = 2097152; // 设置附件上传大小

//        $upload->exts = array('xls'); // 设置附件上传类型jpg|png|gif|bmp|jpeg

        $upload->rootPath = C('PAYAPICLASS_PATH'); // 设置附件上传目录

        $upload->saveName = $save_name;   //文件名

        $upload->subName = date('Y-m-d');   //子目录创建方式，以账号id命名

        // 上传文件

        $info = $upload->uploadOne($_FILES['file']);

        if (!$info) {

            $this->addAdminOperate($msg . '图片上传错误,' . $upload->getError());

            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);

        } else {

            $all_path = C('PAYAPICLASS_PATH') . $info['savepath'] . $info['savename'];

            $post['img_url'] = $all_path;

            $res = PayapiclassModel::addClassInfo($post);

            if ($res) {

                $list = PayapishangjiaModel::getShangjiaList(['del' => 0]);

                $payapiclassid = $res;

                $shangjiaclass = M("payapishangjiapayapiclass");

                foreach ($list as $k) {

                    $shangjiaclass->payapishangjiaid = $k["id"];

                    $shangjiaclass->payapiclassid = $payapiclassid;

                    $shangjiaclass->add();

                    payapishangjiaclassjson($k["id"]);

                }

                $this->addAdminOperate($msg . '添加成功');

                $this->ajaxReturn(['status' => 'ok', 'msg' => "通道分类添加成功"]);

            } else {

                unlink($all_path);

                $this->addAdminOperate($msg . '添加失败');

                $this->ajaxReturn(['status' => 'no', 'msg' => '通道分类添加失败']);

            }

        }

    }



    //修改通道分类页面

    public function EditPayapiClass()

    {

        $id = I("get.id", "");

        if (!$id) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请不要非法操作']);

        }

        $find = PayapiclassModel::getPayapiClassInfo($id);

        if ($find) {

            $this->assign("find", $find);

            $this->display();

        } else {

            $this->ajaxReturn(['status' => 'no', 'msg' => '该记录不存在']);

        }

    }



    //确认修改通道分类(未点击图片时)

    //2019-4-18 rml:添加模型自动验证,优化代码

    public function PayapiClassEdit()

    {

        $post = I('post.');

        $msg = '修改通道分类[' . $post['classname'] . ']:未上传图片,';

        $tablename = D('payapiclass');

        if (!$tablename->create($post)) {

            $this->addAdminOperate($msg . $tablename->getError());

            $this->ajaxReturn(['status' => 'no', 'msg' => $tablename->getError()]);

        }

        $res = $tablename->save($post);

        if ($res) {

            $this->addAdminOperate($msg . '修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => "分类修改成功"]);

        } else {

            $this->addAdminOperate($msg . '修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '分类修改失败']);

        }

    }



    //确认修改通道分类(点击图片时)

    //2019-4-18 rml:添加模型自动验证,优化代码

    public function PayapiClassEditImg()

    {

        $post = I('post.');

        $old = PayapiclassModel::getPayapiClassInfo($post['id']);

        $msg = '修改通道分类[' . $post['classname'] . ']:有上传图片,';

        $tablename = D('payapiclass');

        if (!$tablename->create($post)) {

            $this->addAdminOperate($msg . $tablename->getError());

            $this->ajaxReturn(['status' => 'no', 'msg' => $tablename->getError()]);

        }

        //上传文件

        if (!file_exists(C('PAYAPICLASS_PATH'))) {

            mkdir(C('PAYAPICLASS_PATH'), '0777', true);

        }

        $date_time = date('YmdHis');

        $save_name = $date_time . rand(1000, 9999);

        $upload = new \Think\Upload(); // 实例化上传类

        $upload->maxSize = 2097152; // 设置附件上传大小

//        $upload->exts = array('xls'); // 设置附件上传类型jpg|png|gif|bmp|jpeg

        $upload->rootPath = C('PAYAPICLASS_PATH'); // 设置附件上传目录

        $upload->saveName = $save_name;   //文件名

        $upload->subName = date('Y-m-d');   //子目录创建方式，以账号id命名

        // 上传文件

        $info = $upload->uploadOne($_FILES['file']);

        if (!$info) {

            $this->addAdminOperate($msg . '图片上传有误,' . $upload->getError());

            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);

        } else {

            $all_path = C('PAYAPICLASS_PATH') . $info['savepath'] . $info['savename'];

            $post['img_url'] = $all_path;

            $res = $tablename->save($post);

            if ($res) {

                unlink($old['img_url']);

                $this->addAdminOperate($msg . '修改成功');

                $this->ajaxReturn(['status' => 'ok', 'msg' => "分类修改成功"]);

            } else {

                unlink($all_path);

                $this->addAdminOperate($msg . '修改失败');

                $this->ajaxReturn(['status' => 'no', 'msg' => '分类修改失败']);

            }

        }

    }



    //删除通道分类

    public function DelPayapiClass()

    {

        $id = I("post.id", "");

        $msg = "删除通道分类:";

        if (!$id) {

            $this->addAdminOperate($msg . '非法操作');

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        $find = PayapiclassModel::getPayapiClassInfo($id);

        if (!$find) {

            $this->addAdminOperate($msg . '该记录不存在');

            $this->ajaxReturn(['status' => 'no', 'msg' => '该记录不存在']);

        }

        if ($find["sys"] != 0) {

            $this->addAdminOperate($msg . '系统通道分类不能删除');

            $this->ajaxReturn(['status' => 'no', 'msg' => '系统通道分类不能删除']);

        }

        //判断该分类是否在用户分类表中

        $user_class_count = PayapiclassModel::getUserClass($id);

        if ($user_class_count > 0) {

            $this->addAdminOperate($msg . '该通道分类正在被使用，暂不能删除');

            $this->ajaxReturn(['status' => 'on_use', 'msg' => '该通道分类正在被使用，暂不能删除']);

        }

        $d = PayapiclassModel::delPayapiClass($id, 1);

        if ($d) {

            PayapishangjiapayapiclassModel::delShangjiaAndClass($id, 1);

            $list = PayapishangjiaModel::getShangjiaList(['del' => 0]);

            foreach ($list as $k) {

                payapishangjiaclassjson($k["id"]);

            }

            $this->addAdminOperate($msg . '删除成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);

        } else {

            $this->addAdminOperate($msg . '删除失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);

        }

    }



    //给通道分类添加状态值

    public function payapiClassStatus()

    {

        $id = I("post.id", "");

        $find = PayapiclassModel::getPayapiClassInfo($id);

        $msg = "修改通道分类[" . $find['classname'] . "]状态:";

        $status = I("post.status", "");

        if ($status == 1) {

            $stat = "修改为启用";

        } else {

            $stat = "修改为禁用";

        }

        $r = PayapiclassModel::editPayapiClassStatus($id, $status);

        if ($r) {

            //改变通道分类的状态，则所有商家中有此分类的数据也需要改动

            PayapishangjiapayapiclassModel::changeStatus($id, $status);

            $this->addAdminOperate($msg . $stat . ',修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);

        } else {

            $this->addAdminOperate($msg . $stat . ',修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

        }

    }



    //通道分类的pc状态

    public function payapiClassPc()

    {

        $id = I("post.id", "");

        $pc = I("post.pc", "");

        $find = PayapiclassModel::getPayapiClassInfo($id);

        $msg = "修改通道分类[" . $find['classname'] . "]的pc状态:";

        if ($pc == 1) {

            $stat = "修改为启用";

        } else {

            $stat = "修改为禁用";

        }

        $r = PayapiclassModel::editPayapiClassPc($id, $pc);

        if ($r) {

            $this->addAdminOperate($msg . $stat . ',修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);

        } else {

            $this->addAdminOperate($msg . $stat . ',修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改成功']);

        }

    }



    //通道分类的wap状态

    public function payapiClassWap()

    {

        $id = I("post.id", "");

        $wap = I("post.wap", "");

        $find = PayapiclassModel::getPayapiClassInfo($id);

        $msg = "修改通道分类[" . $find['classname'] . "]的wap状态:";

        if ($wap == 1) {

            $stat = "修改为启用";

        } else {

            $stat = "修改为禁用";

        }

        $r = PayapiclassModel::editPayapiClassWap($id, $wap);

        if ($r) {

            $this->addAdminOperate($msg . $stat . ',修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);

        } else {

            $this->addAdminOperate($msg . $stat . ',修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改成功']);

        }

    }





    /**

     * 2019-4-18 通道账号区域

     */

    //通道账号页面

    public function PayapiAccount()

    {

        $PayapishangjiaList = PayapishangjiaModel::getShangjiaList(['del' => 0]);

        $this->assign("PayapishangjiaList", $PayapishangjiaList);//商家列表

        $MoneytypeclassList = MoneytypeclassModel::getMoneyClassList();

        $this->assign("MoneytypeclassList", $MoneytypeclassList);

        $this->display();

    }



    //加载通道账号列表

    public function LoadPayapiAccount()

    {

        $where = [];

        $i = 0;

        $where[$i] = 'del=0';

        $i++;

        $bieming = I("post.bieming", "", 'tirm');

        if ($bieming) {

            $where[$i] = "bieming like '%" . $bieming . "%'";

            $i++;

        }

        $memberid = I("post.memberid", "", 'trim');

        if ($memberid) {

            $where[$i] = "memberid like '%" . $memberid . "%'";

            $i++;

        }

        $account = I("post.account", "", 'trim');

        if ($account) {

            $where[$i] = "account like '%" . $account . "%'";

            $i++;

        }

        $payapishangjiaid = I("post.payapishangjiaid", "");

        if ($payapishangjiaid) {

            $where[$i] = "payapishangjiaid = " . $payapishangjiaid;

            $i++;

        }

        $moneytypeclassid = I("post.moneytypeclassid", "");

        if ($moneytypeclassid) {

            $where[$i] = "moneytypeclassid = " . $moneytypeclassid;

            $i++;

        }

        $status = I("post.status", "");

        if ($status <> "") {

            $where[$i] = "status =" . $status;

            $i++;

        }

        //2019-01-29汪桂芳添加

        $type = I("post.type", "");

        if ($type <> "") {

            $where[$i] = "type =" . $type;

            $i++;

        }



        $count = D('payapiaccount')->where($where)->count();

        $datalist = D('payapiaccount')->scope('default_scope')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('id DESC')->select();

        foreach ($datalist as $key => $val) {

            $datalist[$key]['account_type'] = $val['user_id'] == 0 ? '系统' : '用户';

        }

        $ReturnArr = [

            'code' => 0,

            'msg' => '数据加载成功',

            'count' => $count,

            'data' => $datalist

        ];

        $this->ajaxReturn($ReturnArr, 'JSON');

    }



    //添加通道账号页面

    public function AddPayapiAccount()

    {

        $PayapishangjiaList = PayapishangjiaModel::getShangjiaList(['del' => 0]);

        $this->assign("PayapishangjiaList", $PayapishangjiaList);//商家列表

        $MoneytypeclassList = MoneytypeclassModel::getMoneyClassList();  //2019-1-9 任梦龙：添加del字段

        $this->assign("MoneytypeclassList", $MoneytypeclassList);

        $user_list = UserModel::selectUser(['del' => 0]);

        $this->assign("user_list", $user_list);

        $this->display();

    }



    //确认添加通道账号

    public function PayapiAccountAdd()

    {

        $msg = '添加通道账号[' . I('post.bieming', '', 'trim') . ']:';

        $return = AddSave('payapiaccount', 'add', '添加通道帐号');

        if ($return["status"] == "ok") {

            PayapiaccountloopsModel::addLoopsinfo($return["id"]);

            //添加密钥表数据

            PayapiaccountkeystrModel::addAccountKey(['payapi_account_id' => $return["id"]]);

        }

        $this->addAdminOperate($msg . $return['msg']);

        $this->ajaxReturn($return, "json");

    }



    //修改通道账号页面

    public function EditPayapiAccount()

    {

        $id = I("get.id", "");

        if (!$id) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请不要非法操作']);

        }

        $find = PayapiaccountModel::getInfo($id);

        if ($find) {

            $this->assign("find", $find);

            //只显示商家，且商家不能更改

            $shangjia_name = PayapishangjiaModel::getShangjiaName($find['payapishangjiaid']);

            $this->assign("shangjia_name", $shangjia_name);

            $MoneytypeclassList = MoneytypeclassModel::getMoneyClassList();

            $this->assign("MoneytypeclassList", $MoneytypeclassList);

//            $user_list = UserModel::selectUser(['del' => 0]);

//            $this->assign("user_list", $user_list);

            $this->display();

        } else {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请不要非法操作']);

        }

    }



    //确认修改通道账号

    public function PayapiAccountEdit()

    {

        $msg = '修改通道账号[' . I('post.bieming', '', 'trim') . ']:';

        $return = AddSave('payapiaccount', 'save', '通道帐号编辑');

        $this->addAdminOperate($msg . $return['msg']);

        $this->ajaxReturn($return, "json");

    }



    //删除通道账号，首先判断该账号是否在通道和用户里

    //2019-4-4 任梦龙：删除时需要判断的系统账号还是用户的账号,同时优化逻辑

    public function PayapiAccountDel()

    {

        $id = I("post.id", "");  //账号id

        if (!$id) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        $find = PayapiaccountModel::getInfo($id);

        if (!$find) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        $msg = '删除通道账号[' . $find['bieming'] . ']:';

        //如果是用户的账号,直接不能删除

        if ($find['user_id']) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '该账号属于用户,不能删除']);

        }

        //2019-4-4 任梦龙：判断通道里是否有账号,用户里是否有账号,由于默认账号是建立在通道有账号的情况下,所以一旦通道没账号,那么默认账号就自然不存在

        $count_tongdao = TongdaozhanghaoModel::isExistAccount($id);

        $count_user = UserpayapiaccountModel::isExistUserAccount($id);

        if ($count_tongdao || $count_user) {

            $this->addAdminOperate($msg . '该通道账户正在使用中,暂不能删除');

            $this->ajaxReturn(['status' => 'no', 'msg' => '该通道账户正在使用中,暂不能删除']);

        }

        $res = PayapiaccountModel::changeAccountType($id, 'del', 1);

        if ($res) {

            //在删除账号时,同时将轮询条件删除

            PayapiaccountloopsModel::delAccountloop($id);

            $this->addAdminOperate($msg . '删除成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);

        } else {

            $this->addAdminOperate($msg . '删除失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);

        }

    }



    //设置金额方案

    //2019-4-4 任梦龙：修改

    //2019-4-8 任梦龙：修改

    public function ShowMoneytypeclass()

    {

        $list = MoneytypeModel::getMoneyType(I("get.id"));

//        if (!$list) {

//            $this->ajaxReturn(['status' => 'no', 'msg' => '该到账方案尚未添加冻结方案']);

//        }

        $this->assign("list", $list);

        $this->display();

    }



    //显示账号密钥页面

    public function Secretkey()

    {

        session('code_switch', 1);   //已进入页面，给一个session值作为标识

        $this->assign("id", I("get.id"));

        $this->display();

    }



    //显示账号的MD5密钥页面

    public function accountMd5Key()

    {

        $account_id = I('get.id');

        $key_info = PayapiaccountkeystrModel::getkeyInfo($account_id);

        $this->assign('account_id', $account_id);

        $this->assign('key_id', $key_info['id']);  //账号密钥表id

        $this->assign('md5keystr', $key_info['md5keystr']);  //账号密钥表id

        //判断当前操作者的二次验证与管理密码的开启状态

        $code_type = $this->getCodeType();

        $this->assign('code_type', $code_type);

        $this->display();

    }



    //修改账号的MD5密钥

    public function editAccountMd5key()

    {

        $account_id = I('post.account_id', 0, 'intval');

        $bieming = PayapiaccountModel::getAccountField($account_id, 'bieming');

        $msg = '修改账号[' . $bieming . ']的MD5密钥:';

        $verfiy_code = I('post.verfiy_code', '', 'trim');  //修改数据时的验证码

        $code_type = I('post.code_type', 0, 'intval');  //验证码类型

        $this->checkVerifyCode($verfiy_code, $code_type, $msg);   //验证密码

        $key_info = PayapiaccountkeystrModel::getkeyInfo($account_id);  //根据有无此记录来判断修改还是增加记录

        if ($key_info) {

            $return = AddSave('payapiaccountkeystr', 'save', '修改md5密钥');

        } else {

            $return = AddSave('payapiaccountkeystr', 'add', '修改md5密钥');

        }

        $this->addAdminOperate($msg . $return['msg']);

        $this->ajaxReturn($return, 'json');

    }



    //显示具体的密钥内容页面

    public function Showkeystr()

    {

        $account_id = I("get.id");  //账号表id

        $filename = I("get.filename");

        $alertstr = I("get.alertstr");

        //将pay_payapiaccount中的密钥字段拆分到pay_payapiaccountketstr中

        $keystr = PayapiaccountkeystrModel::getkeyInfo($account_id);  //获取单条记录

        $this->assign("keystr", $keystr[$filename]);  //密钥对应的内容

        $this->assign("key_id", $keystr['id']);  //账号密钥表id

        $this->assign("filename", $filename);

        $this->assign("alertstr", $alertstr);

        $this->assign("account_id", $account_id);

        //判断当前操作者的二次验证与管理密码的开启状态

        $code_type = $this->getCodeType();

        $this->assign('code_type', $code_type);

        $this->display();

    }



    //修改账号密钥

    public function PayapiAccountkeystrEdit()

    {

        $verfiy_code = I('post.verfiy_code', '', 'trim');

        $code_type = I('post.code_type', 0, 'intval');

        $zh_name = I('post.zh_name', '', 'trim');

        $account_id = I('post.account_id');

        $bieming = PayapiaccountModel::getAccountField($account_id, 'bieming');

        $msg = '修改账号[' . $bieming . ']的' . $zh_name . ':未上传文件,';

        $this->checkVerifyCode($verfiy_code, $code_type, $msg);   //验证密码

        $return = AddSave('payapiaccountkeystr', 'save', $zh_name . "修改");

        $this->addAdminOperate($msg . $return['msg']);

        $this->ajaxReturn($return, "json");

    }



    //上传文件

    //2019-4-1 任梦龙：将路径字段合并为内容表中

    public function uploadFile()

    {

        $id = I("post.id");  //当前账户密钥表id

        $code_type = I('post.code_type');  //验证码类型

        $upload_code = I('post.upload_code');  //验证码

        $account_id = I('post.account_id'); //账号表id

        $file_name = I('post.file_name');  //密钥字段名称

        $zh_name = I('post.zh_name');  //密钥字段中文名称

        $private_pwd = I('post.private_pwd', '');  //私钥密码

        $bieming = PayapiaccountModel::getAccountField($account_id, 'bieming');

        $msg = '上传账号[' . $bieming . ']的' . $zh_name . ':有上传文件,';

        //判断验证码

        $this->checkVerifyCode($upload_code, $code_type, $msg);

        //2019-4-18 rml:判断目录是否生成,加密文件名

        if (!file_exists(C('KEY_STR_PATH'))) {

            mkdir(C('KEY_STR_PATH'), '0777', true);

        }

        $new_file_name = hash('sha1', $file_name . '_' . $account_id);

        $upload = new \Think\Upload();// 实例化上传类

        $upload->maxSize = 3 * 1024 * 1024;// 设置附件上传大小 3M

        $upload->exts = array('pem', 'txt', 'cer', 'pfx');// 设置附件上传类型

        $upload->rootPath = C('KEY_STR_PATH'); // 设置附件上传根目录

        $upload->saveName = $new_file_name;   //文件名以密钥类型名称为准，加密

        $upload->replace = true;   //存在同名文件覆盖

        $upload->subName = 'account-' . $account_id;   //子目录创建方式，以账号id命名



        /*************************************************************/

        //判断上传目录是否存在，否则创建

//        $root_path = C('KEY_STR_PATH') . 'account-' . $account_id;  //上传的根目录

//        if (!file_exists($root_path)) {

//            mkdir($root_path, 0777, true);   //true：表示创建多级目录 ;0777表示文件的最高权限

//        }

        // 上传单个文件

        $info = $upload->uploadOne($_FILES['file']);

        if (!$info) {

            $this->addAdminOperate($msg . $upload->getError());

            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);

        } else {

            // 上传成功 拼接上传文件的路径，存入数据库，并且读取文件内容，也存入数据库

            $file_path = C('KEY_STR_PATH') . $info['savepath'] . $info['savename'];

            //2019-4-8 任梦龙：修改

            if ($info['ext'] == 'pfx') {

                if (!$private_pwd) {

                    $this->ajaxReturn(['status' => 'no', 'msg' => '格式为pfx的文件需填写私钥密码']);

                }

                $file_contents = $this->loadPk12Cert($file_path, $private_pwd);

//                dump($file_contents);die;

            } elseif ($info['ext'] == 'cer') {

                $file_contents = $this->loadX509Cert($file_path);

            } else {

                $file_contents = file_get_contents($file_path);

            }

            if (!$file_contents && $info['ext'] == 'pfx') {

                $this->ajaxReturn(['status' => 'no', 'msg' => '私钥密码错误']);

            }

            if (!$file_contents) {

                $this->ajaxReturn(['status' => 'no', 'msg' => '获取文件内容时出错']);

            }

            $public_begin = '-----BEGIN PUBLIC KEY-----';

            $public_end = '-----END PUBLIC KEY-----';

            $privte_begin = '-----BEGIN RSA PRIVATE KEY-----';

            $private_end = '-----END RSA PRIVATE KEY-----';

            $private_begin_rsa = '-----BEGIN PRIVATE KEY-----';

            $private_end_rsa = '-----END PRIVATE KEY-----';

            $contents = str_replace(array("\r\n", "\r", "\n", $public_begin, $public_end, $privte_begin, $private_end, $private_begin_rsa, $private_end_rsa), '', $file_contents);

            //通道账号密钥表中的字段名称

            $file_name_path = $file_name . '_path';

            $data = [

                $file_name => $contents,

                $file_name_path => $file_path,

                'upload_time' => date('Y-m-d H:i:s'),

            ];

            if ($file_name == 'privatekeystr') {

                $data['private_pwd'] = $private_pwd;

            }

            $res = PayapiaccountkeystrModel::edutAccountKey($id, $data);

            if ($res) {

                $this->addAdminOperate($msg . '上传成功');

                $this->ajaxReturn(['status' => 'ok', 'msg' => '上传成功', 'file_contents' => $contents]);

            } else {

                unlink($file_path);

                $this->addAdminOperate($msg . '上传失败,此时产生过文件记录');

                $this->ajaxReturn(['status' => 'no', 'msg' => '上传失败']);

            }

        }

    }



    //读取pfx

    public function loadPk12Cert($path, $pwd)

    {

        try {

            $file = file_get_contents($path);



            if (!$file) {

                throw new \Exception('loadPk12Cert');

            }



            if (!openssl_pkcs12_read($file, $cert, $pwd)) {

                return false;

                throw new \Exception('loadPk12Cert ERROR');

            }

            return $cert['pkey'];

        } catch (\Exception $e) {

            throw $e;

        }

    }





    //读取cer

    public function loadX509Cert($path)

    {

        try {

            $file = file_get_contents($path);

            if (!$file) {

                throw new \Exception('loadx509Cert::file_get_contents ERROR');

            }



            $cert = chunk_split(base64_encode($file), 64, "\n");

            $cert = "-----BEGIN CERTIFICATE-----\n" . $cert . "-----END CERTIFICATE-----\n";



            $res = openssl_pkey_get_public($cert);



            $detail = openssl_pkey_get_details($res);

            openssl_free_key($res);



            if (!$detail) {

                throw new \Exception('loadX509Cert::openssl_pkey_get_details ERROR');

            }



            return $detail['key'];

        } catch (\Exception $e) {

            throw $e;

        }

    }



    //设置商户号，账号的页面

    //2019-4-2 任梦龙：修改

    public function accountSet()

    {

        //判断当前操作者的二次验证与管理密码的开启状态

        $code_type = $this->getCodeType();

        $this->assign('code_type', $code_type);

        $find = PayapiaccountModel::getInfo(I('get.id'));

        $shangjia_name = PayapishangjiaModel::getShangjiaName($find['payapishangjiaid']);

        $finds = getShangjiaConfig($shangjia_name, $find);

        $this->assign('find', $finds);

        $this->display();

    }



    //账号设置处理程序

    public function editAccountSet()

    {

        $verfiy_code = I('post.verfiy_code', '', 'trim');  //修改数据时的验证码

        $code_type = I('post.code_type', 0, 'intval');  //验证码类型

        $id = I('post.id');

        $bieming = PayapiaccountModel::getAccountField($id, 'bieming');

        $msg = '修改账号[' . $bieming . ']的账号设置:';

        $this->checkVerifyCode($verfiy_code, $code_type, $msg);   //验证密码

        $return = AddSave('payapiaccount', 'save', "修改账号设置");

        $this->addAdminOperate($msg . $return['msg']);

        $this->ajaxReturn($return, "json");

    }



    //2019-4-2 任梦龙：将跳转地址，同步回调地址，异步回调地址单独分离

    public function addressSet()

    {

        $code_type = $this->getCodeType();

        $this->assign('code_type', $code_type);

        $id = I('get.id');

        $find = PayapiaccountModel::getInfo($id);

        $this->assign('id', $id);

        $this->assign('find', $find);

        $this->display();

    }



    //2019-4-2 任梦龙：修改地址设置

    public function editAddressSet()

    {

        $verfiy_code = I('post.verfiy_code', '', 'trim');

        $code_type = I('post.code_type', 0, 'intval');

        $id = I('post.id');

        $bieming = PayapiaccountModel::getAccountName($id);

        $msg = '修改账号[' . $bieming . ']的地址设置:';

        $this->checkVerifyCode($verfiy_code, $code_type, $msg);

        $return = AddSave('payapiaccount', 'save', "修改地址设置");

        $this->addAdminOperate($msg . $return['msg']);

        $this->ajaxReturn($return, "json");

    }





    //修改账号的状态

    //2019-4-29 任梦龙：修改

    public function payapiaccountStatus()

    {

        $id = I("post.id", "");

        $status = I("post.status", "");

        $account_name = PayapiaccountModel::getAccountName($id);

        $stat = $status ? '修改为启用' : '修改为禁用';

        $msg = "修改通道账号[" . $account_name . "]状态:" . $stat;

        $r = PayapiaccountModel::changeAccountType($id, 'status', $status);

        if ($r) {

            $this->addAdminOperate($msg . ',修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);

        } else {

            $this->addAdminOperate($msg . ',修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

        }

    }



    //2019-4-29 rml：添加账号类型

    public function editAccountType()

    {

        $id = I("post.id");

        $type = I("post.type");

        $account_name = PayapiaccountModel::getAccountName($id);

        $stat = $type ? '修改为测试' : '修改为普通';

        $msg = "修改通道账号[" . $account_name . "]类型:" . $stat;

        $r = PayapiaccountModel::changeAccountType($id, 'type', $type);

        if ($r) {

            $this->addAdminOperate($msg . ',修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);

        } else {

            $this->addAdminOperate($msg . ',修改失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

        }

    }



    //修改账号的交易总额页面

    //2019-4-4 任梦龙：修改

    public function EditPayapiaccountMoney()

    {

        $money = PayapiaccountModel::getAccountField(I("get.id"), 'money');

        $this->assign("money", $money);

        $this->assign("payapiaccountid", I("get.id"));

        $this->display();

    }



    //加载账号下的所有通道

    //2019-4-4 任梦龙：修改

    public function LoadPayapiAccountMoney()

    {

        $payapiaccountid = I('get.payapiaccountid');

        $payapiList = M('tongdaozhanghao')->alias('a')

            ->where('a.userid=0 AND a.payapiaccountid=' . $payapiaccountid)

            ->join('LEFT JOIN __PAYAPI__ b ON a.payapiid=b.id')

            ->field('a.id,a.money,b.zh_payname')

            ->select();

        $ReturnArr = [

            'code' => 0,

            'msg' => '数据加载成功',

            'count' => count($payapiList),

            'data' => $payapiList

        ];

        $this->ajaxReturn($ReturnArr, 'json');

    }



    //确认修改账号下的每日交易总额

    //2019-4-4 任梦龙：将方法写入模型层

    public function PayapiaccountMoneyEdit()

    {

        $post = I('post.');

        $account_name = PayapiaccountModel::getAccountName(I('id'));

        $msg = "修改账号[" . $account_name . "]的每日交易总额:";

        $sum = 0;

        foreach ($post as $k => $v) {

            if ($k != 'id' && $k != 'money') {

                $arr[$k] = $v;

                $sum += $v;

            }

        }

        $result = true;

        foreach ($arr as $key => $val) {

            if ($val <= 0) {

                $return["status"] = "no";

                $return["msg"] = "每个通道的日交易总额必须大于0";

                $result = false;

                break;

            }

        }

        if ($result) {

            //判断通道的和是否小于总额

            if ($post['money'] <= 0) {

                $return["status"] = "no";

                $return["msg"] = "该账号的日交易总额必须大于0";

            }elseif($post['money'] > 9999999999){

                $return["status"] = "no";

                $return["msg"] = "该账号的日交易总额不得超过9999999999";

            } elseif ($sum > $post['money']) {

                $return["status"] = "no";

                $return["msg"] = "所有通道的交易额的和不能大于该账号的日交易总额";

            } else {

                $res = true;

                //修改该账号的交易总额

                $accountid = $post['id'];

//                $old = M('payapiaccount')->where('id=' . $accountid)->getField('money');

                $old = PayapiaccountModel::getAccountField($accountid, 'money');

                if ($old != $post['money']) {

                    $data['money'] = $post['money'];

//                    $res = M('payapiaccount')->where('id=' . $accountid)->save($data);

                    $res = PayapiaccountModel::editPayapiAccount($accountid, $data);

                }

                //修改每个通道的交易额

                foreach ($arr as $k => $v) {

//                    $old_tongdao = M('tongdaozhanghao')->where('id=' . $k)->getField('money');

                    $old_tongdao = TongdaozhanghaoModel::getTongdaoMoney($k);

                    if ($old_tongdao != $v) {

                        $save['money'] = $v;

//                        $res = M('tongdaozhanghao')->where('id=' . $k)->save($save);

                        $res = TongdaozhanghaoModel::editTongdao($k, $save);

                    }

                }

                if ($res) {

                    $return["status"] = "ok";

                    $return["msg"] = "交易金额设置成功";

                } else {

                    $return["status"] = "no";

                    $return["msg"] = "账号交易金额失败，请稍后重试！";

                }

            }

        }

        $this->addAdminOperate($msg . $return["msg"]);

        $this->ajaxReturn($return);

    }



    //修改账号单笔最小金额页面

    //2019-4-4 任梦龙：修改

    public function editMinMoney()

    {

        $min_money = PayapiaccountModel::getAccountField(I('get.id'), 'min_money');

        $this->assign('min_money', $min_money);

        $this->display();

    }



    //确认修改最小金额

    //2019-4-4 任梦龙：修改

    public function conformMinMoney()

    {

        $id = I('post.id');

        $account_name = PayapiaccountModel::getAccountName($id);

        $msg = "修改通道账号[" . $account_name . "]的单笔最小金额:";

        $min_money = I('post.min_money');  //获取要修改的数据

        if (!is_numeric($min_money)) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '金额需为数字']);

        }

        if ($min_money < 0) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '单笔最小金额不得小于0']);

        }

        $max_money = PayapiaccountModel::getAccountField($id, 'max_money');

        if ($min_money > $max_money) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '最小金额不得大于最大金额']);

        }

        $res = PayapiaccountModel::changeAccountType($id, 'min_money', $min_money);

        if ($res) {

            $this->addAdminOperate($msg . "设置的金额为[" . $min_money . "],设置成功");

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);

        } else {

            $this->addAdminOperate($msg . "设置的金额为[" . $min_money . "],设置失败");

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败，请稍后重试']);

        }

    }



    //修改账号单笔最大金额页面

    //2019-4-4 任梦龙：修改

    public function editMaxMoney()

    {

        $max_money = PayapiaccountModel::getAccountField(I('get.id'), 'max_money');

        $this->assign('max_money', $max_money);

        $this->display();

    }



    //确认修改最大金额

    public function conformMaxMoney()

    {

        $id = I('post.id');

        $account_name = PayapiaccountModel::getAccountName($id);

        $msg = "修改通道账号[" . $account_name . "]的单笔最大金额:";

        $max_money = I('post.max_money');  //获取要修改的数据

        if (!is_numeric($max_money)) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '金额需为数字']);

        }

        if ($max_money < 0) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '单笔最大金额不得小于0']);

        }

        if ($max_money > 9999999999) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '单笔最大金额不得大于9999999999']);

        }



        $min_money = M('payapiaccount')->where('id = ' . $id)->getField('min_money');

        if ($min_money > $max_money) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '最大金额不得小于最小金额']);

        }

        $res = PayapiaccountModel::changeAccountType($id, 'max_money', $max_money);

        if ($res) {

            $this->addAdminOperate($msg . "设置的金额为[" . $max_money . "],设置成功");

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);

        } else {

            $this->addAdminOperate($msg . "设置的金额为[" . $max_money . "],设置失败");

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败，请稍后重试']);

        }

    }



    //账号费率设置页面

    //2019-4-4 任梦龙：修改

    public function PayapiaccountFeilv()

    {

        $account = PayapiaccountModel::getInfo(I("get.id"));

        $this->assign("account", $account);

        $this->display();

    }



    //账号费率修改程序

    //2019-4-4 任梦龙：修改

    public function EditPayapiaccountFeilv()

    {

        $account_name = PayapiaccountModel::getAccountName(I('id'));

        $msg = "修改通道账号[" . $account_name . "]费率:";

        $return = AddSave('payapiaccount', 'save', "费率修改");

        $this->addAdminOperate($msg . $return['msg']);

        $this->ajaxReturn($return, "json");



    }



    //充值零头页面

    //2019-4-4 任梦龙：修改

    public function accountOddment()

    {

        $oddment = PayapiaccountModel::getInfo(I("get.id"));

        $this->assign('oddment', $oddment);

        $this->display();

    }



    //充值零头开启和关闭

    public function accountOddmentEdit()

    {

        $account_id = I('get.account_id');

        $oddment = I('get.oddment');

        $account_name = PayapiaccountModel::getAccountName($account_id);

        $msg = "修改通道账号[" . $account_name . "]的充值零头状态:";

        if ($oddment == 1) {

            $stat = "修改为启用";

        } else {

            $stat = "修改为禁用";

        }

        //2019-4-4 任梦龙：修改

        $res = PayapiaccountModel::changeAccountType($account_id, 'oddment', $oddment);

        if ($res) {

            $this->addAdminOperate($msg . $stat . ',修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功'], 'json');

        } else {

            $this->addAdminOperate($msg . $stat . ',修改成功');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败'], 'json');

        }

    }



    //充值零头范围设置

    public function accountOddmentRangeEdit()

    {

        $account_id = I('account_id');

        $account_name = PayapiaccountModel::getAccountName($account_id);

        $msg = "修改通道账号[" . $account_name . "]的充值零头范围:";

        $min_oddment = I('min_oddment');

        $max_oddment = I('max_oddment');

        if (strpos($max_oddment, '.') || strpos($min_oddment, '.')) {

            $this->addAdminOperate($msg . '充值零头请输入整数');

            $this->ajaxReturn(['status' => 'no', 'msg' => '充值零头请输入整数'], 'json');

        }

        if ($min_oddment < 1) {

            $this->addAdminOperate($msg . '设置的充值零头下限[' . $min_oddment . ']低于1分');

            $this->ajaxReturn(['status' => 'no', 'msg' => '充值零头下限不能低于1分'], 'json');

        }

        if ($max_oddment > 99) {

            $this->addAdminOperate($msg . '设置的充值零头上限[' . $max_oddment . ']高于99分');

            $this->ajaxReturn(['status' => 'no', 'msg' => '充值零头上限不能高于99分'], 'json');

        }

        if ($min_oddment > $max_oddment) {

            $this->addAdminOperate($msg . '设置的充值零头下限[' . $min_oddment . ']高于设置的上限[' . $max_oddment . ']');

            $this->ajaxReturn(['status' => 'no', 'msg' => '充值零头下限不能高于上限'], 'json');

        }

        //2019-4-4 任梦龙：修改

        $res = PayapiaccountModel::editPayapiAccount($account_id, ['min_oddment' => $min_oddment, 'max_oddment' => $max_oddment]);

        if ($res) {

            $this->addAdminOperate($msg . '设置的充值零头下限为[' . $min_oddment . '],上限为[' . $max_oddment . '],设置成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功'], 'json');

        } else {

            $this->addAdminOperate($msg . '设置的充值零头下限为[' . $min_oddment . '],上限为[' . $max_oddment . '],设置失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败'], 'json');

        }

    }



    //账号轮循设置页面

    //2019-4-4 任梦龙：修改

    public function PayapiAccountLoops()

    {

        $id = I("get.id", "");

        $find_account = PayapiaccountModel::getInfo($id);

        if (!$id || !$find_account) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '通道账号不存在']);

        }

        $find_loops = PayapiaccountloopsModel::getLoopsinfo($id);

        if (!$find_loops) {

            PayapiaccountloopsModel::addLoopsinfo($id);

            $find_loops = PayapiaccountloopsModel::getLoopsinfo($id);

        }

        $this->assign("find", $find_loops);

        $this->assign("account_id", $id);

        $this->display();

    }



    //账号轮循设置处理程序

    //2019-4-4 任梦龙：修改

    public function PayapiaccountloopsEdit()

    {

        $loop_id = I('post.id', '');

        $account_id = I('post.account_id', '');

        if (!$loop_id || !$account_id) {

            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);

        }

        $account_info = PayapiaccountModel::getInfo($account_id);

        $msg = "通道账号[" . $account_info['bieming'] . "]的轮循权重设置:";

        //先去判断是随机还是轮循status=0:随机  1=轮循,如果为轮循，则要去判断最小最大金额

        $status = I('post.status');

        if ($status == 1) {

            $stat = "设置为按条件轮循,";

            $money_ks = I('post.money_ks');

            $money_js = I('post.money_js');

            if (!(is_numeric($money_ks) && is_numeric(($money_js)))) {

                $this->addAdminOperate($msg . $stat . '输入金额为非数字');

                $this->ajaxReturn(['status' => 'no', 'msg' => '金额需为数字']);

            }

            if ($money_ks < $account_info['min_money']) {

                $this->addAdminOperate($msg . $stat . '输入最小金额' . $money_ks . '低于了账号的单笔最小金额' . $account_info['min_money']);

                $this->ajaxReturn(['status' => 'no', 'msg' => '最小金额不得低于单笔最小金额']);

            }

            if ($money_js > $account_info['max_money']) {

                $this->addAdminOperate($msg . $stat . '输入最大金额' . $money_js . '高于了账号的单笔最大金额' . $account_info['max_money']);

                $this->ajaxReturn(['status' => 'no', 'msg' => '最大金额不得高于单笔最大金额']);

            }

            if ($money_ks > $money_js) {

                $this->addAdminOperate($msg . $stat . '输入最小金额' . $money_ks . '高于了最大金额' . $money_js);

                $this->ajaxReturn(['status' => 'no', 'msg' => '最小金额不得高于最大金额']);

            }

        } else {

            $stat = "设置为随机轮循,";

        }

        $return = AddSave('payapiaccountloops', 'save', "轮循权重修改");

        $this->addAdminOperate($msg . $stat . $return['msg']);

        $this->ajaxReturn($return, "json");

    }

    //张杨 2020-02-13 新增汇付进件
    public function HuiFuJingJian()
    {
        $dls_list = M('huifudls')->field('id,dls_name')->select();
        $this->assign('dls_list',$dls_list);
        $this->display();
    }

    public function HuiFuDaiLiShangAdd()
    {
        $dls_name = I("request.dls_name");
        if(trim($dls_name) == ""){
            $return['msg'] = '代理商名称不能为空';
            $return['status'] = 'no';
            $this->ajaxReturn($return, "json");
        }

        $msg = '添加汇付进件代理商[' . I('post.dls_name', '', 'trim') . ']:';
        $return = AddSave('huifudls', 'add', '添加汇付进件代理商');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    public function LoadHuiFuDaiLiShangList()
    {
        $where = [];
        $i = 0;
        $dls_name = I("post.dls_name", "", 'trim');
        if ($dls_name) {
            $where[$i] = "dls_name like '%" . $dls_name . "%'";
            $i++;
        }
        $apikey = I("post.apikey", "", 'trim');
        if ($apikey) {
            $where[$i] = "(apikey_mock like '" . $apikey . "%') or (apikey_prod like '%".$apikey."%')";
            $i++;
        }

        $this->ajaxReturn(PageDataLoad('huifudls', $where), 'JSON');
    }

    public function DelHuiFuDaiLiShang()
    {
        $id = I('post.id');
        if($id){
            if(M('huifudls')->where('id='.$id)->delete()){
                $msg = '删除汇付进件代理商成功';
                $this->addAdminOperate($msg);
                $this->ajaxReturn(['status' => 'ok', 'msg' => $msg]);
            }else{
                $msg = '删除汇付进件代理商失败';
                $this->addAdminOperate($msg);
                $this->ajaxReturn(['status' => 'no', 'msg' => $msg]);
            }
        }else{
            $msg = '删除汇付进件代理商失败';
            $this->addAdminOperate($msg);
            $this->ajaxReturn(['status' => 'no', 'msg' => $msg]);
        }

    }

    public function EditHuiFuDaiLiShang()
    {
        $id = I('request.id');
        $find = M('huifudls')->where('id='.$id)->find();
        if($find){
            $find['user_private_rsa'] = str_replace(' ','+',$find['user_private_rsa']);
            $find['adapay_public_rsa'] = str_replace(' ','+',$find['adapay_public_rsa']);
            $this->assign('find',$find);
            $this->display();
        }else{
            exit('请不要非法操作');
        }
    }

    public function HuiFuDaiLiShangEdit()
    {
        $dls_name = I("request.dls_name");
        if(trim($dls_name) == ""){
            $return['msg'] = '代理商名称不能为空';
            $return['status'] = 'no';
            $this->ajaxReturn($return, "json");
        }

        $msg = '修改汇付进件代理商[' . I('post.dls_name', '', 'trim') . ']:';
        $return = AddSave('huifudls', 'save', '修改汇付进件代理商');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    public function AddHuiFuJingJian()
    {
        $dls_list = M('huifudls')->field('id,dls_name')->select();
        $request_id = date('YmdHis').randpw(18,'NUMBER');
        $bankcode_list = M('huifubankcode')->select();
        $province = M('huifucity')->where("sj_value='0'")->select();
        $this->assign('province',$province);
        $this->assign('bankcode_list',$bankcode_list);
        $this->assign('request_id',$request_id);
        $this->assign('dls_list',$dls_list);
        $this->display();
    }

    public function LoadHuiFuCity()
    {
        $value = I("request.value");
        $city = M('huifucity')->where("sj_value='".$value."'")->select();
        $this->ajaxReturn($city, "json");
    }

    /* public function addcity()
     {
         $json_str = file_get_contents('Public/Admin/json/cs.json');
         $arr = json_decode($json_str,true);
         foreach ($arr as $key => $val){
             $sj_id = M('huifucityl')->add(['value'=>$val['val'],'title'=>$key]);
           //  dump($val['cities']);
           //  dump(json_decode($val['cities']));
             $list = $val['items'];
             foreach($list as $k => $v){
                 M('huifucityl')->add(['sj_value'=>$val['val'],'value'=>$v['val'],'title'=>$k]);
                 $list_list = $v["items"];
                 foreach($list_list as $kk => $vv){
                     M('huifucityl')->add(['sj_value'=>$v['val'],'value'=>$vv,'title'=>$kk]);
                 }
             }
         }
         exit("ok");
     }*/

    public function HuiFuJingJianAdd()
    {
        $post = I('post.');
        foreach($post as $key => $val){
            if(trim($val) === ""){
                $return['msg'] = '必填参数不能为空';
                $return['status'] = 'no';
                $this->ajaxReturn($return, "json");
                break;
            }
        }

        $request_id = I("request.request_id");
        $count = M('huifujingjian')->where("request_id='".$request_id."'")->count();
        if($count > 0){
            $return['msg'] = '请求ID已存在';
            $return['status'] = 'no';
            $this->ajaxReturn($return, "json");
        }

        $msg = '新增进件信息';
        $return = AddSave('huifujingjian', 'add', '新增进件信息');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    public function LoadHuiFuJingJian()
    {
        $where = [];
        $i = 0;
        $request_id = I('request.request_id');
        if($request_id){
            $where[$i] = "request_id like '%".$request_id."%'";
            $i++;
        }

        $mer_name = I('request.mer_name');
        if($mer_name){
            $where[$i] = "mer_name like '%".$mer_name."%'";
            $i++;
        }

        $license_code = I('request.license_code');
        if($license_code){
            $where[$i] = "license_code like '%".$license_code."%'";
            $i++;
        }

        $cont_phone = I('request.cont_phone');
        if($cont_phone){
            $where[$i] = "cont_phone like '%".$cont_phone."%'";
            $i++;
        }

        $dls_id = I('request.dls_id');
        if($dls_id){
            $where[$i] = "dls_id = ".$dls_id;
            $i++;
        }

        $status = I('request.status');
        if($status){
            $where[$i] = "status = ".$status;
            $i++;
        }
//dump($where);
//        dump(I("request."));
        $returnArr = PageDataLoad('huifujingjian', $where);
        $datalist = $returnArr['data'];
        foreach($datalist as $key => $val){
            $datalist[$key]['dls_id'] = M('huifudls')->where("id=".$val['dls_id'])->getField('dls_name');
            $status = "";
            switch($val['status']){
                case 0:
                    $status = "<span style='color:#393D49'>未提交</span>";
                    break;
                case 1:
                    $status = "<span style='color:#009688'>进件提交成功</span>";
                    break;
                case 2:
                    $status = "<span style='color:#2F4056'>进件提交失败</span>";
                    break;
                case 3:
                    $status = "<span style='color:#FF5722'>进件成功</span>";
                    break;
                case 4:
                    $status = "<span style='color:#01AAED'>进件失败</span>";
                    break;
                case 5:
                    $status = "<span style='color:#01AAED'>入驻提交成功</span>";
                    break;
                case 6:
                    $status = "<span style='color:#FF5722'>入驻成功</span>";
                    break;
                case 7:
                    $status = "<span style='color:#01AAED'>入驻失败</span>";
                    break;
                default:
                    $status = '未知';

            }
            $datalist[$key]['status_name'] = $status;
        }
        $returnArr['data'] = $datalist;
        $this->ajaxReturn($returnArr, 'JSON');
    }

    public function DelHuiFuJingJian()
    {
        $id = I('post.id');
        if($id){
            if(M('huifujingjian')->where('id='.$id)->delete()){
                $msg = '删除汇付进件成功';
                $this->addAdminOperate($msg);
                $this->ajaxReturn(['status' => 'ok', 'msg' => $msg]);
            }else{
                $msg = '删除汇付进件失败';
                $this->addAdminOperate($msg);
                $this->ajaxReturn(['status' => 'no', 'msg' => $msg]);
            }
        }else{
            $msg = '删除汇付进件失败';
            $this->addAdminOperate($msg);
            $this->ajaxReturn(['status' => 'no', 'msg' => $msg]);
        }

    }

    public function CopyHuiFuJingJian()
    {
        $id = I("post.id");
        $find = M('huifujingjian')->where('id='.$id)->find();
        if($find){
            $find['request_id'] = date('YmdHis').randpw(18,'NUMBER');
            $find['status'] = "0";
            $find['error_content'] = '';
            $find['success_content'] = '';
            unset($find['id']);
            if(M('huifujingjian')->add($find)){
                $msg = '复制汇付进件成功';
                $this->addAdminOperate($msg);
                $this->ajaxReturn(['status' => 'ok', 'msg' => $msg]);
            }else{
                $msg = '复制汇付进件失败';
                $this->addAdminOperate($msg);
                $this->ajaxReturn(['status' => 'no', 'msg' => $msg]);
            }
        }else{
            $msg = '复制汇付进件失败';
            $this->addAdminOperate($msg);
            $this->ajaxReturn(['status' => 'no', 'msg' => $msg]);
        }
    }

    public function EditHuiFuJingJian()
    {
        $dls_list = M('huifudls')->field('id,dls_name')->select();
        $request_id = date('YmdHis').randpw(18,'NUMBER');
        $bankcode_list = M('huifubankcode')->select();
        $province = M('huifucity')->where("sj_value='0'")->select();
        $this->assign('province',$province);
        $this->assign('bankcode_list',$bankcode_list);
        $this->assign('request_id',$request_id);
        $this->assign('dls_list',$dls_list);

        $id = I("get.id");
        $find = M('huifujingjian')->where("id=".$id)->find();
        $this->assign('find',$find);

        $this->display();
    }

    public function HuiFuJingJianEdit()
    {
        $request_id = I("request.request_id");
        $id = I("request.id");
        $count = M('huifujingjian')->where("request_id='".$request_id."' and id <> ".$id)->count();
        if($count > 0){
            $return['msg'] = '请求ID已存在';
            $return['status'] = 'no';
            $this->ajaxReturn($return, "json");
        }

        $msg = '修改汇付进件：';
        $return = AddSave('huifujingjian', 'save', '修改汇付进件');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    public function HuiFuJingJianTiJiao()
    {
        Vendor('adajj.AdapayMerchantSdk.init');
        $id = I("request.id");
        $find = M('huifujingjian')->where("id=".$id)->find();
        if(!$find){
            $return['msg'] = '进件信息不存在';
            $return['status'] = 'no';
            $this->ajaxReturn($return, "json");
        }

        if($find['status'] == 1 or $find['status'] == 3){
            $return['msg'] = '已成功进件不能重复提交';
            $return['status'] = 'no';
            $this->ajaxReturn($return, "json");
        }

        $parameter = M('huifudls')->where("id=".$find['dls_id'])->find();
        if(!$parameter){
            $return['msg'] = '代理商信息不存在';
            $return['status'] = 'no';
            $this->ajaxReturn($return, "json");
        }

        $config_array = [
            'api_key_live' => $parameter['apikey_prod'],
            'rsa_public_key' => str_replace(' ','+',$parameter['adapay_public_rsa']),
            'rsa_private_key' => str_replace(' ','+',$parameter['user_private_rsa']),
        ];
        // dump($config_array);
        \AdaPay\AdaPay::init($config_array, "live", true);
        # 初始化进件类
        $merchant = new \AdaPayMerchant\MerchantUser();
        $merchant_params = [
            "request_id"=> $find['request_id'],
            "usr_phone"=> $find['usr_phone'],
            "cont_name"=> $find['cont_name'],
            "cont_phone"=> $find['cont_phone'],
            "customer_email"=> $find['customer_email'],
            "mer_name"=> $find['mer_name'],
            "mer_short_name"=> $find['mer_short_name'],
            "license_code"=> $find['license_code'],
            "reg_addr"=> $find['reg_addr'],
            "cust_addr"=> $find['cust_addr'],
            "cust_tel"=> $find['cust_tel'],
            "mer_valid_date"=> $find['mer_valid_date'],
            "legal_name"=> $find['legal_name'],
            "legal_type"=> $find['legal_type'],
            "legal_idno"=> $find['legal_idno'],
            "legal_mp"=> $find['legal_mp'],
            "legal_id_expires"=> $find['legal_id_expires'],
            "card_id_mask"=> $find['card_id_mask'],
            "bank_code"=> $find['bank_code'],
            "card_name"=> $find['card_name'],
            "bank_acct_type"=> $find['bank_acct_type'],
            "prov_code"=> $find['prov_code'],
            "area_code"=> $find['area_code'],
            "legal_start_cert_id_expires"=> $find['legal_start_cert_id_expires'],
            "mer_start_valid_date"=> $find['mer_start_valid_date'],
//            "rsa_public_key"=> $find['rsa_public_key'],
            "rsa_public_key"=> str_replace(' ','+',$find['rsa_public_key']),
        ];

        # 发起进件
        $merchant->create($merchant_params);

# 对进件结果进行处理
        if ($merchant->isError()){
            //失败处理
            // var_export($merchant->result);
            //  $arr = json_decode($merchant->result,true);
            $data = [];
            $data['status'] = 2;
            $data['error_content'] = json_encode($merchant->result);
            M('huifujingjian')->where("id=".$id)->save($data);
            $return['msg'] = '进件失败，失败原因：'.($merchant->result['failure_msg']?$merchant->result['failure_msg']:$merchant->result['error_msg']);
            $return['status'] = 'ok';
            $this->ajaxReturn($return, "json");

        } else {
            //成功处理
            //var_export($merchant->result);
            $data = [];
            $data['status'] = 1;
            $data['success_content'] = json_encode($merchant->result);
            M('huifujingjian')->where("id=".$id)->save($data);
            $return['msg'] = '进件提交成功';
            $return['status'] = 'ok';
            $this->ajaxReturn($return, "json");
        }

    }

    public function HuiFuJingJianXY()
    {
        $id = I("request.id");
        $find = M('huifujingjian')->where("id=".$id)->find();

        if($find['status'] == 1 or $find['status'] == 3  or $find['status'] == 6){
            dump(json_decode($find['success_content']));
        }else{
            dump(json_decode($find['error_content']));

        }

    }

    public function ShowHuiFuJingJian()
    {
        $id = I("get.id");
        $find = M('huifujingjian')->where("id=".$id)->find();
        $find['dls_name'] = M('huifudls')->where("id=".$find['dls_id'])->getField('dls_name');
        $find['bank_code'] = M('huifubankcode')->where("bankcode='".$find['bank_code']."'")->getField('bankname');
        $find['prov_code'] = M('huifucity')->where("value='".$find['prov_code']."'")->getField('title');
        $find['area_code'] = M('huifucity')->where("value='".$find['area_code']."'")->getField('title');
        $find['legal_type'] = $find['legal_type']==0?'身份证':'其它';
        $find['bank_acct_type'] = $find['bank_acct_type']==1?'对公':'对私';
        $this->assign('find',$find);
        $this->display();
    }

    public function HuiFuJingJianSearch()
    {
        Vendor('adajj.AdapayMerchantSdk.init');
        $id = I("request.id");
        $find = M('huifujingjian')->where("id=".$id)->find();
        if(!$find){
            $return['msg'] = '进件信息不存在';
            $return['status'] = 'no';
            $this->ajaxReturn($return, "json");
        }

        if($find['status'] != 1){
            $return['msg'] = '未提交成功进件不能查询';
            $return['status'] = 'no';
            $this->ajaxReturn($return, "json");
        }
        $parameter = M('huifudls')->where("id=".$find['dls_id'])->find();
        if(!$parameter){
            $return['msg'] = '代理商信息不存在';
            $return['status'] = 'no';
            $this->ajaxReturn($return, "json");
        }

        $config_array = [
            'api_key_live' => $parameter['apikey_prod'],
            'rsa_public_key' => str_replace(' ','+',$parameter['adapay_public_rsa']),
            'rsa_private_key' => str_replace(' ','+',$parameter['user_private_rsa']),
        ];
        // dump($config_array);
        \AdaPay\AdaPay::init($config_array, "live", true);
        # 初始化进件类
# 初始化进件类
        $merchant = new \AdaPayMerchant\MerchantUser();
# 进件成功后的request_id
# 发起进件
        $merchant->query(["request_id"=> $find['request_id']]);

# 对进件结果进行处理
        if ($merchant->isError()){
            //失败处理
//            var_dump($merchant->result);
            if($merchant->result['status'] == 'pending'){
                $return['msg'] = '进件正在处理中，请稍后再查询';
                $return['status'] = 'ok';
                $this->ajaxReturn($return, "json");
            }

            $data = [];
            $data['status'] = 4;
            $data['error_content'] = json_encode($merchant->result);
            M('huifujingjian')->where("id=".$id)->save($data);
            $return['msg'] = '进件失败，失败原因：'.($merchant->result['failure_msg']?$merchant->result['failure_msg']:$merchant->result['error_msg']);
            $return['status'] = 'ok';
            $this->ajaxReturn($return, "json");




        } else {
            //成功处理
//            var_dump($merchant->result);
            if($merchant->result['status'] == 'pending'){
                $return['msg'] = '进件正在处理中，请稍后再查询';
                $return['status'] = 'ok';
                $this->ajaxReturn($return, "json");
            }

            if($merchant->result['status'] == 'failed'){
                $data = [];
                $data['status'] = 4;
                $data['error_content'] = json_encode($merchant->result);
                M('huifujingjian')->where("id=".$id)->save($data);
                $return['msg'] = '进件失败，失败原因：'.($merchant->result['failure_msg']?$merchant->result['failure_msg']:$merchant->result['error_msg']);
                $return['status'] = 'ok';
                $this->ajaxReturn($return, "json");
            }


            if($merchant->result['status'] == 'succeeded') {
                $data = [];
                $data['status'] = 3;
                $data['success_content'] = json_encode($merchant->result);
                M('huifujingjian')->where("id=".$id)->save($data);


                $app_id = $merchant->result['app_id_list'][0]['app_id'];
                $apikey_mock = $merchant->result['test_api_key'];
                $apikey_prod = $merchant->result['live_api_key'];

                $data = [];
                $data['jj_id'] = $id;
                $data['app_id'] = $app_id;
                $data['apikey_mock'] = $apikey_mock;
                $data['apikey_prod'] = $apikey_prod;
                $data['user_private_rsa'] = $find['rsa_private_key'];
                $data['user_public_rsa'] = $find['rsa_public_key'];
                $data['adapay_public_rsa'] = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwN6xgd6Ad8v2hIIsQVnbt8a3JituR8o4Tc3B5WlcFR55bz4OMqrG/356Ur3cPbc2Fe8ArNd/0gZbC9q56Eb16JTkVNA/fye4SXznWxdyBPR7+guuJZHc/VW2fKH2lfZ2P3Tt0QkKZZoawYOGSMdIvO+WqK44updyax0ikK6JlNQIDAQAB";
                M('huifujj')->add($data);

                $return['msg'] = '进件成功';
                $return['status'] = 'ok';
                $this->ajaxReturn($return, "json");
            }
        }

    }

    public function HuiFuJingJianZhangHao()
    {
        $id = I('request.id');
        $find = M('huifujj')->where("jj_id=".$id)->find();
//        str_replace(' ','+',$parameter['adapay_public_rsa']),
        $find['user_private_rsa'] = str_replace(' ','+',$find['user_private_rsa']);
        $find['user_public_rsa'] = str_replace(' ','+',$find['user_public_rsa']);
        $find['adapay_public_rsa'] = str_replace(' ','+',$find['adapay_public_rsa']);
        $this->assign('find',$find);
        $this->display();
    }

    public function HuiFuShangHuRuZhu()
    {
        $id = I('request.id');
        M('huifushanghuruzhu')->where("jj_id=".$id)->delete();
        $dls_id = M('huifujingjian')->where('id='.$id)->getField('dls_id');
        $request_id = M('huifujingjian')->where('id='.$id)->getField('request_id');
        $mer_name = M('huifujingjian')->where('id='.$id)->getField('mer_name');
        $mer_short_name = M('huifujingjian')->where('id='.$id)->getField('mer_short_name');
        $sub_api_key = M('huifujj')->where('jj_id='.$id)->getField('apikey_prod');
        $app_id = M('huifujj')->where('jj_id='.$id)->getField('app_id');
        $province = M('huifucityl')->where("sj_value='0'")->select();
        $this->assign('province',$province);
        $classnam1_list = M("huifuhangye")->field('classname1')->group('classname1')->order("id asc")->select();
        $this->assign('classname1_list',$classnam1_list);
        $this->assign('app_id',$app_id);
        $this->assign('sub_api_key',$sub_api_key);
        $this->assign('mer_name',$mer_name."(".$mer_short_name.")");
        $this->assign('id',$id);
        $this->assign('dls_id',$dls_id);
        $this->assign('request_id',$request_id);
        $this->display();
    }

    public function LoadHuiFuHangYeClassName2()
    {
        $value = I("request.value");
        $classname2_list = M('huifuhangye')->where("classname1='".$value."'")->group('classname2')->field('classname2')->select();
        $this->ajaxReturn($classname2_list, "json");
    }

    public function LoadHuiFuHangYeClassName3()
    {
        $value = I("request.value");
        $classname3_list = M('huifuhangye')->where("classname2='".$value."'")->group('classname3')->field('classname3,wx_qy,wx_gr,cls_id,zfb')->select();
        $this->ajaxReturn($classname3_list, "json");
    }

    public function LoadHuiFuCityl()
    {
        $value = I("request.value");
        $city = M('huifucityl')->where("sj_value='".$value."'")->select();
        $this->ajaxReturn($city, "json");
    }

    public function RuZhuHuiFuShangHu()
    {
        $msg = '添加汇付商户入驻';
        $return = AddSave('huifushanghuruzhu', 'add', '添加汇付商户入驻');
        $this->addAdminOperate($msg . $return['msg']);
        if($return['status'] == 'no'){
            $this->ajaxReturn($return, "json");
        }else{
            Vendor('adajj.AdapayMerchantSdk.init');

            $parameter = M('huifudls')->where("id=".I('request.dls_id'))->find();
            if(!$parameter){
                $return['msg'] = '代理商信息不存在';
                $return['status'] = 'no';
                $this->ajaxReturn($return, "json");
            }

            $config_array = [
                'api_key_live' => $parameter['apikey_prod'],
                'rsa_public_key' => str_replace(' ','+',$parameter['adapay_public_rsa']),
                'rsa_private_key' => str_replace(' ','+',$parameter['user_private_rsa']),
            ];
            // dump($config_array);
            \AdaPay\AdaPay::init($config_array, "live", true);
            # 初始化支付配置类
            $merConfig = new \AdaPayMerchant\MerchantConf();

            $mer_config_params = [
                "request_id"=> I('request.request_id'),
                # 推荐商户的api_key
                "sub_api_key"=> I('request.sub_api_key'),
                # 银行渠道号
                "bank_channel_no"=> I('request.bank_channel_no'),
                # 费率
                "fee_type"=> I('request.fee_type'),
                # 商户app_id
                "app_id"=> I('request.app_id'),
                # 微信经营类目
                "wx_category"=> I('request.wx_category'),
                # 支付宝经营类目
                "alipay_category"=> I('request.alipay_category'),
                "cls_id"=> I('request.cls_id'),
                # 服务商模式
                "model_type"=> I('request.model_type'),
                # 商户种类
                "mer_type"=> I('request.mer_type'),
                # 省份code
                "province_code"=> I('request.province_code'),
                # 城市code
                "city_code"=> I('request.city_code'),
                # 县区code
                "district_code"=> I('request.district_code'),
                # 配置信息值
                "add_value_list"=> json_encode(['wx_lite'=>['appid'=> I('request.wxappid')],'wx_pub'=> ['appid'=> I('request.wxappid'),'path'=>I('request.wxpath')],"wx_scan"=>"", "alipay"=>"","alipay_wap"=>"","alipay_lite"=>"","alipay_qr"=>"","alipay_scan"=>""])
            ];

# 发起支付配置
            $merConfig->create($mer_config_params);

# 对进件结果进行处理
            if ($merConfig->isError()){
                //失败处理
//                var_dump($merConfig->result);

                $data = [];
                $data['status'] = 7;
                $data['error_content'] = json_encode($merConfig->result);
                M('huifujingjian')->where("id=".I("request.jj_id"))->save($data);
                $return['msg'] = '商户入驻失败，失败原因：'.($merConfig->result['failure_msg']?$merConfig->result['failure_msg']:$merConfig->result['error_msg']);
                $return['status'] = 'ok';
                $this->ajaxReturn($return, "json");
            } else {
                //成功处理
//                var_dump($merConfig->result);
                $data = [];
                $data['status'] = 5;
                $data['success_content'] = json_encode($merConfig->result);
                M('huifujingjian')->where("id=".I("request.jj_id"))->save($data);
                $return['msg'] = '商户入驻提交成功';
                $return['status'] = 'ok';
                $this->ajaxReturn($return, "json");
            }

        }
        // $this->ajaxReturn($return, "json");
    }

    public function HuiFuRuZhuSearch()
    {
        Vendor('adajj.AdapayMerchantSdk.init');
        $id = I("request.id");
        $find = M('huifujingjian')->where("id=".$id)->find();
        if(!$find){
            $return['msg'] = '进件信息不存在';
            $return['status'] = 'no';
            $this->ajaxReturn($return, "json");
        }

//        if($find['status'] != 5 or $find['status'] != 7){
//            $return['msg'] = '未提交成功入驻不能查询';
//            $return['status'] = 'no';
//            $this->ajaxReturn($return, "json");
//        }
        $parameter = M('huifudls')->where("id=".$find['dls_id'])->find();
        if(!$parameter){
            $return['msg'] = '代理商信息不存在';
            $return['status'] = 'no';
            $this->ajaxReturn($return, "json");
        }

        $config_array = [
            'api_key_live' => $parameter['apikey_prod'],
            'rsa_public_key' => str_replace(' ','+',$parameter['adapay_public_rsa']),
            'rsa_private_key' => str_replace(' ','+',$parameter['user_private_rsa']),
        ];
        // dump($config_array);
        \AdaPay\AdaPay::init($config_array, "live", true);

        # 初始化支付配置类
        $merConfig = new \AdaPayMerchant\MerchantConf();

# 发起支付配置查询
        $merConfig->query(["request_id"=> $find['request_id']]);

# 对进件结果进行处理
        if ($merConfig->isError()){
            //失败处理
//            var_dump($merConfig->result);

            if($merConfig->result['status'] == "pending"){
                $return['msg'] = '商户入驻信息正在处理中，请稍后再查询';
                $return['status'] = 'ok';
                $this->ajaxReturn($return, "json");
            }

            $data = [];
            $data['status'] = 7;
            $data['error_content'] = json_encode($merConfig->result);
            M('huifujingjian')->where("id=".$id)->save($data);
            $return['msg'] = '商户入驻失败，失败原因：'.($merConfig->result['failure_msg']?$merConfig->result['failure_msg']:$merConfig->result['error_msg']);
            $return['status'] = 'ok';
            $this->ajaxReturn($return, "json");

        } else {
            //成功处理
//            var_dump($merConfig->result);
            if($merConfig->result['status'] == "pending"){
                $return['msg'] = '商户入驻信息正在处理中，请稍后再查询';
                $return['status'] = 'ok';
                $this->ajaxReturn($return, "json");
            }

            if($merConfig->result['status'] == "succeeded"){

                $data = [];
                $data['status'] = 6;
                $data['success_content'] = json_encode($merConfig->result);
                M('huifujingjian')->where("id=".$id)->save($data);
                $return['msg'] = '商户入驻信成功';
                $return['status'] = 'ok';
                $this->ajaxReturn($return, "json");
            }

            if($merConfig->result['status'] == "failed"){
                $data = [];
                $data['status'] = 7;
                $data['error_content'] = json_encode($merConfig->result);
                M('huifujingjian')->where("id=".$id)->save($data);
                $return['msg'] = '商户入驻失败，失败原因：'.($merConfig->result['failure_msg']?$merConfig->result['failure_msg']:$merConfig->result['error_msg']);
                $return['status'] = 'ok';
                $this->ajaxReturn($return, "json");
            }
        }
    }

}