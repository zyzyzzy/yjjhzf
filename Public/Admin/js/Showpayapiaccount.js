var tableIns;
layui.use(['table', 'form'], function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#ShowUserPayapiaccount'
        , url: $("#ShowUserPayapiaccount").attr("dataurl")
        , title: '通道账号'
        , cols: [[
            {type: 'numbers', title: 'ID', width: 70}
            , {type: 'checkbox', width: 50}
            , {field: 'bieming', title: '账号名称', templet: '#showbieming'}
            , {field: 'check', title: '状态', templet: '#showstatus'}
            , {field: 'id', title: '设置默认账号', templet: '#showdefault'}
            // , {field: 'account_type', title: '账号类型', align: 'center'}  //2019-3-28 任梦龙：添加账号类型标识
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right', width: 100}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
    //监听账号是否开启的状态
    form.on('switch(checkboxpayapiaccount)', function (data) {
        layer.confirm('确定修改账号状态吗?', function () {
            paypai_id = data.elem.dataset.id;
            ajaxurl = 'TongdaoZhanghao/';
            datastr = "payapiaccountid=" + data.elem.value + "&checked=" + (data.elem.checked ? 1 : 0) + "&payapiid=" + paypai_id;
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: datastr,
                dataType: 'json',
                success: function (obj) {
                    if (obj.status == "ok") {
                        layer.msg(obj.msg, {icon: 6, time: 1500}, function () {
                            location.reload();
                        });
                    } else {
                        layer.msg(obj.msg, {icon: 5, time: 1500}, function () {
                            location.reload();
                        });
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    layer.msg('操作错误，请重试!', {icon: 5});
                }
            });
        });
    });

    //监听设置默认账号的状态
    form.on('switch(checkdefaultaccount)', function (data) {
        layer.confirm('确认修改默认账号状态吗?', function () {
            paypai_id = data.elem.dataset.id;
            ajaxurl = 'defaultongdaoZhanghao/';
            datastr = "payapiaccountid=" + data.elem.value + "&checked=" + (data.elem.checked ? 1 : 0) + "&payapiid=" + paypai_id;
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: datastr,
                dataType: 'json',
                success: function (obj) {
                    //设置完后得刷新页面，不然页面会有多个默认账户设置成功的样式，虽说数据库没有变化，手动刷新也可以显示最新的页面，但是默认账户只有一个，设置完后应该不会再去改变
                    if (obj.status == "ok") {
                        layer.msg(obj.msg, {icon: 6, time: 2000}, function () {
                            location.reload();
                        });
                    } else {
                        layer.msg(obj.msg, {icon: 5, time: 2000}, function () {
                            location.reload();
                        });
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    layer.msg('操作错误，请重试!', {icon: 5, time: 1500}, function () {
                        location.reload();
                    });
                }
            });
        });
    });
});

//批量选择账号
function addAll(mythis) {
    layer.confirm('确认开启选中的账号吗？', function (index) {
        //捉到所有被选中的，发异步进行删除
        layui.use('table', function () {
            var table = layui.table;
            var checkStatus = table.checkStatus('ShowUserPayapiaccount');
            data = checkStatus.data;
            idstr = '';
            for (j = 0; j < data.length; j++) {
                idstr += data[j]['id'] + ',';
            }
            idstr = idstr.substr(0, idstr.length - 1);
            if (idstr == '') {
                layer.msg('没有选中任何账号!', {icon: 2, time: 1500});
                return false;
            }
            ajaxurl = $(mythis).attr('ajaxurl');
            datastr = 'idstr=' + idstr;   //账号id序列
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: datastr,
                dataType: 'json',
                success: function (res) {
                    if (res.status == 'ok') {
                        layer.msg(res.msg, {icon: 6, time: 1500}, function () {
                            location.reload();
                        });
                    } else {
                        layer.msg('通道账号添加失败!', {icon: 2, time: 1500});
                        return false;
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    layer.msg('操作错误，请重试!', {icon: 5});
                }
            });
        });
    });
}

//批量关闭账号
function delAll(mythis) {
    layer.confirm('确认关闭选中的账号吗？', function (index) {
        //捉到所有被选中的，发异步进行删除
        layui.use('table', function () {
            var table = layui.table;
            var checkStatus = table.checkStatus('ShowUserPayapiaccount');
            data = checkStatus.data;
            idstr = '';
            for (j = 0; j < data.length; j++) {
                idstr += data[j]['id'] + ',';
            }
            idstr = idstr.substr(0, idstr.length - 1);
            if (idstr == '') {
                layer.msg('没有选中任何账号!', {icon: 2, time: 1500});
                return false;
            }
            ajaxurl = $(mythis).attr('ajaxurl');
            datastr = 'idstr=' + idstr;
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: datastr,
                dataType: 'json',
                success: function (res) {
                    if (res.status == 'ok') {
                        layer.msg(res.msg, {icon: 6, time: 1500}, function () {
                            location.reload();
                        });
                    } else {
                        layer.msg('通道账号关闭失败!', {icon: 2, time: 1500});
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    layer.msg('操作错误，请重试!', {icon: 5});
                }
            });
        });
    });
}
