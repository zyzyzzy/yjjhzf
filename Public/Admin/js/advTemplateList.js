var tableIns;
layui.use(['table','laydate'], function () {
    var table = layui.table;
    var laydate = layui.laydate;
    var form = layui.form;
    tableIns = table.render({
        elem: '#advTemplateList'
        ,url: $("#advTemplateList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID',width:70}
            , {type: 'checkbox',width:50}
            , {field: 'title', title: '标题'}
            , {field: 'pc_template_name', title: 'PC端模板文件名'}
            , {field: 'pc_img_name', title: 'PC端模板图片', templet: '#pc_img'}
            , {field: 'wap_template_name', title: 'WAP端模板文件名'}
            , {field: 'wap_img_name', title: 'WAP端模板图片', templet: '#wap_img'}
            , {field: 'default', title: '默认', templet: '#default'}
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
        ajaxurl = $(this).attr("ajaxurl");
        datastr = "id=" + this.value + "&default=" + (obj.elem.checked?1:0);
        $.ajax({
            type:'POST',
            url:ajaxurl,
            data:datastr,
            dataType:'json',
            success:function(data){
                if(data.status == "ok"){
                    layer.tips(data.msg, obj.othis,{
                        tips: [2,'#5FB878']
                    });
                    if(obj.elem.checked){
                        location.replace(location.href);
                    }
                }else{
                    layer.tips(data.msg, obj.othis,{
                        tips: [3,'#FF5722']
                    });
                }
            },
            error:function(XMLHttpRequest, textStatus, errorThrown) {
                layer.tips('操作错误,请重试', obj.othis,{
                    tips: [3,'#FF5722']
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
                title: $("#title").val(),
            }
            , page: {
                curr: 1
            }
        });
    });
}