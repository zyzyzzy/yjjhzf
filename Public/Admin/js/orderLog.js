 var tableIns;
  layui.use('table', function() {
      var table = layui.table;
      var form = layui.form;
      tableIns = table.render({
          elem: '#orderLog'
          , url: $("#orderLog").attr("dataurl")
          ,toolbar: '#showtoolbar'
          ,defaultToolbar: ['filter']
          , cols: [[
              {type: 'numbers', title:'ID',width:100}
              , {field: 'sys_order_num', title: '系统订单号',width:320}
              , {field: 'user_order_num', title: '用户订单号',width:230,templet:'#userordernum'}
              , {field: 'username', title: '用户名',width:150}
              , {field: 'content_log', title: '内容'}
              , {field: 'at_time', title: '记录时间',width:160}
          ]]
          , page: true
          , text: {
              none: '无数据'
          }
          , method: 'post'
      });
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
  });
  function searchbutton(){
      layui.use('table',function(){
          tableIns.reload({
              where: {
                  sys_order_num: $("#sys_order_num").val()
                  ,user_order_num: $("#user_order_num").val()
                  ,username: $("#username").val()
                  ,start: $("#start").val()
                  ,end: $("#end").val()
              }
              ,page: {
                  curr: 1
              }
          });
      });
  }

 function changelistdownload() {
     str = "";
     $(".searchstr").each(function(index,element){
         str = str+$(this).attr("name")+"="+$(this).val()+"&";
     });
     str = str.substr(0,str.length-1);
     url = $('#changelistdownload').attr("url");
     window.open(url+"?"+str);
 }