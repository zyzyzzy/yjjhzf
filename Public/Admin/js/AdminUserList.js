/*
 * 管理员列表js代码
 */
var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#AdminUserList'
        , url: $("#AdminUserList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID',width: 70,align:'center'}
            , {field: 'user_name', title: '管理员名称',width: 150}
            , {field: 'bieming', title: '管理员别名',width: 150}
            , {field: 'lastlogin_time', title: '最近登录时间', sort: true,width: 160}
            , {field: 'id', title: '密码设置', templet: '#showmanagepwd'}
            , {field: 'id', title: '账号登录',templet: '#showsameadmin'}
            , {field: 'status', title: '状态', templet: '#showstatus'}
            , {field: 'google', title: '谷歌验证', templet: '#showsgoogle'}
            , {field: 'manage_status', title: '管理密码', templet: '#showsmanagestatus'}
            , {field: 'id', title: '操作', templet: '#caozuo', width: 200, fixed: 'right'}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
    //监听锁定操作，修改状态
    form.on('switch(statuscheckbox)', function (obj) {
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&status=" + (obj.elem.checked ? 1 : 2);
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'json',
            success: function (data) {
                //判断权限状态码
                if(data.status == 'no_auth'){
                    layer.tips(data.msg, obj.othis, {
                        tips: [3, '#FF5722']
                    });
                }
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
                layer.msg('操作错误，请检查！',{icon:5 ,time:2000});
            }
        });
    });

    form.on('switch(googlecheckbox)', function (obj) {
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&switch=" + (obj.elem.checked ? 1 : 2);
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'json',
            success: function (data) {
                //判断权限状态码
                if(data.status == 'no_auth'){
                    layer.tips(data.msg, obj.othis, {
                        tips: [3, '#FF5722']
                    });
                }
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
                layer.msg('操作错误，请检查！',{icon:5 ,time:2000});
            }
        });
    });

    //添加管理密码的开关
    form.on('switch(managestatuscheckbox)', function (obj) {
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&manage=" + (obj.elem.checked ? 1 : 2);
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'json',
            success: function (data) {
                //判断权限状态码
                if(data.status == 'no_auth'){
                    layer.tips(data.msg, obj.othis, {
                        tips: [3, '#FF5722']
                    });
                }
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
                layer.msg('操作错误，请检查！',{icon:5 ,time:2000});
            }
        });
    });
});

function member_del(obj, id) {
    layer.confirm('确认要删除吗？', function (index) {
        //发异步删除数据
        ajaxurl = $(obj).attr("ajaxurl");
        datastr = "id=" + id;
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'text',
            success: function (str) {
                //去换行，去空格
                var status = getClearStr(str);
                //判断权限状态码是否存在
                if (status.search("no_auth") != -1) {
                    //JSON.parse() 将字符串转换为对象或数组
                    var str_obj = JSON.parse(status);
                    var status_auth = getClearStr(str_obj.status);
                    if (status_auth == 'no_auth') {
                        layer.msg(str_obj.msg, {icon: 5, time: 1500});
                        return false;
                    }
                }
                if (status == "ok") {
                    $(obj).parents("tr").remove();
                    layer.msg('已删除!', {icon: 1, time: 1000});
                } else {
                    layer.msg('删除失败!', {icon: 2, time: 1000});
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                alert("error");
            }
        });
    });
}

/*
 * 页面搜索功能
 * where（传参）:前面为字段名，后面为字段值
 */
function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                user_name: $("#userName").val()
                //2019-1-21 任梦龙：添加别名
                , bieming: $("#bieming").val()
                , status: $("#allStatus").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}

//去除换行
function clearBr(key) {
    key = key.replace(/<\/?.+?>/g, "");
    key = key.replace(/[\r\n]/g, "");
    return key;
}

//去掉字符串两端的空格
function clearTrim(str) {
    return str.replace(/(^\s*)|(\s*$)/g, "");
}

//先去换行，后去空格
function getClearStr(str) {
    var str_br = clearBr(str);
    var str_trim = clearTrim(str_br);
    return str_trim;
}