<?php
//分页方法封装
//PageDataLoad(资源地址,模型层名称,全区域)
function PageDataLoad($tablename, $where)
{
    $ReturnArr = [
        'code' => 0,
        'msg' => '数据加载成功', //响应结果
        'count' => 0, //总页数
        'data' => [
        ]
    ];
    /**
     * D函数用于实例化模型类 格式 [资源://][模块/]模型
     * @param string $name 资源地址
     * @param string $layer 模型层名称
     * @return Model
     */
    //总页数
    $count = D($tablename)->where($where)->count();
    //分页页面展示设置,得到数据库里的数据（del=0）二维数组
    $datalist = D($tablename)->scope('default_scope')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('id desc')->select();
    //通过user_id查询对应的用户名字
    foreach ($datalist as $key => $val) {
        $datalist[$key]['par_name'] = getUserName($val['user_id']);
    }
    $ReturnArr['count'] = $count;
    $ReturnArr['data'] = $datalist;
    return $ReturnArr; //分页响应结果输出
}

//时间戳
function YmdHis()
{
    return date("Y-m-d H:i:s"); //转换为YYYY-MM-DD HH:II:SS 年月日 时分秒 格式输出
}

/**
 * 导出EXCEL函数
 * @access public
 * @param  $title 标题名
 * @param $menu_zh  菜单中文名，类型数组
 * @param $menu_en 菜单字段名，类型数组
 * @param $list  具体内容，类型数组
 * @param $config 配置 RowHeight 行高  Width 列宽
 * @throws Exception
 */
function DownLoadExcel($title, $menu_zh, $menu_en, $list, $config = array('RowHeight' => 30, 'Width' => 40))
{
    Vendor('PHPExcel.PHPExcel');
    $objPHPExcel = new \PHPExcel();
    $objPHPExcel->getProperties()
        ->setCreator("Da")
        ->setLastModifiedBy("Da")
        ->setTitle("Office 2007 XLSX Test Document")
        ->setSubject("Office 2007 XLSX Test Document")
        ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
        ->setKeywords("office 2007 openxml php")
        ->setCategory("Test result file");
    $objPHPExcel->setActiveSheetIndex(0);
    $objPHPExcel->getActiveSheet(0)->setTitle($title);
    $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight($config['RowHeight']);
    for ($colIndex = 'A', $i = 0; $colIndex < count($menu_zh), $i < count($menu_zh); $colIndex++, $i++) {
        $objPHPExcel->getActiveSheet()->getColumnDimension($colIndex)->setWidth($config['Width']);
        $objPHPExcel->getActiveSheet(0)->setCellValue($colIndex . '1', $menu_zh[$i]);
        $objPHPExcel->getActiveSheet(0)->getStyle($colIndex . "1")->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle($colIndex . "1")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    }
    $row = 2;
    foreach ($list as $key => $val) {
        $colIndex = 'A';
        foreach ($menu_en as $k => $v) {
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight($config['RowHeight']);
            $objPHPExcel->getActiveSheet()->getStyle($colIndex . $row)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet(0)->setCellValueExplicit($colIndex . $row, $val[$v], \PHPExcel_Cell_DataType::TYPE_STRING);
            $colIndex++;
        }
        $row++;
    }
    $filename = date('YmdHis', time()) . $title;
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment; filename=" . $filename . ".xls");
    header("Content-Transfer-Encoding: binary");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0,max-age=0");
    header("Pragma: no-cache");
    $objWriter->save('php://output');
    exit();
}

