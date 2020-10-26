var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    /*
     * type:设定列类型
     * field：字段名
     * title：标题名称
     * sort：排序
     * templet：自定义列模板
     */
    tableIns = table.render({
        elem: '#noticeList'
        , url: $("#noticeList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID', width: 70}
            , {type: 'checkbox', width: 50}
            , {field: 'title', title: '公告标题'}
            , {field: 'user_name', title: '所属用户'}
            , {field: 'date_time', title: '创建时间'}
            , {field: 'id', title: '操作', templet: '#caozuo', width: 150, fixed: 'right'}
        ]]
        , page: true
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
                user_id: $("#user_id").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}