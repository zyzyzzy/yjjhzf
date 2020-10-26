var tableIns;
layui.use('table', function() {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#moneychanges'
        , url: $("#moneychanges").attr("dataurl")
        , cols: [[
            {type: 'numbers'}
            , {field: 'changetype', title: '类型'}
            , {field: 'oldmoney', title: '原金额'}
            , {field: 'changemoney', title: '变动金额'}
            , {field: 'nowmoney', title: '变动后金额'}
            , {field: 'datetime', title: '变动时间'}
            , {field: 'transid', title: '订单号'}
            , {field: 'tcusername', title: '提成用户名'}
            , {field: 'tcdengji', title: '提成级别'}
            , {field: 'remarks', title: '备注'}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});

//日期
layui.use('laydate', function(){
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

//搜索
function searchbutton(){
    layui.use('table',function(){
        tableIns.reload({
            where: {
                transid: $("#transid").val()
                ,changetype: $("#changetype").val()
                ,start: $("#start").val()
                ,end: $("#end").val()
            }
            ,page: {
                curr: 1
            }
        });
    });

}

