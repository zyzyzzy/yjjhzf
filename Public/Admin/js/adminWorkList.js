//系统后台工单js
var tableIns;
layui.use(['table','laydate'], function () {
    var table = layui.table;
    var laydate = layui.laydate;
    var form = layui.form;
    tableIns = table.render({
        elem: '#adminWorkList'
        ,url: $("#adminWorkList").attr("dataurl")
        , cols: [[
            {type: 'numbers', title: 'ID',width:70}
            , {type: 'checkbox',width:50}
            , {field: 'work_num', title: '<span id="tips_num" style="cursor: pointer">工单编号 <i class="layui-icon layui-icon-about"></i></span>',width:250, templet: '#worknum'}
            , {field: 'username', title: '用户名',width:150}
            , {field: 'title', title: '标题'}
            , {field: 'status', title: '状态', templet: '#status',width:120}
            , {field: 'date_time', title: '创建时间',sort: true}
            , {field: 'updatetime', title: '完结时间', templet: '#updatetime',sort: true}
            , {field: 'id', title: '<span id="tips_caozuo" style="cursor: pointer">操作 <i class="layui-icon layui-icon-about"></i></span>', templet: '#caozuo', fixed: 'right',width:150}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });

    laydate.render({
        elem: '#start'
        ,type: 'datetime'
    });

    laydate.render({
        elem: '#end'
        ,type: 'datetime'
    });
});

$(document).on("click","th[data-field=work_num]",function(){
    var msg = '编号后面如果跟上红色*,表示此条工单已被用户删除';
    layerTips(msg,'tips_num',8000);
});
$(document).on("click","th[data-field=id]",function(){
    var msg = '查看:点击后可查看此工单的沟通记录等;<br>'
        +'添加:点击后可将此工单添加到帮助文档,可添加已解决并且帮助文档中不存在的工单,尽量不要添加含用户隐私信息的工单;<br>'
        +'删除:点击后删除此工单,可删除帮助文档中不存在的工单,尽量只删除用户已删除的工单;<br>'
        +'转交:点击后可提醒系统管理员,对于无法解释的问题可用此功能转交给我们系统来处理;<br>';
    layerTips(msg,'tips_caozuo',30000);
});

/*
 * 页面搜索功能
 * where（传参）:前面为字段名，后面为字段值
 */
function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                work_num: $("#work_num").val()
                , user_name: $("#user_name").val()
                , title: $("#title").val()
                , status: $("#statusSearch").val()
                , user_del: $("#delSearch").val()
                , start: $("#start").val()
                , end: $("#end").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}

//批量删除
function delAll (obj) {
    layer.confirm('确认要删除吗？',function(index){
        //捉到所有被选中的，发异步进行删除
        layui.use('table',function(){
            var table = layui.table;
            var checkStatus = table.checkStatus('adminWorkList')
                ,data = checkStatus.data;
            idstr = "";
            for(j = 0; j < data.length; j++) {
                idstr += data[j]["id"]+",";
            }
            id_str = idstr.substr(0,idstr.length-1);  //id的字符串
            ajaxurl = $(obj).attr("ajaxurl");
            datastr = "id_str=" + id_str;
            $.ajax({
                type:'POST',
                url:ajaxurl,
                data:datastr,
                dataType:'json',
                success:function(data){
                    if(data.status == "ok"){
                        layer.msg(data.msg, {icon:6,time:3000},function () {
                            location.reload();
                        });
                    }else{
                        layer.msg(data.msg,{icon:5,time:5000});
                    }
                },
                error:function(XMLHttpRequest, textStatus, errorThrown) {
                    layer.msg('操作错误，请重试!',{icon:5,time:3000});
                }
            });
        });
    });
}