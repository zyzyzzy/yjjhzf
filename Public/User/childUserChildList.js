var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#childUserChildList'
        , url: $("#childUserChildList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID', width: 70}
            , {field: 'username', title: '用户名', width: 200}
            , {field: 'memberid', title: '商户号', width: 200}
            , {field: 'usertype', title: '用户类型', templet: '#user_type', width: 100}
            , {field: 'status', title: '状态', templet: '#showstatus', width: 100}
            , {field: 'authentication', title: '认证', templet: '#showauthentication', width: 100}
            , {field: 'regdatetime', title: '注册时间', width: 180,sort:true}
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
        datastr = "id=" + this.value + "&status=" + (obj.elem.checked ? 2 : 3);
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'text',
            success: function (str) {
                if (str == "ok") {
                    layer.tips('修改成功', obj.othis, {
                        tips: [2, '#5FB878']
                    });
                } else {
                    layer.tips('修改失败', obj.othis, {
                        tips: [3, '#FF5722']
                    });
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {

            }
        });
    });

});


layui.use('laydate', function () {
    var laydate = layui.laydate;

    //执行一个laydate实例
    laydate.render({
        elem: '#start' //指定元素
        , type: "datetime"
    });

    //执行一个laydate实例
    laydate.render({
        elem: '#end' //指定元素
        , type: "datetime"
    });
});

function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                username: $("#username").val()
                , status: $("#status").val()
                , userrengzheng: $("#userrengzheng").val()
                , usertype: $("#usertype").val()
                , start: $("#start").val()
                , end: $("#end").val()
            }
            , page: {
                curr: 1
            }
        });
    });

}



