var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#UserList'
        , url: $("#UserList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID', width: 70}
            , {field: 'bankname', title: '银行名称', width: 100}
            , {field: 'banknumber', title: '银行卡号', width: 200}
            , {field: 'status', title: '状态', templet: '#showstatus', width: 100}
            , {field: 'mr', title: '默认', templet: '#showmr', width: 100}
            , {field: 'bankmyname', title: '开户人姓名', width: 100}
            , {field: 'shenfenzheng', title: '身份证号', width: 180}
            , {field: 'tel', title: '手机号', width: 120}
            , {field: 'bankfenname', title: '分行名称', width: 100}
            , {field: 'bankzhiname', title: '支行名称', width: 100}
            , {field: 'banksheng', title: '开户省/市', templet: '#showshengshi', width: 120}
            , {field: 'banklianhanghao', title: '联行号', width: 200}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right', width: 100}

        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });

    //监听锁定操作
    form.on('switch(statuscheckbox)', function (obj) {
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&status=" + (obj.elem.checked ? 1 : 0);
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
                layer.tips('操作错误,请重试', obj.othis, {
                    tips: [3, '#FF5722']
                });
            }
        });
    });

    form.on('switch(Mrcheckbox)', function (obj) {
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&mr=" + (obj.elem.checked ? 1 : 0);
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'json',
            success: function (data) {
                if (data.status == "ok") {
                    layer.confirm(data.msg, {
                        btn: ['确认'] //按钮
                    }, function () {
                        location.reload();
                    });

                } else {
                    layer.confirm(data.msg, {
                        btn: ['确认'] //按钮
                    }, function () {
                        location.reload();
                    });
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                layer.confirm('操作错误,请重试！', {
                    btn: ['确认'] //按钮
                }, function () {
                    location.reload();
                });
            }
        });
    });
});

function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                bankname: $("#bankname").val()
                , banknumber: $("#banknumber").val()
                , bankmyname: $("#bankmyname").val()
                , shenfenzheng: $("#shenfenzheng").val()
                , tel: $("#tel").val()
                , status: $("#status").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}