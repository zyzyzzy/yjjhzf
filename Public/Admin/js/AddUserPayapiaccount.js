var tableIns;
layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
    tableIns =  table.render({
        elem: '#PayapiAccountList'
        ,url: $("#PayapiAccountList").attr("dataurl")
        ,cellMinWidth: 80
        ,title: '通道账号'
        ,cols: [[
            {type:'checkbox'}
            ,{field:'bieming', title:'账号别名'}
            ,{field:'payapishangjianame', title:'所属商家'}
            ,{field:'memberid', title:'商户号'}
        ]]
        ,page: true
        ,text: {
            none: '无数据'
        }
        ,method: 'post'
    });
        //监听锁定操作
});

function AddAll (mythis) {
    layer.confirm('确认添加选中的账号吗？',function(index){
        //捉到所有被选中的，发异步进行删除
        layui.use('table',function(){
            var table = layui.table;
            var checkStatus = table.checkStatus('PayapiAccountList');
                data = checkStatus.data;
            idstr = "";
            for(j = 0; j < data.length; j++) {
                idstr += data[j]["id"]+",";
            }
            idstr = idstr.substr(0,idstr.length-1);
　　　　　　if(idstr == ""){
                 layer.msg('没有选中任何账号!',{icon:2,time:1000});
                 return;
            }
            ajaxurl = $(mythis).attr("ajaxurl");
            datastr = "idstr=" + idstr;
            $.ajax({
                type:'POST',
                url:ajaxurl,
                data:datastr,
                dataType:'text',
                success:function(str){
                    if(str == "ok"){
                        layer.confirm("通道账号添加成功！", {
                            btn: ['确认'] //按钮
                        }, function () {
                                parent.location.reload();
                        });
                    }else{
                        layer.msg('通道账号添加失败!',{icon:2,time:1000});
                    }

                },
                error:function(XMLHttpRequest, textStatus, errorThrown) {

                }
            });
        });
    });

}