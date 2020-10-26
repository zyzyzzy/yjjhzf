var tableIns;
layui.use('table', function() {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#childRecordList'
        , url: $("#childRecordList").attr("dataurl")
        , cols: [[
            {type: 'numbers'}
            , {field: 'login_time', title: '登录时间',align:'center'}
            , {field: 'login_ip', title: '登录IP',templet:'#login_ip',align:'center'}
            , {field: 'id', title: '登录地址',templet:'#login_address',align:'center'}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
    var laydate = layui.laydate;
    //执行一个laydate实例
    laydate.render({
        elem: '#start' //指定元素
        ,type: 'datetime'
    });
    //执行一个laydate实例
    laydate.render({
        elem: '#end' //指定元素
        ,type: 'datetime'
    });
});
function searchbutton(){
    layui.use('table',function(){
        tableIns.reload({
            where: {
                start: $("#start").val()
                ,end: $("#end").val()
                ,login_ip: $("#loginIp").val()
            }
            ,page: {
                curr: 1
            }
        });
    });
}