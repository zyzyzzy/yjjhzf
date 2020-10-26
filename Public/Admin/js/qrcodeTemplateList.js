var tableIns;
layui.use(['table','laydate'], function () {
    var table = layui.table;
    var laydate = layui.laydate;
    var form = layui.form;
    tableIns = table.render({
        elem: '#qrcodeTemplateList'
        ,url: $("#qrcodeTemplateList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID',width:70}
            , {type: 'checkbox',width:50}
            , {field: 'title', title: '标题'}
            , {field: 'template_name', title: '模板文件名'}
            , {field: 'img_name', title: '模板图片', templet: '#img'}
            , {field: 'payapiclass_name', title: '通道分类'}
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
                console.log(data);
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
                payapiclass_id: $("#payapiclass_id").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}

function del(obj, id) {
    layer.confirm('确认要删除吗？', function (index) {
        //发异步删除数据
        /******************************************************/
        ajaxurl = $(obj).attr("ajaxurl");
        datastr = "id=" + id;
        //  alert(ajaxurl+"----"+datastr);
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'json',
            success: function (data) {
                if (data.status == "ok") {
                    $(obj).parents("tr").remove();
                    layer.msg(data.msg, {icon: 1, time: 1500});
                } else {
                    layer.msg(data.msg, {icon: 2, time: 2000});
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                alert("error");
            }
        });
        /*****************************************************/
    });
}

//批量删除
function delAll (obj) {
    layer.confirm('确认要删除吗？',function(index){
        //捉到所有被选中的，发异步进行删除
        layui.use('table',function(){
            var table = layui.table;
            var checkStatus = table.checkStatus('qrcodeTemplateList')
                ,data = checkStatus.data;
            idstr = "";
            for(j = 0; j < data.length; j++) {
                idstr += data[j]["id"]+",";
            }
            id_str = idstr.substr(0,idstr.length-1);  //id的字符串
            ajaxurl = $(obj).attr("ajaxurl");
            datastr = "id_str=" + id_str;
            $.ajax({
                type:'POST',
                url:ajaxurl,
                data:datastr,
                dataType:'json',
                success:function(data){
                    var status = removeTrimLine(data.status);
                    if(status == "ok"){
                        layer.msg(data.msg, {icon:6,time:2000},function () {
                            location.reload();
                        });
                    }else{
                        layer.msg(data.msg,{icon:5,time:2000});
                    }
                },
                error:function(XMLHttpRequest, textStatus, errorThrown) {
                    layer.msg('操作错误，请重试!',{icon:5,time:3000});
                }
            });
        });
    });
}