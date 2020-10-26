<?php
/**
 * Created by PhpStorm.
 * User: 汪桂芳
 * Date: 2018/12/27
 * Time: 下午10:56
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
<!--                    2019-1-22 任梦龙：修改左边菜单栏样式-->
<!--                    <i class="iconfont">&#xe6a7;</i>-->
                    <cite style="margin-left: 35px;"><?php echo($key); ?></cite>
                </a>
            </li>
            <?php
        }
    }
}

/**
 * 2019-1-15 任梦龙：添加
 * description: 递归菜单
 * @author: 任梦龙
 * @param unknown $array
 * @param number $fid
 * @param number $level
 * @param number $type 1:顺序菜单 2树状菜单
 * @return multitype:number
 */
function get_column($array, $type = 1, $fid = 0, $level = 0) {
    $column = [];
    if ($type == 2)
        foreach ($array as $key => $vo) {
            if ($vo['pid'] == $fid) {
                $vo['level'] = $level;
                $column[$key] = $vo;
                $column [$key][$vo['id']] = get_column($array, $type = 2, $vo['id'], $level + 1);
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







