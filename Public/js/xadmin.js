$(function () {
    //加载弹出层
    layui.use(['form', 'element'],
        function () {
            layer = layui.layer;
            element = layui.element;
        });

    //触发事件
    var tab = {
        tabAdd: function (title, url, id) {
            //新增一个Tab项
            element.tabAdd('xbs_tab', {
                title: title
                ,
                content: '<iframe tab-id="' + id + '" frameborder="0" src="' + url + '" scrolling="yes" class="x-iframe"></iframe>'
                ,
                id: id
            })
        }
        , tabDelete: function (othis) {
            //删除指定Tab项
            element.tabDelete('xbs_tab', '44'); //删除：“商品管理”


            othis.addClass('layui-btn-disabled');
        }
        , tabChange: function (id) {
            //切换到指定Tab项
            element.tabChange('xbs_tab', id); //切换到：用户管理
        }
    };


    tableCheck = {
        init: function () {
            $(".layui-form-checkbox").click(function (event) {
                if ($(this).hasClass('layui-form-checked')) {
                    $(this).removeClass('layui-form-checked');
                    if ($(this).hasClass('header')) {
                        $(".layui-form-checkbox").removeClass('layui-form-checked');
                    }
                } else {
                    $(this).addClass('layui-form-checked');
                    if ($(this).hasClass('header')) {
                        $(".layui-form-checkbox").addClass('layui-form-checked');
                    }
                }

            });
        },
        getData: function () {
            var obj = $(".layui-form-checked").not('.header');
            var arr = [];
            obj.each(function (index, el) {
                arr.push(obj.eq(index).attr('data-id'));
            });
            return arr;
        }
    }

    //开启表格多选
    tableCheck.init();


    $('.container .left_open i').click(function (event) {
        if ($('.left-nav').css('left') == '0px') {
            $('.left-nav').animate({left: '-221px'}, 100);
            $('.page-content').animate({left: '0px'}, 100);
            $('.page-content-bg').hide();
        } else {
            $('.left-nav').animate({left: '0px'}, 100);
            $('.page-content').animate({left: '221px'}, 100);
            if ($(window).width() < 768) {
                $('.page-content-bg').show();
            }
        }

    });

    $('.page-content-bg').click(function (event) {
        $('.left-nav').animate({left: '-221px'}, 100);
        $('.page-content').animate({left: '0px'}, 100);
        $(this).hide();
    });

    $('.layui-tab-close').click(function (event) {
        $('.layui-tab-title li').eq(0).find('i').remove();
    });

    $("tbody.x-cate tr[fid!='0']").hide();
    // 栏目多级显示效果
    $('.x-show').click(function () {
        if ($(this).attr('status') == 'true') {
            $(this).html('&#xe625;');
            $(this).attr('status', 'false');
            cateId = $(this).parents('tr').attr('cate-id');
            $("tbody tr[fid=" + cateId + "]").show();
        } else {
            cateIds = [];
            $(this).html('&#xe623;');
            $(this).attr('status', 'true');
            cateId = $(this).parents('tr').attr('cate-id');
            getCateId(cateId);
            for (var i in cateIds) {
                $("tbody tr[cate-id=" + cateIds[i] + "]").hide().find('.x-show').html('&#xe623;').attr('status', 'true');
            }
        }
    })

    //左侧菜单效果
    // $('#content').bind("click",function(event){
    $('.left-nav #nav li').click(function (event) {

        if ($(this).children('.sub-menu').length) {
            if ($(this).hasClass('open')) {
                $(this).removeClass('open');
                $(this).find('.nav_right').html('&#xe697;');
                $(this).children('.sub-menu').stop().slideUp();
                $(this).siblings().children('.sub-menu').slideUp();
            } else {
                $(this).addClass('open');
                $(this).children('a').find('.nav_right').html('&#xe6a6;');
                $(this).children('.sub-menu').stop().slideDown();
                $(this).siblings().children('.sub-menu').stop().slideUp();
                $(this).siblings().find('.nav_right').html('&#xe697;');
                $(this).siblings().removeClass('open');
            }
        } else {

            var url = $(this).children('a').attr('_href');
            var title = $(this).find('cite').html();
            var index = $('.left-nav #nav li').index($(this));

            for (var i = 0; i < $('.x-iframe').length; i++) {
                if ($('.x-iframe').eq(i).attr('tab-id') == index + 1) {
                    tab.tabChange(index + 1);
                    event.stopPropagation();
                    return;
                }
            }
            ;

            tab.tabAdd(title, url, index + 1);
            tab.tabChange(index + 1);
        }

        event.stopPropagation();

    })

})
var cateIds = [];

function getCateId(cateId) {

    $("tbody tr[fid=" + cateId + "]").each(function (index, el) {
        id = $(el).attr('cate-id');
        cateIds.push(id);
        getCateId(id);
    });
}

