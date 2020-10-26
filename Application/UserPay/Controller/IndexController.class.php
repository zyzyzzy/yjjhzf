<?php

namespace UserPay\Controller;
use Think\Controller;
use Admin\Model\CashierModel;
use UserPay\Model\UserModel;

//充值模块,用户不需要登录就可以直接访问,通过usercode来查找用户;暂时用的默认版本,所以版本号 签名方式 回调地址都写固定的
class IndexController extends Controller
{
    protected $user_id;
    protected $encryptedFields = ['version','memberid', 'orderid', 'amount', 'orderdatetime', 'notifyurl', 'paytype', 'signmethod','sign'];  //签名需要的字段
    public function __construct()
    {
        parent::__construct();
    }


    public function test()
    {
        $this->display('AdvTemplate/pc_hero');
    }

    //页面显示
    public function index()
    {
        //读取表前缀
        $usercode = I("usercode");
        if (!$usercode) {
            exit("参数错误!");
        }
        $user = M("user")->where("usercode='" . $usercode . "'")->find();
        if (!$user) {
            exit("非法链接");
        }
        $mobile = $this->isMobile();
        $user_id = $user['id'];
        //查询用户的收银台设置
        $user_cashier = CashierModel::userCashierInfo($user_id);
        //查询用户开通的所有通道分类
        $all_userpayapiclass = M('userpayapiclass')->alias('a')->where('userid='.$user_id)->join('LEFT JOIN __PAYAPICLASS__ b ON b.id=a.payapiclassid')
            ->getField('a.id,a.payapiclassid,a.payapiid,b.classname,b.classbm,b.status,b.pc,b.wap,b.sys,b.img_url');
        if($user_cashier && $user_cashier['type']==1){
            //用户自定义收银台
            if($mobile){
                $json_arr = json_decode($user_cashier['wap_payapiclass_json'],true);
            }else{
                $json_arr = json_decode($user_cashier['pc_payapiclass_json'],true);
            }
            if($json_arr){
                //查询用户的通道分类
                $str = '';
                foreach ($json_arr as $key=>$val){
                    $str .= $val.',';
                }
                $str = trim($str,',');
                $all_userpayapiclass = M('userpayapiclass')->alias('a')->where('userid='.$user_id.' and payapiclassid in ('.$str.')')->join('LEFT JOIN __PAYAPICLASS__ b ON b.id=a.payapiclassid')
                    ->getField('a.id,a.payapiclassid,a.payapiid,b.classname,b.classbm,b.status,b.pc,b.wap,b.sys,b.img_url');
            }
            $this->assign('user_cashier',$user_cashier);
        }
        $true_userpayapiclass = [];
        foreach ($all_userpayapiclass as $k=>$v){
            //判断分类的状态和是否在pc端显示
            if($v['status']==1){
                if($mobile){
                    //手机端
                    if($v['wap']==1){
                        $true_userpayapiclass[$k] = $v;
                    }
                }else{
                    //pc端
                    if($v['pc']==1){
                        $true_userpayapiclass[$k] = $v;
                    }
                }
                //判断是否有网银支付,是的话加载银行
                if($v['sys']==1){
                    //查询商家id
                    $shangjiaid = M('payapi')->where('id='.$v['payapiid'])->getField('payapishangjiaid');
                    //查询商家开通的交易银行id
                    $banks = M('shangjiabankcode')->where('shangjia_id='.$shangjiaid)->select();
                    //查询所有银行信息
                    foreach ($banks as $item) {
                        $bank_status = M('systembank')->where('id='.$item['systembank_id'])->getField('jiaoyi');
                        if ($item['trans_bankcode'] && $bank_status==1) {
                            $systembankid[] = $item['systembank_id'];
                        }
                    }
                    $list = M("Systembank")->where(["id" => ["in", $systembankid]])->select();
                    if($list){
                        $true_userpayapiclass[$k]['banks'] = $list;
                    }
                }
            }
        }
        if($true_userpayapiclass){
            foreach ($true_userpayapiclass as $k=>$v){
                $new_userpayapiclass[] = $v;
            }
        }
        $this->assign('user_id',$user_id);
        $this->assign('new_userpayapiclass',$new_userpayapiclass);
        $this->assign('count',count($true_userpayapiclass));
        if($mobile){
            $this->display('wapIndex');
        }else{
            $this->display();
        }
    }

