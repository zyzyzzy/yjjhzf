var tableIns;
layui.use('table', function () {
    var table = layui.table;
    tableIns = table.render({
        elem: '#viewAdminOpt'
        , url: $("#viewAdminOpt").attr("dataurl")
        , cols: [[
            {type: 'numbers', width: '7%'}
            , {type: 'checkbox'}
            , {field: 'menu_title', title: '菜单名称', templet: '#showmenutitle'}
            , {field: 'controller', title: '控制器名称', templet: '#showcontroller', width: '20%'}
            , {field: 'action', title: '方法名称', templet: '#showaction', width: '20%'}
            // , {field: 'level', title: '菜单级别', templet: '#showlevel', width: '10%'}
            , {field: 'id', title: '操作', templet: '#caozuo', width: '15%', fixed: 'right'}
        ]]
        , page: true
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