<?php

namespace Version\Controller;

use Think\Controller;

class IndexDaifuController extends Controller {

      public  function  __construct()
      {
      }

    /**
     * 结算通道版本入口
     * @param $parameter
     * @param $checktypename
     * @param $returnJson
     * @return bool
     */
      public function index($parameter,&$returnJson){

          //2019-08-15 张杨
          //2019-08-15 张杨  为了防止参数类型不为数组或参数不存在照成程序代码报错加了一个判断
          if(!is_array($parameter) || !array_key_exists('version',$parameter) || !is_string($parameter['version'])){
              $returnJson["msg"] = "参数 version 错误1";
              return false;
          }

          $version = M("settleversion")->where(['numberstr' => $parameter["version"], 'del' => 0])->find();
          if (!$version) {
              $returnJson["msg"] = "参数 version 错误2";
              return false;
          }

          //判断结算接口版本的状态
          if ($version['status'] != 1) {
              $returnJson["msg"] = "结算接口版本[" . $version['numberstr'] . "]被禁用,请联系管理员处理";
              return false;
          }
          $obj = A("Version/" . $version['actionname']);
          if (!$obj) {
              $returnJson["msg"] = "控制器 Version/" . $version['actionname'] . " 不存在";
              return false;
          } else {
              return $obj->index($parameter, $returnJson, $obj);
          }
          //2019-08-15 张杨



//
//          if(!$actionname = M("settleversion")->where("numberstr='".$parameter["version"]."'")->getField("actionname")){
//              $returnJson["msg"] = "version 参数错误";
//              return false;
//          }
//
//          if($obj = A("Version/".$actionname)){
//              return $obj->index($parameter,$returnJson,$obj);
//          }else{
//              $returnJson["msg"] = "控制器 Version/".$actionname." 不存在";
//              return false;
//          }

      }

}
