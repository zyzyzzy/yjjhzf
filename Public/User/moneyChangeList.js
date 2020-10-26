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
              {type: 'numbers', title:'ID', width:100}
              ,{field:'type', title:'类型', width: 100}
              ,{field:'transid', title:'订单号',templet:'#trans_id', width: 350}
              ,{field:'oldmoney', title:'原金额', width: 120}
              ,{field:'changemoney', title:'变动金额', width: 120}
              ,{field:'nowmoney', title:'变动后金额', width: 120}
              ,{field:'datetime', title:'变动时间', width: 160}
              ,{field:'payapiname', title:'通道名称',templet:'#payapiname', width: 150}
              // ,{field:'tcusername', title:'提成用户名',templet:'#tcusername', width: "7%"}
              // ,{field:'tcdengji', title:'提成等级',templet:'#tcdengji', width: "6%"}
              ,{field:'remarks', title:'备注',templet:'#remarks'}
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
     laydate.render({
         elem: '#start'
         ,type: 'datetime'
     });
     laydate.render({
         elem: '#end'
         ,type: 'datetime'
     });
 });

  function searchbutton(){
      layui.use('table',function(){
          tableIns.reload({
              where: {
                  username: $("#username").val()
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