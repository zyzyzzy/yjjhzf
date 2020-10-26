var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#userGroupList'
        , url: $("#userGroupList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID',width: 70,align: 'center'}
            , {field: 'auth_name', title: '角色名称'}
            , {field: 'status', title: '状态', templet: '#showstatus'}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right',width: 150}
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
                if (data.status == 'ok') {
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
                auth_name: $("#authName").val()
                , status: $("#allStatus").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}
