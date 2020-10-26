<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614 <weibo.com/luofei614>
// +----------------------------------------------------------------------


/**
 * 权限认证类
 * 功能特性：
 * 1，是对规则进行认证，不是对节点进行认证。用户可以把节点当作规则名称实现对节点进行认证。
 *      $auth=new Auth();  $auth->check('规则名称','用户id')
 * 2，可以同时对多条规则进行认证，并设置多条规则的关系（or或者and）
 *      $auth=new Auth();  $auth->check('规则1,规则2','用户id','and')
 *      第三个参数为and时表示，用户需要同时具有规则1和规则2的权限。 当第三个参数为or时，表示用户值需要具备其中一个条件即可。默认为or
 * 3，一个用户可以属于多个用户组(think_auth_group_access表 定义了用户所属用户组)。我们需要设置每个用户组拥有哪些规则(think_auth_group 定义了用户组权限)
 *
 * 4，支持规则表达式。
 *      在think_auth_rule 表中定义一条规则时，如果type为1， condition字段就可以定义规则表达式。 如定义{score}>5  and {score}<100  表示用户的分数在5-100之间时这条规则才会通过。
 */

//数据库
/*
-- ----------------------------
-- think_auth_rule，规则表，
-- id:主键，name：规则唯一标识, title：规则中文名称 status 状态：为1正常，为0禁用，condition：规则表达式，为空表示存在就验证，不为空表示按照条件验证
-- ----------------------------
DROP TABLE IF EXISTS `think_auth_rule`;
CREATE TABLE `think_auth_rule` (
`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
`name` char(80) NOT NULL DEFAULT '',
`title` char(20) NOT NULL DEFAULT '',
`type` tinyint(1) NOT NULL DEFAULT '1',
`status` tinyint(1) NOT NULL DEFAULT '1',
`condition` char(100) NOT NULL DEFAULT '',  # 规则附件条件,满足附加条件的规则,才认为是有效的规则
PRIMARY KEY (`id`),
UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
-- ----------------------------
-- think_auth_group 用户组表，
-- id：主键， auth_name:用户组中文名称， rule_id：用户组拥有的规则id， 多个规则","隔开，status 状态：为1正常，为0禁用
-- ----------------------------
DROP TABLE IF EXISTS `think_auth_group`;
CREATE TABLE `think_auth_group` (
`id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
`title` char(100) NOT NULL DEFAULT '',
`status` tinyint(1) NOT NULL DEFAULT '1',
`rules` char(80) NOT NULL DEFAULT '',
PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
-- ----------------------------
-- think_auth_group_access 用户组明细表
-- uid:用户id，group_id：用户组id
-- ----------------------------
DROP TABLE IF EXISTS `think_auth_group_access`;
CREATE TABLE `think_auth_group_access` (
`uid` mediumint(8) unsigned NOT NULL,
`group_id` mediumint(8) unsigned NOT NULL,
UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
KEY `uid` (`uid`),
KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 */

//2019-1-16 任梦龙：新建用户权限类文件
class UserAuth
{

    //默认配置
    protected $_config = array(
        'AUTH_ON' => true, // 认证开关
        'AUTH_TYPE' => 1, // 认证方式，1为实时认证；2为登录认证。
        'AUTH_GROUP' => 'userauthgroup', // 用户组数据表名
        'AUTH_GROUP_ACCESS' => 'userauthgroupaccess', // 用户-用户组关系表
        'AUTH_RULE' => 'userauthrule', // 权限规则表
        'AUTH_USER' => 'childuser', // 用户信息表
    );

    public function __construct()
    {
        $prefix = C('DB_PREFIX');
        $this->_config['AUTH_GROUP'] = $prefix . $this->_config['AUTH_GROUP'];
        $this->_config['AUTH_RULE'] = $prefix . $this->_config['AUTH_RULE'];
        $this->_config['AUTH_USER'] = $prefix . $this->_config['AUTH_USER'];
        $this->_config['AUTH_GROUP_ACCESS'] = $prefix . $this->_config['AUTH_GROUP_ACCESS'];
        if (C('AUTH_CONFIG')) {
            //可设置配置项 AUTH_CONFIG, 此配置项为数组。
            $this->_config = array_merge($this->_config, C('AUTH_CONFIG'));
        }
    }

