  var tableIns;
  layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
 tableIns =  table.render({
      elem: '#payapiList'
      ,url: $("#payapiList").attr("dataurl")
      ,cols: [[
         {type:'numbers',title: 'ID',width:70}
        // ,{field: 'payapiname', title: '通道名称'}
        ,{field: 'payapiclassname', title: '通道分类名称'}
        ,{field: 'payapiclassbm', title: '通道分类编码'}
         ,{field: 'order_feilv', title: '交易费率',templet:'#feilv'}
         ,{field: 'order_min_feilv', title: '单笔最低手续费（元）'}
         ,{field: 'status', title: '状态',templet:'#payapistatus'}
      ]]
      ,page: true
      ,text: {
        none: '无数据'
      }
      ,method: 'post'
    });
  });

 function searchbutton(){
    layui.use('table',function(){
       tableIns.reload({
         where: {
           payapiclassid: $("#payapiclassid").val()
         }
         ,page: {
           curr: 1
         }
       });
    });

  }

 

  

