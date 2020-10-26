<?php

/**

 * Created by PhpStorm.

 * User: zhangyang

 * Date: 2018/7/9

 * Time: 下午5:16

 */

//后台菜单选项类

//$menujson  json菜单样式

function admin_menu($menujson)
{

    //循环获取加载json菜单选项单

    foreach ($menujson as $key => $val) {

        //icon  矢量图标格式

        if (isset($val["icon"])) {
            ?>

            <li>

                <a href="javascript:;">

                    <i class="layui-icon"><?php echo($val["icon"]); ?></i>

                    <cite><?php echo($key); ?></cite>

                    <i class="iconfont nav_right">&#xe697;</i>

                </a>

                <ul class="sub-menu">

                    <?php

                    admin_menu($val["menu"])

                    ?>

                </ul>

            </li>

            <?php
        } else {
            ?>

            <li>

                <a _href="<?php echo(U($val)); ?>">

                    <cite style="margin-left: 35px;"><?php echo($key); ?></cite>

                </a>

            </li>

            <?php
        }
    }
}



//引用支付类json 方法

//$id  商家id

function payapishangjiaclassjson($id)
{
    $shangjiaclass = M("payapishangjiapayapiclass");

    $classlist = $shangjiaclass->where("payapishangjiaid=" . $id)->field("payapiclassid,status")->select();

    M("payapishangjia")->where("id=" . $id)->setField("classjson", json_encode($classlist, JSON_FORCE_OBJECT));
}



function showpayapioption($payapiclassid)
{
    $where = array(

        'payapiclassid' => $payapiclassid,

        'status' => 1,

        'del'=>0  //2019-3-26 任梦龙：增加删除状态的条件

    );

    $list = M("payapi")->where($where)->order("payapishangjiaid desc")->select();

    $reutrnstr = "";

    foreach ($list as $k) {
        $reutrnstr .= '<option value="' . $k["id"] . '">' . huoqushangjianame($k["payapishangjiaid"]) . '--' . $k["zh_payname"] . '</option>';
    }

    return $reutrnstr;
}



function huoqushangjianame($id)
{
    return M("payapishangjia")->where("id=" . $id)->getField("shangjianame");
}



function HuoQuPayapiMoney($id, $payapiid)
{

    // $id = I("request.id","");

    // $payapiid = I("request.payapiid","");

    $money = M("tongdaozhanghao")->where("payapiid=" . $payapiid . " and payapiaccountid = " . $id)->getField("money");

    return $money;
}



function checkboxcheck($val)
{
    if ($val == 0) {
        return "checked";
    } else {
        return "";
    }
}



function checkboxOne($val)
{
    if ($val == 1) {
        return "checked";
    } else {
        return "";
    }
}



/*

 * 获取单条信息

 * $table_name

 * $id

 */

function getOneInfo($table_name, $id)
{
    return M($table_name)->where('id=' . $id)->find();
}



/**

 * description: 递归菜单

 * @author: 任梦龙

 * @param unknown $array

 * @param number $fid

 * @param number $level

 * @param number $type 1:顺序菜单 2树状菜单

 * @return multitype:number

 */

function get_column($array, $type = 1, $fid = 0, $level = 0)
{
    $column = [];

    if ($type == 2) {
        foreach ($array as $key => $vo) {
            if ($vo['pid'] == $fid) {
                $vo['level'] = $level;

                $column[$key] = $vo;

                $column [$key][$vo['id']] = get_column($array, $type = 2, $vo['id'], $level + 1);
            }
        }
    } else {
        foreach ($array as $key => $vo) {
            if ($vo['pid'] == $fid) {
                $vo['level'] = $level;

                $column[] = $vo;

                $column = array_merge($column, get_column($array, $type = 1, $vo['id'], $level + 1));
            }
        }
    }

    return $column;
}
