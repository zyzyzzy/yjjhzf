// 点击登录
$('#adminLogin').click(function () {
    datastr = "";
    $(".addeditinput").each(function () {
        datastr += $(this).attr("name") + "=" + $(this).val() + "&";
    });
    var login_url = $('#loginUrl').val();
    var index_url = $('#indexUrl').val();
    $.ajax({
        url: login_url,
        type: 'post',
        data: datastr,
        dataType: 'json',
        success: function (res) {
            //2019-3-19 任梦龙：修改
            if (res.status == 'ok') {
                layer.msg(res.msg, {
                    icon: 6, time: 1500
                }, function () {
                    //跳转到后台首页面
                    window.location.href = index_url;
                });
            }else {
                layer.msg(res.msg, {icon: 5, time: 1500});
                //2019-3-7 任梦龙：当报错时，自动刷新验证码
                var old_src = $('#verifyImg').attr('src');
                $('#verifyImg').attr('src', old_src + '?' + Math.random());
                return false;
            }
        }
    });
    return false;
});

if (window != top) {
    top.location.href = location.href;
}