var tableIns;
layui.use('table', function() {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#EmailSeting'
        , url: $("#EmailSeting").attr("dataurl")
        , cols: [[
            {type: 'numbers', title:'ID',width:70}
            , {field: 'user_name', title: '用户名'}
            , {field: 'email', title: '用户邮箱'}
            , {field: 'dk', title: '端口'}
            , {field: 'smtp', title: 'SMTP'}
            , {field: 'receive_email', title: '收件人邮箱'}
            , {field: 'id', title: '测试发送邮件', templet: '#TestEmail'}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right',width:100}

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
                username: $("#username").val(),
                email: $("#email").val(),
                sjrxm: $("#sjrxm").val(),
            }
            ,page: {
                curr: 1
            }
        });
    });
}

