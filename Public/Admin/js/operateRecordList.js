var tableIns;
layui.use('table', function() {
    var table = layui.table;
    tableIns = table.render({
        elem: '#operateRecordList'
        , url: $("#operateRecordList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID', width: '7%'}
            , {field: 'user_name', title: '主用户', width: '10%'}
            , {field: 'child_name', title: '子账号', width: '10%'}
            , {field: 'operatedatetime', title: '操作时间', width: '18%'}
            , {field: 'userip', title: '用户IP', templet: '#user_ip', width: '18%'}
            , {field: 'content', title: '操作内容', templet: '#content'}
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
                ,userip: $("#userip").val()
                , child_id: $("#child_id").val()
            }
            ,page: {
                curr: 1
            }
        });
    });
}