<?php

namespace Version\Controller;

use Think\Controller;

class DefaultqueryController extends VersionController {

    protected $parameterarray = ['version','memberid', 'orderid', 'sign','signmethod']; //用户自己选择的参数，必填
    protected $encryptedFields = ['version','memberid', 'orderid','signmethod'];  //签名需要的字段

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 检查收到的参数是否合法
     * @param $parameter 获取到的参数
     * @return mixed
     */
    protected function CheckParameterCorrect($parameter)
    {

        if (!$this->MemberidToUserid($parameter["memberid"])) {
            $this->returnJson["msg"] = "商户ID错误！";
            return false;
        }

        if (!preg_match('/[0-9a-zA-Z][^_]{6,32}/i', $parameter["orderid"])) {
            $this->returnJson["msg"] = "订单号最短6个字符，最长32个字符，订单号只能由数字和大小写字母组成，不能包含下划线";
            return false;
        }


        if($this->CheckOrderid($parameter['memberid'],$parameter['orderid'])){
            $this->returnJson["msg"] = "订单号不存在";
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
                    return true;
                } else {
                    $this->returnJson["msg"] = "数据签名验证失败" .urldecode(http_build_query($signdata)) . $secretkey["md5str"];
                    return false;
                }
                break;
            case "rsa":
                $rsa = new \Org\Util\RSA($secretkey["publickeypath"], $secretkey["sys_privatekeypath"]);
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
        $this->FormatData = [
            'userid' => $this->MemberidToUserid($parameter["memberid"]), //  用户ID
            'orderid' => $parameter["orderid"],  //用户订单号
            'notifyurl' => $parameter["notifyurl"],   //异步回调地址
            'orderdatetime' => $parameter["orderdatetime"],    //交易订单时间
            'bankcode' => $parameter["bankcode"],   //银行编码
            'tongdao' => $parameter["paytype"],  //通道分类编码
            'version' => $parameter["version"],
            'extend' => $parameter["reserved"],   //扩展字段
            'amntype' => 'query'
        ];
        return $this->FormatData;
    }
}