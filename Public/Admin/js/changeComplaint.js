var tableIns;
layui.use('table', function(){
    var table = layui.table;
    var form = layui.form;
    tableIns =  table.render({
        elem: '#changeComplaint'
        ,url: $("#changeComplaint").attr("dataurl")
        ,cols: [[
            {type:'numbers'}
            ,{field:'admin_name', title:'管理员' , templet:'#admin_name',width:'15%'}
            ,{field:'old_status', title:'原始状态' , templet:'#old_status',width:'12%'}
            ,{field:'change_status', title:'改后状态' , templet:'#change_status',width:'12%'}
            ,{field:'date_time', title:'改变时间' ,width:'22%'}
            ,{field:'remarks', title:'备注' , templet:'#remarks'}
        ]]
        ,page: false
        ,text: {
            none: '无数据'
        }
        ,method: 'post'
    });
});
