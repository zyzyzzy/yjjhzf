var tableIns;
layui.use(['table', 'form'], function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#seePayapiAccountInfo'
        , url: $("#seePayapiAccountInfo").attr("dataurl")
        , title: '账号使用信息'
        , cols: [[
            {type: 'numbers', width: 50}
            , {type: 'checkbox'}
            , {field: 'user_name', title: '用户名', templet: '#showusername', align: 'center'}
            , {field: 'class_name', title: '分类名称', templet: '#showpayapiclass', align: 'center'}
            , {field: 'zh_payname', title: '通道名称', templet: '#showpayapiname', align: 'center'}
            , {field: 'bieming', title: '账号别名', templet: '#showbieming', align: 'center'}
            , {field: 'class_name', title: '操作', templet: '#caozuo', align: 'center', fixed: 'right'}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});


/*
 * 页面搜索功能
 * where（传参）:前面为字段名，后面为字段值
 */
function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                user_name: $("#userName").val()
                , status: $("#allStatus").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}

//删除
function member_del(obj, id) {
    var user_id = $(obj).attr('data-user');  //用户id
    var payapi_id = $(obj).attr('data-payapi');  //通道id
    var payapiaccount_id = $(obj).attr('data-account');  //账号id
    layer.confirm('确认要删除吗？', function (index) {
        //发异步删除数据
        ajaxurl = $(obj).attr("ajaxurl");
        datastr = "id=" + id;
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {id: id, user_id: user_id, payapi_id: payapi_id, payapiaccount_id: payapiaccount_id},
            dataType: 'json',
            success: function (res) {
                if (res.status == "ok") {
                    $(obj).parents("tr").remove();
                    layer.msg(res.msg, {icon: 6, time: 1000});
                } else {
                    layer.msg(res.msg, {icon: 5, time: 1000});
                    return false;
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                layer.msg('未知错误!', {icon: 2, time: 1000});
            }
        });
    });
}