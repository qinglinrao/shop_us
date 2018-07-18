//初始化判断导航位置

//判断滑屏距离 确定导航位置
$(window).scroll(function () {
    var scrollTop = $(this).scrollTop();
    /*var saleHeight = $('.sale').height();
    if (scrollTop >= saleHeight) {
        $('.tabs').css({
            'position': 'fixed',
            'top': 0,
            'max-width': '640px'
        })
    } else {
        $('.tabs').css({
            'position': 'static'
        })
    }*/
    if(scrollTop>550){
        $('.bottom-to-top').show();
    }else{
        $('.bottom-to-top').hide();
    }
});
$('.bottom-to-top').click(function(){
    $(window).scrollTop(0);
});
//导航点击字体颜色改变
$('.tab').click(function () {
    $(this).addClass('tab-cur').siblings('.tab').removeClass('tab-cur');
});

//商品介绍锚点
$('.tab').eq(0).click(function () {
    $('body,html').animate({
        'scrollTop': $('.uintro').offset().top - 45
    }, 500);
});
//购买须知锚点
$('.tab').eq(1).click(function () {
    $('body,html').animate({
        'scrollTop': $('.table-details').offset().top - 45
    }, 500);
});
//评价锚点
$('.tab').eq(2).click(function () {
    $('body,html').animate({
        'scrollTop': $('.appraise-head').offset().top - 45
    }, 500);
});
//开团锚点
$('.kt-btn').click(function(){
    $('body,html').animate({
        'scrollTop': $('.price-box').offset().top - 45
    }, 500);
});



//购买数量 && 总价计算
var curNum = Number($('.pc-num').html());
var singPrice = Number($('.one_price').val());
var youPrice=Number($('.you i').html());

function edit_price(){
    curNum = Number($('.pc-num').html());
    if(youPrice){
        $('.all-price i').html(singPrice * curNum+youPrice+'.00');
    }else{
        $('.all-price i').html(singPrice * curNum+'.00');
    }
}


$('.pc-next').click(function () {
    curNum++;
    $('.pc-num').html(curNum);
    var singPrice = Number($('.one_price').val());
    var youPrice=Number($('.you i').html());
    if(youPrice){
        $('.all-price i').html(singPrice * curNum+youPrice+'.00');
    }else{
        $('.all-price i').html(singPrice * curNum+'.00');
    }
});
$('.pc-prev').click(function () {
    if (curNum > 1) {
        curNum--;
        $('.pc-num').html(curNum);
        var singPrice = Number($('.one_price').val());
        var youPrice=Number($('.you i').html());
        if(youPrice){
            $('.all-price i').html(singPrice * curNum+youPrice+'.00');
        }else{
            $('.all-price i').html(singPrice * curNum+'.00');

        }
    }
});


