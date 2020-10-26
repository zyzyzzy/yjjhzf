<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/1/24/024
 * Time: 下午 7:57
 */

/**
 * 系统配置信息
 * environmental check
 */
function env_check()
{
    $env_items[] = array('name' => '操作系统', 'min' => '无限制', 'good' => 'linux', 'cur' => PHP_OS, 'status' => 1);
    $env_items[] = array('name' => 'PHP版本', 'min' => '5.3', 'good' => '5.3', 'cur' => PHP_VERSION, 'status' => (PHP_VERSION < 5.3 ? 0 : 1));
    $tmp = function_exists('gd_info') ? gd_info() : array();
    preg_match("/[\d.]+/", $tmp['GD Version'], $match);
    unset($tmp);
    $env_items[] = array('name' => 'GD库', 'min' => '2.0', 'good' => '2.0', 'cur' => $match[0], 'status' => ($match[0] < 2 ? 0 : 1));
    //ini_get: 获取一个配置选项的值
    $env_items[] = array('name' => '附件上传', 'min' => '未限制', 'good' => '2M', 'cur' => ini_get('upload_max_filesize'), 'status' => 1);
    //disk_free_space — 返回目录中的可用空间
    $disk_place = function_exists('disk_free_space') ? floor(disk_free_space(ROOT_PATH) / (1024 * 1024)) : 0;
    $env_items[] = array('name' => '磁盘空间', 'min' => '100M', 'good' => '>100M', 'cur' => empty($disk_place) ? '未知' : $disk_place . 'M', 'status' => $disk_place < 100 ? 0 : 1);
    return $env_items;
}

/**
 * 文件权限检查
 * file check
 */
function dirfile_check()
{
    $dirfile_items = array(
        array('type' => 'dir', 'path' => 'Application/Common/Conf'),
//        array('type' => 'dir', 'path' => 'install'),
    );
    foreach ($dirfile_items as $key => $item) {
        $item_path = '/' . $item['path'];
        if ($item['type'] == 'dir') {
            if (!dir_writeable(ROOT_PATH . $item_path)) {
                if (is_dir(ROOT_PATH . $item_path)) {
                    $dirfile_items[$key]['status'] = 0;
                    $dirfile_items[$key]['current'] = '+r';
                } else {
                    $dirfile_items[$key]['status'] = -1;
                    $dirfile_items[$key]['current'] = 'nodir';
                }
            } else {
                $dirfile_items[$key]['status'] = 1;
                $dirfile_items[$key]['current'] = '+r+w';
            }
        } else {
            if (file_exists(ROOT_PATH . $item_path)) {
                if (is_writable(ROOT_PATH . $item_path)) {
                    $dirfile_items[$key]['status'] = 1;
                    $dirfile_items[$key]['current'] = '+r+w';
                } else {
                    $dirfile_items[$key]['status'] = 0;
                    $dirfile_items[$key]['current'] = '+r';
                }
            } else {
                if ($fp = @fopen(ROOT_PATH . $item_path, 'wb+')) {
                    $dirfile_items[$key]['status'] = 1;
                    $dirfile_items[$key]['current'] = '+r+w';
                    @fclose($fp);
                    @unlink(ROOT_PATH . $item_path);
                } else {
                    $dirfile_items[$key]['status'] = -1;
                    $dirfile_items[$key]['current'] = 'nofile';
                }
            }
        }
    }
}

/**
 * 函数检查
 * function is exist
 */
function function_check()
{
    $func_items = array(
        array('name' => 'mysql_connect'),
        array('name' => 'fsockopen'),
        array('name' => 'gethostbyname'),
        array('name' => 'file_get_contents'),
        array('name' => 'mb_convert_encoding'),
        array('name' => 'json_encode'),
        array('name' => 'curl_init'),
    );
    foreach ($func_items as $key => $item) {
        $func_items[$key]['status'] = function_exists($item['name']) ? 1 : 0;
    }
    return $func_items;
}


/**
 * dir is writeable
 * @return number
 */
