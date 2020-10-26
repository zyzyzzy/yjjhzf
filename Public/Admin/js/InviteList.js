var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#InviteList'
        , url: $("#InviteList").attr("dataurl")
        , toolbar: '#showtoolbar'
        , defaultToolbar: ['filter']
        , cols: [[
            {type: 'numbers', title: 'ID', width: 70}
            , {type: 'checkbox', width: 50}
            , {field: 'invite_code', title: '邀请码', width: 320}
            , {field: 'reg_address', title: '注册地址', templet: '#showregaddress', width: 100}
            , {field: 'reg_type', title: '注册类型', templet: '#showregtype', width: 100}
            , {field: 'status', title: '状态', templet: '#showstatus', width: 100}
            , {field: 'make_name', title: '发布者', width: 100}
            , {field: 'type', title: '发布者类型', templet: '#showmaketype', width: 100}
            , {field: 'user_name', title: '使用者', width: 100}
            , {field: 'par_name', title: '所属上级', width: 100}
            , {field: 'create_time', title: '注册时间', sort: true,width: 160}
            , {field: 'over_time', title: '过期时间', sort: true,width: 160}
            , {field: 'use_time', title: '使用时间', sort: true, templet: '#showusetime',width: 160}
            , {field: 'id', title: '操作', templet: '#caozuo', width: 100, fixed: 'right'}
        ]]
        , page: true
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
                invite_code: $("#inviteCode").val()
                , user_name: $("#userName").val()
                , reg_type: $("#regType").val()
                , status: $("#allStatus").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}
