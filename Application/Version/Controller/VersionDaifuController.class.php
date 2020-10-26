<?php

namespace Version\Controller;

use Think\Controller;

class VersionDaifuController extends Controller {

      protected $returnJson  = [];
      protected $userid; //用户ID
      protected $secretKey = [];  //用户密钥
      protected $FormatData = [];  //封装提交参数
      protected $ReturnData = []; //封装回调参数
      protected $DecryptData;  //解密后的需要参与签名的提交参数


    public  function  __construct()
      {
          parent::__construct();
          $this->FormatData = C('REQUEST_FORMAT_DATA');
      }

     final public function index($parameter,&$returnJson,$obj){

         $this->returnJson = &$returnJson;  //首先传址

         //2018-01-27 张杨
         //判断子类中是否定义必填参数 property_exists：判断类或对象中是否存在某一属性
         if(!(property_exists ($obj,'parameterarray'))){
             $this->returnJson["msg"] = "该版本中没有定义必填字段";
             return false;
         }


         foreach ($this->parameterarray as $key) {
             if (empty($parameter[$key])) {
                 $this->returnJson["msg"] = "参数 $key 不能为空";
                 return false;
             }
         }

         //判断子类中是否定义签名参数 property_exists：判断类或对象中是否存在某一属性
          if(!(property_exists ($obj,'encryptedFields'))){
              $this->returnJson["msg"] = "该版本中没有定义签名需要的字段";
              return false;
          }


         foreach ($this->encryptedFields as $key) {
             if (empty($parameter[$key])) {
                 $this->returnJson["msg"] = "参数 $key 不能为空";
                 return false;
             }
         }


         $this->returnJson["parameter"] = $parameter;
         return (
             call_user_func(array($obj,"CheckParameterCorrect"),$parameter)
             && $this->SetUserId($parameter,$obj)
             && $this->SetSecretKey($obj)
             && $this->PackageData($parameter,$obj)
            // && call_user_func(array($obj,"CheckSign"),$parameter,$this->DecryptData,$this->secretKey)
             && call_user_func(array($obj,"CheckSign"),$this->getencryptedFields($parameter,$this->encryptedFields),$this->DecryptData,$this->secretKey)
             && $this->CheckFormatData($parameter,$obj)
         );
      }


    /**
     * 2019-01-27 张杨
     * 同一个用户不可有订单号重复
     * @param $memberid
     * @param $orderid
     */
     final public function CheckOrderid($memberid,$orderid)
     {
         if (M("settle")->lock(true)->where("userordernumber='" . $orderid . "' and memberid = '" . $memberid . "'")->count() > 0) {
             return false;
         }else{
             return true;
         }
     }

    /**
     * 检查收到的参数是否完整
     * @param $parameter 获取到的参数
     * @return mixed
     */
    protected function CheckParameterComplete($parameter){

    }

    /**
     * 检查收到的参数是否合法
     * @param $parameter 获取到的参数
     * @return mixed
     */
    protected function CheckParameterCorrect($parameter){

    }

    /**
     * 获取用户ID
     * @param $parameter
     * @return bool
     * 子类不可重写
     */
    private function GetUserId($parameter){


    }

    /**
     * 获取用户的密钥
     * @param $parameter
     * @return bool
     * 子类不可重写
     */
    private function GetSecretKey($parameter){

    }

    /**
     * 解密参数
     * @param $parameter
     * @return bool
     */
    private function DecryptData(&$parameter){


    }

    /**
     * 检测数据签名是否正确
     * @param $parameter
     * @return bool
     */
    protected function CheckSign($parameter){


    }

    /**
     * 标准格式化提交数据
     * @param $parameter
     */
    private function FormatData(){

    }

    /**
     * 封装回调数据
     */
    private function ReturnData(){

    }

    final private function CheckFormatData($parameter,$obj){
        $formatdata =  call_user_func(array($obj,"FormatData"),$parameter);
        if(!is_array($formatdata)){
            $this->returnJson["msg"] = '格式化数据失败';
            return false;
        }
        /*
        $error = true;
        $errorstr = "";
        foreach($formatdata["type"]=="query"?C('REQUEST_FORMAT_DATA_QUERY'):C('REQUEST_FORMAT_DATA') as $key => $val){
            if($key != "other" and (!array_key_exists($key,$formatdata) or $formatdata[$key] == "")){
                $errorstr .= $key."、";
                $error = false;
            }
            if($key == "other" and array_key_exists("other",$formatdata) and !is_array($formatdata["other"])){
                $this->returnJson["msg"] = '格式化数据字段 other 可以为空，不为空时必须为数组类型';
                return false;
                //continue;
            }
        }
        if(!$error){
            $this->returnJson["msg"] = '格式化数据字段（'.$errorstr.'）不能为空！';

            return false;
        }
        */
        $this->returnJson["status"] = "success";
        $this->returnJson["msg"] = "成功";
        $this->returnJson["formatdata"] = $formatdata;
       // $this->FormatData = $formatdata;
        return true;
    }

    /**组装待加密字段 2019-01-27 张杨  添加备注
     * @param $parameter
     * @param $obj
     * @return bool
     */
    final private function PackageData($parameter,$obj){
        $return = call_user_func(array($obj,"DecryptData"),$parameter,$this->secretKey);
        if(is_bool($return) and !$return){
            return false;
        }elseif(is_array($return)){
           // $this->returnJson["parameter"] = $parameter;
            $this->DecryptData = $return;
            $this->returnJson["decryptdata"] = $this->DecryptData;
            return true;
        }else{
            $this->returnJson["msg"] = '待签名数据格式错误 ，必须为数组';
            return false;
        }
    }

