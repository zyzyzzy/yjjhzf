 var tableIns;
  layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
 tableIns =  table.render({
      elem: '#orderFreezeMoneyList'
      ,url: $("#orderFreezeMoneyList").attr("dataurl")
     ,toolbar: '#showtoolbar'
     ,defaultToolbar: ['filter']
      ,cols: [[
         {type:'numbers'}
         ,{field:'freeze_money', title:'冻结金额',width:'8%'}
         ,{field:'moneytypename', title:'冻结类型',width:'8%'}
         ,{field:'date_time', title:'添加时间',width:'13%'}
         ,{field:'expect_time', title:'预计到账时间',width:'13%'}
         ,{field:'actual_time', title:'实际到账时间',width:'13%'}
         ,{field:'id', title:'冻结类型',templet:'#freeze_type',width:'7%'}
         ,{field:'id', title:'是否解冻',templet:'#unfreeze',width:'7%'}
         ,{field:'id', title:'解冻类型',templet:'#unfreeze_type',width:'7%'}
         ,{field:'id', title:'操作',templet:'#caozuo', fixed: 'right'}
      ]]
      ,page: false
      ,text: {
        none: '无数据'
      }
      ,method: 'post'
    });
  });

  //手动解冻
  function manualUnfreeze(obj,id) {
      var msg = '确定要解冻此金额吗？';
      layer.confirm(msg,function (index) {
          var ajaxurl = $(obj).attr("ajaxurl");
          $.ajax({
              type:"POST",
              url: ajaxurl,
              dataType: "json",
              data:{id:id},
              success:function (data) {
                  if(data.stat == 'ok'){
                      layer.msg("金额解冻成功",{icon:6,time:1500},function(){
                          location.reload();
                      });
                  }else{
                      layer.msg('金额手动解冻失败',{icon:5,time:1500})
                  }
              }
          })
      })
  }

 //补发任务
 function sendTask(obj,id) {
     var msg = '此订单请求自动解冻的定时任务发送失败,是否补发任务请求？';
     layer.confirm(msg,function (index) {
         var ajaxurl = $(obj).attr("ajaxurl");
         $.ajax({
             type:"POST",
             url: ajaxurl,
             dataType: "json",
             data:{id:id},
             success:function (data) {
                 console.log(data);
                 if(data.status == 'ok'){
                     layer.msg(data.msg,{icon:6,time:1500});
                 }else{
                     layer.msg(data.msg,{icon:5,time:1500})
                 }
             }
         })
     })
 }
