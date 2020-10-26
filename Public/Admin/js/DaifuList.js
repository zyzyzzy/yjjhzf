var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#DaifuList'
        , url: $("#DaifuList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID',width: 70}
            , {type: 'checkbox',width: 50}
            , {field: 'zh_payname', title: '代付通道名',width: 150}
            , {field: 'en_payname', title: '代付通道编码',width: 150}
            , {field: 'shangjianame', title: '通道商家', templet: '#shangjianame'}
            , {field: 'settle_feilv', title: '默认结算运营费率', templet: '#settle_feilv'}
            , {field: 'settle_min_feilv', title: '结算单笔最低手续费（元）', templet: '#settle_min_feilv'}
            , {field: 'status', title: '状态', templet: '#status'}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right',width: 100}
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
                console.log(data);
                var str = removeTrimLine(data.status);
                if (str == "ok") {
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
                layer.tips('操作错误，请重试', obj.othis, {
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
                , payapishangjiaid: $("#payapishangjiaid").val()
                , status: $("#daidustatus").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}
