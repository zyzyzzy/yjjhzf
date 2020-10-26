<?php

namespace UserPay\Controller;

use Think\Controller;
use UserPay\Model\UserModel;

//自助收银模块,弹窗扫码,支付宝或微信扫完后直接进入输入金额页面,通道已确定
class SelfCashController extends Controller
{
    protected $user_id;

    public function __construct()
    {
        parent::__construct();
    }

    //扫码页面
    public function index()
    {
        $usercode = I("get.usercode");
        if (!$usercode) {
            exit("参数错误!");
        }
        $user = UserModel::findUser($usercode);
        if (!$user) {
            exit("非法链接");
        }
        //判断用户的自助收银是否开启
        $status = UserModel::getUserSelfcashStatus($user['id']);
        if (!$status) {
            exit("用户自助收银功能已关闭,请联系管理员处理");
        }
        //2019-4-16 rml：判断用户是否已经认证
        if ($user['authentication'] != 3) {
            exit("用户还未通过认证,请先去通过认证");
        }
        //判断用户是否有二维码,有的话直接调用,没有的话就生成
        $qrcode = UserModel::getUserSelfcashQrcode($user['id']);
        if (!$qrcode || !file_get_contents($qrcode)) {
            import("Vendor.phpqrcode.phpqrcode", '', ".php");
            $qrcode = C('USERQRCODE_PATH') . $usercode . ".png";//二维码图片路径
            $website = M('website')->field('pay_domain,pay_http')->order('id DESC')->find();
            if ($website['pay_http'] == 1) {
                $http = "http://";
            }
            if ($website['pay_http'] == 2) {
                $http = "https://";
            }
            $url = $http . $website['pay_domain'] . U('UserPay/SelfCash/selfCash') . '?usercode=' . $usercode;
            \QRcode::png($url, $qrcode, "L", 20);
            UserModel::setUserSelfcashQrcode($user['id'], $qrcode);
        }
        $this->assign('qrcode', $qrcode);
        $this->display();
    }

    //自助收银页面
    //2019-4-24 rml：移动端输入金额有问题
    public function selfCash()
    {
        //2019-4-24 rml：扫码时先要判断用户是否开通自助收银功能,如果没有开通,则报错
        //获取二维码所属用户
        $usercode = I("get.usercode");
        $user = UserModel::findUser($usercode);
        if ($user['order'] != 0) {
            exit('用户交易功能已关闭,请联系管理员处理');
        }

        if ($user['selfcash_status'] != 1) {
            exit('用户自助收银功能已关闭,请联系管理员处理');
        }
        //判断是用支付宝还是微信扫码
        $client = $this->judgment();
        if (!$client) {
            exit("请使用支付宝,微信或QQ扫码支付");
        }
        if ($client == 1) {
            $client = "支付宝";
            $payname = 'AlipayWap';
        }
        if ($client == 2) {
            $client = "微信";
            $payname = 'WeiXinJSApi';
        }
        if ($client == 3) {
            exit("没有qq支付的通道,暂不能使用qq支付,请用支付宝或微信扫码支付");
        }

        //判断是否开通此通道及通道是否启用
        $payapi = M('payapi')->where(['en_payname' => $payname])->find();
        if($payapi['del'] != 0){
            exit("通道不存在,请联系管理员处理");
        }

        if (!$payapi['status']) {
            exit("通道暂不可用,请联系管理员处理");
        }

        $user_payapi = M('userpayapiclass')->where(['userid' => $user['id'], 'payapiid' => $payapi['id']])->getField('id');
        if (!$user_payapi) {
            exit("此商户暂未开通此通道,请联系管理员处理");
        }
        $this->assign('usercode', $usercode);
        $this->assign('client', $client);
        $this->assign('payname', $payname);
        //查询用户的背景,商户logo及商户名称
        $selfcash_back = UserModel::getUserSelfcashBack($user['id']);
        $user_cashier = M('cashier')->where('user_id=' . $user['id'])->field('logo,company,type')->find();
        $this->assign('selfcash_back', $selfcash_back);
        $this->assign('user_cashier', $user_cashier);
        $this->display();
    }

