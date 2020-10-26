//2019-1-14 任梦龙：添加系统管理员回收站js
var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#recoveryAdminUser'
        , url: $("#recoveryAdminUser").attr("dataurl")
        //,cellMinWidth: 80
        , cols: [[
            {type: 'numbers'}
            , {type: 'checkbox'}
            , {field: 'user_name', title: '管理员名称', width: 320}
            , {field: 'id', title: '操作', templet: '#caozuo'}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});
// function member_del(obj, id) {
//     layer.confirm('确认要删除吗？', function (index) {
//         //发异步删除数据
//         /******************************************************/
//         ajaxurl = $(obj).attr("ajaxurl");
//         datastr = "id=" + id;
//         //  alert(ajaxurl+"----"+datastr);
//         $.ajax({
//             type: 'POST',
//             url: ajaxurl,
//             data: datastr,
//             dataType: 'text',
//             success: function (str) {
//                 //去换行，去空格
//                 var status = getClearStr(str);
//                 //判断权限状态码是否存在
//                 if (status.search("no_auth") != -1) {
//                     //JSON.parse() 将字符串转换为对象或数组
//                     var str_obj = JSON.parse(status);
//                     var status_auth = getClearStr(str_obj.status);
//                     if (status_auth == 'no_auth') {
//                         layer.msg(str_obj.msg, {icon: 5, time: 1500});
//                         return false;
//                     }
//                 }
//                 if (status == "ok") {
//                     $(obj).parents("tr").remove();
//                     layer.msg('已删除!', {icon: 1, time: 1000});
//                 } else {
//                     layer.msg('删除失败!', {icon: 2, time: 1000});
//                 }
//             },
//             error: function (XMLHttpRequest, textStatus, errorThrown) {
//                 alert("error");
//             }
//         });
//         /*****************************************************/
//     });
// }
/*
 * 页面搜索功能
 * where（传参）:前面为字段名，后面为字段值
 */
function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                user_name: $("#userName").val()
                , status: $("#allStatus").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}
