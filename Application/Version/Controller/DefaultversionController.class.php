<?php

namespace Version\Controller;

use Think\Controller;

class DefaultversionController extends VersionController
{

    protected $parameterarray;  //必填参参数
    protected $encryptedFields; //签名参数
    protected $query; //是否是查询

    public function __construct()
    {
        parent::__construct();
        $this->query = I("request.query")=="query"?true:false;
        if($this->query){  //查询
            $this->parameterarray = ['version','memberid', 'orderid', 'sign','signmethod','notifyurl']; //用户自己选择的参数，必填
            $this->encryptedFields = ['version','memberid', 'orderid','signmethod','notifyurl'];  //签名需要的字段
        }else{   //交易
            $this->parameterarray = [
                'version' => true,
                'memberid' => true,
                'orderid' => true,
                'amount' => true,
                'orderdatetime' => true,
                'notifyurl' => true,
                'callbackurl' => false,
                'paytype' => true,
                'signmethod' => true,
                'sign' => true
            ]; //用户自己选择的参数，必填
           $this->encryptedFields = ['version','memberid', 'orderid', 'amount', 'orderdatetime', 'notifyurl', 'paytype', 'signmethod','sign'];  //签名需要的字段
        }
    }


    /**
     * 检查收到的参数是否合法
     * @param $parameter 获取到的参数
     * @return mixed
     */
    protected function CheckParameterCorrect($parameter)
    {

        if (!($user_id = $this->MemberidToUserid($parameter["memberid"]))) {
            $this->returnJson["msg"] = "商户ID错误！";
            return false;
        }

        //2019-03-18汪桂芳添加:判断用户是否指定有版本号
        $res = $this->checkUserVersion($user_id,$parameter["version"]);
        if(!$res['status']){
            $this->returnJson["msg"] = $res['msg'];
            return false;
        };

        if (!preg_match('/[0-9a-zA-Z][^_]{6,32}/i', $parameter["orderid"])) {
            $this->returnJson["msg"] = "订单号最短6个字符，最长32个字符，订单号只能由数字和大小写字母组成，不能包含 下划线";
            return false;
        }

        //2019-02-14 任梦龙：修改逻辑，应该是错误时才拦截
        //2019-01-27 张杨
        // 把判断订单重复的方法提到父类去了
        //2019-03-07 张杨 判断是否是查询，再判断订单号是否存在并报错
        if(!$this->CheckOrderid($parameter['memberid'],$parameter['orderid'])){
            if(!$this->query){
                $this->returnJson["msg"] = "订单号已存在";
                return false;
            }
        }else{
            if($this->query){
                $this->returnJson["msg"] = "订单不存在";
                return false;
            }
        }

        if(!$this->query){
            // 2019-01-27 张杨
            // 2019-08-15 张杨 完善了判断金额的逻辑
           // if (!(floor($parameter["amount"]) == $parameter["amount"]) or ( floor( $parameter['amount'] ) < 1 ) ) {
            if (!(strval(intval($parameter["amount"])) == strval($parameter["amount"])) or ( intval( $parameter['amount'] ) < 1 ) ) {
                $this->returnJson["msg"] = "交易金额单位为分，必须为整数";
                return false;
            }
            $patten = "/^20\d{2}[\-](0?[1-9]|1[012])[\-](0?[1-9]|[12][0-9]|3[01])(\s+(0?[0-9]|1[0-9]|2[0-3])\:(0?[0-9]|[1-5][0-9])\:(0?[0-9]|[1-5][0-9]))?$/";
            if (!preg_match($patten, $parameter["orderdatetime"])) {
                $this->returnJson["msg"] = "订单时间格式错误";
                return false;
            }

            if (strtotime(date("Y-m-d H:i:s")) - strtotime($parameter["orderdatetime"]) < 0) {
                $this->returnJson["msg"] = "订单时间不能比当前时间还要早";
                return false;
            }
        }

        if ($parameter["signmethod"] != "md5" and $parameter["signmethod"] != "rsa") {
            $this->returnJson["msg"] = "签名方法只能是 md5 或 rsa";
            return false;
        }
        return true;
    }


