 var tableIns;
  layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
 tableIns =  table.render({
      elem: '#userFreezeMoneyList'
      ,url: $("#userFreezeMoneyList").attr("dataurl")
     ,toolbar: '#showtoolbar'
     ,defaultToolbar: ['filter']
      ,cols: [[
         {type:'numbers'}
         ,{field:'freeze_money', title:'冻结金额',width:'8%'}
         ,{field:'date_time', title:'添加时间',width:'14%'}
         ,{field:'expect_time', title:'预计到账时间',width:'14%'}
         ,{field:'freezeordernumber', title:'冻结订单号',width:'28%'}
         // ,{field:'userordernumber', title:'用户订单号',width:'15%'}
         // ,{field:'sysordernumber', title:'系统订单号',width:'20%'}
         ,{field:'id', title:'冻结类型',templet:'#freeze_type',width:'8%'}
         ,{field:'id', title:'操作',templet:'#caozuo', fixed: 'right'}
      ]]
      ,page: true
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
                  console.log(data);
                  if(data.stat == 'ok'){
                      layer.msg('金额手动解冻成功',{icon:6,time:1500},function(){
                          // location.reload();
                          var parenturl = $(obj).attr("parenturl");
                          parent.location.href=parenturl;
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
                     layer.msg(data.msg,{icon:6,time:1500},function () {
                         location.reload();
                     });
                 }else{
                     layer.msg(data.msg,{icon:5,time:1500})
                 }
             }
         })
     })
 }