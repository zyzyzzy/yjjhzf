var tableIns;
  layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
 tableIns =  table.render({
      elem: '#MoneyTypeList'
      ,url: $("#MoneyTypeList").attr("dataurl")
      ,cellMinWidth: 80
      ,cols: [[
        {type:'numbers', title: 'ID', width: 70}
        ,{field:'moneytypename', title:'冻结方案名称'}
        ,{field:'dzsj_day', title:'到账天数',templet:'#checktype_day'}
        ,{field:'jiejiar', title:'节假日',templet:'#checktype_jjr'}
        ,{field: 'dzsj_time', title: '到账时间',templet:'#checktype_time'}
        ,{field: 'dzbl', title: '到账百分比',templet:'#dzblbfb'}
        ,{field: 'datetime',title: '添加时间',sort: true}
        ,{field: 'id', title: '操作', templet: '#caozuo', fixed: 'right', width: 100}
      ]]
      ,page: true
      ,text: {
        none: '无数据'
      }
      ,method: 'post'
    });
  });

  function member_del(obj,id){
        layer.confirm('确认要删除吗？',function(index){
            //发异步删除数据
            ajaxurl = $(obj).attr("ajaxurl");
            moneyclass_id = $(obj).attr("data-id");
                $.ajax({
                    type:'POST',
                    url:ajaxurl,
                    data:{id:id,moneyclass_id:moneyclass_id},
                    dataType:'json',
                    success:function(data){
                        if(data.status == "no_id"){
                            layer.msg(data.msg,{icon:2,time:1500});
                        }
                        if(data.status == "on_use"){
                            layer.msg(data.msg,{icon:2,time:2000});
                        }
                        if(data.status == "ok"){
                                $(obj).parents("tr").remove();
                                layer.msg(data.msg,{icon:1,time:1000});
                            }else{
                                layer.msg(data.msg,{icon:2,time:1000});
                            }
                        },
                    error:function(XMLHttpRequest, textStatus, errorThrown) {
                        alert("error");
                        }
                    });
        });
    }









  