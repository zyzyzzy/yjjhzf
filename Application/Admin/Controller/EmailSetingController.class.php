<?php

namespace Admin\Controller;

use Admin\Model\EmailsetingModel;
use Think\Controller;


class EmailSetingController extends CommonController
{
    public function EmailSeting()
    {
        $this->display();
    }

    //加载邮箱列表
    public function LoadEmailSeting()
    {
        $where = [];
        $i = 0;
        $username = I('post.username', '', 'trim');
        if ($username) {
            $where[$i] = 'user_name="' . $username . '"';
        }
        $email = I('post.email', '', 'trim');
        if ($email) {
            $where[$i] = 'email="' . $email . '"';
        }
        $xjrxm = I('post.xjrxm', '', 'trim');
        if ($xjrxm) {
            $where[$i] = 'receive_email="' . $xjrxm . '"';
        }
        $page = I('post.page', 1);
        $limit = I('post.limit', 10);
        $count = EmailsetingModel::getCount($where);
        $emials = EmailsetingModel::loadEmails($where, $page, $limit);
        $this->ajaxReturn([
            'code' => 0,
            'msg' => '数据加载成功',//响应结果
            'count' => $count,//总页数
            'data' => $emials
        ], 'JSON');
    }

    //删除邮箱
    public function DelEmailSeting()
    {
        $id = I("post.id");
        $email = EmailsetingModel::getEmailName($id);
        $msg = '删除邮箱[' . $email . ']:';
        $res = EmailsetingModel::delEmail($id);
        if ($res) {
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . '删除成功');
        $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
    }

    //添加邮箱页面
    public function EmailSetingAdd()
    {
        $this->display();
    }

    //确认添加
    public function emailcreate()
    {
        $msg = '添加邮箱[' . I('post.email', '', 'trim') . ']:';
        $return = AddSave('emailseting', 'add', '添加邮箱');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //邮箱编辑页面
    public function EmailEdit()
    {
        $email = EmailsetingModel::getInfo(I('id'));
        $this->assign('email', $email);
        //判断当前操作者的二次验证与管理密码的开启状态
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //确认修改
    public function emailUpdate()
    {
        $msg = '修改邮箱[' . I('post.email') . ']:';
        //检测验证码的代码
        $verfiy_code = I('post.verfiy_code', '', 'trim');  //修改数据时的验证码
        $code_type = I('post.code_type', 0, 'intval');  //验证码类型
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);   //验证密码
        $return = AddSave('emailseting', 'save', '修改邮箱');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");


    }

    //测试发送邮件页面
    public function testEmail()
    {
        $id = I('get.id');
        $info = EmailsetingModel::getInfo($id);
        $this->assign('info', $info);
        $this->display();
    }

    //确认发送
    public function testSendEmail()
    {
        $receive_email = I('post.receive_email', '', 'trim');
        $msg = '给邮箱[' . $receive_email . ']发送邮件:';
        $conf = I('post.');
        if (SendMail($conf)) {
            $this->addAdminOperate($msg . '发送成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '发送成功']);
        }
        $this->addAdminOperate($msg . '发送失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '发送失败']);
    }

    /**
     * setHtml
     * 生成HTML表单并提交
     * @param $tjurl 提交地址
     * @param array $arraystr 表单字段数组
     * @param bool $test 是否测试模式，默认 false
     */
    public function setHtml($tjurl, $arraystr, $test = false, $method = "post")
    {
        if ($test) {
            $str = '<form id="Form1" name="Form1" method="' . $method . '" action="' . $tjurl . '">';
            foreach ($arraystr as $key => $val) {
                $str = $str . $key . '：' . $val . '<br /><input type="hidden" name="' . $key . '" value="' . $val . '">';
            }
            $str = $str . '<input type="submit" value="submit">';
            $str = $str . '</form>';
        } else {
            $str = '<form id="Form1" name="Form1" method="' . $method . '" action="' . $tjurl . '">';
            foreach ($arraystr as $key => $val) {
                $str = $str . '<input type="hidden" name="' . $key . '" value="' . $val . '">';
            }
            $str = $str . '</form>';
            $str = $str . '<script>';
            $str = $str . 'document.Form1.submit();';
            $str = $str . '</script>';
        }
        exit($str);
    }

    /**
     * 将请求报文中所有需要签名的数据元采用 key=value 的形式按照名称排序(即按key的字典顺序排序)，以&连接符连接得到代签名字符串 signStr
     * @param $data
     * @return string
     */
    public function getSignStr($data)
    {
        ksort($data);
        $str = "";
        foreach ($data as $key => $value) {
            if ($value != '' && $key != 'sign') {
                $str .= $key . '=' . $value . '&';
            }
        }
        return trim($str, '&');
    }
}