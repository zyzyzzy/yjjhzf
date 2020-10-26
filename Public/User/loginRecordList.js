var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#loginRecordList'
        , url: $("#loginRecordList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID',width: 100,fixed:'left'}
            , {field: 'user_name', title: '主用户',  width: 200}
            , {field: 'child_name', title: '子账号', width: 200}
            , {field: 'logindatetime', title: '登录时间', width: 200,sort:true}
            , {field: 'loginip', title: '登录IP', templet: '#login_ip', width: 150}
            , {field: 'id', title: '登录地址', templet: '#loginaddress'}
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
                // 2019-1-22 任梦龙：添加子账号的搜索
                child_name: $("#child_name").val()
                , start: $("#start").val()
                , end: $("#end").val()
                , loginip: $("#loginip").val()
                , child_id: $("#child_id").val()  //添加子账号搜索
            }
            , page: {
                curr: 1
            }
        });
    });
}