/*弹出层*/

/*
 参数解释：
 title   标题
 url     请求的url
 id      需要操作的数据id
 w       弹出层宽度（缺省调默认值）
 h       弹出层高度（缺省调默认值）
 */
function x_admin_show(title, url, w, h) {
    if (title == null || title == '') {
        title = false;
    }
    ;
    if (url == null || url == '') {
        url = "404.html";
    }
    ;
    if (w == null || w == '') {
        w = ($(window).width() * 0.9);
    }
    ;
    if (h == null || h == '') {
        h = ($(window).height() - 50);
    }
    ;

    layer.open({
        type: 2,
        area: [w + 'px', h + 'px'],
        fix: false, //不固定
        maxmin: false,
        shadeClose: true,
        shade: 0.4,
        title: title,
        content: url
    });

}

/**
 * 2019-1-17 任梦龙：添加弹出层，后面所有的弹出层都用此函数，，且原来的函数后期也得慢慢全部修改过来，便于判断权限
 */
function y_admin_show(title, url, w, h) {
    if (title == null || title == '') {
        title = false;
    }
    ;
    if (url == null || url == '') {
        url = "404.html";
    }
    ;
    if (w == null || w == '') {
        w = ($(window).width() * 0.9);
    }
    ;
    if (h == null || h == '') {
        h = ($(window).height() - 50);
    }
    ;
    $.ajax({
        url: url,
        type: 'get',
        dataType: 'json',
        success: function (data) {
            if (data.status == 'no_auth') {
                layer.msg(data.msg, {icon: 5, time: 1500});
                return false;
            }
            //2019-2-18 任梦龙：添加判断:如果点击时非法操作
            if (data.status == 'no') {
                layer.msg(data.msg, {icon: 5, time: 1500});
                return false;
            }
        },
        error: function () {
            layer.open({
                type: 2,
                area: [w + 'px', h + 'px'],
                fix: false, //不固定
                maxmin: false,
                shadeClose: true,
                shade: 0.4,
                title: title,
                content: url
            });
        }
    });


}

/*关闭弹出框口*/
function x_admin_close() {
    var index = parent.layer.getFrameIndex(window.name);
    parent.layer.close(index);
}

function FormSumit(ajaxurl, fromname = "", sx = true, w = "true",mythis='') {
    if(mythis){
        $(mythis).addClass('layui-btn-disabled').attr('disabled',true);
    }
    datastr = "";
    $(fromname + ".addeditinput").each(function () {
        datastr += $(this).attr("name") + "=" + $(this).val() + "&";
    });
    $.ajax({
        type: 'post',
        url: ajaxurl,
        data: datastr,
        datatype: 'json',
        success: function (obj) {
            //2019-1-17  任梦龙：添加权限识别码
            if (obj.status == 'no_auth') {
                layer.msg(obj.msg, {icon: 5, time: 1500});
                return false;
            }
            if (obj.status == "ok") {
                if (sx) {
                    layer.confirm(obj.msg, {
                        btn: ['确认'] //按钮
                    }, function () {
                        if (!w) {
                            location.reload();
                        } else {
                            parent.location.reload();
                        }
                    });
                } else {
                    layer.msg(obj.msg, {icon: 1});
                    if(mythis){
                        $(mythis).removeClass('layui-btn-disabled').attr('disabled',false);
                    }
                }
            } else {
                layer.msg(obj.msg, {icon: 5});
                if(mythis){
                    $(mythis).removeClass('layui-btn-disabled').attr('disabled',false);
                }
            }
        },
        error: function (XMLHttpRequest, textStauts, errorThrown) {
            layer.msg('操作错误，请检查！', {icon: 5, time: 1500});
            return false;
        }
    });
}

//layer.tips函数  参数：msg:提示信息  id:关联元素的id  time:时间，单位是微秒
function layerTips(msg, id, time) {
    layer.tips(msg, '#' + id, {
        tips: [1, '#0FA6D8', '100px'], //设置tips方向和颜色 类型：Number/Array，默认：2 tips层的私有参数。支持上右下左四个方向，通过1-4进行方向设定。如tips: 3则表示在元素的下面出现。有时你还可能会定义一些颜色，可以设定tips: [1, '#c00']
        tipsMore: false, //是否允许多个tips 类型：Boolean，默认：false 允许多个意味着不会销毁之前的tips层。通过tipsMore: true开启
        area: ['auto'],
        time: time  //微秒，time秒后销毁，还有其他的基础参数可以设置。。。。这里就不添加了
    });
}