//随机数密码
//randpw(@param->$len随机数字符串长度,@param->$format随机数取值范围)
//随机数取值范围:All->全英文大小写字符和数字  CHAR->纯英文大小写字符
function randpw($len = 8, $format = 'ALL')
{
    $is_abc = $is_numer = 0;
    $password = $tmp = '';
    switch ($format) {
        case 'ALL':
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            break;
        case 'MIX':
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            break;
        case 'CHAR':
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            break;
        case 'NUMBER':
            $chars = '0123456789';
            break;
        default :
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            break;
    }
    mt_srand((double)microtime() * 1000000 * getmypid());
    //密码长度<设置随机数长度 修改字符串长度
    while (strlen($password) < $len) {
        $tmp = substr($chars, (mt_rand() % strlen($chars)), 1);
        if (($is_numer <> 1 && is_numeric($tmp) && $tmp > 0) || $format == 'CHAR') {
            $is_numer = 1;
        }
        if (($is_abc <> 1 && preg_match('/[a-zA-Z]/', $tmp)) || $format == 'NUMBER') {
            $is_abc = 1;
        }
        $password .= $tmp;
    }
    if ($is_numer <> 1 || $is_abc <> 1 || empty($password)) {
        $password = randpw($len, $format);
    }
    return $password;
}

//添加保存
//AddSave( @param->$tablename资源地址(表名),@param->$type选择方式,@param->$msg响应状态信息)
function AddSave($tablename, $type = 'add', $msg)
{
    $tableobj = D($tablename);
    $return = [];
    if (!$tableobj->create()) {
        $return["status"] = "no";
        $return["msg"] = $tableobj->getError();
    } else {
        //2019-3-12 任梦龙：添加filter('trim'):过滤参数,去两端的空格
        //2019-3-13 任梦龙：修改
        //保存方式 添加/保存覆盖
        if ($type == 'add') {
            $r = $tableobj->add();
            $return["id"] = $r;
        } elseif ($type == 'save') {
            $r = $tableobj->save();
        }
        //响应状态码
        if ($r) {
            $return["status"] = "ok";
            $return["msg"] = $msg . "成功！";
        } else {
            $return["status"] = "no";
            $return["msg"] = $msg . "失败，请稍后重试！";
        }
    }
    return $return;
}

//获取通道类别名称
function GetPayapiclassName($id)
{
    return M("payapiclass")->where("id=" . $id)->getField("classname");
}

//记录一些日志
function AmnLog($filename, $content)
{
    if (!is_dir(C("TESTLOG"))) {
        mkdir(C("TESTLOG"));
    }
    file_put_contents(C("TESTLOG") . "/" . $filename, $content . "\n", FILE_APPEND);
}

//判断控制器是否存在
function class_exists_amn($name)
{
    $class = parse_res_name($name, C('DEFAULT_C_LAYER'));
    if (class_exists($class, flase)) {
        return true;
    } else {
        return false;
    }
}

function regDomain()
{
    $domain = I('domain');
    $pattern1 = '#^((http://)|(https://))?([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}(:\d{0,9})?#';
    $pattern2 = '#(((\d{1,2})|(1\d{1,2})|(2[0-4]\d)|(25[0-5]))\.){3}((\d{1,2})|(1\d{1,2})|(2[0-4]\d)|(25[0-5]))(:\d{0,9})?#';
    if (preg_match($pattern1, $domain, $matches) || preg_match($pattern2, $domain, $matches)) {
        return true;
    }
    return false;
}

/*
 * user：任梦龙
 * 随机产生邀请码
 * 从0-9 a-z A-Z中产生32位随机字符
 * shuffle()打乱数组
 * array_merge()合并数组
 */
function getInviteCode()
{
    $arr_merge = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
    shuffle($arr_merge);
    $arr_code = array_rand($arr_merge, 32);
    $invite = '';
    foreach ($arr_code as $val) {
        $invite .= $arr_merge[$val];
    }
    return $invite;
}

//2019-3-7 任梦龙：去掉生成邀请码的方法


/*
 * user：任梦龙
 * 通过用户id查询对应的用户名
 * $user_id：用户id
 */
function getUserName($user_id)
{
    return D('user')->getFieldById($user_id, 'username');
}

/*
 * 测试发送邮件
 * $conf：邮箱的配置信息
 */
