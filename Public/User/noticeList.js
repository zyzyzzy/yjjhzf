var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#noticeList'
        , url: $("#noticeList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID', width: 70}
            , {field: 'title', title: '公告标题'}
            , {field: 'date_time', title: '创建时间'}
            , {field: 'id', title: '操作', templet: '#caozuo', width: 200, fixed: 'right'}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});
