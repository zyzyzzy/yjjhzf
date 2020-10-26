var tableIns;
layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
    tableIns =  table.render({
        elem: '#EditPayapiaccountMoney'
        ,url: $("#EditPayapiaccountMoney").attr("dataurl")
        ,cols: [[
            {type:'checkbox'}
            ,{field:'zh_payname', title:'通道名称', templet:'#zh_payname'}
            ,{field:'money', title:'每日交易总额' , templet:'#editMoney'}
        ]]
        ,page: false
        ,text: {
            none: '无数据'
        }
        ,method: 'post'
    });
});