function sendMail($conf)
{
//    Vendor('PHPMailer.PHPMailerAutoload');
//    Vendor('PHPMailer.class#phpmailer');
//    Vendor('PHPMailer.class#pop3');
//    Vendor('PHPMailer.class#smtp');
    require_once 'Vendor/PHPMailer/class.phpmailer.php';
    require_once 'Vendor/PHPMailer/class.pop3.php';
    require_once 'Vendor/PHPMailer/class.smtp.php';
    require_once 'Vendor/PHPMailer/PHPMailerAutoload.php';
    $mail = new \PHPMailer(); //实例化
    $mail->IsSMTP(); // 启用SMTP
    $mail->Host = $conf['smtp']; //smtp服务器的名称（这里以QQ邮箱为例）
    $mail->SMTPSecure = C('MAIL_SECURE');//开启ssl
    $mail->Port = $conf['dk'];//端口号
    $mail->SMTPAuth = C('MAIL_SMTPAUTH'); //启用smtp认证
    $mail->Username = $conf['email']; //发件人邮箱名
    $mail->Password = $conf['em_code']; //qq邮箱发件人授权密码
    $mail->From = $conf['email']; //发件人地址（也就是你的邮箱地址）
    $mail->FromName = $conf['user_name']; //发件人姓名
    $mail->AddAddress($conf['receive_email'], "尊敬的客户");   //收件人邮箱
    $mail->WordWrap = 50; //设置每行字符长度
    $mail->IsHTML(C('MAIL_ISHTML')); // 是否HTML格式邮件
    $mail->CharSet = C('MAIL_CHARSET'); //设置邮件编码
    $mail->Subject = $conf['title']; //邮件主题
    $mail->Body = $conf['content']; //邮件内容
    $mail->AltBody = "这是一个纯文本的身体在非营利的HTML电子邮件客户端"; //邮件正文不支持HTML的备用显示
    return ($mail->Send());
}


//获取ip
function getIp()
{
    if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } else {
            if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
                $ip = getenv("REMOTE_ADDR");
            } else {
                if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
                    $ip = $_SERVER['REMOTE_ADDR'];
                } else {
                    $ip = "unknown";
                }
            }
        }
    }
    return ($ip);
}

//2019-1-8 任梦龙：淘宝接口：根据ip获取所在城市名称
function get_area($ip = '')
{
    if ($ip == '') {
        $ip = getIp();
    }
    $url = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip;
    $ret = https_request($url);
    $arr = json_decode($ret, true);
    return $arr;
}

//2019-1-8 任梦龙：模拟POST请求函数
function https_request($url, $data = null)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    if (!empty($data)) {//如果有数据传入数据
        curl_setopt($curl, CURLOPT_POST, 1);//CURLOPT_POST 模拟post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);//传入数据
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}


//获取随机码
//2019-01-07汪桂芳添加
function random_str($length)
{
    // 生成一个包含 大写英文字母, 小写英文字母, 数字 的数组
    $arr = array_merge(range('a', 'z'), range('A', 'Z'), range(1, 9));
    $str = '';
    $arr_len = count($arr);
    for ($i = 0; $i < $length; $i++) {
        $rand = mt_rand(0, $arr_len - 1);
        $str .= $arr[$rand];
    }
    return $str;
}

/**
 * 生成用于GOOGLE身份验证的密钥
 * 张杨 2019-01-13
 * @return string
 * @throws Exception
 */
function create_secret()
{
    Vendor('GoogleCode.PHPGangsta_GoogleAuthenticator');
    $ga = new \PHPGangsta_GoogleAuthenticator();
    return $ga->createSecret(128);
}

/**
 * 生成给身份验证APP扫码用的二维码
 *
 * 张杨 2019-01-13
 * @param $username  在本系统内的用户名
 * @param $secret    密钥
 * @param $websitename   系统名称
 * @return string   返回一个网址，把这个网站作为<img src=""> img 的 src的值，会在页面上显示二维码
 */
