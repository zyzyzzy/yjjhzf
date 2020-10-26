// 2019-1-14 任梦龙：添加系统银行回收站列表js
var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#recoveryBank'
        , url: $("#recoveryBank").attr("dataurl")
        , cellMinWidth: 80
        , cols: [[
            {type: 'numbers'}
            , {type: 'checkbox'}
            , {field: 'bankname', title: '银行名称'}
            , {field: 'bankcode', title: '银行编码', sort: true}
            , {field: 'id', title: '操作', templet: '#caozuo'}
        ]]
        , page: true
        , text: {
            none: '无数据'
        }
        , method: 'post'
    });
});

function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                bankname: $("#bankname").val()
                , bankcode: $("#bankcode").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}
//xadmin.js中已存在共用的批量删除方法，现在不需要用了，但还是先留着，以防共用方法有错误
// function delAll(mythis) {
//     layer.confirm('删除后无法恢复，确认吗？', function (index) {
//         //捉到所有被选中的，发异步进行删除
//         layui.use('table', function () {
//             var table = layui.table;
//             var checkStatus = table.checkStatus('recoveryBank')
//                 , data = checkStatus.data;
//             // json = JSON.stringify(data);
//             idstr = "";
//             for (j = 0; j < data.length; j++) {
//                 idstr += data[j]["id"] + ",";
//             }
//             idstr = idstr.substr(0, idstr.length - 1);
//             //////////////////////////////////////////////////////////
//             ajaxurl = $(mythis).attr("ajaxurl");
//             datastr = "idstr=" + idstr;
//             // alert(ajaxurl+"-----"+datastr);
//             $.ajax({
//                 type: 'POST',
//                 url: ajaxurl,
//                 data: datastr,
//                 dataType: 'json',
//                 success: function (data) {
//                     var str = removeTrimLine(data.status);
//                     if (str == "ok") {
//                         layer.msg(data.msg, {icon: 6, time: 2000});
//                         $(".layui-form-checked").not('.header').parents('tr').remove();
//                     } else {
//                         layer.msg(data.msg, {icon: 5, time: 2000});
//                     }
//                 },
//                 error: function (XMLHttpRequest, textStatus, errorThrown) {
//                     layer.msg('请求失败，请检查！', {icon: 5, time: 2000});
//                 }
//             });
//             /////////////////////////////////////////////////////////
//         });
//     });
// }