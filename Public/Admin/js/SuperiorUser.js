  var tableIns;
  layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
 tableIns =  table.render({
      elem: '#SuperiorUser'
      ,url: $("#SuperiorUser").attr("dataurl")
      ,cols: [[
         {type:'numbers'}
        ,{type:'checkbox'}
        ,{field:'username', title:'用户名',templet:'#showusername'}
        ,{field:'memberid', title:'商户号',sort: true,templet:'#showmemberid',width:150}
        ,{field:'usertype', title:'用户类型'}
        ,{field:'superiorid', title:'上级用户',templet:'#showsuperiorid'}
        ,{field: 'status', title: '状态',templet:'#showstatusname'}
        ,{field: 'authentication', title: '认证',templet:'#showauthentication'}
        ,{field: 'id', title: '手续费',templet:'#sxf'}
        ,{field: 'id',title: '密钥/域名',templet:'#showmiyaoyuming'}
        ,{field: 'id', title: '银行卡管理', templet: '#showyinhangguanli'}
        ,{field: 'regdatetime', title: '注册时间',sort: true,width:180}
        ,{field: 'money', title: '总余额',width:80}
        ,{field: 'id', title: '操作', templet: '#caozuo', fixed: 'right'}
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
                  //判断权限状态码
                  if(data.status == 'no_auth'){
                      layer.tips(data.msg, obj.othis, {
                          tips: [3, '#FF5722']
                      });
                  }
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

              }
          });
      });
  });

  function member_del(obj,id){
        layer.confirm('确认要删除吗？',function(index){
            //发异步删除数据
            ajaxurl = $(obj).attr("ajaxurl");
                datastr = "id=" + id;
                $.ajax({
                    type:'POST',
                    url:ajaxurl,
                    data:datastr,
                    dataType:'text',
                    success:function(str){
                            if(str == "ok"){
                                $(obj).parents("tr").remove();
                                layer.msg('已删除!',{icon:1,time:1000});
                            }else{
                                layer.msg('删除失败!',{icon:2,time:1000});
                            }
                        },
                    error:function(XMLHttpRequest, textStatus, errorThrown) {
                        alert("error");

                        }
                    });
        });
    }

  layui.use('laydate', function(){
        var laydate = layui.laydate;
        //执行一个laydate实例
        laydate.render({
          elem: '#start' //指定元素
        });

        //执行一个laydate实例
        laydate.render({
          elem: '#end' //指定元素
        });
      });

       /*用户-停用*/
      function member_stop(obj,id){
          layer.confirm('确认要停用吗？',function(index){
              if($(obj).attr('title')=='启用'){
                //发异步把用户状态进行更改
                $(obj).attr('title','停用')
                $(obj).find('i').html('&#xe62f;');
                $(obj).parents("tr").find(".td-status").find('span').addClass('layui-btn-disabled').html('已停用');
                layer.msg('已停用!',{icon: 5,time:1000});
              }else{
                $(obj).attr('title','启用')
                $(obj).find('i').html('&#xe601;');
                $(obj).parents("tr").find(".td-status").find('span').removeClass('layui-btn-disabled').html('已启用');
                layer.msg('已启用!',{icon: 5,time:1000});
              }
          });
      }

      function delAll (argument) {
        var data = tableCheck.getData();
        layer.confirm('确认要删除吗？'+data,function(index){
            //捉到所有被选中的，发异步进行删除
            layer.msg('删除成功', {icon: 1});
            $(".layui-form-checked").not('.header').parents('tr').remove();
        });
      }

 function searchbutton(){
    layui.use('table',function(){
       tableIns.reload({
         where: {
           username: $("#username").val()
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