function create_googlecode_qr($username, $secret, $websitename)
{
    Vendor('GoogleCode.PHPGangsta_GoogleAuthenticator');
    $ga = new \PHPGangsta_GoogleAuthenticator();
    $qrCodeUrl = $ga->getQRCodeGoogleUrl($username, $secret, $websitename);
    return $qrCodeUrl;
}

/**
 * 判断输入的验证码是否正确
 * 张杨 2019-01-13
 * @param $secret   密钥
 * @param $onecode   输入的验证码
 * @return bool
 */
function verifcode_googlecode($secret, $onecode)
{
    Vendor('GoogleCode.PHPGangsta_GoogleAuthenticator');
    $ga = new \PHPGangsta_GoogleAuthenticator();
//    echo($ga->getCode($secret));
    $checkResult = $ga->verifyCode($secret, $onecode, 2);    // 2 = 2*30秒 时钟容差
    return $checkResult ? true : false;
}

/**
 * 最近 $num 天的年月日
 * @param int $num
 * @param null $param
 */
function getLatestDate($param = null, $num = 7)
{
    $date = [];
    $i = $num - 1;
    for ($i; $i >= 0; $i--) {
        $date[] = [
            'd' => date('d', strtotime('-' . $i . 'day')),
            'm' => date('m', strtotime('-' . $i . 'day')),
            'y' => date('Y', strtotime('-' . $i . 'day'))
        ];
    }
    if ($param == 'y') {
        //return array_column($date,NULL,'y');
        return array_keys(array_column($date, NULL, 'y'));
    }
    if ($param == 'm') {
        return array_keys(array_column($date, NULL, 'm'));
    }
    if ($param == 'd') {
        return array_keys(array_column($date, NULL, 'd'));
    }
    return $date;
}

//2019-02-18汪桂芳添加
/**
 * curl的post提交
 * @param $url
 * @param $send_data
 * @return mixed
 */
