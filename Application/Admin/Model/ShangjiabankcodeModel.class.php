<?php

namespace Admin\Model;

use Think\Model;

class ShangjiabankcodeModel extends Model{

    /*
     * 通过systembank_id和shangjia_id获取商家银行编码信息
     */
    public static function getShangjiaBankInfo($systembank_id,$shangjia_id){
        $where = [
            'systembank_id'=>$systembank_id,
            'shangjia_id'=>$shangjia_id
        ];
        return M('shangjiabankcode')->where($where)->find();
    }

    /*
     * 通过systembank_id和shangjia_id修改商家银行编码信息
     */
    public static function editShangjiaBankInfo($systembank_id,$shangjia_id,$data){
        $where = [
            'systembank_id'=>$systembank_id,
            'shangjia_id'=>$shangjia_id
        ];
        return M('shangjiabankcode')->where($where)->save($data);
    }

    /**
     * 添加记录
     */
    public static function addShangjiaBank($data){
        return M('shangjiabankcode')->add($data);
    }

}

