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
         ,{field:'date_time', title:'添加时间',width:'14%'}
         // ,{field:'dzsj_day', title:'到账天数',width:'5%'}
         // ,{field:'id', title:'节假日',templet:'#jiejia',width:'10%'}
         // ,{field:'dzbl', title:'到账百分比'}
         ,{field:'expect_time', title:'预计到账时间',width:'14%'}
         ,{field:'actual_time', title:'实际到账时间',width:'14%'}
         ,{field:'id', title:'冻结类型',templet:'#freeze_type',width:'8%'}
         ,{field:'id', title:'是否解冻',templet:'#unfreeze',width:'8%'}
         ,{field:'id', title:'解冻类型',templet:'#unfreeze_type',width:'8%'}
         ,{field:'id', title:'操作',templet:'#caozuo'}
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
                  }
              }
          })
      })
  }
