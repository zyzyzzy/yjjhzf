  var tableIns;
  layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;

 tableIns =  table.render({
      elem: '#daifuList'
      ,url: $("#daifuList").attr("dataurl")
      ,cols: [[
         {type:'numbers'}
        // ,{field: 'payapiname', title: '通道名称'}
        ,{field: 'daifu_name', title: '通道名称'}
         ,{field: 'settle_feilv', title: '结算费率',templet:'#feilv'}
         ,{field: 'settle_min_feilv', title: '单笔最低手续费（元）'}
         ,{field: 'status', title: '状态',templet:'#payapistatus'}
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
            dataType:'text',
            success:function(str){
                    if(str == "ok"){
                        layer.tips('修改成功', obj.othis,{
                            tips: [2,'#5FB878']
                        });
                    }else{
                        layer.tips('修改失败', obj.othis,{
                            tips: [3,'#FF5722']
                        });
                    }
                },
            error:function(XMLHttpRequest, textStatus, errorThrown) {

                }
            });
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

 

  

