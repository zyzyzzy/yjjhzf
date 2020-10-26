var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#viewUserMenu'
        , url: $("#viewUserMenu").attr("dataurl")
        , cols: [[
            {type: 'numbers'}
            , {type: 'checkbox'}
            , {field: 'menu_title', title: '菜单名称', templet: '#showmenutitle', width: 250}
            , {field: 'controller', title: '控制器名称', templet: '#showcontroller', width: 200}
            , {field: 'action', title: '方法路径', templet: '#showaction', width: 200}
            , {field: 'level', title: '菜单级别', templet: '#showlevel', width: 200}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right'}
        ]]
        , page: true
        , limit: 10
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});

function member_del(obj, id) {
    layer.confirm('确认要删除吗？', function (index) {
        //发异步删除数据
        ajaxurl = $(obj).attr("ajaxurl");
        datastr = "id=" + id;
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'text',
            success: function (str) {
                if (str == "ok") {
                    $(obj).parents("tr").remove();
                    layer.msg('已删除!', {icon: 1, time: 1000});
                } else if (str == "isexist") {
                    layer.msg('存在子菜单!!', {icon: 2, time: 1000});
                } else {
                    layer.msg('删除失败!', {icon: 2, time: 1000});
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                alert("error");
            }
        });
    });
}
/*
 * 页面搜索功能
 * where（传参）:前面为字段名，后面为字段值
 */
function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                menu_title: $("#menuTitle").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}