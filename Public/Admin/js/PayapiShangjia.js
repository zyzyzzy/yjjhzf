layui.use('table', function(){
	var form = layui.form;
 //监听锁定操作
    form.on('switch(payapiclass)', function(obj){
            ajaxurl = $(this).attr("ajaxurl");
            datastr = "payapishangjiaid=" + this.value + "&payapiclassid="+ $(this).attr("payapiclassid") +"&status=" + (obj.elem.checked?1:0);
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

                    }
                });
    });
  });

  function checkedjs(str,payapiclassid){
  	var obj = JSON.parse(str);
  	for(var i in obj){
  	   if(obj[i]["payapiclassid"] == payapiclassid){
	   	  return obj[i]["status"]==1?true:false;
	   }
  	}
  	return false;
  }

   