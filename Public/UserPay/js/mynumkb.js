(function($){
    $('body').height($('body')[0].clientHeight);
    var _count = 0;
    var Mynumkb = function(element, options){
        _count++;
        this.count = _count;
        this.$element = $(element);
        this.$element.attr("data-count",this.count);
        this.options = $.extend({},$.fn.mynumkb.defaults, options);
        this.init();
    }
    Mynumkb.prototype = {
        constructor: Mynumkb,
        init:function(){
            var me= this;
            me.render();
            me.bind();
            me.initHtml();
        },
        render:function(){
            var me = this;
            me.$eparentNode = me.$element.parent();
        },
        bind:function(){
            var me = this;
            me.$element.on("click",$.proxy(me['_eClick'],me));

            $(document).on("mousedown","#mykeyboard"+me.count+" li",$.proxy(me['_limousedown'],me));
            $(document).on("mouseup","#mykeyboard"+me.count+" li",$.proxy(me['_limouseup'],me));
            $(document).on("click","#mykeyboard"+me.count+" li",$.proxy(me['_liclick'],me));
        },
        initHtml:function(){
            var me = this;
            var _html = [
                '<div class="mykb-box" id="mykeyboard'+me.count+'">',
                '<ul class="num-key fl">',
                '<li class="num">1</li>',
                '<li class="num">2</li>',
                '<li class="num">3</li>',
                '<li class="num">4</li>',
                '<li class="num">5</li>',
                '<li class="num">6</li>',
                '<li class="num">7</li>',
                '<li class="num">8</li>',
                '<li class="num">9</li>',
                '<li class="num ling">0</li>',
                '<li class="num">.</li>',
                '</ul>',
                '<ul class="delete-sure fl">',
                '<li class="del"><img src="/Public/UserPay/images/delete.png" class="del" style="cursor: pointer;"/></li>',
                '<li class="sure"><button class="sure" id="btn" disabled  style="cursor: pointer;">微信支付</button></li>',
                '</ul>',
                '</div>',
            ].join("");

            $("body").append(_html);
            me.setPosition();
        },
        setPosition:function(){
            var me = this;
            var parentNode = me.$eparentNode;
            var $element = me.$element;
            $("body").css("position","relative");
            var height = $element.outerHeight();
            var width = $element.outerWidth();
            var position = $element.position();
            var $keyboard = $("#mykeyboard"+me.count);
            var ulWidth = $keyboard.outerWidth();
            var ulHeight = $keyboard.outerHeight();
            var left = position.left;
            $keyboard.css({
                bottom:0
                // top:position.top+height+100+"px",
                /* left:left+width+30+"px" */
            });
        },
        _eClick:function(e){
            var me = this;
            var count = me.$element.data("count");
            var $keyboard = $("#mykeyboard"+count);
            $keyboard.fadeIn(100).siblings(".mykb-box").hide();
        },
        _liclick:function(e){
            var me = this;
            var $target = $(e.target);
            console.log($target);
            if($target.hasClass("del")){//退格
                me.setValue("del");
                var money = me.$element.val();
                if(!money){
                    $('#btn').attr('disabled','disabled');
                    $(".delete-sure .sure").removeClass("blue-bg");
                }
            }else if($target.hasClass("exit")||$target.hasClass("sure")){//退出--确定
                // me.close();
                $('#input-amount').val(me.$element.val());
                $('#form').submit();
            }else if($target.hasClass("clearall")){//清除
                me.$element.val("");
            }else{//输入其他数字
                if($('#btn').attr('disabled')=='disabled'){
                    $(".delete-sure .sure").addClass("blue-bg");
                    $('#btn').removeAttr('disabled');
                }
                var str = $target.text();
                var money = me.$element.val();
                var money_length = money.length;
                if(!money){
                    //判断首位不能为点
                    if(str=='.'){
                        alert('请输入合法数字,首位不能为.');
                        return false;
                    }
                    me.setValue("add",str);
                }else{
                    //判断总位数
                    if(money_length>=15){
                        alert('不能超过15个字符');
                        return false;
                    }
                    //判断首位为0第二位必须是.
                    if(money==0 && money_length==1 && str!='.'){
                        alert('首位字符为0时,第二位字符必须为.');
                        return false;
                    }
                    //判断不能有两个.
                    if(str=='.'){
                        var n = money.indexOf(".")
                        if(n>0){
                            alert('只能输入一位数小数点');
                        }
                    }
                    me.setValue("add",str);
                }
            }
        },
        _limousedown:function(e){
            var me = this;
            var val = $(e.target).html();
            $(e.target).addClass("active").siblings().removeClass("active");
        },
        _limouseup:function(e){
            var me = this;
            var val = $(e.target).html();
            $(e.target).removeClass("active");

        },
        setValue:function(type,str){
            var me = this;
            var curpos = me.getCursorPosition();
            var val = me.$element.val();
            var newstr = "";
            if(type == 'add'){
                newstr = me.insertstr(val,str,curpos);
                me.$element.val(newstr);
                me.$element.textFocus(curpos+1);
            }else if(type == 'del'){
                newstr = me.delstr(val,curpos);
                me.$element.val(newstr);
                me.$element.textFocus(curpos-1);
            }

        },
        insertstr:function(str,insertstr,pos){
            var str = str.toString();
            var newstr = str.substr(0,pos)+''+insertstr+''+str.substr(pos);
            return newstr;
        },
        delstr:function(str,pos){
            var str = str.toString();
            var newstr = "";
            if(pos != 0){
                newstr = str.substr(0,pos-1)+str.substr(pos);
            }else{
                newstr = str;
            }
            return newstr;
        },
        getCursorPosition:function(){
            var me = this;
            var $element = me.$element[0];
            var cursurPosition=-1;
            if($element.selectionStart!=undefined && $element.selectionStart!=null){//非IE浏览器
                cursurPosition= $element.selectionStart;
            }else{//IE
                var range = document.selection.createRange();
                range.moveStart("character",-$element.value.length);
                cursurPosition=range.text.length;
            }
            return cursurPosition;
        },
        close:function(){
            var me = this;
            var count = me.$element.data("count");
            var $keyboard = $("#mykeyboard"+count);
            // $keyboard.fadeOut(100);
            me.$element.attr("isshowkb","false");
        }
    };
    $.fn.mynumkb = function (option) {
        return this.each(function () {
            var options = typeof option == 'object' ? option : {};
            var data = new Mynumkb(this, options);
        })
    }
    $.fn.mynumkb.defaults = {

    };
    $.fn.mynumkb.Constructor = Mynumkb;


})(jQuery);
(function($){
    $.fn.textFocus=function(v){
        var range,len,v=v===undefined?0:parseInt(v);
        this.each(function(){
            if(navigator.userAgent.msie){
                range=this.createTextRange();
                v===0?range.collapse(false):range.move("character",v);
                range.select();
            }else{
                len=this.value.length;
                v===0?this.setSelectionRange(len,len):this.setSelectionRange(v,v);
            }
            this.focus();
        });
        return this;
    }
})(jQuery);