    /**设置用户ID，2019-01-27 张杨 添加备注并修改代码逻辑
     * @param $parameter
     * @param $obj
     * @return bool
     */
    final private function SetUserId($parameter,$obj){
        $userid = call_user_func(array($obj,"GetUserId"),$parameter);
        if(!$userid){
            $this->returnJson['msg'] = "用户ID不存在";
            return false;
        }else{

            $this->userid = $userid;
            return true;
        }
        /*
        if(is_bool($userid) and !$userid){
            return false;
        }elseif (is_string($userid) and $userid != ""){
            $count = M("user")->where("id=".$userid)->count();
            if($count == 1){
                $this->userid = $userid;
                return true;
            }else{
                $this->returnJson["msg"] = "用户ID不存在";
                return false;
            }
        }else{
            $this->returnJson["msg"] = "用户ID为空";
            return false;
        }
        */
    }

    /**设置用户秘钥 2019-01-27 张杨  添加备注
     * @param $obj
     * @return bool
     */
    final private function SetSecretKey($obj){
        $secretkey = call_user_func(array($obj,"GetSecretKey"),$this->userid);
        if(is_bool($secretkey) and !$secretkey){
            return false;
        }elseif (is_array($secretkey)){
            $keyarray = C("SECRETKEY_ARRAY");
            $i = 0;
            $str = "";
            foreach($keyarray as $key){
                if(empty($secretkey[$key])){
                    $str .= $key."、";
                    $i++;
                }
            }

            if($i >= sizeof($keyarray)){
                $this->returnJson["msg"] = "用户密钥数据(".$str.")不可全部为空";
                return false;
            }else{
                $this->secretKey = $secretkey;
                return true;
            }

        }else{
            $this->returnJson["msg"] = "用户密钥数据格式错误，必须为数组";
            return false;
        }
    }

    /**根据商户ID获取用户ID 2019-01-27 张杨添加备注
     * @param $memberid
     * @return mixed
     */
    final protected  function MemberidToUserid($memberid){
            $userid = M("secretkey")->where("memberid='".$memberid."'")->getField("userid");
            return $userid;
    }

    /**根据 用户ID 获取 商户ID 2019-01-27 张杨
     * @param $userid
     * @return mixed
     */
    final protected  function UseridToMemberid($userid){
        $memberid = M("secretkey")->where("userid='".$userid."'")->getField("memberid");
        return $memberid;
    }

    /**根据用户ID获取用户的相关秘钥 2019-01-27 张杨 添加备注
     * @param $userid
     * @return mixed
     */
    final protected function UserIdToSecretKey($userid){
        $secretkey = M("secretkey")->where("userid=".$userid)->field(C("SECRETKEY_ARRAY"))->find();
        return $secretkey;
    }

    final protected function http_post($url, $param)
    {

        if (empty($url) || empty($param)) {
            return false;
        }
        $param = http_build_query($param);
        try {

            $ch = curl_init();//初始化curl
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //正式环境时解开注释
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $data = curl_exec($ch);//运行curl
            curl_close($ch);

            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    //2019-03-18汪桂芳添加:判断用户是否指定有版本号
   final public function checkUserVersion($user_id,$version,$versiontype='pay')
    {
        //先判断用户是否有指定版本
        switch($versiontype){
            case 'pay':
                $version_arr['fields'] = 'version_id';
                $version_arr['tablename'] = 'version';
                break;
            case 'settle':
                $version_arr['fields'] = 'settle_version';
                $version_arr['tablename'] = 'settleversion';
                break;
            default:
        }
        if(!$version_arr){
            $res['status'] = false;
            $res['msg'] = 'version 参数错误';
            return $res;
        }
      //  $user_id = M('secretkey')->where('memberid="'.$parameter['memberid'].'"')->getField('userid');
        $user_version = M('user')->where('id='.$user_id)->getField($version_arr['fields']);
        $user_version_id = M($version_arr['talbename'])->where("numberstr='".$version."'")->getField("id");
        if($user_version){
            //用户指定了版本,只判断是否在指定版本中
            $user_version_arr = explode(',',$user_version);
            if(!in_array($user_version_id,$user_version_arr)){
                $res['status'] = false;
                $res['msg'] = 'version 参数错误,不在用户指定版本中';
                return $res;
            }
        }
        //用户未指定版本,判断是否在通用版本中
        $public_version_arr = M($version_arr['tablename'])->where('del=0 and all_use=1')->field('id')->select();
        if($public_version_arr){
            //通用版本存在
            $temp_version_arr = [];
            foreach ($public_version_arr as $k=>$v){
                $temp_version_arr[] = $v['id'];
            }
            if(!in_array($user_version_id,$temp_version_arr)){
                $res['status'] = false;
                $res['msg'] = 'version 参数错误,不在可用版本中';
                return $res;
            }
        }else{
            //通用版本不存在
            $res['status'] = false;
            $res['msg'] = 'version 参数错误,系统没有可用版本,请联系管理员处理';
            return $res;
        }

        $res['status'] = true;
        $res['msg'] = '';
        return $res;
    }

    /**
     * 2019-03-26 张杨 ，返回待加密验签的字段数组
     * @param $parameter
     * @return array
     */
    final protected function getencryptedFields($parameter,$encryptedFields){
        $arr = [];
        foreach($encryptedFields as $k){
            $arr[$k] = $parameter[$k];
        }
        return $arr;

    }


}