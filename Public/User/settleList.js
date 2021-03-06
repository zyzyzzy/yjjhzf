 var tableIns;
  layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
 tableIns =  table.render({
      elem: '#settleList'
      ,url: $("#settleList").attr("dataurl")
      ,toolbar: '#showtoolbar'
      ,defaultToolbar: ['filter']
      ,cols: [[
         {type:'numbers', title:'ID',width:100}
         ,{field:'userordernumber', title:'用户订单号',templet:'#user_ordernumber', width: 210}
         // ,{field:'type', title:'类型',templet:'#ordertype', width: "4%"}
         ,{field:'ordermoney', title:'结算金额', width: 100}
         ,{field:'moneytrade', title:'手续费', width: 100}
         ,{field:'money', title:'到账金额', width: 100}
         ,{field:'deduction_type', title:'扣款方式',templet:'#deduction_type', width: 90}
         ,{field:'status', title:'结算状态',templet:'#orderstatus', width: 90}
         ,{field:'refundstatus', title:'退款状态',templet:'#orderrefundstatus', width: 90}
         ,{field:'bankname', title:'银行名称', width: 100}
         ,{field:'bankcardnumber', title:'银行卡号', width: 180}
         ,{field:'bankusername', title:'开户名',templet:'#bankusername', width: 100}
         ,{field:'phonenumber', title:'手机号',templet:'#phonenumber', width: 120}
         ,{field:'applytime', title:'申请时间', width: 160}
         ,{field:'dealtime', title:'处理时间',templet:'#dealtime', width: 160}
         ,{field:'settleid', title:'操作',templet:'#caozuo', fixed: 'right', width: 110}
      ]]
      ,page: true
      ,text: {
        none: '无数据'
      }
      ,method: 'post'
    });
  });

  //记载日期插件
  layui.use('laydate', function(){
        var laydate = layui.laydate;
        //执行一个laydate实例
        laydate.render({
          elem: '#apply_start' //指定元素
          ,type: 'datetime'
        });
        //执行一个laydate实例
        laydate.render({
          elem: '#apply_end' //指定元素
          ,type: 'datetime'
        });
      //执行一个laydate实例
      laydate.render({
          elem: '#deal_start' //指定元素
          ,type: 'datetime'
      });
      //执行一个laydate实例
      laydate.render({
          elem: '#deal_end' //指定元素
          ,type: 'datetime'
      });
  });

  //搜索
 function searchbutton(){
     var tj_start = $("#apply_start").val();
     var tj_end = $("#apply_end").val();
     if(tj_start > tj_end){
         layer.msg('申请时间：开始时间不得大于或等于结束时间',{icon: 5,time: 2000});
         return false;
     }

     var su_start = $("#deal_start").val();
     var su_end = $("#deal_end").val();
     if(su_start > su_end){
         layer.msg('处理时间：开始时间不得大于或等于结束时间',{icon: 5,time: 2000});
         return false;
     }
    layui.use('table',function(){
       tableIns.reload({
         where: {
           bankname: $("#bankname").val()
           ,status: $("#status").val()
           ,refundstatus: $("#refundstatus").val()
           ,userordernumber: $("#userordernumber").val()
           ,apply_start: $("#apply_start").val()
           ,apply_end: $("#apply_end").val()
           ,deal_start: $("#deal_start").val()
           ,deal_end: $("#deal_end").val()
             ,bankusername: $("#searchBankUser").val()
             ,phonenumber: $("#searchPhone").val()
             ,identitynumber: $("#identityNumber").val()
             ,bankcardnumber: $("#bankcardNumber").val()
             ,money_start: $("#money_start").val()
             ,money_end: $("#money_end").val()
         }
         ,page: {
           curr: 1
         }
       });
    });
  }

//导出记录
  function settlelistdownload() {
      str = "";
      $(".searchstr").each(function(index,element){
          str = str+$(this).attr("name")+"="+$(this).val()+"&";
      });
      str = str.substr(0,str.length-1);
      url = $('#settlelistdownload').attr("url");
      window.open(url+"?"+str);
  }

  //退款
 function refund(obj,settle_id) {
        layer.confirm('确定要申请退款吗?',function () {
            var url = $(obj).attr('ajaxurl');
            $.ajax({
                url: url,
                type: 'post',
                data:{settle_id:settle_id},
                dataType: 'json',
                success: function (data) {
                    if (data.status == 'ok') {
                        layer.msg(data.msg, {icon: 6, time: 2000},function () {
                            location.reload();
                        });
                    }else{
                        layer.msg(data.msg, {icon: 5, time: 2000});
                    }
                }
            });
        })
 }