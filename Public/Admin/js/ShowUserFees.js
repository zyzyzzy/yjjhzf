function searchbutton() {
    layui.use('table', function () {
        tableIns.reload({
            where: {
                payapiid: $("#payapiid").val(),
                userid: $("#userid").val()
            }
            , page: {
                curr: 1
            }
        });
    });
}
