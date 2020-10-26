//银行卡号黑名单列表js
var tableIns;
layui.use('table', function () {
    var table = layui.table;
    tableIns = table.render({
        elem: '#blackBankNum'
        , url: $("#blackBankNum").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID',width: 70}
            ,{type: 'checkbox',width: 50}
            , {field: 'bank_num', title: '银行卡号'}
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
                bank_num: $("#bank_num").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}