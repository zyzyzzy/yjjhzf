  var tableIns;
  layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
 tableIns =  table.render({
      elem: '#PayapiClass'
      ,url: $("#PayapiClass").attr("dataurl")
      ,cols: [[
         {type:'numbers', title:'ID',width:70}
        ,{field:'classname', title:'分类名称',width:150}
        ,{field:'classbm', title:'分类编码',width:120}
         ,{field:'order_feilv', title:'默认运营费率', templet: '#feilv'}
         ,{field:'order_min_feilv', title:'单笔最低手续费（元）', templet: '#order_min_feilv'}
         ,{field:'img_url', title:'分类图片', templet: '#img'}
         ,{field:'status', title:'状态', templet: '#showstatus'}
         ,{field:'pc', title:'PC', templet: '#showpc'}
         ,{field:'wap', title:'WAP', templet: '#showwap'}
        ,{field: 'id', title: '操作', templet: '#caozuo', fixed: 'right',width:100}
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
          datastr = "id=" + this.value + "&status=" + (obj.elem.checked?1:0);
          $.ajax({
              type:'POST',
              url:ajaxurl,
              data:datastr,
              dataType:'json',
              success:function(data){
                  if(data.status == "ok"){
                      layer.tips(data.msg, obj.othis,{
                          tips: [2,'#5FB878']
                      });
                  }else{
                      layer.tips(data.msg, obj.othis,{
                          tips: [3,'#FF5722']
                      });
                  }
              },
              error:function(XMLHttpRequest, textStatus, errorThrown) {
                  layer.tips('操作错误,请重试', obj.othis,{
                      tips: [3,'#FF5722']
                  });
              }
          });
      });

      //pc端是否显示
      form.on('switch(pccheckbox)', function(obj){
          ajaxurl = $(this).attr("ajaxurl");
          datastr = "id=" + this.value + "&pc=" + (obj.elem.checked?1:0);
          $.ajax({
              type:'POST',
              url:ajaxurl,
              data:datastr,
              dataType:'json',
              success:function(data){
                  if(data.status == "ok"){
                      layer.tips(data.msg, obj.othis,{
                          tips: [2,'#5FB878']
                      });
                  }else{
                      layer.tips(data.msg, obj.othis,{
                          tips: [3,'#FF5722']
                      });
                  }
              },
              error:function(XMLHttpRequest, textStatus, errorThrown) {
                  layer.tips('操作错误,请重试', obj.othis,{
                      tips: [3,'#FF5722']
                  });
              }
          });
      });

      //手机端端是否显示
      form.on('switch(wapcheckbox)', function(obj){
          ajaxurl = $(this).attr("ajaxurl");
          datastr = "id=" + this.value + "&wap=" + (obj.elem.checked?1:0);
          $.ajax({
              type:'POST',
              url:ajaxurl,
              data:datastr,
              dataType:'json',
              success:function(data){
                  if(data.status == "ok"){
                      layer.tips(data.msg, obj.othis,{
                          tips: [2,'#5FB878']
                      });
                  }else{
                      layer.tips(data.msg, obj.othis,{
                          tips: [3,'#FF5722']
                      });
                  }
              },
              error:function(XMLHttpRequest, textStatus, errorThrown) {
                  layer.tips('操作错误,请重试', obj.othis,{
                      tips: [3,'#FF5722']
                  });
              }
          });
      });
  });

  //2019-4-18 rml:添加搜索条件
  function searchbutton() {
      layui.use('table', function () {
          tableIns.reload({
              where: {
                  classname: $("#classname").val()
                  , classbm: $("#classbm").val()
                  , status: $("#status").val()
              }
              , page: {
                  curr: 1
              }
          });
      });
  }

  

  

   

  



