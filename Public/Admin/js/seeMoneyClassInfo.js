var tableIns;
layui.use(['table', 'form'], function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#seeMoneyClassInfo'
        , url: $("#seeMoneyClassInfo").attr("dataurl")
        , title: '账号使用信息'
        , cols: [[
            {type: 'numbers', width: 70}
            , {field: 'bieming', title: '账号名称', templet: '#showbieming', align: 'center'}
            , {field: 'username', title: '用户名称', templet: '#showusername', align: 'center'}
        ]]
        , page: false
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});