function dir_writeable($dir)
{
    $writeable = 0;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755);
    } else {
        @chmod($dir, 0755);
    }
    if (is_dir($dir)) {
        if ($fp = @fopen("$dir/test.txt", 'w')) {
            @fclose($fp);
            @unlink("$dir/test.txt");
            $writeable = 1;
        } else {
            $writeable = 0;
        }
    }
    return $writeable;
}

/**
 * 将数据库配置信息写入文件
 */
function writeConfig($info)
{
    //extract — 从数组中将变量导入到当前的符号表   此函数会将键名当作变量名，值作为变量的值  EXTR_SKIP:如果有冲突，不覆盖已有的变量。
    extract($GLOBALS, EXTR_SKIP);
    $config = 'Application/Common/Conf/database_test.php';
    $configfile = @file_get_contents($config);
    $configfile = trim($configfile);
    $configfile = substr($configfile, -2) == '?>' ? substr($configfile, 0, -2) : $configfile;
    $charset = 'UTF-8';
    $db_host = $info['db_host'];
    $db_port = $info['db_port'];
    $db_user = $info['db_user'];
    $db_pwd = $info['db_pwd'];
    $db_name = $info['db_name'];
    $db_prefix = $info['db_prefix'];
    $db_type = 'mysql';
    $cookie_pre = strtoupper(substr(md5(random(6) . substr($_SERVER['HTTP_USER_AGENT'] . md5($_SERVER['SERVER_ADDR'] . $db_host . $db_user . $db_pwd . $db_name . substr(time(), 0, 6)), 8, 6) . random(5)), 0, 4)) . '_';
//        $configfile = str_replace("===url===", $url, $configfile);
    $configfile = str_replace("===db_prefix===", $db_prefix, $configfile);
    $configfile = str_replace("===db_host===", $db_host, $configfile);
    $configfile = str_replace("===db_user===", $db_user, $configfile);
    $configfile = str_replace("===db_pwd===", $db_pwd, $configfile);
    $configfile = str_replace("===db_name===", $db_name, $configfile);
    $configfile = str_replace("===db_port===", $db_port, $configfile);
    @file_put_contents('Application/Common/Conf/database_new.php', $configfile);
}

//make rand
function random($length, $numeric = 0)
{
    $seed = base_convert(md5(print_r($_SERVER, 1) . microtime()), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    $hash = '';
    $max = strlen($seed) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $seed[mt_rand(0, $max)];
    }
    return $hash;
}

/**
 * 生成表文件
 */
function runquery($sql, $db_prefix, $conn)
{
//  global $lang, $tablepre, $db;
    if (!isset($sql) || empty($sql)){
        return false;
    }

    $sql = str_replace("\r", "\n", str_replace('pay_', $db_prefix, $sql));  //将前缀转换为自己定义的前缀
    $ret = array();  //得到每一个创建表的sql语句
    $num = 0;
    foreach (explode(";\n", trim($sql)) as $query) {
        $ret[$num] = '';
        $queries = explode("\n", trim($query));
        foreach ($queries as $query) {
            $ret[$num] .= (isset($query[0]) && $query[0] == '#') || (isset($query[1]) && isset($query[1]) && $query[0] . $query[1] == '--') ? '' : $query;
        }
        $num++;
    }
    unset($sql);
    foreach ($ret as $query) {
        $query = trim($query);
        if ($query) {
            if (substr($query, 0, 12) == 'CREATE TABLE') {
                $line = explode('`', $query);
                $data_name = $line[1];
//                    echo $data_name.'<br/>';
//                    showjsmessage('数据表  ' . $data_name . ' ... 创建成功');
                $conn->query(droptable($data_name));
                $conn->query($query);
                unset($line, $data_name);
            } else {
                $conn->query($query);
            }
        }
    }
    return true;
}

//抛出JS信息
function showjsmessage($message) {
    echo '<script type="text/javascript">showmessage(\''.addslashes($message).' \');</script>'."\r\n";
    flush();
    ob_flush();
}

/**
 * drop table
 */
function droptable($table_name)
{
    return "DROP TABLE IF EXISTS `" . $table_name . "`;";
}