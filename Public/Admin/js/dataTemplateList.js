var tableIns;
layui.use(['table','laydate'], function () {
    var table = layui.table;
    var laydate = layui.laydate;
    var form = layui.form;
    tableIns = table.render({
        elem: '#dataTemplateList'
        ,url: $("#dataTemplateList").attr("dataurl")
        , cols: [[
            {type: 'numbers'}
            , {type: 'checkbox'}
            , {field: 'admin_user', title: '所属后台', templet: '#admin_user',width:'10%'}
            , {field: 'title', title: '标题',width:'20%'}
            , {field: 'action', title: '方法名',width:'20%'}
            , {field: 'default', title: '默认', templet: '#default',width:'10%'}
            , {field: 'img_name', title: '模板图片', templet: '#img',width:'10%',align:'center'}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right'}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
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
                type: $("#type").val(),
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
        ajaxurl = $(obj).attr("ajaxurl");
        datastr = "id=" + id;
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'json',
            success: function (data) {
                var str = removeTrimLine(data.status);
                if (str == "ok") {
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
    });
}

//批量删除
function delAll (obj) {
    layer.confirm('确认要删除吗？',function(index){
        //捉到所有被选中的，发异步进行删除
        layui.use('table',function(){
            var table = layui.table;
            var checkStatus = table.checkStatus('dataTemplateList')
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
                        // $(".layui-form-checked").not('.header').parents('tr').remove();
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