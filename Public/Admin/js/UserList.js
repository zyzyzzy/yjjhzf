  var tableIns;
  layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
 tableIns =  table.render({
      elem: '#UserList'
      ,url: $("#UserList").attr("dataurl")
     ,toolbar: '#showtoolbar'
     ,defaultToolbar: ['filter']
      ,cols: [[
         {type:'numbers',title:'ID', width: 70}
        ,{field:'username', title:'用户名',templet:'#showusername', width: 150}
         ,{field:'bieming', title:'用户别名',templet:'#showbieming', width: 120}
        ,{field:'memberid', title:'商户号',templet:'#showmemberid', width: 120}
        ,{field:'usertype', title:'用户类型', width: 90}
        ,{field:'superiorid', title:'上级用户',templet:'#showsuperiorid', width: 120}
        ,{field:'status', title:'用户状态',templet:'#showuserstatus', width: 90}
        ,{field: 'authentication', title: '认证',templet:'#showauthentication', width: 80}
        ,{field: 'google', title: '谷歌验证',templet:'#google', width: 100}
        ,{field: 'money', title: '可用余额',templet:'#has_money',sort: true, width: 100}
        ,{field: 'freezemoney', title: '冻结金额',templet:'#freeze_money',sort: true, width: 100}
         ,{field: 'id',title: '密钥/域名',templet:'#showmiyaoyuming', width: 90}
         ,{field: 'id', title: '银行卡', templet: '#showyinhangguanli', width: 80}
         ,{field: 'order', title: '交易状态',templet:'#showorderstatus', width: 95}
         ,{field: 'test_status', title: '测试状态',templet:'#showteststatus', width: 90}
         ,{field: 'status', title: '状态',templet:'#showstatusname', width: 95}
         ,{field: 'regdatetime', title: '注册时间',sort: true, width: 160}
        ,{field: 'id', title: '操作', templet: '#caozuo', width: 160, fixed: 'right'}
      ]]
      ,page: true
      ,text: {
        none: '无数据'
      }
      ,method: 'post'
    });

      //监听锁定操作
      form.on('switch(statuscheckbox)', function(obj){
          ajaxurl = $(this).attr("ajaxurl");
          datastr = "id=" + this.value + "&status=" + (obj.elem.checked?2:3);
          $.ajax({
              type:'POST',
              url:ajaxurl,
              data:datastr,
              dataType:'json',
              success:function(data){
                  if (data.status == 'ok') {
                      layer.tips(data.msg, obj.othis, {
                          tips: [2, '#5FB878']
                      });
                  } else {
                      layer.tips(data.msg, obj.othis, {
                          tips: [3, '#FF5722']
                      });
                  }
              },
              error:function(XMLHttpRequest, textStatus, errorThrown) {
                  layer.msg('操作错误，请检查！',{icon:5 ,time:2000});
              }
          });
      });

      //交易状态
      form.on('switch(ordercheckbox)', function(obj){
          ajaxurl = $(this).attr("ajaxurl");
          datastr = "id=" + this.value + "&order=" + (obj.elem.checked?0:1);
          $.ajax({
              type:'POST',
              url:ajaxurl,
              data:datastr,
              dataType:'json',
              success:function(data){
                  if (data.status == 'ok') {
                      layer.tips(data.msg, obj.othis, {
                          tips: [2, '#5FB878']
                      });
                  } else {
                      layer.tips(data.msg, obj.othis, {
                          tips: [3, '#FF5722']
                      });
                  }
              },
              error:function(XMLHttpRequest, textStatus, errorThrown) {
                  layer.msg('操作错误，请检查！',{icon:5 ,time:2000});
              }
          });
      });

      //测试状态
      form.on('switch(teststatuscheckbox)', function(obj){
          ajaxurl = $(this).attr("ajaxurl");
          datastr = "id=" + this.value + "&test_status=" + (obj.elem.checked?0:1);
          $.ajax({
              type:'POST',
              url:ajaxurl,
              data:datastr,
              dataType:'json',
              success:function(data){
                  if (data.status == 'ok') {
                      layer.tips(data.msg, obj.othis, {
                          tips: [2, '#5FB878']
                      });
                  } else {
                      layer.tips(data.msg, obj.othis, {
                          tips: [3, '#FF5722']
                      });
                  }
              },
              error:function(XMLHttpRequest, textStatus, errorThrown) {
                  layer.msg('操作错误，请检查！',{icon:5 ,time:2000});
              }
          });
      });
  });

  layui.use('laydate', function(){
        var laydate = layui.laydate;
        laydate.render({
          elem: '#start'
        });
        laydate.render({
          elem: '#end'
        });
      });

 function searchbutton(){
    layui.use('table',function(){
       tableIns.reload({
         where: {
           username: $("#username").val()
           ,bieming: $("#bieming").val()
           ,status: $("#status").val()
           ,userrengzheng: $("#userrengzheng").val()
           ,usertype: $("#usertype").val()
           ,start: $("#start").val()
           ,end: $("#end").val()
         }
         ,page: {
           curr: 1
         }
       });
    });
  }

  function relationsearchbutton(){
      layui.use('table',function(){
          tableIns.reload({
              where: {
                  order_type: $("#order_type").val()
                  ,order_id: $("#order_id").val()
              }
              ,page: {
                  curr: 1
              }
              ,done:function () {
                  layer.closeAll();
              }
          });
      });
  }

function searchsuperiorid (username) {
	 layui.use('table',function(){
       tableIns.reload({
         where: {
           username: username
         }
         ,page: {
           curr: 1
         }
       });
    });
}

function searchusername(superiorid){
	layui.use('table',function(){
       tableIns.reload({
         where: {
           superiorid: superiorid
         }
         ,page: {
           curr: 1
         }
       });
    });
}
