var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#PayapiAccount'
        , url: $("#PayapiAccount").attr("dataurl")
        // , cellMinWidth: 80
        , toolbar: '#showtoolbar'
        , title: '通道账号'
        , defaultToolbar: ['filter', 'print']
        , cols: [[
            {type: 'numbers', title: 'ID', width: 70}
            , {field: 'bieming', title: '账号名称', width: 160}
            , {field: 'payapishangjianame', title: '所属商家', width: 150}
            // , {field: 'owner_name', title: '所属用户', templet: '#owner_name'}
            // , {field: 'commission_rate', title: '分润费率', templet: '#commission_rate'}
            , {field: 'memberid', title: '商户号', templet: '#memberid_temp', width: 170}
            , {field: 'account', title: '帐号', templet: '#account_temp', width: 120}
            , {field: 'account_type', title: '所属者', width: 80}
            // ,{field: 'type', title: '类型',templet:'#showtype', width: "6%"}
            // ,{field:'cbfeilv', title:'成本费率',align:'center' , templet:'#showcbfeilv'}
            // ,{field:'feilv', title:'费率',align:'center' , templet:'#showfeilv'}
            , {field: 'money', title: '每日交易总额', templet: '#showmoney', width: 115}
            , {field: 'min_money', title: '单笔最小金额', templet: '#showminmoney', width: 115}
            , {field: 'max_money', title: '单笔最大金额', templet: '#showmaxmoney', width: 115}
            , {field: 'feilv', title: '费率', templet: '#showfeilv', width: 75}
            , {field: 'oddment', title: '充值零头', templet: '#showoddment', width: 90}
            , {field: 'moneytypeclassname', title: '到账方案', templet: '#showmoneytypeclass', width: 90}
            , {field: 'id', title: '账号设置', templet: '#showmd5keystr', width: 90}
            , {field: 'type', title: '类型', templet: '#showtype', width: 100}
            , {field: 'status', title: '状态', templet: '#showstatus', width: 100}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right', width: 135}
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

    form.on('switch(oddmentcheckbox)', function (obj) {
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&oddment=" + (obj.elem.checked ? 1 : 0);
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

    //2019-4-29 rml：修改账号类型
    form.on('switch(typecheckbox)', function (obj) {
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&type=" + (obj.elem.checked ? 0 : 1);
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
});

function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                bieming: $("#bieming").val()
                , memberid: $("#memberid").val()
                , account: $("#account").val()
                , payapishangjiaid: $("#payapishangjiaid").val()
                , moneytypeclassid: $("#moneytypeclassid").val()
                , status: $("#status").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}
