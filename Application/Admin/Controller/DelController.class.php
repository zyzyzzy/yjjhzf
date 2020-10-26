<?php

namespace Admin\Controller;

use Think\Controller;

class DelController extends CommonController
{
    private $dateFileds = [
        'adminloginerror' => 'login_time',        //管理员登陆错误记录表
        'adminloginrecord' => 'login_datetime',    //管理员登录记录表
        'adminoperaterecord' => 'date_time',      //管理员操作记录表
        'delayunfreeze' => 'date_time',       //冻结金额延期记录表
        'manualunfreeze' => 'date_time',      //订单冻结金额手动解冻记录表
        'moneychange' => 'datetime',     //资金变动记录表
        'order' => 'datetime',     //交易记录表
        'ordercommission' => 'date_time',     //订单提成记录表
        'ordercomplaint' => 'date_time',     //订单投诉处理过程记录表
        'orderfreezemoney' => 'date_time',     //订单冻结金额明细表
        'orderlog' => 'at_time',     //订单日志表
        //'ordertichengmoney'=>'datetime',     //订单提成记录表  (重复的表)
        'orderunfreezetask' => 'date_time',     //冻结订单自动解冻任务请求解冻过程
        'secretkeyrecord' => 'date_time',     //修改密钥记录表
        'settle' => 'applytime',     //结算记录表
        'userinvitecode' => 'create_time',     //管理员生成邀请码表
        'userloginerror' => 'login_time',     //用户登录错误信息表
        'userloginrecord' => 'logindatetime',     //用户登录记录表
        'usernotice' => 'date_time',     //管理员发布公告表
        'useroperaterecord' => 'operatedatetime',     //用户操作记录表
        'userworkorder' => 'date_time',     //用户工单管理表
        'userworkordercontent' => 'datetime',     //用户工单沟通内容表
        'userworkorderhelp' => 'date_time',     //用户工单帮助文档表
    ];

    protected $orders = [
        'ordermoney' => 'sysordernumber',      //订单金额表
        'orderother' => 'sysordernumber',      //交易扩展信息表
        'orderreturncontent' => 'sysordernumber',      //订单返回内容信息表
        'ordershorturl' => 'sysordernumber',      //返回二维码路径转换表
        'orderreturncontent' => 'sysordernumber',      //订单返回内容信息表
        'ordersubmitparameter' => 'sysordernumber',     //订单提交参数记录表
        'userparameter' => 'sys_order_num',     //用户提交参数信息表
    ];

    protected $setttles = [
        'settlemoney' => 'ordernumber',     //结算金额表
    ];

    public function index()
    {
        $this->display();
    }

    public function del()
    {
        $start = I('start');
        $end = I('end');
        if ($start == "" || $end == "") {
            $this->ajaxReturn([
                'status' => 'error',
                'msg' => '请选择删除时间区间'
            ]);
        } else {
            $start .= " 00:00:00";
            $end .= " 23:59:59";
        }

        $tables = I('table');
        /*
         *
         * 为防止数据误删除,暂时只清除 管理员登陆错误记录表 和 管理员登录记录表数据
         * */
//        $tables = [
//            'adminloginerror',
//            'adminloginrecord'
//        ];
        $msgs = [];
        while (count($tables) > 0) {
            $table = array_shift($tables);
            $where = [
                $this->dateFileds[$table] => [['EGT', $start], ['ELT', $end]],
            ];
            if ($table == 'order') {
                $sysordernumbers = M($table)->where($where)->getField('sysordernumber', true);
                if ($sysordernumbers) {
                    $this->deleteOrder($sysordernumbers);
                }

            }
            if ($table == 'settle') {
                $ordernumbers = M($table)->where($where)->getField('ordernumber', true);
                if ($ordernumbers) {
                    $this->deleteSettle($ordernumbers);
                }

            }
            $res = M($table)->where($where)->delete();
            //$db_prefix = C('DB_PREFIX');
//            if($res){
//                $msgs[]="数据表".$db_prefix.$table.$start."至".$end."之间数据删除成功!";
//            }else{
//                $msgs[]="数据表".$db_prefix.$table.$start."至".$end."之间数据删除失败!";
//            }
        }

        $this->ajaxReturn([
            'status' => 'success',
            'msg' => '执行完成',
        ], 'json', JSON_UNESCAPED_UNICODE);

    }


    public function deleteOrder($sysordernumbers)
    {
        $tables = $this->orders;
        foreach ($tables as $k => $v) {
            $where = [$v => ['in', $sysordernumbers]];
            $res = M($k)->where($where)->delete();

            $db_prefix = C('DB_PREFIX');
            if ($res) {
                $msgs[] = "数据表" . $db_prefix . $k . "数据删除成功!";
            } else {
                $msgs[] = "数据表" . $db_prefix . $k . "之间数据删除失败!";
            }
        }
    }

    public function deleteSettle($ordernumbers)
    {
        $tables = $this->setttles;
        foreach ($tables as $k => $v) {
            $where = [$v => ['in', $ordernumbers]];
            $res = M($k)->where($where)->delete();

            $db_prefix = C('DB_PREFIX');
            if ($res) {
                $msgs[] = "数据表" . $db_prefix . $k . "数据删除成功!";
            } else {
                $msgs[] = "数据表" . $db_prefix . $k . "之间数据删除失败!";
            }
        }
    }

}