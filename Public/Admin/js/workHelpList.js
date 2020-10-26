var tableIns;
layui.use(['table','laydate'], function () {
    var table = layui.table;
    var laydate = layui.laydate;
    var form = layui.form;
    tableIns = table.render({
        elem: '#workHelpList'
        ,url: $("#workHelpList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID',width:70}
            , {type: 'checkbox',width:50}
            , {field: 'title', title: '标题(关键字)',width:300}
            , {field: 'content', title: '问题内容'}
            , {field: 'date_time', title: '创建时间',width:200}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right',width:130}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });

    laydate.render({
        elem: '#start'
        ,type: 'datetime'
    });

    laydate.render({
        elem: '#end'
        ,type: 'datetime'
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
                title: $("#title").val()
                , start: $("#start").val()
                , end: $("#end").val()
            }
            , page: {
                curr: 1
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
            var checkStatus = table.checkStatus('workHelpList')
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
                    if(data.status == "ok"){
                        layer.msg(data.msg, {icon:6,time:3000},function () {
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