    /**
     * 获取用户ID
     * @param $parameter
     * @return bool
     */
    private function GetUserId($parameter)
    {
        return $this->MemberidToUserid($parameter["memberid"]);

    }

    /**
     * 获取用户的密钥
     * @param $parameter
     * @return bool
     */
    private function GetSecretKey($userid)
    {

        return $this->UserIdToSecretKey($userid);
    }

    /**
     * 解密参数并返回待加密的字段数组
     * @param $parameter
     * @return bool
     */
    private function DecryptData($parameter, $secretkey)
    {
        //解密参数  待拓展

        //返回签名要用到的 参数
        $signdata=[];
        foreach($parameter as $k=>$v){
            if(in_array($k,$this->encryptedFields)){
                $signdata[$k]=$v;
            }
        }
        return $signdata;
    }

    /**
     * 检测数据签名是否正确
     * @param $parameter
     * @return bool
     */
    protected function CheckSign($parameter, $signdata, $secretkey)
    {
        //剔除签名
        unset($signdata['sign']);
        switch ($parameter["signmethod"]) {
            case "md5":
                ksort($signdata);

                $md5str = strtoupper(md5(urldecode(http_build_query($signdata)) . $secretkey["md5str"]));
                if ($md5str == $parameter["sign"]) {
                   // return true;
                } else {
                    //return true;
                    $this->returnJson["msg"] = "数据签名验证失败" .urldecode(http_build_query($signdata)) . $secretkey["md5str"];
                    return false;
                }
                break;
            case "rsa":
                $rsa = new \Org\Util\RSA($secretkey["user_keypath"], $secretkey["sys_privatekeypath"]);
                ksort($signdata);
                $signstr = urldecode(http_build_query($signdata));
                if ($rsa->verify($signstr, $parameter["sign"])) {
                    return false;
                } else {
                    $this->returnJson["msg"] = "数据签名验证失败";
                    return false;
                }
                break;
            default:
        }
        return true;
    }

    /**
     * 标准格式化提交数据
     * @param $parameter
     */
    private function FormatData($parameter)
    {
        if($this->query){
            $this->FormatData = [
                'userid' => $this->MemberidToUserid($parameter["memberid"]), //  用户ID
                'orderid' => $parameter["orderid"],  //用户订单号
                'notifyurl' => $parameter["notifyurl"],   //异步回调地址
                'version' => $parameter["version"],
                'signmethod' => $parameter["signmethod"],
                'query'  => 'query'
            ];
        }else{
            $this->FormatData = [
                'userid' => $this->MemberidToUserid($parameter["memberid"]), //  用户ID
                'amount' => floatval($parameter["amount"] / 100),  //交易金额
                'orderid' => $parameter["orderid"],  //用户订单号
                'callbackurl' => $parameter["callbackurl"],  //同步跳转回调地址
                'notifyurl' => $parameter["notifyurl"],   //异步回调地址
                'orderdatetime' => $parameter["orderdatetime"],    //交易订单时间
                'bankcode' => $parameter["bankcode"],   //银行编码
                'tongdao' => $parameter["paytype"],  //通道分类编码
                'version' => $parameter["version"],
                //'extend' => $parameter["reserved"],   //扩展字段
                'other' => [  // 会按原样返回的字段
                    'signmethod' => $parameter["signmethod"],
                    'extend' => $parameter['extend']
                ], //其它的字段
            ];
        }

        return $this->FormatData;
    }

