<?php
/**
 * 用户菜单模型层
 */

namespace User\Model;

use Think\Model;

class UserauthruleModel extends Model
{
    public static function getMenu($pid)
    {
        $parents = static::getChilds($pid);
        $arr = [];
        if(count($parents)>0){
            foreach($parents as $key=>$parent){
                $arr[$key]=$parent;
                if($parent['level']<2){
                    $childs = static::getMenu($parent['id']);
                    if(count($childs)>0){
                        $arr[$key]['childs']=static::getMenu($parent['id']);
                    }
                }
            }
        }
        return $arr;
    }

    public static function getMenuIds($pid)
    {
        $parents = static::getChilds($pid);
        $arr = [];
        if(count($parents)>0){
            foreach($parents as $key=>$parent){
                $arr[]=$parent['id'];
                if($parent['level']<2){
                    $childs = static::getMenuIds($parent['id']);
                    if(count($childs)>0){
                        $arr=array_merge($arr,static::getMenuIds($parent['id']));
                    }
                }
            }
        }
        return $arr;
    }

    public static function getChilds($pid)
    {
        return D('userauthrule')->where([
            'pid'=>['eq',$pid]
        ])->select();
    }

}