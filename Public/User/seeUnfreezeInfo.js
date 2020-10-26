  var tableIns;
  layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;

 tableIns =  table.render({
      elem: '#seeUnfreezeInfo'
      ,url: $("#seeUnfreezeInfo").attr("dataurl")
     ,toolbar: '#showtoolbar'
     ,defaultToolbar: ['filter']
      ,cols: [[
         {type:'numbers'}
         ,{field:'type', title:'类型',templet:'#type'}
         ,{field:'before_time', title:'改前时间'}
         ,{field:'after_time', title:'改后时间'}
         ,{field:'date_time', title:'记录时间'}
         ,{field:'adminuser_name', title:'管理员用户名'}
         ,{field:'adminuser_ip', title:'管理员ip'}
      ]]
      ,page: false
      ,text: {
        none: '无数据'
      }
      ,method: 'post'
    });
  });

