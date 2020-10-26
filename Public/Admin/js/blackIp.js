//IP黑名单列表js
var tableIns;
layui.use('table', function () {
    var table = layui.table;
    tableIns = table.render({
        elem: '#blackIp'
        , url: $("#blackIp").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID',width: 70}
            ,{type: 'checkbox',width: 50}
            , {field: 'ip', title: 'IP地址'}
            , {field: 'date_time', title: '添加时间'}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right',width: 100}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});

function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                ip: $("#ip").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}