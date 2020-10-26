var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#userAccount'
        , url: $("#userAccount").attr("dataurl")
        , cellMinWidth: 80
        , toolbar: '#showtoolbar'
        , title: '通道账号'
        , defaultToolbar: ['filter', 'print']
        , cols: [[
            {type: 'numbers', title: 'ID', width: 70}
            , {field: 'bieming', title: '账号名称', width: 200}
            , {field: 'payapi_name', title: '所属通道'}
            // , {field: 'commission_rate', title: '分润费率', templet: '#commission_rate'}
            // , {field: 'memberid', title: '商户号', templet: '#memberid_temp'}
            // , {field: 'money', title: '每日交易总额', templet: '#showmoney', width: 120}
            , {field: 'id', title: '单笔最小/最大限额', templet: '#showminmoney'} //单笔限额（最大/最小）
            // ,{field: 'max_money', title: '单笔最大金额', templet: '#showmaxmoney', width:120}  //单笔最大金额
            , {field: 'feilv', title: '费率', templet: '#showfeilv'}
            // , {field: 'oddment', title: '充值零头', templet: '#showoddment', width: "6%"}
            , {field: 'id', title: '账号设置', templet: '#showmd5keystr'}
            , {field: 'default_status', title: '设置默认', templet: '#showdefault'}
            , {field: 'status', title: '状态', templet: '#showstatus'}
            , {field: 'id', title: '操作', templet: '#caozuo', width: 100, fixed: 'right'}
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

    form.on('switch(defaultcheckbox)', function (obj) {
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&default_status=" + (obj.elem.checked ? 1 : 0);
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
                        btn: ['确认'],
                    }, function () {
                        location.reload();
                    });
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                layer.confirm('操作错误,请重试', {
                    btn: ['确认'],
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
                bieming: $("#bieming").val()
                // , memberid: $("#memberid").val()
                // , account: $("#account").val()
                // , payapishangjiaid: $("#payapishangjiaid").val()
                // , moneytypeclassid: $("#moneytypeclassid").val()
                , status: $("#status").val()
                , user_payapiid: $("#user_payapiid").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}