function curlPost($url, $send_data)
{
    $curl = curl_init(); //初始化
    curl_setopt($curl, CURLOPT_URL, $url);  //设置抓取的url
    curl_setopt($curl, CURLOPT_HEADER, 0);  //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);   //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_POST, 1);  //设置post方式提交
    curl_setopt($curl, CURLOPT_POSTFIELDS, $send_data);  //设置post数据
//    curl_setopt($curl, CURLOPT_ENCODING, '');//解决返回的数据乱码问题
    $task_return = curl_exec($curl); //执行命令
    $task_return = mb_convert_encoding($task_return, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');
    curl_close($curl);  //关闭URL请求
    return $task_return;
}

/**
 * 发送定时任务时的签名字符串
 * @param $data
 * @return string
 */
function getTaskSignStr($data)
{
    $keys = [
        "version",
        "merid",
        "createtime",
        "notifyurl",
        "content"
    ];
    sort($keys);
    $str = '';
    foreach ($keys as $k => $parameter) {
        $str .= $parameter . "=" . $data[$parameter] . "&";
    }
    return trim($str, '&');
}


/**
 * 生成html
 */
function setHtml($tjurl, $arraystr, $test = false, $method = "post")
{

    if ($test) {
        $str = '<form id="Form1" name="Form1" method="' . $method . '" action="' . $tjurl . '">';
        foreach ($arraystr as $key => $val) {
            $str = $str . $key . '：' . $val . '<br /><input type="hidden" name="' . $key . '" value="' . $val . '">';
        }
        $str = $str . '<input type="submit" value="确认提交">';
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
 * 2019-03-08汪桂芳:生成唯一的冻结订单号
 * @param $leng
 * @return string
 */
function Createfreezeordernumber($leng = 36)
{
    $freezeordernumber = randpw($leng, 'ALL');  //2019-01-27 大写字母和小写字母和数字
    $num = M("orderfreezemoney")->lock(true)->where("freezeordernumber='" . $freezeordernumber . "'")->count();
    if ($num >= 1) {
        return Createfreezeordernumber($leng);
    } else {
        return $freezeordernumber;
    }

}

/**
 * 2019-03-13汪桂芳:添加黑名单记录
 * @param $user_id 用户或管理员id
 * @param $admin_user 标识用户还是管理员的操作
 * @param $black_type 黑名单类型 1:ip 2:域名 3:手机号 4:身份证号 5:银行卡号
 * @param $black_value 黑名单的值
 * @param $operate_type 操作类型 1:登录 2:用户充值 3:用户结算
 * @param $content 传递的参数值等组成成的json字符串
 */
function addBlackRecord($user_id, $admin_user, $black_type, $black_value, $operate_type, $content)
{
    $data = [
        'user_id' => $user_id,
        'admin_user' => $admin_user,
        'black_type' => $black_type,
        'black_value' => $black_value,
        'operate_type' => $operate_type,
        'content' => $content,
        'date_time' => date('Y-m-d H:i:s')
    ];
    return M('blackrecord')->add($data);
}

function strToInt($str)
{
    if (is_string($str)) {
        return intval($str);
    }
    if (is_array($str)) {
        $arr = [];
        foreach ($str as $key => $value) {
            $arr[$key] = intval($value);
        }
        return $arr;
    }
}

function getClassnameBypayapiid($payapiid)
{
    return \User\Model\PayapiclassModel::getClassnameBypayapiid($payapiid);
}

function getDaifuNameByid($id)
{
    return \Admin\Model\DaifuModel::getDaifuName($id);
}

//2019-4-2 任梦龙：根据商家获取配置文件,现在就只有支付宝和微信,以后有其他的直接在这里添加判断
function getShangjiaConfig($shangjia_name, $find)
{
    if (stristr($shangjia_name, '支付宝')) {
        if (file_exists('Application/Common/Conf/ZFB.php')) {
            //判断是否有数据
            if (C('ZFB_CONFIG')) {
                $find['ziduan'] = C('ZFB_CONFIG');
            }
            if (C('ZFB_EXTRA')) {
                $find['extra'] = C('ZFB_EXTRA');
            }
        }
    } elseif (stristr($shangjia_name, '微信')) {
        if (file_exists('Application/Common/Conf/WX.php')) {
            if (C('WX_CONFIG')) {
                $find['ziduan'] = C('WX_CONFIG');
            }
            if (C('WX_EXTRA')) {
                $find['extra'] = C('WX_EXTRA');
            }
        }
    }
    return $find;
}

function checksettle($userid)
{

    $website = \Admin\Model\WebsiteModel::getWebsite();
    //全局总开关
    if ($website['all_valve'] == 1) {
        return false;
    }
    //结算总开关
    if ($website["settle_valve"] == 0) {
        return false;
    }
    $settleconfig = \User\Model\SettleconfigModel::getSettleConfigInfo($userid);
    //结算运用系统设置
    if ($settleconfig['user_type'] == 0) {
        $settleconfig = \User\Model\SettleconfigModel::getSettleConfigInfo(0);
        //系统设置结算关闭
        if ($settleconfig['status'] == 0) {
            return false;
        }
    } else {
        //结算运用自定义设置
        if ($settleconfig['status'] == 0) {
            return false;
        }
    }
    return true;
}

;

function isMobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;

    //此条摘自TPM智能切换模板引擎，适合TPM开发
    if (isset ($_SERVER['HTTP_CLIENT']) && 'PhoneClient' == $_SERVER['HTTP_CLIENT'])
        return true;
    //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA']))
        //找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
    //判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'
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

/**
 * 利用百度地图接口获取地址
 * @param $ip : 获取的IP
 * @return string
 */
function getAreaByApi($ip)
{
    $bai_url = "http://api.map.baidu.com/location/ip?ip={$ip}&ak=5bG583hMklSqrHKggpYfcXqSByKd8QRO&coor=bd09ll";
    $return = https_request($bai_url);
    if ($return) {
        $return = json_decode($return, true);
        if ($return['status'] == 0) {
            $address = $return['content']['address_detail'];
            return $address['province'] . '-' . $address['city'] . '-' . $address['district'];
        } else {
            return '';
        }
    } else {
        return '';
    }
}