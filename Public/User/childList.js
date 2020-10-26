var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#childList'
        , url: $("#childList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID', width: 70, align: 'center'}
            , {field: 'child_name', title: '子账号名称', width: 150}
            , {field: 'bieming', title: '子账号别名', width: 150}
            , {field: 'lastlogin_time', title: '最近登录时间', sort: true, width: 200}
            , {field: 'user_pwd', title: '修改密码', templet: '#showmanagepwd'}
            , {field: 'id', title: 'ip白名单', templet: '#showip'}
            , {field: 'google', title: '谷歌验证', templet: '#showsgoogle'}
            , {field: 'status', title: '状态', templet: '#showstatus'}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right', width: 160}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
    //监听锁定操作
    form.on('switch(statuscheckbox)', function (obj) {
        /******************************************************************************/
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&status=" + (obj.elem.checked ? 1 : 2);
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'json',
            success: function (data) {
                if (data.status == "ok") {
                    layer.tips(data.msg, obj.othis, {
                        tips: [2, '#5FB878']
                    });
                } else {
                    layer.tips(data.msg, obj.othis, {
                        tips: [3, '#FF5722']
                    });
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                layer.tips('操作错误，请重试！', obj.othis, {
                    tips: [3, '#FF5722']
                });
            }
        });
        /*****************************************************************************/
    });
});


function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                child_name: $("#childName").val()
                , bieming: $("#bieming").val()
                , status: $("#status").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}
