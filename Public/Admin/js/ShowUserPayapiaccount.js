var tableIns;
layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
    tableIns =  table.render({
        elem: '#PayapiAccountList'
        ,url: $("#PayapiAccountList").attr("dataurl")
        ,cellMinWidth: 80
        ,title: '通道账号'
        ,cols: [[
            {type:'numbers'}
            ,{field:'bieming', title:'账号别名'}
            ,{field:'payapishangjianame', title:'所属商家'}
            ,{field:'id', title:'当前通道' ,templet:'#showpayapiname'}
            ,{field:'memberid', title:'商户号'}
            ,{field:'cbfeilv', title:'成本费率',sort: true}
            ,{field:'feilv', title:'默认费率',sort: true}
            ,{field:'money', title:'总限额',sort: true}
             ,{field:'id', title:'当前通道限额',sort: true , templet:'#showpayapimoney'}
            ,{field: 'id', title: '删除', templet: '#caozuo', fixed: 'right'}
        ]]
        ,page: true
        ,text: {
            none: '无数据'
        }
        ,method: 'post'
    });
});

function searchbutton(){
    layui.use('table',function(){
       tableIns.reload({
         where: {
           bieming: $("#bieming").val()
           ,memberid: $("#memberid").val()
           ,account: $("#account").val()
           ,payapishangjiaid: $("#payapishangjiaid").val()
           ,moneytypeclassid: $("#moneytypeclassid").val()
           ,status: $("#status").val()
         }
         ,page: {
           curr: 1
         }
       });
    });
  }

function huoqudata(id){
    ajaxurl = $("#PayapiAccountList").attr("houquurl");
    datastr = "id=" + id + "&payapiid=" + $("#PayapiAccountList").attr("payapiid");
    var money = 0;
    $.ajax({
        type:'POST',
        url:ajaxurl,
        data:datastr,
        dataType:'text',
       async:false,
        success:function(str){
            money = str;

        },
        error:function(XMLHttpRequest, textStatus, errorThrown) {
        }
    });
    return money;
}