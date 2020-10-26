<?php

namespace Admin\Controller;

use Think\Controller;

class CopyRightController extends CommonController
{
    public function copyRight()
    {
        $this->display();
    }

    //更新日志页面
    public function updateLog()
    {
        $domain = 'http://www.aimanong.com';
        $url = 'http://www.aimanong.com/index.php/home/log/show.html';
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $result = curl_exec($ch);
        if(preg_match('#href="(.*?)"#',$result,$matches)){
            $re = curl_init();
            curl_setopt($re,CURLOPT_URL,$domain.$matches[1]);
            curl_setopt($re,CURLOPT_RETURNTRANSFER,1);
            $res = curl_exec($re);
            if($res){
                $style="<style>$res</style>";
            }
        }
        echo preg_replace('#</html>#',$style."</html>",$result);
    }
}