<?php

namespace Unfreeze\Model;

use Think\Model;
//2019-02-22汪桂芳创建
class OrderUnfreezetaskModel extends Model
{
    //添加请求解冻的记录
    public static function addUnfreezeTask($freezeordernumber,$can_unfreeze,$remarks)
    {
        $data = [
            'freezeordernumber'=>$freezeordernumber,
            'date_time'=>date('Y-m-d H:i:s'),
            'can_unfreeze'=>$can_unfreeze,
            'remarks'=>$remarks
        ];
        return M('orderunfreezetask')->lock(true)->add($data);
    }
}