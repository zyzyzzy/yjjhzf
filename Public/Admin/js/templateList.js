var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#templateList'
        ,url: $("#templateList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID',width:70}
            , {type: 'checkbox',width:50}
            , {field: 'temp_name', title: '模板文件名'}
            , {field: 'msg', title: '模板说明'}
            , {field: 'img_path', title: '模板图片', templet: '#img'}
            , {field: 'type_name', title: '登录类型'}
            , {field: 'default', title: '默认', templet: '#default'}
            , {field: 'date_time', title: '添加时间',width:160}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right',width:100}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });

    //监听锁定操作
    form.on('switch(defaultcheckbox)', function(obj){
        type = $(this).attr('data-type');  //登录类型
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&default=" + (obj.elem.checked?1:0) + '&type=' + type;
        $.ajax({
            type:'POST',
            url:ajaxurl,
            data:datastr,
            dataType:'json',
            success:function(data){
                if(data.status == "ok"){
                    // layer.tips(data.msg, obj.othis,{
                    //     tips: [2,'#5FB878']
                    // });
                    layer.confirm(data.msg, {
                        btn: ['确认'] //按钮
                    }, function () {
                        location.reload();
                    });

                }else{
                    layer.confirm(data.msg, {
                        btn: ['确认'] //按钮
                    }, function () {
                        location.reload();
                    });
                    // layer.tips(data.msg, obj.othis,{
                    //     tips: [3,'#FF5722']
                    // });
                }
            },
            error:function(XMLHttpRequest, textStatus, errorThrown) {
                // layer.tips('操作失败，请重试', obj.othis,{
                //     tips: [3,'#FF5722']
                // });
                layer.confirm('操作失败，请重试', {
                    btn: ['确认'] //按钮
                }, function () {
                    location.reload();
                });
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
                temp_name: $("#temp_name").val(),
                type: $("#type").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}