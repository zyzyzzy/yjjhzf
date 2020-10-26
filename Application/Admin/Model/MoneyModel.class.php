<?php
namespace Admin\Model;

use Think\Model\RelationModel;

class MoneyModel extends RelationModel
{
    protected $_link =[
        "user" => [
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'user',
            'foreign_key' => 'userid'
        ],
        "payapi" => [
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'payapi',
            'foreign_key' => 'payapiid'
        ],
        "payapiaccount" => [
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'payapiaccount',
            'mapping_fields' => [
                'id','payapishangjiaid','moneytypeclassid','bieming','memberid',
                'account','status','cbfeilv','feilv','money'
            ],
            'foreign_key' => 'payapiaccountid'
        ],

    ];

    public static function getUserMoney($userid)
    {
        return D('money')->relation(true)->where("userid=".$userid)->select();
    }

    public static function getMoneys($userid,$payapiid,$accountid,$page=1,$limit=10, $payapiclassid)
    {
        $where ='';
        if($userid){
            $where.="userid='".$userid."'";
        }
        if($payapiclassid!="" && $payapiid==""){
            if($where){
                $where.=" and ";
            }
//            if($userid){
            $payapiIds = PayapiclassModel::getUserPayapiIds($userid,$payapiclassid);
//            }else{
//                $payapiIds = PayapiclassModel::getPayapiIds($payapiclassid);
//            }

//            print_r($payapiIds);die;
            if(count($payapiIds)>1){
                $where.="payapiid in (".implode(',',$payapiIds).") ";
            }
            if(count($payapiIds)==1){
                $where.="payapiid='".implode(',',$payapiIds)."' ";
            }
        }
        if($payapiid){
            if($where){
                $where.=" and ";
            }
            $where.="payapiid='".$payapiid."'";
        }

        if($accountid){
            if($where){
                $where.=" and ";
            }
            $where.="payapiaccountid='".$accountid."'";
        }

        return D('money')->relation(true)->where($where)->order('money')->page($page,$limit)->select();
    }

    public static function getMoneyChanges($id,$page=1,$limit=10)
    {
        $money = D('money')->where("id=".$id)->find();
        $where['userid'] = $money['userid'];
        $where['accountid'] = $money['payapiaccountid'];
        $where['payapiid'] = $money['payapiid'];
        if(I('transid')){
            $where['transid'] = ['like',"%".I('transid')."%"];
        }
        if(I('changetype')){
            $where['changetype'] = I('changetype');
        }
        if(I('start')){
            $where['datetime'] = ['egt',I('start')];
        }
        if(I('end')){
            $where['datetime'] = ['elt',I('end')];
        }
        $data = M('moneychange')->where($where)->page($page,$limit)->select();
        foreach ($data as $k=>$v){
            $changetypes = C('MONEY_CHANGE_TYPE');
            $data[$k]['changetype'] = $changetypes[$v['changetype']];
            if($v['tcuserid']){
                $data[$k]['tcusername'] = M('user')->where('id='.$v['tcuserid'])->getField('username');
            }else{
                $data[$k]['tcusername'] = '';
            }

        }
        return $data;
    }

    public static function getMoney($id)
    {
        return D('money')->relation(true)->where("id=".$id)->find();
    }

    public function addMoney($id,$add_money)
    {
        $money = D('money')->where("id=".$id)->find();
        if(!$money){
            return false;
        }
        return D('money')->where("id=".$id)->setField("money",$add_money+$money['money']);
    }

    public static function addFreezemoney($id,$add_freezemoney)
    {
        $money = D('money')->where("id=".$id)->find();
        if(!$money){
            return false;
        }
        return D('money')->where("id=".$id)->setField([
            "freezemoney"=>$add_freezemoney+$money['freezemoney'],
            'money'=>$money['money']-$add_freezemoney
        ]);
    }

    public function cutMoney($id,$cut_money)
    {
        $money = D('money')->where("id=".$id)->find();
        if(!$money){
            return false;
        }
        return D('money')->where("id=".$id)->setField("money",$money['money']-$cut_money);
    }

    public static function cutFreezemoney($id,$cut_freezemoney)
    {
        $money = D('money')->where("id=".$id)->find();
        if(!$money){
            return false;
        }
        return D('money')->where("id=".$id)->setField([
            "freezemoney"=>$money['freezemoney']-$cut_freezemoney,
            "money" => $money['money'] + $cut_freezemoney
        ]);
    }

    public static function addMoneyChange($data){
        return M('moneychange')->add($data);
    }

    public static function getUserMoneyInfo($userid){
        return M('usermoney')->where('userid='.$userid)->find();
    }

    public static function changeUserMoney($userid,$data){
        return M('usermoney')->where('userid='.$userid)->save($data);
    }
}