    /**2019-01-29 张杨  封装回调参数
     * @param $parameter
     * @return array
     */
    private function ReturnData($parameter,$query=""){
        //dump($parameter);
        if($query == "query"){
            $ReturnData = [
                'memberid' => $this->UseridToMemberid($parameter["userid"]), //  用户ID
                'amount' => floatval($parameter["amount"]) * 100,  //交易金额
                'true_amount'  => floatval($parameter['true_amount']) * 100,  //实际支付金额
                'orderid' => $parameter["orderid"],  //用户订单号
                'sysorderid' => $parameter["sysorderid"], //系统订单号
                'submitime' => $parameter["submittime"],    //提交时间
                'successtime' => $parameter['successtime'], //成功时间
                'version' => $parameter["version"],
                'status' => $parameter["status"]=="00"?"success":"error",
                'signmethod' => $parameter['signmethod'],
            ];
        }else{
            $ReturnData = [
                'memberid' => $this->UseridToMemberid($parameter["userid"]), //  用户ID
                'amount' => floatval($parameter["amount"]) * 100,  //交易金额
                'amount_trade' => floatval($parameter['amount_trade']) * 100, //交易手续费
                'true_amount'  => floatval($parameter['true_amount']) * 100,  //实际支付金额
                'orderid' => $parameter["orderid"],  //用户订单号
                'sysorderid' => $parameter["sysorderid"], //系统订单号
                'submitime' => $parameter["submittime"],    //提交时间
                'successtime' => $parameter['successtime'], //成功时间
                'tongdao' => $parameter["tongdao"],  //通道分类编码
                'version' => $parameter["version"],
                'status' => $parameter["status"]=="00"?"success":"error",
                'signmethod' => $parameter['other']['signmethod'],
            ];
            $ReturnData = array_merge($ReturnData,$parameter['other']);
        }

        //return array_merge($ReturnData,$parameter['other']);
        return $ReturnData;
    }

    /**
     * 2019-03-03 张杨 回调数据加密
     * @param $signdata
     * @param $secretkey
     * @return bool|string
     */
    private function getSign($signdata, $secretkey){

        switch ($signdata["signmethod"]) {
            case "md5":
                ksort($signdata);
                $md5str = strtoupper(md5(urldecode(http_build_query($signdata)) . $secretkey["md5str"]));
                return $md5str;
                break;
            case "rsa":
                $rsa = new \Org\Util\RSA($secretkey["user_keypath"], $secretkey["sys_privatekeypath"]);
                ksort($signdata);
                $signstr = urldecode(http_build_query($signdata));
                return $rsa->sign($signstr);
                break;
            default:
                return "";
        }

    }
    /**
     * 异步回调方法
     */
    public function notifyurl($parameter){
        $returndata = $this->ReturnData($parameter);
        $secretkey = $this->GetSecretKey($parameter['userid']);
        $returndata["sign"] = $this->getSign($returndata,$secretkey);
        $res = $this->http_post($parameter['notifyurl'],$returndata);
        if(strtoupper($res) == "OK"){
            $return = [
                'reutrncontent' => $res,
                'status'        => true
            ];
        }else{
            $return = [
                'reutrncontent' => $res,
                'status'        => false
            ];
        }

        return $return;
    }

    /**
     * 同步跳转回调
     */
    public function callbackurl($parameter){
        $returndata = $this->ReturnData($parameter);
        $secretkey = $this->GetSecretKey($parameter['userid']);
    //    dump($returndata);
     //   dump($secretkey);
        $returndata["sign"] = $this->getSign($returndata,$secretkey);
      //  dump($returndata);
        setHtml($parameter["callbackurl"],$returndata);
    }

    /**
     * 查询异步回调
     * @param $parameter
     */
    public function queryNotifyurl($parameter){
        $returndata = $this->ReturnData($parameter,"query");
        $secretkey = $this->GetSecretKey($parameter['userid']);
        $returndata["sign"] = $this->getSign($returndata,$secretkey);
        $res = $this->http_post($parameter['notifyurl'],$returndata);
    }

    /**
     * 查询响应输出
     * @param $parameter
     */
    public function queryExit($parameter){
        $returndata = $this->ReturnData($parameter,"query");
        $secretkey = $this->GetSecretKey($parameter['userid']);
        $returndata["sign"] = $this->getSign($returndata,$secretkey);
        return json_encode($returndata);
    }

}