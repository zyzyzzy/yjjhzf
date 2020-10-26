var tableIns;
layui.use('table', function() {
    var table = layui.table;
    tableIns = table.render({
        elem: '#adminOperateList'
        , url: $("#adminOperateList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID', width: 100}
            , {field: 'admin_name', title: '管理员',width:'15%'}
            , {field: 'date_time', title: '操作时间',width:'15%'}
            , {field: 'admin_ip', title: '登录IP',templet:'#admin_ip',width:'15%'}
            , {field: 'msg', title: '操作信息'}
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
        ,type: 'datetime'  //yMd Hms
    });
    laydate.render({
        elem: '#end'
        ,type: 'datetime'
    });
});
function searchbutton(){
    layui.use('table',function(){
        tableIns.reload({
            where: {
                start: $("#start").val()
                ,end: $("#end").val()
                ,admin_ip: $("#admin_ip").val()
                ,admin_id: $("#admin_id").val()
            }
            ,page: {
                curr: 1
            }
        });
    });
}