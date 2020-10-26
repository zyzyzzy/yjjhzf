var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#Banklist'
        , url: $("#Banklist").attr("dataurl")
        , cellMinWidth: 80
        , cols: [[
            {type: 'numbers', title: 'ID', width: 70}
            , {type: 'checkbox', width: 50}
            , {field: 'bankname', title: '银行名称'}
            , {field: 'bankcode', title: '银行编码'}
            , {field: 'img_url', title: '银行图片', templet: '#img'}
            , {field: 'jiaoyi', title: '交易应用', templet: '#jiaoyicheckbox'}
            , {field: 'jiesuan', title: '结算应用', templet: '#jiesuancheckbox'}
            , {field: 'datetime', title: '添加时间', width: 200}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right', width: 100}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
    //监听锁定操作
    form.on('switch(jiaoyicheckbox)', function (obj) {
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&jiaoyi=" + (obj.elem.checked ? 1 : 0);
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
                layer.tips('系统繁忙,请重试!', obj.othis, {
                    tips: [3, '#FF5722']
                });
            }

        });
    });

    form.on('switch(jiesuancheckbox)', function (obj) {
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&jiesuan=" + (obj.elem.checked ? 1 : 0);
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
                layer.tips('系统繁忙,请重试!', obj.othis, {
                    tips: [3, '#FF5722']
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
                , bankcode: $("#bankcode").val()
                , jiaoyisearch: $("#jiaoyisearch").val()
                , jiesuansearch: $("#jiesuansearch").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}


function qbsj() {
    layui.use('form', function () {
        var form = layui.form;
        $("#bankname").val("");
        $("#bankcode").val("");
        $("#jiaoyisearch").val("");
        $("#jiesuansearch").val("");
        form.render();
        searchbutton();
    });
}


function delAll(mythis) {
    layer.confirm('确认要删除吗？', function (index) {
        //捉到所有被选中的，发异步进行删除
        layui.use('table', function () {
            var table = layui.table;
            var checkStatus = table.checkStatus('Banklist')
                , data = checkStatus.data;
            idstr = "";
            for (j = 0; j < data.length; j++) {
                idstr += data[j]["id"] + ",";
            }
            idstr = idstr.substr(0, idstr.length - 1);
            ajaxurl = $(mythis).attr("ajaxurl");
            datastr = "idstr=" + idstr;
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: datastr,
                dataType: 'text',
                success: function (str) {
                    if (str == "ok") {
                        layer.msg('删除成功', {icon: 1});
                        $(".layui-form-checked").not('.header').parents('tr').remove();
                    } else {
                        layer.msg('删除失败!', {icon: 2, time: 1000});
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert("error");
                }
            });
        });
    });

}