    /**
     * 检查权限
     * @param $user_id ：对应的用户id
     * @param name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param uid  int           认证用户的id
     * @param string mode        执行check的模式
     * @param relation string    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @return boolean           通过验证返回true;失败返回false
     */
    public function check($user_id, $name, $uid, $type = 1, $mode = 'url', $relation = 'or')
    {
        //如果没设开关，则直接返回
        if (!$this->_config['AUTH_ON']) {
            return true;
        }

        $authList = $this->getAuthList($user_id, $uid, $type); //获取用户需要验证的所有有效规则列表
        //将当前的url全部转为小写，并转换为数组，方便与 $authList 对比
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $list = array(); //保存验证通过的规则名
        if ('url' == $mode) {
            $REQUEST = unserialize(strtolower(serialize($_REQUEST)));  //当前的地址：例如：Array ( [/user/userinfo/loginrecordlist_html] => )
        }
        foreach ($authList as $auth) {
            $query = preg_replace('/^.+\?/U', '', $auth);
            if ('url' == $mode && $query != $auth) {
                parse_str($query, $param); //解析规则中的param
                $intersect = array_intersect_assoc($REQUEST, $param);
                $auth = preg_replace('/\?.*$/U', '', $auth);
                if (in_array($auth, $name) && $intersect == $param) {
                    //如果节点相符且url参数满足
                    $list[] = $auth;
                }
            } else if (in_array($auth, $name)) {
                $list[] = $auth;
            }
        }
        if ('or' == $relation and !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ('and' == $relation and empty($diff)) {
            return true;
        }
        return false;
    }

    /**
     * 根据用户id获取用户组,返回值为数组
     * $user_id  用户id
     * @param  uid int     用户的菜单id
     * @return array       用户所属的用户组 array(
     *     array('uid'=>'用户id','group_id'=>'用户组id','auth_name'=>'用户组名称','rule_id'=>'用户组拥有的规则id,多个,号隔开'),
     *     ...)
     */
    public function getGroups($user_id, $uid)
    {
        static $groups = array();
        if (isset($groups[$uid])) {
            return $groups[$uid];
        }
        $where = "a.cid='$uid' and g.status='1' and a.user_id='$user_id' and g.del=0";
        $user_groups = M()
            ->table($this->_config['AUTH_GROUP_ACCESS'] . ' a')
            ->where($where)
            ->join($this->_config['AUTH_GROUP'] . " g on a.group_id=g.id")
            ->field('cid,group_id,auth_name,rule_id')->select();
        $groups[$uid] = $user_groups ?: array();
        return $groups[$uid];
    }

    /**
     * 获得权限列表
     * $user_id  用户id
     * @param integer $uid 用户id
     * @param integer $type
     */
    protected function getAuthList($user_id, $uid, $type)
    {
        static $_authList = array(); //保存用户验证通过的权限列表
        $t = implode(',', (array)$type);

        if (isset($_authList[$uid . $t])) {
            return $_authList[$uid . $t];
        }

        if (2 == $this->_config['AUTH_TYPE'] && isset($_SESSION['_AUTH_LIST_' . $uid . $t])) {
            return $_SESSION['_AUTH_LIST_' . $uid . $t];
        }

        //读取用户所属用户组
        $groups = $this->getGroups($user_id, $uid);
        $ids = array(); //保存用户所属用户组设置的所有权限规则id
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rule_id'], ',')));
        }
        $ids = array_unique($ids);  //去重
        if (empty($ids)) {
            $_authList[$uid . $t] = array();
            return array();
        }

        $map = array(
            'id' => array('in', $ids),
//            'type'   => $type,
            //2019-4-19 rml：去掉状态
//            'status' => 1,
//            'del' => 0,
        );
        //读取用户组所有权限规则
        $rules = M()->table($this->_config['AUTH_RULE'])->where($map)->field('menu_url')->select();

        //循环规则，判断结果。
        $authList = array();
        foreach ($rules as $rule) {
            //只要url存在就记录
            if($rule['menu_url']){
                $authList[] = strtolower($rule['menu_url']);
            }
        }

        $_authList[$uid . $t] = $authList;

        if (2 == $this->_config['AUTH_TYPE']) {
            //规则列表结果保存到session
            $_SESSION['_AUTH_LIST_' . $uid . $t] = $authList;
        }
        return array_unique($authList);

    }

    /**
     * 获得用户资料,根据自己的情况读取数据库
     */
    protected function getUserInfo($uid)
    {
        static $userinfo = array();
        if (!isset($userinfo[$uid])) {
            $userinfo[$uid] = M()->where(array('uid' => $uid))->table($this->_config['AUTH_USER'])->find();
        }
        return $userinfo[$uid];
    }

}
