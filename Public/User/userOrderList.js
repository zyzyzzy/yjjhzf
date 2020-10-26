// 用户自身的自助账号交易记录js
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
         ,{field:'memberid', title:'商户号',width:100}
         ,{field:'userordernumber', title:'用户订单号',width:215}
         ,{field:'status', title:'<span id="tips_status" style="cursor: pointer">状态 <i class="layui-icon layui-icon-about"></i></span>',templet:'#orderstatus',width:100}
         ,{field:'type', title:'<span id="tips_type" style="cursor: pointer">类型 <i class="layui-icon layui-icon-about"></i></span>',templet:'#type',width:80}
         // ,{field:'sysordernumber', title:'系统订单号'}
         // ,{field:'username', title:'用户名'}
         ,{field:'new_ordermoney', title:'提交金额',width:100,sort:true}
         ,{field:'new_true_ordermoney', title:'实际金额',templet:'#true_ordermoney',width:100}
         // ,{field:'traderate', title:'交易费率'}
         // ,{field:'moneytrade', title:'订单手续费'}
         ,{field:'new_money', title:'到账金额',width:100}
         ,{field:'id', title:'冻结金额',templet:'#freezemoney',width:100}
         ,{field:'payapiname', title:'通道名称',templet:'#payapiname',width:120}
         ,{field:'accountname', title:'通道账号',templet:'#accountname',width:150}
         ,{field:'userip', title:'提交地址IP',width:150}
         ,{field:'datetime', title:'订单时间',width:160,sort:true}
         ,{field:'successtime', title:'成功时间',templet:'#successtime',width:160,sort:true}
         ,{field:'orderid', title:'操作',templet:'#caozuo', fixed: 'right',width:78}
      ]]
      ,page: true
      ,text: {
        none: '无数据'
      }
      ,method: 'post'

     ,done:function (res,curr,count) {
         console.log(res);
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

  });

  //记载日期插件
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
      laydate.render({
          elem: '#success_start'
          ,type: 'datetime'
      });
      laydate.render({
          elem: '#success_end'
          ,type: 'datetime'
      });
      });

 $(document).on("click","th[data-field=type]",function(){
     // var msg = '当类型改为测试时，该条数据不计入统计<br>修改完成后请手动刷新该页面，才能更新统计数据';
     var msg = '当订单类型为测试时，该条数据不计入统计';
     layerTips(msg,'tips_type',15000);
 });

 $(document).on("click","th[data-field=status]",function(){
     var msg = '1.未支付：订单已生成，但用户并未完成支付<br>2.已付未返：订单已经成功支付，系统已回调了用户，但未收到用户的响应<br>3.已付已返：订单已经成功支付，系统已回调了用户，用户收到回调后已正常响应系统';
     layerTips(msg,'tips_status',30000);
 });

  //搜索
 function searchbutton(){
     var tj_start = $("#start").val();
     var tj_end = $("#end").val();
     if(tj_start > tj_end){
         layer.msg('提交时间：开始时间不得大于或等于结束时间',{icon: 5,time: 2000});
         return false;
     }

     var su_start = $("#success_start").val();
     var su_end = $("#success_end").val();
     if(su_start > su_end){
         layer.msg('成功时间：开始时间不得大于或等于结束时间',{icon: 5,time: 2000});
         return false;
     }
    layui.use('table',function(){
       tableIns.reload({
         where: {
            userip: $("#userip").val()
           ,status: $("#status").val()
           ,ordertype: $("#ordertype").val()
           ,userordernumber: $("#userordernumber").val()
           ,start: $("#start").val()
           ,end: $("#end").val()
           ,success_start: $("#success_start").val()
           ,success_end: $("#success_end").val()
           ,money_start: $("#money_start").val()
           ,money_end: $("#money_end").val()
           ,payapiid: $("#payapiid").val()
           ,payapiaccountid: $("#payapiaccountid").val()
         }
         ,page: {
           curr: 1
         }
       });
    });
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