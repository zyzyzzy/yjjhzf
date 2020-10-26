<?php

namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller {

    /**
     * 安装引导首页
     */
    //2019-4-1 任梦龙：系统宣传首页
    public function index()
    {
        $this->display('index2');
    }

    /**
     * 第二部，显示同意协议框
     */
    public function step1()
    {
        $this->assign('env_items', env_check());  //系统环境检测
        $this->assign('dirfile_items', dirfile_check());  //目录，文件读写检测
        $this->assign('func_items', function_check());  //函数检测
        $this->display('Index/step1');
    }

    /**
     * 第三部，连接数据库，进行操作
     */
    public function sqlLink()
    {
        $info = I('post.');
        $charset = 'UTF-8';
        $db_host = $info['db_host'];  //数据库地址
        $db_name = $info['db_name'];  //数据库名称
        $db_user = $info['db_user'];  //数据库用户名
        $db_pwd = $info['db_pwd'];    //数据库密码
        $db_port = $info['db_port'];  //数据库端口
        $db_prefix = $info['db_prefix'];  //数据库前缀
        $web_site = $info['web_site'];  //网站名称
        $admin_name = $info['admin_name'];  //超管名称
        $password = $info['password'];  //超管密码
        $repassword = $info['repassword'];  //确认密码
        $time = date('Y-m-d H:i:s');
        if (!$db_host || !$db_port || !$db_user || !$db_pwd || !$db_name || !$db_prefix || !$web_site || !$admin_name || !$password) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '数据输入不完整，请检查']);
        }

        if (strpos($db_prefix, '.') !== false) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '数据表前缀为空，或者格式错误，请检查']);
        }
        if ($password != $repassword) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码不一致，请检查']);
        }
        //连接数据库
        $conn = mysqli_connect($db_host, $db_user, $db_pwd, '', $db_port);  //连接数据库
        if (!$conn) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '连接错误' . mysqli_connect_error()]);
        }

        //创建定义的数据库
        define('DBCHARSET', 'UTF8');   //设置数据库默认编码
        $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET " . DBCHARSET);
        writeConfig($info);   //将配置信息写入新文件

        //依次写入表
        $_charset = strtolower(DBCHARSET);
        $conn->select_db($db_name);
        $conn->set_charset($_charset);
        $sql = file_get_contents("Application/Common/Conf/test.sql");
        $sql = str_replace("\r\n", "\n", $sql);
        $sql_res = runquery($sql, $db_prefix, $conn);
        if (!$sql_res) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '数据表内容为空']);
        }

        //插入超管数据
        $conn->query("INSERT INTO {$db_prefix}adminuser (`user_name`,`bieming`,`password`,`status`,`lastlogin_time`,`del`,`super_admin`,`privacy_id`) 
VALUES ('$admin_name','超级管理员','" . md5($password) . "', '1','{$time}', '0', '1', '1');");

        //新增一个标识文件，用来屏蔽重新安装
        $fp = @fopen('lock', 'wb+');
        @fclose($fp);

        $url = U('Index/step3');
        $this->ajaxReturn(['status' => 'ok', 'msg' => '导入成功', 'url' => $url]);

    }

    /**
     * 第四部，跳到前后台选择地址
     */
    public function step3()
    {
        $admin_url = U('Admin/Index/index');
        $user_url = U('User/Index/index');
        $this->assign('admin_url', $admin_url);
        $this->assign('user_url', $user_url);
        $this->display();
    }



}