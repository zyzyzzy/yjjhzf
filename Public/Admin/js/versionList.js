var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#versionList'
        , url: $("#versionList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID', width: 70}
            , {field: 'numberstr', title: '接口版本号', width: 200}
            , {field: 'bieming', title: '接口版本别名', width: 200}
            , {field: 'actionname', title: '控制器名称', width: 200}
            , {field: 'status', title: '状态', templet: '#showstatus'}
            , {field: 'al_use', title: '通用', templet: '#showalluse'}
            , {field: 'date_time', title: '创建时间', sort: true}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right', width: 150}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
    //监听锁定操作，修改状态开关
    form.on('switch(statuscheckbox)', function (obj) {
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
                layer.msg('操作错误，请检查！', {icon: 5, time: 2000});
            }
        });
    });

    //监听锁定操作，修改通用开关
    form.on('switch(allusecheckbox)', function (obj) {
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&all_use=" + (obj.elem.checked ? 1 : 2);
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
                layer.msg('操作错误，请检查！', {icon: 5, time: 2000});
            }
        });
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
                numberstr: $("#numberstr").val()
                , actionname: $("#actionname").val()
                , status: $("#status").val()
                , all_use: $("#all_use").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}