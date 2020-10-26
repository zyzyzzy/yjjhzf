var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#settleList'
        , url: $("#settleList").attr("dataurl")
        , toolbar: '#showtoolbar'
        , defaultToolbar: ['filter']
        , cols: [[
            {type: 'numbers', title:'ID',width:100}
            , {field: 'memberid', title: '商户号', width: 100}
            , {field: 'ordernumber', title: '系统订单号', width: 200}
            , {field: 'userordernumber', title: '用户订单号', width: 210}
            , {field: 'ordermoney', title: '结算金额', width: 100}
            , {field: 'moneytrade', title: '手续费', width: 100}
            , {field: 'money', title: '到账金额', width: 90}
            , {field: 'status', title: '结算状态', templet: '#orderstatus', width: 90}
            , {field: 'refundstatus', title: '退款状态', templet: '#orderrefundstatus', width: 100}
            // , {field: 'deduction_type', title: '扣款方式', templet: '#deduction_type', width: "6%"}
            , {field: 'bankname', title: '银行名称', width: 100}
            // ,{field:'bankzhiname', title:'支行名称'}
            // ,{field:'bankcode', title:'银行编码'}
            // ,{field:'banknumber', title:'联行号'}
            , {field: 'bankcardnumber', title: '银行卡号', width: 180}
            , {field: 'bankusername', title: '开户名', templet: '#bankusername', width: 100}
            , {field: 'phonenumber', title: '手机号', templet: '#phonenumber', width: 130}
            // ,{field:'province', title:'开户省'}
            // ,{field:'city', title:'开户市'}
            , {field: 'applytime', title: '申请时间', width: 160}
            // , {field: 'dealtime', title: '处理时间', templet: '#dealtime', width: "9%"}

            , {field: 'settleid', title: '操作', templet: '#caozuo', fixed: 'right', width: 200}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});
//记载日期插件
layui.use('laydate', function () {
    var laydate = layui.laydate;
    //执行一个laydate实例
    laydate.render({
        elem: '#apply_start' //指定元素
        , type: 'datetime'
    });
    //执行一个laydate实例
    laydate.render({
        elem: '#apply_end' //指定元素
        , type: 'datetime'
    });
    //执行一个laydate实例
    laydate.render({
        elem: '#deal_start' //指定元素
        , type: 'datetime'
    });
    //执行一个laydate实例
    laydate.render({
        elem: '#deal_end' //指定元素
        , type: 'datetime'
    });
});
//搜索
function searchbutton() {
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
    layui.use('table', function () {
        tableIns.reload({
            where: {
                memberid: $("#memberid").val()
                , bankname: $("#bankname").val()
                , status: $("#status").val()
                , refundstatus: $("#refundstatus").val()
                , ordernumber: $("#ordernumber").val()
                , userordernumber: $("#userordernumber").val()
                , shangjiaid: $("#shangjiaid").val()
                , apply_start: $("#apply_start").val()
                , apply_end: $("#apply_end").val()
                , deal_start: $("#deal_start").val()
                , deal_end: $("#deal_end").val()
                , bankusername: $("#searchBankUser").val()
                , phonenumber: $("#searchPhone").val()
                , identitynumber: $("#identityNumber").val()
                , bankcardnumber: $("#bankcardNumber").val()
                ,money_start: $("#money_start").val()
                ,money_end: $("#money_end").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}
//导出记录
function settlelistdownload() {
    str = "";
    $(".searchstr").each(function (index, element) {
        str = str + $(this).attr("name") + "=" + $(this).val() + "&";
    });
    str = str.substr(0, str.length - 1);
    url = $('#settlelistdownload').attr("url");
    window.open(url + "?" + str);
}