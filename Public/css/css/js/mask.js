function show(){
    $(".lbOverlay").css({"height":window.screen.availHeight});
    $(".lbOverlay").show();

    var st=$(document).scrollTop();
    var objH=$(".hidden_pro_au").height();
    var ch=$(window).height();
    var objT=Number(st)+(Number(ch)-Number(objH))/2.25;
    $(".hidden_pro_au").css("top",objT);
    
    var sl=$(document).scrollLeft();
    var objW=$(".hidden_pro_au").width();
    var cw=$(window).width();
    var objL=Number(sl)+(Number(cw)-Number(objW))/2;
    $(".hidden_pro_au").css("left",objL);
    $(".hidden_pro_au").slideDown("20000");
}
function closeDiv(){
    $(".lbOverlay").hide();
    $(".hidden_pro_au").hide();
}