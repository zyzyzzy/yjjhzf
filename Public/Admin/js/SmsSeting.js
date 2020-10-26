var tableIns;
layui.use('table', function() {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#SmsSeting'
        , url: $("#SmsSeting").attr("dataurl")
        , cols: [[
            {type: 'numbers', title:'ID',width:70}
            , {field: 'app_name', title: '短信接口名'}
            , {field: 'mode_code', title: '模板号'}
            , {field: 'created_at', title: '添加时间'}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right',width:100}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});

function searchbutton(){
    layui.use('table',function(){
        tableIns.reload({
            where: {
                app_name: $("#app_name").val()

            }
            ,page: {
                curr: 1
            }
        });
    });
}

function member_del(obj,id){
    layer.confirm('确认要删除吗？',function(index){
        //发异步删除数据
        ajaxurl = $(obj).attr("ajaxurl");
        datastr = "id=" + id;
        $.ajax({
            type:'POST',
            url:ajaxurl,
            data:datastr,
            dataType:'text',
            success:function(str){
                if(str == "ok"){
                    $(obj).parents("tr").remove();
                    layer.msg('已删除!',{icon:1,time:1000});
                }else{
                    layer.msg('删除失败!',{icon:2,time:1000});
                }
            },
            error:function(XMLHttpRequest, textStatus, errorThrown) {
                alert("error");
            }
        });
    });
}
