//用户或者子账号的操作记录js
var tableIns;
layui.use('table', function () {
    var table = layui.table;
    tableIns = table.render({
        elem: '#userOperateRecord'
        , url: $("#userOperateRecord").attr("dataurl")
        , cols: [[
            {type: 'numbers', width: 100,fixed:'left'}
            , {field: 'user_name', title: '主用户', align: 'center', width: 200}
            , {field: 'child_name', title: '子账号', align: 'center', width: 200}
            , {field: 'operatedatetime', title: '操作时间', align: 'center', width: 200}
            , {field: 'userip', title: '用户IP', templet: '#user_ip', align: 'center', width: 200}
            , {field: 'content', title: '操作内容', templet: '#content', align: 'center'}
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

//搜索条件
function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                start: $("#start").val()
                , end: $("#end").val()
                , userip: $("#userip").val()
                , child_id: $("#child_id").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}