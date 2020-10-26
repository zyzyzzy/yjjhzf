<?php
/**
 * Created by PhpStorm.
 * User: 汪桂芳
 * Date: 2019/2/20
 * Time: 15:40
 */
namespace Unfreeze\Controller;

use Think\Controller;

class TestController extends Controller {
    public function test()
    {
        //发送到独立系统的定时任务
        //拼接请求参数
        $send_data = [
            'version' => '1.0.0',  //版本号
            'merid' => C('AMNPAY_MERID'),  //独立系统分配的商户编号,从配置文件读出
            'notifyurl' => 'http://www.baidu.com',  //异步回调地址,暂时没有,后期可能会加
            'createtime' => date('Y-m-d H:i:s'),  //系统请求接口创建任务的时间
        ];
        //查询交易订单生成的所有冻结订单
        $new_all_orderfreeze = M('orderfreezemoney')->where("sysordernumber=123456789")->select();
        $content = [];
        foreach ($new_all_orderfreeze as $k=>$v) {
            $content[] = [
                'merorderno' => $v['freezeordernumber'],  //冻结订单号
                'request_url' => C('AUTO_UNFREEZE_URL'),  //请求路径,从配置文件中读出
                'request_time' => $v['expect_time'],  //接口请求系统开始解冻的时间
            ];
        }
        $send_data['content'] = json_encode($content);
//        echo $send_data['content'];die;
        //签名
        $userKey = C('AMNPAY_SECRETKEY'); //与独立系统对接的密钥,从配置文件读取
        $signStr = getTaskSignStr($send_data); //签名字符串
        openssl_sign($signStr, $sign, $userKey);  //rsa加密
        $send_data['sign'] = base64_encode($sign);
        //curl请求定时任务接口
        $post_url = C('MORE_TASK_UNFREEZE_URL');//多条定时任务请求接口
        $task_return = curlPost($post_url,$send_data);
        $task_return = json_decode($task_return,true);
        print_r($task_return);
    }
}