    //判断是否为手机端访问
    public function isMobile(){
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
            return true;

        //此条摘自TPM智能切换模板引擎，适合TPM开发
        if(isset ($_SERVER['HTTP_CLIENT']) &&'PhoneClient'==$_SERVER['HTTP_CLIENT'])
            return true;
        //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA']))
            //找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
        //判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array(
                'nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile'
            );
            //从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        //协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }
        return false;
    }

    //提交
    public function payConfirm()
    {
        header("Content-type: text/html; charset=utf-8");
        $money = I("post.order_money", "");
        $payapiclassid = I("post.payapiclassid", "");
        $bankcode = I("post.trans_bankcode", "");

        $usercode = I("usercode");
        $user = M("user")->where("usercode='" . $usercode . "'")->find();
        $user_id = $user['id'];
        $this->user_id = $user_id;
        $msg = "用户[".$user['username']."]申请充值:";

        //判断输入金额等
        if ($money == "" || $payapiclassid == "") {
            $this->addUserOperate($user_id,$msg.'未填金额或通道分类');
            $this->ajaxReturn(['msg'=>"请不要非法提交！",'status'=>'no'],"json",JSON_UNESCAPED_UNICODE);
        }
        $money = $money*100;//默认版本的金额单位为分

        //判断用户交易功能
        if($user['order']==1){
            $this->addUserOperate($user_id,$msg.'用户交易功能已关闭');
            $this->ajaxReturn(['msg'=>"您的交易功能已关闭,请联系管理员处理！",'status'=>'no'],"json",JSON_UNESCAPED_UNICODE);
        }

        //判断系统交易功能
        $website = M('website')->where('id=1')->find();
        if($website['all_valve']==1 || $website['api_valve']){
            $this->addUserOperate($user_id,$msg.'系统交易功能已关闭');
            $this->ajaxReturn(['msg'=>"系统的交易功能已关闭,请联系管理员处理！",'status'=>'no'],"json",JSON_UNESCAPED_UNICODE);
        }

        //获取商户号
        $memberid = M('secretkey')->where('userid='.$user_id)->getField('memberid');

        //获取通道分类编码
        $paytype = M('payapiclass')->where("id=" . $payapiclassid)->getField("classbm");

        //获取网站的支付域名
        if($website['back_http']==1){
            $http = "http://";
        }
        if($website['back_http']==2){
            $http = "https://";
        }
        //获取订单号
        $orderid = $this->getUserordernumber($user_id);

        //订单时间
        $datetime = date("Y-m-d H:i:s");

        //版本号
        $version = '1.0.0';

        //签名方式
        $signmethod = 'md5';

        //用户密钥
        $key = $this->getUserMd5strById();

        $str = '<form name="Form1" method="post" action="' . U("/Pay/Index") . '">';
        $str .= '<input type="hidden" name="version" value="' . $version . '">';
        $str .= '<input type="hidden" name="signmethod" value="' . $signmethod . '">';
        $str .= '<input type="hidden" name="memberid" value="' . $memberid . '">';
        $str .= '<input type="hidden" name="amount" value="' . $money . '">';
        $str .= '<input type="hidden" name="orderid" value="'.$orderid.'">';
        $str .= '<input type="hidden" name="notifyurl" value="' .$http.$website['back_domain']. U("notifyurl") . '">';//系统回调用户的同步回调地址,用哪里请求回调地址就写在哪里,一般判断是否成功,补单时请求
        $str .= '<input type="hidden" name="callbackurl" value="'.$http.$website['back_domain']. U("callbackurl") . '">';//系统回调用户的同步回调地址,一般跳到支付成功的页面,充值时请求
        $str .= '<input type="hidden" name="orderdatetime" value="' . $datetime . '">';
        $str .= '<input type="hidden" name="bankcode" value="' . $bankcode . '">';
        $str .= '<input type="hidden" name="paytype" value="' . $paytype . '">';
        $str .= '<input type="hidden" name="source_type" value="1">';

        //签名
        $requestarray = array(
            "version" => $version,
            "memberid" => $memberid,
            "orderid" => $orderid,
            "amount" => $money,
            "orderdatetime" => $datetime,
            "notifyurl" => $http.$website['back_domain'] . U("notifyurl"),
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
        $this->addUserOperate($user_id,$msg.'申请成功');
        echo $str;
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
        if($signmd5 == $sign and $ArrayField["status"] == "success"){
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
        if($signmd5 == $sign and $ArrayField["status"] == "success"){
            //判断是pc收银台还是手机收银台,pc收银台直接跳转到一个页面,手机收银台支付宝wap跳到支付宝付款成功的页面(微信的在微信提交的页面判断)
            $info = M('order')->where('sysordernumber="'.$ArrayField['sysorderid'].'"')->field('userordernumber,successtime,true_ordermoney,payapiid')->find();
            $this->assign('info',$info);
            $mobile = $this->isMobile();
            if($mobile){
                //跳到手机页面
                //判断是否是微信支付的
                $payname = M('payapi')->where('id='.$info['payapiid'])->getField('en_payname');
                if($payname=='WeiXinSm' || $payname=='WeiXinJSAPI'){
                    $this->display('wechatSuccess');
                }else{
                    $this->display('alipaySuccess');
                }
            }else{
                //跳到pc页面
                //查询用户的收银台设置
                $user_id = UserModel::getUseridByMemberid($ArrayField['memberid']);
                $user_cashier = CashierModel::userCashierInfo($user_id);
                $this->assign('user_cashier',$user_cashier);
                $this->display('pcSuccess');
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

    //获取用户订单号
    public function getUserordernumber($user_id)
    {
        $num = ($user_id+10000).date('YmdHis').rand(1000,9999);
        $res = M('order')->where(['userid'=>$user_id,'userordernumber'=>$num])->select();
        if($res){
            return $this->getUserordernumber($user_id);
        }
        return $num;
    }

    //通过用户id获取用户md5密钥
    protected function getUserMd5strById()
    {
        return  M('secretkey')->where('userid='.$this->user_id)->getField('md5str');
    }

    //通过商户号获取用户md5密钥
    protected function getUserMd5strByMemberid($memberid)
    {
        return  M('secretkey')->where('memberid="'.$memberid.'"')->getField('md5str');
    }

    //添加操作记录
    public function addUserOperate($user_id,$content)
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