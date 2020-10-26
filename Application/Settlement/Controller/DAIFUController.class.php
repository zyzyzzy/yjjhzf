<?php
/**
 * Created by PhpStorm.
 * User: zyzyz
 * Date: 2019/8/25
 * Time: 0:36
 */

namespace Settlement\Controller;

use Think\Controller;
use Daifu\Model\SettleModel;

class DAIFUController extends SettleController
{
    protected $CheckDomain = false;  //是否判断提交域名
    protected $CheckIp = false;  //是否判断提交ip
    protected $checkStatus = true;  //通道状态
    protected $sysOrdernumberLeng = 32; //体系订单号长度
    protected $callbackurl = "callbackurl";
    protected $notifyurl = "notifyurl";
    protected $tiurl;  //提交地址

    public function pay($settle_parameter){
        //dump($settle_parameter);
        //---------------------$settle_parameter-------------------------
//        array(13) {
//            ["userordernumber"] => string(13) "1566972717681"
//            ["shangjiaid"] => string(2) "19"
//            ["payapiid"] => NULL
//            ["payapiaccountid"] => string(2) "32"
//            ["userid"] => string(1) "1"
//            ["ordermoney"] => string(4) "4.30"
//            ["settle_money"] => string(4) "4.30"
//            ["callbackurl"] => string(46) "https://www.juhezhifu.cc/Pay//callbackurl.html"
//            ["notifyurl"] => string(44) "https://www.juhezhifu.cc/Pay//notifyurl.html"
//            ["account_key"] => array(9) {
//                ["publickeystr_path"] => string(0) ""
//                ["privatekeystr_path"] => string(0) ""
//                ["upstreamkeystr_path"] => string(0) ""
//                ["publickeystr"] => NULL
//                ["privatekeystr"] => NULL
//                ["upstreamkeystr"] => string(32) "40967464d42936b51d91d9c0b1ece340"
//                ["memberid"] => string(18) "wx25b09b900f687334"
//                ["account"] => string(10) "1431740002"
//                ["md5keystr"] => string(32) "acd85ff686bb5f65432401613f10edc8"
//  }
//  ["sysordernumber"] => string(32) "dlwobe0snim5w8vVq81N0isl5xj91r4Q"
//            ["memberid"] => string(18) "wx25b09b900f687334"
//            ["settle_bank"] => array(24) {
//                ["id"] => string(2) "11"
//                ["ordernumber"] => string(32) "dlwobe0snim5w8vVq81N0isl5xj91r4Q"
//                ["userordernumber"] => string(13) "1566972717681"
//                ["userid"] => string(1) "1"
//                ["memberid"] => string(5) "10001"
//                ["bankname"] => string(12) "招商银行"
//                ["bankzhiname"] => string(12) "东湖支行"
//                ["banknumber"] => NULL
//                ["bankcode"] => NULL
//                ["bankcardnumber"] => string(16) "6225881274034957"
//                ["bankusername"] => string(6) "张杨"
//                ["identitynumber"] => string(18) "420116198312102710"
//                ["phonenumber"] => string(11) "15871707089"
//                ["province"] => NULL
//                ["city"] => NULL
//                ["applytime"] => string(19) "2019-08-23 17:10:20"
//                ["dealtime"] => NULL
//                ["type"] => string(1) "0"
//                ["status"] => string(1) "0"
//                ["ordermoney"] => string(6) "4.3000"
//                ["refundmoney"] => string(6) "0.0000"
//                ["remarks"] => NULL
//                ["refundstatus"] => string(1) "0"
//                ["refundremarks"] => string(0) ""
//  }
//}

        //---------------------$settle_parameter-------------------------
        //  0 订单生成成功，未处理  1 订单生成成功，处理中  2 订单生成成功，已打款
        $sysordernumber = $settle_parameter['sysordernumber'];  //系统订单号
        $settle_status = true;   //代付结果处理成功
        $response_array = $this->createResponseArray($sysordernumber);
       // file_put_contents('cccccccccccc.txt', http_build_query($response_array), FILE_APPEND);
        if($response_array['response_status']){
            if($settle_status){
                $response_array['status'] = 1;   //处理中
                $response_array['msg'] = '结算申请成功，正在处理中';
               // file_put_contents('ggggggggggggggggggg.txt', http_build_query($response_array), FILE_APPEND);
                SettleModel::updateSettleStatus($sysordernumber,1,'结算申请成功，正在处理中');
            }else{
                $response_array['status'] = 4;   //打款失败
                $response_array['msg'] = '打款失败';
                SettleModel::updateSettleStatus($sysordernumber,4,'打款失败');
            }
        }

        //$this->ajaxReturn($response_array);
        $this->responseFromData($response_array);
    }


    public function notifyurl()
    {
        $sysordernumber = "";  //系统订单号
        $settle_status = true;   //代付结果处理成功


        $response_array = $this->createResponseArray($sysordernumber);
        if($response_array['response_status'] == true) {
            if ($settle_status) {
                $response_array['status'] = 2;   //已打款
                $response_array['msg'] = '已打款';
                SettleModel::updateSettleStatus($sysordernumber, 2, '已打款');
            } else {
                $response_array['status'] = 4;   //打款失败
                $response_array['msg'] = '打款失败';
                SettleModel::updateSettleStatus($sysordernumber, 4, '打款失败');
            }
        }
      if($response_array['notifyurl']){
          $return =  $this->responseFromData($response_array,'notifyurl');
          if($return['status'] == true){
              exit("SUCCESS");
          }
      }else{
          exit("SUCCESS");
      }

    }
}