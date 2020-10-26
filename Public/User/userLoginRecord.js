//用户或者子账号的登陆记录js
var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#userLoginRecord'
        , url: $("#userLoginRecord").attr("dataurl")
        , cols: [[
            {type: 'numbers', width: 100,fixed:'left'}
            , {field: 'user_name', title: '主用户', align: 'center', width: 200}
            , {field: 'child_name', title: '子账号', align: 'center', width: 200}
            , {field: 'logindatetime', title: '登录时间', align: 'center', width: 200}
            , {field: 'loginip', title: '登录IP', templet: '#login_ip', align: 'center', width: 200}
            , {field: 'id', title: '登录地址', templet: '#loginaddress', align: 'center'}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});

layui.use('laydate', function(){
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
                child_name: $("#child_name").val()
                , start: $("#start").val()
                , end: $("#end").val()
                , loginip: $("#loginip").val()
                , child_id: $("#child_id").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}