<?php
namespace Pay\Model;

use Think\Model;

class AdvjumpdataModel extends Model
{
    //生成标识码
    public static function getCode()
    {
        $code = randpw(20,'ALL');
        if(M('advjumpdata')->where('code="'.$code.'"')->getField('id')){
            return self::getCode();
        }
        return $code;
    }

    //查询数据
    public static function getAdvInfo($id)
    {
        return M('advjumpdata')->where('id='.$id)->find();
    }

    //添加数据
    public static function addAdvInfo($data)
    {
        return M('advjumpdata')->add($data);
    }

    //查询code
    public static function getCodeById($id)
    {
        return M('advjumpdata')->where('id='.$id)->getField('code');
    }

    //通过code查询数据
    public static function getInfoByCode($code)
    {
        return M('advjumpdata')->where('code="'.$code.'"')->find();
    }
}