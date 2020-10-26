//手机号黑名单列表js
var tableIns;
layui.use('table', function () {
    var table = layui.table;
    tableIns = table.render({
        elem: '#blackTel'
        , url: $("#blackTel").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID',width: 70}
            ,{type: 'checkbox',width: 50}
            , {field: 'tel', title: '手机号'}
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
                tel: $("#tel").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}