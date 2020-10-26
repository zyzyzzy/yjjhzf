var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#viewUserOpt'
        , url: $("#viewUserOpt").attr("dataurl")
        , cols: [[
            {type: 'numbers'}
            , {type: 'checkbox'}
            , {field: 'menu_title', title: '菜单名称', templet: '#showmenutitle', width: 250}
            , {field: 'controller', title: '控制器名称', templet: '#showcontroller', width: 200}
            , {field: 'action', title: '方法路径', templet: '#showaction', width: 200}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right'}
        ]]
        , page: true
        , limit: 10
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});

/*
 * 页面搜索功能
 * where（传参）:前面为字段名，后面为字段值
 */
function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                menu_title: $("#menuTitle").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}

//批量删除
function delAllInfo(mythis, table_id) {
    layer.confirm('确认要删除吗？', function (index) {
        //捉到所有被选中的，发异步进行删除
        layui.use('table', function () {
            var table = layui.table;
            var checkStatus = table.checkStatus(table_id)
                , data = checkStatus.data;
            // json = JSON.stringify(data);
            idstr = "";
            for (j = 0; j < data.length; j++) {
                idstr += data[j]["id"] + ",";
            }
            idstr = idstr.substr(0, idstr.length - 1);
            //////////////////////////////////////////////////////////
            ajaxurl = $(mythis).attr("ajaxurl");
            datastr = "idstr=" + idstr;
            // alert(ajaxurl+"-----"+datastr);
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: datastr,
                dataType: 'json',
                success: function (data) {
                    //2019-1-18 任梦龙：添加权限判断
                    if(data.status == 'no_auth'){
                        layer.msg(data.msg, {icon: 5, time: 2000});
                        return false;
                    }
                    if (data.status == "ok") {
                        layer.msg(data.msg, {icon: 6, time: 2000});
                        $(".layui-form-checked").not('.header').parents('tr').remove();
                    } else {
                        layer.msg(data.msg, {icon: 5, time: 2000});
                        return false;
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    layer.msg('请求失败，请检查！', {icon: 5, time: 2000});
                }
            });
            /////////////////////////////////////////////////////////
        });
    });
}