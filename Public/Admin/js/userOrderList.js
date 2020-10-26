//用户自助通道账号交易记录js
var tableIns;
  layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
 tableIns =  table.render({
      elem: '#userOrderList'
      ,url: $("#userOrderList").attr("dataurl")
     ,where:{
         start: $("#start").val()
         ,end: $("#end").val()
     }
      ,toolbar: '#showtoolbar'
      ,defaultToolbar: ['filter']
      ,cols: [[
         {type:'numbers', title:'ID',width:100}
         ,{field:'username', title:'用户名',width:100}
         ,{field:'memberid', title:'商户号',width:100}
         ,{field:'userordernumber', title:'用户订单号',width:220}
         ,{field:'new_ordermoney', title:'提交金额',width:100}
         ,{field:'new_true_ordermoney', title:'实际金额',templet:'#true_ordermoney',width:100}
         // ,{field:'new_moneytrade', title:'手续费',width:'6%'}
         ,{field:'id', title:'冻结金额',templet:'#freezemoney',width:100}
         ,{field:'new_money', title:'到账金额',width:100}
         ,{field:'payapiname', title:'通道名称',templet:'#payapiname',width:130}
         ,{field:'accountname', title:'通道账号',templet:'#accountname',width:130}
         ,{field:'status', title:'<span id="tips_status" style="cursor: pointer">状态 <i class="layui-icon layui-icon-about"></i></span>',templet:'#orderstatus',width:100}
         ,{field:'complaint', title:'<span id="tips_complaint" style="cursor: pointer">投诉状态 <i class="layui-icon layui-icon-about"></i></span>',templet:'#complaintstatus',width:110}
         ,{field:'type', title:'<span id="tips_type" style="cursor: pointer">类型 <i class="layui-icon layui-icon-about"></i></span>',templet:'#type',width:100}
         ,{field:'datetime', title:'订单时间',width:160,sort:true}
         ,{field:'successtime', title:'成功时间',templet:'#successtime',width:160,sort:true}
         ,{field:'orderid', title:'操作',templet:'#caozuo',width:110,fixed: 'right'}
      ]]
      ,page: true
      ,text: {
        none: '无数据'
      }
      ,method: 'post'
     ,done:function (res,curr,count) {
         if(res.data.length>0){
             $('#sum_ordermoney').text(res.data[0].sum_ordermoney);
             $('#sum_costmoney').text(res.data[0].sum_costmoney);
             $('#sum_trademoney').text(res.data[0].sum_trademoney);
             $('#sum_money').text(res.data[0].sum_money);
             $('#sum_freezemoney').text(res.data[0].sum_freezemoney);
             $('#count_success').text(res.data[0].count_success);
             $('#count_fail').text(res.data[0].count_fail);
             $('#count_test').text(res.data[0].count_test);
             $('#success_rate').text(res.data[0].success_rate);
         }else{
             $('#sum_ordermoney').text(0);
             $('#sum_costmoney').text(0);
             $('#sum_trademoney').text(0);
             $('#sum_money').text(0);
             $('#sum_freezemoney').text(0);
             $('#count_success').text(0);
             $('#count_fail').text(0);
             $('#count_test').text(0);
             $('#success_rate').text(0);
         }
         //控制饼形图
         var deg = { percent: 100, left: 225, leftBC: "#ccc", right: 225, rightBC: "#ccc" };
         var percent= $("#success_rate").text();
         percent = percent.replace('%','');
         loadPercent(percent);
         function loadPercent(percent) {
             var allDeg = countDegByPercent(percent);
             if (allDeg >= 180) {
                 var tmpDeg = allDeg - 180;
                 deg.left = 45 + tmpDeg;
                 deg.right = 225;
                 deg.leftBC = "#5fb878";
                 deg.rightBC = "#5fb878";
             } else {
                 deg.right = 45 + allDeg;
                 deg.rightBC = "#5fb878";
                 deg.leftBC = "#ccc";
             }
             $(".circleProgress.rightcircle").css({ "-webkit-transform": "rotate(" + deg.right + "deg)", "border-left": "14px solid " + deg.rightBC, "border-bottom": "14px solid " + deg.rightBC });
             $(".circleProgress.leftcircle").css({ "-webkit-transform": "rotate(" + deg.left + "deg)", "border-top": "14px solid " + deg.leftBC, "border-right": "14px solid " + deg.leftBC });
         }
         function countDegByPercent(percent) {
             return percent * 3.6;
         }
     }
    });

      //监听锁定操作
      form.on('switch(typecheckbox)', function(obj){
          ajaxurl = $(this).attr("ajaxurl");
          $.ajax({
              type:'POST',
              url:ajaxurl,
              dataType:'json',
              success:function(data){
                  if(data.status == "ok"){
                      layer.msg(data.msg,{icon:6,time:2000},function() {
                          location.reload();
                      })
                  }else{
                      layer.msg(data.msg,{icon:5,time:1500})
                  }

              },
              error:function(XMLHttpRequest, textStatus, errorThrown) {
                  layer.msg('操作错误,请重试',{icon:5,time:1500})
              }
          });
      });
  });

  //记载日期插件
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
      laydate.render({
          elem: '#success_start' //指定元素
          ,type: 'datetime'
      });
      laydate.render({
          elem: '#success_end' //指定元素
          ,type: 'datetime'
      });
      });

 $(document).on("click","th[data-field=type]",function(){
     var msg = '当类型改为测试时，该条数据不计入统计<br>修改完成后请手动刷新该页面，才能更新统计数据';
     layerTips(msg,'tips_type',15000);
 });

 $(document).on("click","th[data-field=complaint]",function(){
     var msg = '点击进去可修改订单投诉状态;关掉弹框后请手动刷新该页面才能显示最新数据';
     layerTips(msg,'tips_complaint',15000);
 });

 $(document).on("click","th[data-field=status]",function(){
     var msg = '1.未支付：订单已生成，但用户并未完成支付<br>2.已付未返：订单已经成功支付，系统已回调了用户，但未收到用户的响应<br>3.已付已返：订单已经成功支付，系统已回调了用户，用户收到回调后已正常响应系统';
     layerTips(msg,'tips_status',30000);
 });

  //搜索
 function searchbutton(){
    layui.use('table',function(){
       tableIns.reload({
           where: {
             memberid: $("#memberid").val()
           ,userip: $("#userip").val()
           ,status: $("#status").val()
           ,complaint: $("#complaint").val()
           ,ordertype: $("#ordertype").val()
           ,userordernumber: $("#userordernumber").val()
           ,sysordernumber: $("#sysordernumber").val()
           ,shangjiaid: $("#shangjiaid").val()
           ,payapiid: $("#payapiid").val()
           ,accountid: $("#accountid").val()
           ,start: $("#start").val()
           ,end: $("#end").val()
           ,success_start: $("#success_start").val()
           ,success_end: $("#success_end").val()
           ,money_start: $("#money_start").val()
           ,money_end: $("#money_end").val()
           ,users_id: $("#users_id").val()
         }
         ,page: {
           curr: 1
         }
       });
    });
  }

