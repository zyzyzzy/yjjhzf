 var tableIns;
  layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
 tableIns =  table.render({
      elem: '#commissionList'
      ,url: $("#commissionList").attr("dataurl")
      ,cols: [[
         {type:'numbers'}
         ,{field:'transid', title:'订单号'}
         ,{field:'changemoney', title:'提成金额（元）'}
         ,{field:'datetime', title:'订单时间'}
         ,{field:'remarks', title:'备注'}
      ]]
      ,page: true
      ,text: {
        none: '无数据'
      }
      ,method: 'post'
    });
  });
  layui.use('laydate', function(){
        var laydate = layui.laydate;
        //执行一个laydate实例
        laydate.render({
          elem: '#start' //指定元素
            ,type:'datetime'
        });

        //执行一个laydate实例
        laydate.render({
            elem: '#end' //指定元素
            ,type:'datetime'
        });
      });

 function searchbutton(){
    layui.use('table',function(){
       tableIns.reload({
         where: {
             transid: $("#transid").val()
           ,start: $("#start").val()
           ,end: $("#end").val()
         }
         ,page: {
           curr: 1
         }
       });
    });
  }
