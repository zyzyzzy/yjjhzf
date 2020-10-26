//域名黑名单列表js
var tableIns;
layui.use('table', function () {
    var table = layui.table;
    tableIns = table.render({
        elem: '#blackDomain'
        , url: $("#blackDomain").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID',width: 70}
            ,{type: 'checkbox',width: 50}
            , {field: 'domain', title: '域名地址'}
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
                domain: $("#domain").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}