//改变订单类型
function changeType(obj,orderid,type) {
     var msg = '';
     if(type==0){
         msg = "确认要将此订单改为测试订单吗？";
     }else{
         msg = "确认要将此订单改为交易订单吗？";
     }
    layer.confirm(msg,function (index) {
            var ajaxurl = $(obj).attr("ajaxurl");
            $.ajax({
                type:"POST",
                url: ajaxurl,
                dataType: "json",
                data:{orderid:orderid,type:type},
                success:function (data) {
                    if(data.status == 'ok'){
                        layer.msg("订单类型修改成功，请重刷页面",{icon:6,time:3000},function(){
                            // location.reload();
                        });
                    }
                }
            })
        })
}

//导出记录
  function orderlistdownload() {
      str = "";
      $(".searchstr").each(function(index,element){
          str = str+$(this).attr("name")+"="+$(this).val()+"&";
      });
      str = str.substr(0,str.length-1);
      url = $('#orderlistdownload').attr("url");
      window.open(url+"?"+str);
  }

 //订单验证
 function orderVerify(obj,orderid) {
     var ajaxurl = $(obj).attr("ajaxurl");
     $.ajax({
         type:"POST",
         url: ajaxurl,
         dataType: "json",
         data:{orderid:orderid},
         success:function (data) {
             if(data.stat == 'ok'){
                 layer.msg(data.msg,{icon:6,time:10000},function(){
                     // location.reload();
                 });
             }else{
                 layer.msg(data.msg,{icon:5,time:10000});
             }

         }
     })
 }

 //订单补单
 function orderSupplement(obj,orderid) {
     var ajaxurl = $(obj).attr("ajaxurl");
     $.ajax({
         type:"POST",
         url: ajaxurl,
         dataType: "json",
         data:{orderid:orderid},
         success:function (data) {
             if(data.stat == 'ok'){
                 layer.msg(data.msg,{icon:6,time:10000},function(){
                     // location.reload();
                 });
             }else{
                 layer.msg(data.msg,{icon:5,time:10000});
             }

         }
     })
 }