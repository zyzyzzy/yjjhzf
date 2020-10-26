 var tableIns;
  layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
 tableIns =  table.render({
      elem: '#childOrderList'
      ,url: $("#childOrderList").attr("dataurl")
      ,toolbar: '#showtoolbar'
      ,defaultToolbar: ['filter']
      ,cols: [[
         {type:'numbers', title:'ID',width:100}
         ,{field:'memberid', title:'商户号',width:150}
         ,{field:'username', title:'用户名',width:150}
         ,{field:'userordernumber', title:'用户订单号',width:230}
         ,{field:'status', title:'<span id="tips_status" style="cursor: pointer">状态 <i class="layui-icon layui-icon-about"></i></span>',templet:'#orderstatus',width:100}
         ,{field:'new_ordermoney', title:'订单金额',width:150}
         ,{field:'tc_money', title:'提成金额',width:150}
         ,{field:'payapiclassname', title:'通道分类',templet:'#payapiclassname',width:150}
         ,{field:'datetime', title:'订单时间',width:180}
         ,{field:'successtime', title:'成功时间',templet:'#successtime',width:180}
         ,{field:'orderid', title:'操作',templet:'#caozuo', fixed: 'right',width:90}
      ]]
      ,page: true
      ,text: {
        none: '无数据'
      }
      ,method: 'post'
     ,done:function (res,curr,count) {
         console.log(res);
         if(res.data.length>0){
             $('#sum_ordermoney').text(res.data[0].sum_ordermoney);
             $('#sum_tcmoney').text(res.data[0].sum_tcmoney);
         }else{
             $('#sum_ordermoney').text(0);
             $('#sum_tcmoney').text(0);
         }
     }
    });
  });

  //记载日期插件
  layui.use('laydate', function(){
        var laydate = layui.laydate;
        //执行一个laydate实例
        laydate.render({
          elem: '#start' //指定元素
          ,type: 'datetime'
        });

        //执行一个laydate实例
        laydate.render({
          elem: '#end' //指定元素
          ,type: 'datetime'
        });

      laydate.render({
          elem: '#success_start' //指定元素
          ,type: 'datetime'
      });

      laydate.render({
          elem: '#success_end' //指定元素
          ,type: 'datetime'
      });
      });

 $(document).on("click","th[data-field=type]",function(){
     // var msg = '当类型改为测试时，该条数据不计入统计<br>修改完成后请手动刷新该页面，才能更新统计数据';
     var msg = '当订单类型为测试时，该条数据不计入统计';
     layerTips(msg,'tips_type',15000);
 });

 $(document).on("click","th[data-field=status]",function(){
     var msg = '1.未支付：订单已生成，但用户并未完成支付<br>2.已付未返：订单已经成功支付，系统已回调了用户，但未收到用户的响应<br>3.已付已返：订单已经成功支付，系统已回调了用户，用户收到回调后已正常响应系统';
     layerTips(msg,'tips_status',30000);
 });

  //搜索
 function searchbutton(){
    layui.use('table',function(){
       tableIns.reload({
         where: {
             username: $("#username").val()
           ,status: $("#status").val()
           ,userordernumber: $("#userordernumber").val()
           ,start: $("#start").val()
           ,end: $("#end").val()
           ,success_start: $("#success_start").val()
           ,success_end: $("#success_end").val()
         }
         ,page: {
           curr: 1
         }
       });
    });

  }

 //导出记录
 function orderlistdownload() {
     str = "";
     $(".searchstr").each(function(index,element){
         str = str+$(this).attr("name")+"="+$(this).val()+"&";
     });
     str = str.substr(0,str.length-1);
     url = $('#orderlistdownload').attr("url");
     window.open(url+"?"+str);
 }