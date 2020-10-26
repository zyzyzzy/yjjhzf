<?php
namespace Workerman\Controller;
use Think\Controller;
use Workerman\Worker;
class WorkermanController extends Controller{

    /**
     * 构造函数
     * @access public
     */
    public function __construct(){

    }

    public function index(){
        if(!IS_CLI){
            die("无法直接访问，请通过命令行启动");
        }
        $http_worker = new \Workerman\Worker('http://192.168.0.119:2345');
        // 启动4个进程对外提供服务
        $http_worker->count = 5;
        $http_worker->onWorkerStart = function($worker)
        {
            // 只在id编号为0的进程上设置定时器，其它1、2、3号进程不设置定时器

            if($worker->id === 5)
            {
                \Workerman\Lib\Timer::add(1, function(){
                   //解冻操作
                });
            }

            if($worker->id != 0 and  $worker->id != 1 or $worker->id != 2)
            {

                \Workerman\Lib\Timer::add(5, function(){
                   // $str = M("test")->select();
                   echo rand(1,5)."<br>";
                });
            }

            if($worker->id === 0 or $worker->id === 1 or $worker->id === 2)
            {

                \Workerman\Lib\Timer::add(5, function(){
                    // M("test")->add(array("abc"=>"dddd","aaa"=>"aaaa"));

                });
            }
        };

// 接收到浏览器发送的数据时回复hello world给浏览器
        $http_worker->onMessage = function($connection, $data)
        {
            // 向浏览器发送hello world
            dump($data);
            $str = M("order")->where("id=19")->getField("sysordernumber");
            $connection->send('hello world________'.$data["get"]["a"]."----------".$str);
        };

// 运行worker
        Worker::runAll();

    }
}
