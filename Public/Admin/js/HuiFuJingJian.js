var tableIns;
layui.use('table', function () {
    var table = layui.table;
    var form = layui.form;
    tableIns = table.render({
        elem: '#PayapiList'
        , url: $("#PayapiList").attr("dataurl")
        , toolbar: '#showtoolbar'
        , defaultToolbar: ['filter']
        , cols: [[
           // {type: 'numbers', title: 'ID', width: 70}
            // , {type: 'checkbox', width: 50}
             {field: 'request_id', title: '请求ID',width: 280}
            , {field: 'dls_id', title: '所属代理商'}
            , {field: 'mer_name', title: '企业名称',width:200}
            , {field: 'license_code', title: '营业执照'}
            // , {field: 'legal_name', title: '法人'}
            // , {field: 'legal_idno', title: '法人证件号码'}
            , {field: 'cont_name', title: '联系人'}
            , {field: 'cont_phone', title: '联系人手机号'}
            // , {field: 'card_id_mask', title: '结算银行卡'}
            , {field: 'status_name', title: '进件状态'}
            , {field: 'id', title: '操作', templet: '#caozuo', fixed: 'right', width: 350}
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
                request_id: $("#request_id").val()
                , mer_name: $("#mer_name").val()
                , license_code: $("#license_code").val()
                , cont_phone: $("#cont_phone").val()
                , dls_id: $("#dls_id").val()
                , status: $("#status").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}

 

  

