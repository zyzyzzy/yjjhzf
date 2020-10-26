//注册
$('#regWeb').click(function () {
    datastr = "";
    $(".addeditinput").each(function () {
        datastr += $(this).attr("name") + "=" + $(this).val() + "&";
    });
    var reg_url = $('#regUrl').val();
    var index_url = $('#indexUrl1').val();
    $.ajax({
        url: reg_url,
        type: 'post',
        data: datastr,
        dataType: 'json',
        success: function (data) {
            if (data.status == 'ok') {
                layer.msg(data.msg, {icon: 6}, function () {
                    window.location.href = index_url;
                });
            } else {
                layer.msg(data.msg, {icon: 5});
                var old_src = $('#verifyImg').attr('src');
                $('#verifyImg').attr('src', old_src + '?' + Math.random());
            }
        },
        error: function () {
            layer.msg('操作错误，请重试', {icon: 5});
        }
    });

});

//发送手机短信
var time = 5;

function sendPhone(_this) {
    var phone = $('#reg_phone').val();  //手机号
    if (phone == false) {
        layer.msg('手机不得为空', {icon: 5, time: 1500});
        return false;
    }
    if (time == 5) {

        var url = 'sendPhone';
        $.ajax({
            type: 'post',
            url: url,
            data: {phone: phone},
            dataType: 'json',
            success: function (data) {
                if (data.error_code == 0) {
                    layer.msg(data.reason, {icon: 6, time: 1500});
                } else {
                    layer.msg(data.reason, {icon: 5, time: 1500});
                }
            }
        });
    }
    if (time == 0) {

        _this.removeAttribute('disabled');
        _this.value = '获取手机验证码';
        $('#getPhone').removeClass('color_phone');
        time = 5;
        return false;
    } else {
        _this.setAttribute('disabled', true);
        _this.value = '重新发送' + time + 's';
        $('#getPhone').addClass('color_phone');
        time--;
    }
    setTimeout(function () {
        sendPhone(_this);
    }, 1000);
}

//登录
$('#subLogin').click(function () {
    datastr = "";
    $(".addeditinput").each(function () {
        datastr += $(this).attr("name") + "=" + $(this).val() + "&";
    });
    var login_url = $('#loginUrl').val();  //请求路径
    var index_url = $('#indexUrl2').val();   //主页面路径
    $.ajax({
        type: 'post',
        url: login_url,
        data: datastr,
        dataType: 'json',
        success: function (data) {
            if (data.status == 'ok') {
                layer.msg(data.msg, {icon: 6, time: 1000}, function () {
                    window.location.href = index_url;
                });
            } else {
                layer.msg(data.msg, {icon: 5});
                var old_src = $('#verifyImg').attr('src');
                $('#verifyImg').attr('src', old_src + '?' + Math.random());
                return false;
            }
        },
        error: function () {
            layer.msg('操作错误，请重试', {icon: 5});
        }
    });
});

if (window != top) {
    top.location.href = location.href;
}
