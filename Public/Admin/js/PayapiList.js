var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#PayapiList'
        , url: $("#PayapiList").attr("dataurl")
        , toolbar: '#showtoolbar'
        , defaultToolbar: ['filter']
        , cols: [[
            {type: 'numbers', title: 'ID', width: 70}
            , {type: 'checkbox', width: 50}
            , {field: 'zh_payname', title: '原通道名称', width: 180}
            , {field: 'en_payname', title: '原通道编码', width: 160}
            , {field: 'bieming_zh_payname', title: '自定义通道名称', templet: '#bieming_zh', width: 160}
            , {field: 'bieming_en_payname', title: '自定义通道编码', templet: '#bieming_en', width: 160}
            , {field: 'payapishangjiaid', title: '通道商家', width: 160}
            , {field: 'payapiclassname', title: '通道类别', width: 150}
            , {field: 'id', title: '通道账号', templet: '#showaccount', width: 150}
            , {field: 'id', title: '广告设置', templet: '#showjump', width: 150}
            , {field: 'status', title: '通道状态', templet: '#showstatus', width: 110}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right', width: 150}
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
                layer.tips('操作错误,请重试！', obj.othis, {
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
                zh_payname: $("#zh_payname").val()
                , en_payname: $("#en_payname").val()
                , bieming_zh_payname: $("#bieming_zh_payname").val()
                , bieming_en_payname: $("#bieming_en_payname").val()
                , payapishangjiaid: $("#payapishangjiaid").val()
                , payapiclassid: $("#payapiclassid").val()
                , status: $("#status").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}

 

  

