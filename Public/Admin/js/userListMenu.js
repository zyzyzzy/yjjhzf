var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#userListMenu'
        , url: $("#userListMenu").attr("dataurl")
        , cols: [[
            {type: 'numbers',width: 80,align: 'center'}
            , {field: 'menu_title', title: '菜单名称', templet: '#showmenutitle', width: 250}
            , {field: 'controller', title: '控制器名称', templet: '#showcontroller', width: 200}
            , {field: 'action', title: '方法路径', templet: '#showaction', width: 200}
            , {field: 'level', title: '菜单级别', templet: '#showlevel', width: 200}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right',align: 'left'}
        ]]
        , page: false
        , limit: 10
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});

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