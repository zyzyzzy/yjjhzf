 var tableIns;
  layui.use('table', function() {
      var table = layui.table;
      var form = layui.form;
      tableIns = table.render({
          elem: '#moneyChangeList'
          , url: $("#moneyChangeList").attr("dataurl")
          ,toolbar: '#showtoolbar'
          ,defaultToolbar: ['filter']
          , cols: [[
              {type: 'numbers', title:'ID',width:100}
              ,{field:'memberid', title:'商户号', width: 100}
              ,{field:'transid', title:'系统订单号', width: 310,templet:'#orderid'}
              ,{field:'type', title:'类型', width: 110}
              ,{field:'oldmoney', title:'原金额', width: 120}
              ,{field:'changemoney', title:'变动金额', width: 120}
              ,{field:'nowmoney', title:'变动后金额', width: 120}
              ,{field:'payapiname', title:'通道名称', width: 180,templet:'#payapiname'}
              ,{field:'accountname', title:'账号名称', width: 180,templet:'#accountname'}
              ,{field:'datetime', title:'变动时间', width: 160}
              ,{field:'remarks', title:'备注',templet:'#remarks', width: 150}
          ]]
          , page: true
          , text: {
              none: '无数据'
          }
          , method: 'post'
      });


  });
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
 });

  function searchbutton(){
      layui.use('table',function(){
          tableIns.reload({
              where: {
                  username: $("#username").val()
                  ,memberid: $("#memberid").val()
                  ,transid: $("#transid").val()
                  ,payapiid: $("#payapiid").val()
                  ,daifuid: $("#daifuid").val()
                  ,accountid: $("#accountid").val()
                  ,type: $("#type").val()
                  ,start: $("#start").val()
                  ,end: $("#end").val()
              }
              ,page: {
                  curr: 1
              }
          });
      });
  }

 //导出记录
 function changelistdownload() {
     str = "";
     $(".searchstr").each(function(index,element){
         str = str+$(this).attr("name")+"="+$(this).val()+"&";
     });
     str = str.substr(0,str.length-1);
     url = $('#changelistdownload').attr("url");
     window.open(url+"?"+str);
 }

