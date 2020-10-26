var tableIns;
layui.use('table', function() {
    var table = layui.table;
    tableIns = table.render({
        elem: '#loginRecordList'
        , url: $("#loginRecordList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID', width: '7%'}
            , {field: 'user_name', title: '主用户'}
            , {field: 'child_name', title: '子账号'}
            , {field: 'logindatetime', title: '登录时间',width: '15%'}
            , {field: 'loginip', title: '登录IP', templet: '#login_ip'}
            , {field: 'id', title: '登录地址', templet: '#loginaddress',width: '15%'}
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

function searchbutton(){
    layui.use('table',function(){
        tableIns.reload({
            where: {
                start: $("#start").val()
                ,end: $("#end").val()
                ,loginip: $("#loginip").val()
                , child_id: $("#child_id").val()
            }
            ,page: {
                curr: 1
            }
        });
    });
}