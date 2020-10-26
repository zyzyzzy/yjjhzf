<?php

namespace Payaccount\Model;

use Think\Model;

class PayapiaccountModel extends Model{

    protected $_scope = array(
        // 命名范围normal
        'default_scope'=>array(
            'field'=> "id,memberid,feilv,cbfeilv,account,moneytypeclassid,status,md5keystr,publickeystr,privatekeystr,sys_publickeystr,sys_privatekeystr,payapishangjiaid",
        ),
        'status' =>array(
            'field' => 'status'
        )

    );



}

