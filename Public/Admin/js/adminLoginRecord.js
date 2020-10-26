//管理员登陆记录js
var tableIns;
layui.use('table', function () {
    var table = layui.table;
    tableIns = table.render({
        elem: '#adminLoginRecord'
        , url: $("#adminLoginRecord").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID', width: 100}
            , {field: 'admin_name', title: '管理员', width: '15%'}
            , {field: 'login_datetime', title: '登录时间', width: '15%'}
            , {field: 'login_ip', title: '登录IP', templet: '#login_ip', width: '15%'}
            , {field: 'login_address', title: '登录地址', templet: '#login_address'}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});
layui.use('laydate', function () {
    var laydate = layui.laydate;
    laydate.render({
        elem: '#start' //指定元素
        , type: 'datetime'
    });
    laydate.render({
        elem: '#end'
        , type: 'datetime'
    });
});

function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                start: $("#start").val()
                , end: $("#end").val()
                , login_ip: $("#loginIp").val()
                , admin_id: $("#admin_id").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}