function delete_del(obj, id) {
    layer.confirm('确认要删除吗？', function (index) {
        //发异步删除数据
        /******************************************************/
        ajaxurl = $(obj).attr("ajaxurl");
        datastr = "id=" + id;
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'text',
            success: function (str) {
                // 2019-1-9 任梦龙：处理空格与换行，添加on_use状态码，表示正在使用中
                var str = removeTrimLine(str);
                if (str == 'on_use') {
                    layer.msg('正在使用中，暂不能删除!', {icon: 2, time: 2000});
                    return false;
                }
                if (str == "ok") {
                    $(obj).parents("tr").remove();
                    layer.msg('已删除!', {icon: 1, time: 1000});
                } else {
                    layer.msg('删除失败!', {icon: 2, time: 1000});
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                alert("error");
            }
        });
        /*****************************************************/

    });
}

//2019-1-15  任梦龙：添加单条删除方法，json格式    后面写的全部用这个，且原来的也需要修改过来，方便统一用json格式的
function delete_info(obj, id) {
    layer.confirm('确认要删除吗？', function (index) {
        //发异步删除数据
        /******************************************************/
        ajaxurl = $(obj).attr("ajaxurl");
        datastr = "id=" + id;
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'json',
            success: function (data) {
                //2019-3-6 任梦龙：删除no_auth  on_use标识，因为除了ok,都是错误提示
                //2019-4-18 rml:由于固定宽度后，$(obj).parents("tr").remove();不起效了，所以直接刷新页面
                if (data.status == 'ok') {
                    // $(obj).parents("tr").remove();
                    layer.msg(data.msg, {icon: 6, time: 1500},function(){
                        location.reload();
                    });
                } else {
                    layer.msg(data.msg, {icon: 5, time: 2000});
                    return false;
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                layer.msg('操作错误，请检查', {icon: 5, time: 2000});
            }
        });
        /*****************************************************/

    });
}
//2019-3-6 任梦龙：删除生成邀请码和时间选择器（overTime）的方法，移到页面，因为邀请码只会在当前操作，而这个js文件是全局的

//去空格，去换行
function removeTrimLine(str) {
    var res_str = str.replace(/[\r\n]/g, "");//去掉回车换行
    return res_str;
}

// 2019-1-14 任梦龙：添加删除单条记录的共用js
function delActualInfo(obj, id) {
    layer.confirm('删除后无法恢复，确认吗？', function (index) {
        //发异步删除数据
        /******************************************************/
        ajaxurl = $(obj).attr("ajaxurl");
        datastr = "id=" + id;
        //  alert(ajaxurl+"----"+datastr);
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'json',
            success: function (data) {
                if (data.status == 'no_auth') {
                    layer.msg(data.msg, {icon: 5, time: 1500});
                    return false;
                }
                if (str == "ok") {
                    $(obj).parents("tr").remove();
                    layer.msg(data.msg, {icon: 6, time: 2000});
                } else {
                    layer.msg(data.msg, {icon: 5, time: 2000});
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                layer.msg('请求失败，请检查！', {icon: 5, time: 2000});
            }
        });
        /*****************************************************/
    });
}

//2019-3-6 任梦龙：修改提示语
//2019-1-14 任梦龙：添加批量删除的共用js
//mythis：批量删除按钮  table_id：对应的表格id
function delAllActualInfo(mythis, table_id) {
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
                    //2019-2-26 任梦龙：修改str为data.status
                    if (data.status == "ok") {
                        layer.msg(data.msg, {icon: 6, time: 1500}, function () {
                            location.reload();
                        });
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

// 2019-1-15 任梦龙：添加恢复单条记录的共用js
function recoveryInfo(obj, id) {
    layer.confirm('确认要恢复数据吗？', function (index) {
        //发异步数据
        /******************************************************/
        ajaxurl = $(obj).attr("ajaxurl");
        datastr = "id=" + id;
        //  alert(ajaxurl+"----"+datastr);
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: datastr,
            dataType: 'json',
            success: function (data) {
                if (data.status == 'no_auth') {
                    layer.msg(data.msg, {icon: 5, time: 1500});
                    return false;
                }
                if (str == "ok") {
                    $(obj).parents("tr").remove();
                    layer.msg(data.msg, {icon: 6, time: 2000});
                } else {
                    layer.msg(data.msg, {icon: 5, time: 2000});
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                layer.msg('请求失败，请检查！', {icon: 5, time: 2000});
            }
        });
        /*****************************************************/
    });
}

//2019-1-15 任梦龙：添加批量恢复的共用js
//mythis：批量删除按钮  table_id：对应的表格id
function recoveryAllData(mythis, table_id) {
    layer.confirm('确认要恢复数据吗？', function (index) {
        //捉到所有被选中的，发异步进行恢复
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
                    if (data.status == 'no_auth') {
                        layer.msg(data.msg, {icon: 5, time: 1500});
                        return false;
                    }
                    if (str == "ok") {
                        layer.msg(data.msg, {icon: 6, time: 2000});
                        $(".layui-form-checked").not('.header').parents('tr').remove();
                    } else {
                        layer.msg(data.msg, {icon: 5, time: 2000});
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

