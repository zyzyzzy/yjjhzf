<?php
namespace Think\Session\Driver;

use Predis\Client;
use think\Exception;

class Redis
{
    /** @var \Redis */
    protected $handler = null;
    protected $config = [
        // redis主机
        'host' => '127.0.0.1',
        // redis端口
        'port' => 6379,
        // 密码
        'password' => '',
        // 操作库
        'select' => 1,
        // 有效期(秒)
        'expire' => 10,
        // 超时时间(秒)
        'timeout' => 0,
        // 是否长连接
        'persistent' => true,
        // sessionkey前缀
        'session_name' => 'session_amnpay_',
    ];

    public function __construct($config = [])
    {
      /*  $this->config['host'] = C("SESSION_REDIS_HOST") ? C("SESSION_REDIS_HOST") : $this->config['host'];
        $this->config['port'] = C("SESSION_REDIS_POST") ? C("SESSION_REDIS_POST") : $this->config['port'];
        $this->config['password'] = C("SESSION_REDIS_AUTH") ? C("SESSION_REDIS_AUTH") : $this->config['password'];
        $this->config['select'] = C("SESSION_REDIS_SELECT") ? C("SESSION_REDIS_SELECT") : $this->config['select'];
        $this->config['expire'] = C("SESSION_REDIS_EXPIRE") ? C("SESSION_REDIS_EXPIRE") : $this->config['expire'];
        $this->config['session_name'] = C('SESSION_PREFIX') ? C('SESSION_PREFIX') : $this->config['session_name'];
        $this->config['timeout'] = C('SESSION_CACHE_TIMEOUT') ? C('SESSION_CACHE_TIMEOUT') : $this->config['timeout'];*/
        $this->config['session_name'] = C('SESSION_PREFIX') ? C('SESSION_PREFIX') : $this->config['session_name'];
        $this->config['expire'] = C("SESSION_REDIS_EXPIRE") ? C("SESSION_REDIS_EXPIRE") : $this->config['expire'];
        $this->config['host'] = C("SESSION_REDIS_HOST") ? C("SESSION_REDIS_HOST") : $this->config['host'];
        $this->config['port'] = C("SESSION_REDIS_POST") ? C("SESSION_REDIS_POST") : $this->config['port'];
        $this->config['password'] = C("SESSION_REDIS_AUTH") ? C("SESSION_REDIS_AUTH") : $this->config['password'];
    }

    /**
     * 打开Session
     * @access public
     * @param string $savePath
     * @param mixed $sessName
     * @return bool
     * @throws Exception
     */
    public function open($savePath, $sessName)
    {
        // 检测php环境
//        if (!extension_loaded('redis')) {
//            throw new Exception('not support:redis');
//        }
//        $this->handler = new \Redis;
        $this->handler = new Client([
           'host' => $this->config['host'],
            'port' => $this->config['port']
        ]);
        if ('' != $this->config['password']) {
            $this->handler->auth($this->config['password']);
        }
        // 建立连接
//        $func = $this->config['persistent'] ? 'pconnect' : 'connect';
//        $this->handler->$func($this->config['host'], $this->config['port'], $this->config['timeout']);
//        if ('' != $this->config['password']) {
//            $this->handler->auth($this->config['password']);
//        }
//        if (0 != $this->config['select']) {
//            $this->handler->select($this->config['select']);
//        }
        return true;
    }

    /**
     * 关闭Session
     * @access public
     */
    public function close()
    {
        $this->gc(ini_get('session.gc_maxlifetime'));
        $this->handler->close();
        $this->handler = null;
        return true;
    }

    /**
     * 读取Session
     * @access public
     * @param string $sessID
     * @return string
     */
    public function read($sessID)
    {
        return (string)$this->handler->get($this->config['session_name'] . $sessID);
    }

    /**
     * 写入Session
     * @access public
     * @param string $sessID
     * @param String $sessData
     * @return bool
     */
    public function write($sessID, $sessData)
    {

        if ($this->config['expire'] > 0) {
            $this->handler->set($this->config['session_name'] . $sessID, $sessData);
           return $this->handler->expire($this->config['session_name'] . $sessID, $this->config['expire']);
           // return $this->handler->setex($this->config['session_name'] . $sessID, $this->config['expire'], $sessData);
        } else {
            return $this->handler->set($this->config['session_name'] . $sessID, $sessData);
        }
    }

    /**
     * 删除Session
     * @access public
     * @param string $sessID
     * @return bool
     */
    public function destroy($sessID)
    {
        return $this->handler->del($this->config['session_name'] . $sessID) > 0;
    }

    /**
     * Session 垃圾回收
     * @access public
     * @param string $sessMaxLifeTime
     * @return bool
     */
    public function gc($sessMaxLifeTime)
    {
        return true;
    }
}