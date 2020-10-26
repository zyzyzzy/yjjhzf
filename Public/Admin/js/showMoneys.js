var tableIns;

layui.use('table', function() {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#moneys'
        , url: $("#moneys").attr("dataurl")
        , cols: [[
            {type: 'numbers'}
            , {type: 'checkbox'}
            , {field: 'user', title: '用户名', templet: '#user'}
            , {field: 'payapi', title: '通道名',  templet: '#payapi', width: 150}
            , {field: 'payapiaccount', title: '账号',templet: '#payapiaccount'}
            , {field: 'money', title: '金额', sort: true}
            , {field: 'freezemoney', title: '冻结金额'}
            , {field: 'edit', title: '编辑', templet: '#edit', fixed: 'right'}
        ]]
        , page: true
        , text: {

            none: '无数据'

        }
        , method: 'post'
    });
});


function searchbutton(){
    layui.use('table',function(){
        tableIns.reload({
            where: {
                username: $("#username").val()
                ,userid: $("#userid").val()
                ,accountid: $("#accountid").val()
                ,payapiid: $("#payapiid").val()
                ,payapiclassid: $("#payapiclassid").val()
            }
            ,page: {
                curr: 1
            }
        });
    });
}