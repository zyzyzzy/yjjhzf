//到账方案js
var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#moneyClassList'
        , url: $("#moneyClassList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID', width: 70}
            , {type: 'checkbox', width: 50}
            , {field: 'moneyclassname', title: '方案名称', templet: '#showmoneyclass'}
            , {field: 'datetime', title: '添加时间', sort: true}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right', width: 200}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});

//搜索
function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                moneyclassname: $("#moneyclassname").val(),
            }
            , page: {
                curr: 1
            }
        });
    });
}
