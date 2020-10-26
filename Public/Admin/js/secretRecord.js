var tableIns;
layui.use('table', function () {
    var table = layui.table;
    tableIns = table.render({
        elem: '#secretRecord'
        , url: $("#secretRecord").attr("dataurl")
        , cols: [[
            {type: 'numbers'}
            , {type: 'checkbox'}
            , {field: 'user_name', title: '邀请码', templet: '#showinvitecode', width: "20%"}

        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});