    //判断扫码客户端,暂时没有判断银联的代码
    function judgment()
    {
        //判断是不是支付宝
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            return 1;
        }
        //判断是不是微信
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return 2;
        }
        //判断是不是QQ
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'QQ') !== false) {
            return 3;
        }
        //哪个都不是
        return 0;
    }


    //提交
    public function payConfirm()
    {
        header("Content-type: text/html; charset=utf-8");
        $money = I("post.order_money", "");
        $payname = I("post.payname", "");    //通道编码
        $usercode = I("usercode");

        $user = UserModel::findUser($usercode);
        $msg = "用户[" . $user['username'] . "]申请充值:";
        $money = $money * 100;//默认版本的金额单位为分
        //2019-4-16 rml：先去判断系统总开关，再去判断用户的交易功能
        //判断系统交易功能
        $website = M('website')->field('all_valve,api_valve,pay_domain,pay_http')->order('id DESC')->find();

        if ($website['all_valve'] != 0 || $website['api_valve'] != 0) {
            $this->addUserOperate($user['id'], $msg . '系统交易功能已关闭');
            $this->ajaxReturn(['msg' => "系统的交易功能已关闭,请联系管理员处理！", 'status' => 'no'], "json", JSON_UNESCAPED_UNICODE);
        }

        //判断用户交易功能
        if ($user['order'] != 0) {
            $this->addUserOperate($user['id'], $msg . '用户交易功能已关闭');
            $this->ajaxReturn(['msg' => "用户的交易功能已关闭,请联系管理员处理！", 'status' => 'no'], "json", JSON_UNESCAPED_UNICODE);
        }

        //获取商户号
        $memberid = M('secretkey')->where(['userid' => $user['id']])->getField('memberid');
        if (!$memberid) {
            $this->addUserOperate($user['id'], $msg . '用户商户号未生成');
            $this->ajaxReturn(['msg' => "用户商户号未生成,请联系管理员处理！", 'status' => 'no'], "json", JSON_UNESCAPED_UNICODE);
        }
        //用户密钥
        $key = M('secretkey')->where(['userid' => $user['id']])->getField('md5str');
        if (!$key) {
            $this->addUserOperate($user['id'], $msg . '用户md5密钥未生成');
            $this->ajaxReturn(['msg' => "用户md5密钥未生成！", 'status' => 'no'], "json", JSON_UNESCAPED_UNICODE);
        }

        //获取订单号
        $orderid = $this->getUserordernumber($user['id']);
        if (!$orderid) {
            $this->addUserOperate($user['id'], $msg . '订单号生成失败');
            $this->ajaxReturn(['msg' => "订单号生成失败,请重试！", 'status' => 'no'], "json", JSON_UNESCAPED_UNICODE);
        }

        //获取通道分类编码
        $payapi = M('payapi')->where('en_payname="' . $payname . '"')->find();
        $paytype = M('payapiclass')->where("id=" . $payapi['payapiclassid'])->getField("classbm");
        //获取网站的支付域名
        if ($website['pay_http'] == 1) {
            $http = "http://";
        }
        if ($website['pay_http'] == 2) {
            $http = "https://";
        }

        //订单时间
        $datetime = date("Y-m-d H:i:s");

        //版本号
        $version = '1.0.0';

        //签名方式
        $signmethod = 'md5';

        $str = '<form name="Form1" method="post" action="' . U("/Pay/Index") . '">';
        $str .= '<input type="hidden" name="version" value="' . $version . '">';
        $str .= '<input type="hidden" name="signmethod" value="' . $signmethod . '">';
        $str .= '<input type="hidden" name="memberid" value="' . $memberid . '">';
        $str .= '<input type="hidden" name="amount" value="' . $money . '">';
        $str .= '<input type="hidden" name="orderid" value="' . $orderid . '">';
        $str .= '<input type="hidden" name="notifyurl" value="' . $http . $website['pay_domain'] . U("notifyurl") . '">';
        $str .= '<input type="hidden" name="callbackurl" value="' . $http . $website['pay_domain'] . U("callbackurl") . '">';//用户回调系统的地址,所以pay模块的pay控制器中这两个地址得写死
        $str .= '<input type="hidden" name="orderdatetime" value="' . $datetime . '">';
        $str .= '<input type="hidden" name="bankcode" value="">';
        $str .= '<input type="hidden" name="paytype" value="' . $paytype . '">';
        $str .= '<input type="hidden" name="source_type" value="2">';

        //签名
        $requestarray = array(
            "version" => $version,
            "memberid" => $memberid,
            "orderid" => $orderid,
            "amount" => $money,
            "orderdatetime" => $datetime,
            "notifyurl" => $http . $website['pay_domain'] . U("notifyurl"),
            "paytype" => $paytype,
            "signmethod" => $signmethod,
        );
        $sign = $this->md5sign($key, $requestarray);
        $str .= '<input type="hidden" name="sign" value="' . $sign . '">';
        $str .= '<input type="submit" value="正在跳转中......">';
        $str .= '</form>';
        $str .= '<script>';
        $str .= 'document.Form1.submit();';
        $str .= '</script>';
        $this->addUserOperate($user['id'], $msg . '申请成功');
        echo $str;
        exit;
    }

    //异步回调
    public function notifyurl()
    {
        $ArrayField = $_POST;
        $sign = $ArrayField["sign"];
        unset($ArrayField["sign"]);
        $key = $this->getUserMd5strByMemberid($ArrayField['memberid']);  //密钥
        ksort($ArrayField);
        $signmd5 = strtoupper(md5(urldecode(http_build_query($ArrayField)) . $key));
        if ($signmd5 == $sign and $ArrayField["status"] == "success") {
            exit('ok');
        }
    }

    //同步回调
    public function callbackurl()
    {
        $ArrayField = $_POST;
        $sign = $ArrayField["sign"];
        unset($ArrayField["sign"]);
        unset($ArrayField["extend"]);
        $key = $this->getUserMd5strByMemberid($ArrayField['memberid']);  //密钥
        ksort($ArrayField);
        $signmd5 = strtoupper(md5(urldecode(http_build_query($ArrayField)) . $key));
        if ($signmd5 == $sign and $ArrayField["status"] == "success") {
            $info = M('order')->where('sysordernumber="' . $ArrayField['sysorderid'] . '"')->field('userordernumber,successtime,true_ordermoney')->find();
            $this->assign('info', $info);
            $client = $this->judgment();
            if ($client == 1) {
                $this->display('Index/alipaySuccess');
            }
            if ($client == 2) {
                $this->display('Index/wechatSuccess');
            }
        }
    }

    //加密
    protected function md5sign($key, $signdata)
    {
        ksort($signdata);
        $sign = strtoupper(md5(urldecode(http_build_query($signdata)) . $key));
        return $sign;
    }

    //通过商户号获取用户md5密钥
    protected function getUserMd5strByMemberid($memberid)
    {
        return M('secretkey')->where('memberid="' . $memberid . '"')->getField('md5str');
    }

    //获取用户订单号
    public function getUserordernumber($user_id)
    {
        $num = ($user_id + 10000) . date('YmdHis') . rand(1000, 9999);
        return $num;
    }

    //添加操作记录
    public function addUserOperate($user_id, $content)
    {
        $data = [
            'userid' => $user_id,
            'userip' => getIp(),
            'operatedatetime' => date('Y-m-d H:i:s'),
            'content' => $content,
            'child_id' => 0,
        ];
        M('useroperaterecord')->add($data);
    }
}