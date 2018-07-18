(function() {
    var productData = window.productData,
        twData = window.twData || [];
    delete window.productData;
    delete window.twData;
    try {
        gtag('event', 'view_item', {
            "items": [{
                "id": productData.product.id,
                "name": productData.product.sku,
                "quantity": 1,
                "price": productData.product.sale_price
            }]
        });
    } catch (e) {}

    function pageFit(doc, win, maxwidth, minwidth, font) {
        var docEl = doc.documentElement,
            resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
            recalc = function() {
                var clientWidth = docEl.clientWidth;
                if (!clientWidth) return;
                if (clientWidth >= minwidth && clientWidth <= maxwidth) {
                    docEl.style.fontSize = font * (clientWidth / maxwidth) + 'px';
                } else if (clientWidth > maxwidth) {
                    docEl.style.fontSize = font + 'px';
                } else if (clientWidth < minwidth) {
                    docEl.style.fontSize = font * (minwidth / maxwidth) + 'px';
                }
            };
        recalc();
        if (!doc.addEventListener) return;
        win.addEventListener(resizeEvt, recalc, false);
        doc.addEventListener('DOMContentLoaded', recalc, false);
    }
    pageFit(document, window, 640, 320, 37.5);
    $('.video-wrapper, video').removeClass('video-wrapper');
    $('.description-body').find('iframe').wrap('<div class="video-wrapper"/>');
    $('.description-body').find('video').wrap('<div class="video-wrapper"/>');
    var ua = navigator.userAgent,
        iOS = /iPad|iPhone|iPod/.test(ua),
        iOS11 = /OS 11_0_1|OS 11_0_2|OS 11_0_3|OS 11_1|OS 11_1_1|OS 11_1_2|OS 11_2|OS 11_2_1/.test(ua);
    $('img[data-original]').lazyload({
        threshold: 500
    });
    /*window.addEventListener('DOMContentLoaded', function() {
        new SmartPhoto(".js-smartPhoto");
    });*/
    var mySwiper1 = new Swiper('.swiper-container1', {
        slidesPerView: 1,
        loop: true,
        autoplay: {
            delay: 5000
        },
        lazy: {
            /*__PUBLIC__/Goods/img/loading.gif*/
            loadPrevNext: true,
            loadPrevNextAmount: 2,
        },
        //懒加载
        //lazy:true,
        pagination: {
            el: '.swiper-pagination',
            type: 'bullets'
        }
    });
    var time = new Date;
    time.setTime(Math.round(time / 3600000) * 3600000 + 3600000);
    timer(parseInt((time - (new Date)) / 1000));

    function timer(diff) {
        var ms = 0,
            _time = $('#seckill_time'),
            _hour = _time.find('.hour'),
            _minute = _time.find('.minute'),
            _second = _time.find('.s'),
            _msecond = _time.find('.ms');
        var func = function() {
            if (diff < 0) return;
            if (--ms < 0) ms = 9;
            _msecond.text(ms);
            if (ms === 9) {
                --diff;
                var hour = Math.floor(diff / 3600),
                    remain = diff - hour * 3600,
                    minute = Math.floor(remain / 60),
                    second = remain - minute * 60;
                if (hour < 10) hour = '0' + hour;
                if (minute < 10) minute = '0' + minute;
                if (second < 10) second = '0' + second;
                _hour.text(hour);
                _minute.text(minute);
                _second.text(second);
                if (diff == 0) {
                    _msecond.text(0);
                    clearInterval(seed);
                }
            }
        };
        func();
        var seed = setInterval(func, 100);
    }
    $(window).on('scroll', function() {
        var nav = $('.p-head'),
            scrollTop = document.documentElement.scrollTop || document.body.scrollTop,
            height = 48,
            scrollDiv = $('#scrollTop'),
            seckillDiv = $('.seckill-container'),
            dH = document.documentElement.clientHeight,
            imgH = parseInt($('.swiper-container').css('height')),
            orderSubmit = $('#submit_order');
        if (scrollTop > 5) {
            orderSubmit.show();
        }
        if (scrollTop >= imgH) {
            seckillDiv.addClass('fixed');
        } else {
            seckillDiv.removeClass('fixed');
        }
        if (scrollTop > dH + 100) {
            scrollDiv.addClass('fadeInRight').addClass('loading').removeClass('fadeOutRight').show();
        } else {
            scrollDiv.removeClass('fadeInRight').removeClass('loading').addClass('fadeOutRight');
            setTimeout(function() {
                if (!scrollDiv.hasClass('loading')) {
                    scrollDiv.hide()
                }
            }, 500);
        }
    });
    $('#submit_order').click(function() {
        fbq && fbq("track", "AddToCart");
        try {
            gtag('event', 'ecommerce', {
                'event_category': 'Ecommerce',
                'event_action': 'AddToCart'
            });
            gtag('event', 'add_to_cart', {
                "items": [{
                    "id": productData.product.id,
                    "name": productData.product.sku,
                    "quantity": 1,
                    "price": productData.product.sale_price
                }]
            });
        } catch (e) {}
        if (iOS && iOS11) {
            $('body').css('position', 'fixed');
        }
        $('.widgets-cover').addClass('show');
    });
    $('.sku-close').on('click', function() {
        var $cover = $(this).closest('.widgets-cover');
        // 判断关闭订单页面时候，是否展示规格页面。
        var size_num = $('.size_num').val();
        /*if ($cover.hasClass('widgets-cover-order') && $('.sku-list-wrap').length > 0) {*/
        if ($cover.hasClass('widgets-cover-order') && size_num > 0) {
            $cover.removeClass('widgets-cover-order').addClass('widgets-cover-sku');
        } else {
            $cover.removeClass('show');
            if (iOS && iOS11) {
                $('body').css('position', 'relative');
            }
        }
    });
    $('input,textarea').on('focus', function() {
        $(this).data('pla', $(this).attr('placeholder')).attr('placeholder', '');
    }).on('blur', function() {
        $(this).attr('placeholder', $(this).data('pla'));
    });
    var $skuInfo = $('.sku-info'),
        $imgWrap = $('.img-wrap img'),
        $priceWrap = $('.main .price'),
        setImages = false,
        setPrice = false;
    $('.sku-list-wrap .items a').on('click', function() {
        if ($(this).hasClass('disabled')) return false;
        $(this).toggleClass('checked').siblings('a').removeClass('checked');
        $(this).toggleClass('sel').siblings('a').removeClass('sel');

        var id = $('.product_id').val();
        var url = '/index/getImg/id/'+id;
        var colorColIndex = $(this).text();
        if(colorColIndex){
            url += '/color/'+colorColIndex;
        }
        $.get(url+'.html', function(result) {
            var data = JSON.parse(result);
            if(data.img && colorColIndex){
                $('.j-summary-img').attr('src',data.img);
            }
        });

        var ids = [],
            names = [],
            $variantId = $('[name=variant_id]'),
            $items = $('.sku-list-wrap .items');
        $('.sku-list-wrap .checked').each(function() {
            names.push('<span>' + String($(this).text()) + '</span>');
            ids.push(String($(this).data('id')));
        });
        $skuInfo.find('span.selected-container').html(names.join(''));
        $variantId.val('');
        setImages = setPrice = false;
        productData.variants.forEach(function(variant) {
            var valIds = variant.attributes.split('-');
            /*点击规格选择，不更换图片和不切换价钱*/
            /*if (!setImages && variant.images && inArray(ids, valIds)) {
                $imgWrap.attr('src', variant.images);
                setImages = true;
            }
            if (!setPrice && variant.sale_price_format && inArray(ids, valIds)) {
                $priceWrap.text(variant.sale_price_format);
                setPrice = true;
            }*/
            arrayDiff(valIds, ids).length || ($variantId.val(variant.id));

        });
        $('#quantity').trigger('input');
        $items.find('.disabled').removeClass('disabled');
        $items.find('a').each(function() {
            if ($(this).hasClass('checked')) return true;
            var nextIds = [String($(this).data('id'))],
                $me = $(this),
                disable = true;
            ids.forEach(function(id) {
                $me.parent().find('[data-id="' + id + '"]').length || nextIds.push(id);
            });
            $.each(productData.variants, function(i, variant) {
                var valIds = variant.attributes.split('-');
                if (variant.active && !arrayDiff(nextIds, valIds).length) {
                    disable = false;
                    return false;
                }
            });
            $(this).toggleClass('disabled', disable);
        });

        function inArray(arr1, arr2) {
            for (var i in arr1) {
                var value = arr1[i];
                if ($.inArray(value, arr2) <= -1) {
                    return false;
                }
            }
            return true;
        }

        function arrayDiff(arr1, arr2) {
            var values = [];
            arr1.forEach(function(value) {
                $.inArray(value, arr2) > -1 || values.push(value);
            });
            return values;
        }
    });
    var $quantity = $('#quantity'),
        $quantityAdd = $('#quantity_add'),
        $quantityMinus = $('#quantity_minus');
    $quantityMinus.click(function() {
        var a = 1 * $quantity.val() - 1;
        a > productData.purchaseLimit && (a = productData.purchaseLimit);
        if (1 >= a) {
            $(this).addClass('disabled');
            a = 1;
        } else {
            $(this).removeClass('disabled');
            $quantityAdd.removeClass('disabled');
        }
        $quantity.val(a).trigger('input');
    });
    $quantityAdd.click(function() {

        //复制规格列表，.sku-wrap .body这个也要相应增加--todo
        //$('.sku-list-wrap').before($('.sku-list-wrap').clone());
        var a = 1 * $quantity.val() + 1;
        1 > a && (a = 1);
        if (a >= productData.purchaseLimit) {
            $(this).addClass('disabled');
        } else {
            $(this).removeClass('disabled');
            $quantityMinus.removeClass('disabled');
        }
        $quantity.val(a).trigger('input');
    });
    $quantity.on('input propertychange', function() {
        var num = this.value * 1,
            data = {
                variant_id: $('[name=variant_id]').val(),
                quantity: num
            };
        if (num > productData.purchaseLimit) {
            showMessage(translate("Purchase limit {num} items.", {
                num: productData.purchaseLimit
            }));
            $(this).val(num = productData.purchaseLimit);
            return false;
        }
        /*这里可以动态更新商品金额*/
        var trprice = Number($('.trprice').val());
        $('#j_total').text(this.value * trprice + '.00');

        /*$.post('/product/checkout?id=' + $('[name=id]').val(), data, function(resp) {
            $('#j_total').text(resp.data.totalFeeFormat);
        }, 'json');*/
    });
    $('.widgets-cover-sku .sku-buy').on('click', function() {
        // 判断是否选中全部的规格。
        var size_num = $('.size_num').val();
        var sel_num = document.getElementsByClassName('sel');
        /*if (!$('[name=variant_id]').val()) {*/
        if(parseInt(size_num) != parseInt(sel_num.length)){
            showMessage(translate("Please select the product's attributes."));
            return false;
        }
        $(this).closest('.widgets-cover').removeClass('widgets-cover-sku').addClass('widgets-cover-order');
    });
    $('.footer').on('click', '.order-buy', function() {


        var sizeId = $('#ggid').val();

        //判断规格是否选择
        /*var _colorIndex=$('.color .sel').index();
        var _weightIndex=$('.weight .sel').index();
        var _sizeIndex=$('.size .sel').index();*/
        /*if(_colorIndex <0 || _weightIndex <0 || _sizeIndex <0){*/

        var goodId = $('.product_id').val();
        var name = $('#contact_name').val();
        var phone = $('#mob').val();
        var address = $('#address').val();
        var code = $('#postcode').val();
        var email = $('#email').val();
        var remark = $('#message').val();
        var num = $('#quantity').val();

        if (parseInt(num) <= 0) {
            /*alert('购买数量最少为1');*/
            showMessage('Purchase at least 1 items');
            $('.pc-num').html(1);
            edit_price();
            return false;
        }

        if (parseInt(num) > 2) {
            /*限购两个*/
            showMessage('Purchase limit 2 items.');
            $('.pc-num').html(2);
            edit_price();
            return false;
        }
        if (name.length<=0) {
            /*alert('收件人名称不能为空');*/
            showMessage('Please enter the contact name.');
            $("input[name='contact']").focus();
            return false;
        }

        var re=/^[a-zA-Z][a-zA-Z0-9_-]+$/;
        if(!re.test(name)){
            showMessage('Please enter the correct name');
            $("input[name='contact']").focus();
            return false;
        }

        if (phone.length<=0) {
            showMessage('Please enter the phone number.');
            $("input[name='mobile']").focus();
            return false;
        }

        /*var myreg=/^[1][3,4,5,7,8][0-9]{9}$/;
        if (!myreg.test(phone)) {
            /!*alert('收件人手机号错误');*!/
            showMessage('The phone number is error');
            $('#custom_mobile').focus();
            return false;
        }*/

        //判断地址
        //State
       if($("#location_c").find('option:selected').text() == 'please select'){
           // $("#location_c").css({ border: "1px solid red"});
           $('#location_c').focus();
           showMessage('Please select the State.');
           return false;
       }

        //City
        if($("#location_a").find('option:selected').text() == 'please select'){
            // $("#location_a").css({ border: "1px solid red"});
            $('#location_a').focus();
            showMessage('Please select the City.');
            return false;
        }

        //District
        if($("#location_d").find('option:selected').text() == 'please select'){
            showMessage('Please select the District.');
            $('#location_d').focus();
            return false;
        }

        //Street
        if($("#addresstwo").find('option:selected').text() == 'please select'){
            showMessage('Please select the Street 1.');
            $('#addresstwo').focus();
            return false;
        }

        if (address.length <= 0) {
            showMessage('Please enter the Street &Building.');
            $('#address').focus();
            return false;
        }

        //拼接地址
        var address_all = address+", "+$('#addresstwo').val()+", "+ $('#location_d').val()+", "+ $('#location_a').val()+", "+ $('#location_c').val() ;


       if (code.length<=0) {
            showMessage('Please enter the post/zip code.');
            $('#postcode').focus();
            return false;
        }

        /*var re= /^[1-9][0-9]{5}$/;
        if(!re.test(code)) {
            /!*alert('邮编格式错误');*!/
            showMessage('The post/zip code is error');
            $('#postcode').focus();
            return false;
        }*/

        /*if (email.length<=0) {
            showMessage('Please enter the email.');
            $('#email').focus();
            return false;
        }

        var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if (!filter.test(email)) {
            /!*alert('email格式有误');*!/
            showMessage('The email is error');
            $('#email').focus();
            return false;
        }*/



        //付款方式判断
        /*var _payIndex=$('.pay-box .on').index();
        if(_payIndex<0){
            $('.pay-head i').show();
            $('body,html').animate({
                'scrollTop': $('.pay').offset().top - 45
            }, 500);
            return false;
        }else{
            $('.pay-head i').hide();
        }*/

        //必须选择全部的规格

        /*var sel_num = document.getElementsByClassName('sel');

        if(parseInt(size_num) != parseInt(sel_num.length)){
            var seckill_height = $('.seckill-container').height();
            $('body,html').animate({
                'scrollTop': $('.am-card-header').offset().top - seckill_height
            }, 500);

            showMessage('Please select all the specification.');
            return false;
        }*/

        //获取支付方式
        /*var payType = $('.pay-box .on').attr('type');*/
        var payType = 5;


        var color_sel=$('.items_color .checked').text();
        var weight_sel=$('.items_weight .checked').text();
        var size_sel=$('.items_size .checked').text();
        $('.order-buy').hide();

        var o_code = $('#o_code').val();

        //判断goodId，和sizeId
        var param = {"o_code":o_code,"color":color_sel,"weight":weight_sel,"size":size_sel,"userName":name,"goodId":goodId, "sizeId":sizeId, "address":address_all,"phone":phone,"email":email,"code":code,"remark":remark,"goodCount":num,"payType":payType};
        //ajax提交数据
        $.ajax({
            type: "post",
            url: "/order/createOrder",
            data: param,
            dataType: "json",
            success: function(data){
                console.log(data);
                if (data.code == 0) {
                    window.location.href='/index/buysuccess/id/'+data.data.orderId;
                } else {
                    /*alert(data.data.msg);*/
                    showMessage(data.data.msg);
                }
            }
        });

        /*try {
            gtag('event', 'ecommerce', {
                'event_category': 'Ecommerce',
                'event_action': 'SubmitOrder'
            });
            gtag('event', 'begin_checkout', {
                "items": [{
                    "id": productData.product.id,
                    "name": productData.product.sku,
                    "quantity": $("#quantity").val(),
                    "price": productData.product.sale_price
                }]
            });
        } catch (e) {}
        try {
            fbq('track', 'InitiateCheckout');
        } catch (e) {}
        $.post('/product/purchase', $('.j-checkout-form').serialize(), function(resp) {
            if (resp.error) {
                showMessage(resp.message);
                try {
                    gtag('event', 'ecommerce', {
                        'event_category': 'FormError',
                        'event_action': resp.data.name,
                        'event_label': resp.message
                    });
                } catch (e) {}
            } else {
                try {
                    gtag('event', 'ecommerce', {
                        'event_category': 'Ecommerce',
                        'event_action': 'Purchase',
                        'event_label': resp.data.id
                    });
                    gtag('event', 'purchase', {
                        "transaction_id": resp.data.tradeId,
                        "value": resp.data.amount,
                        "currency": resp.data.currency,
                        "items": [{
                            "id": productData.product.id,
                            "name": productData.product.sku,
                            "quantity": $("#quantity").val(),
                            "price": productData.product.sale_price
                        }]
                    });
                } catch (e) {}
                try {
                    fbq('track', 'Purchase', {
                        value: resp.data.amount,
                        currency: resp.data.currency
                    });
                } catch (e) {}
                window.location.href = '/product/purchase-success?id=' + resp.data.id + '&trade_id=' + resp.data.tradeId;
            }
        }, 'json');
        return false;*/
    });
    $('#scrollTop').click(function() {
        $('body,html').animate({
            scrollTop: 0
        }, 'slow');
    });
    var twCityHtml = '';
    for (var j in twData) {
        twCityHtml += '<option value="' + j + '">' + twData[j]['name'] + '</option>'
    }
    $('.tw-city').html(twCityHtml).on('change', function() {
        var id = $(this).val(),
            list = twData[id]['children'],
            html = '';
        for (var i in list) {
            html += '<option value="' + i + '">' + list[i] + '</option>'
        }
        $('.tw-area').html(html);
    }).trigger('change');

    var levelone,leveltwo,levelthree,levelfour;
    $("#location_c").append(renderAddress());
    $("#location_c").change(function(event){
        levelone = event.target.value;
        $("#location_a").empty();
        $("#location_d").empty();
        $("#addresstwo").empty();
        $("#postcode").val("");
        $("#location_a").append(renderTwoLevel(levelone));
        $("#location_a").change(function(e){
            leveltwo = e.target.value;
            $("#location_d").empty();
            $("#addresstwo").empty();
            $("#postcode").val("");
            $("#location_d").append(renderThreeLevel(levelone,leveltwo));
            $("#location_d").change(function(eve){
                levelthree = eve.target.value;
                $("#addresstwo").empty();
                $("#postcode").val("");
                $("#addresstwo").append(renderForuLevel(levelone,leveltwo,levelthree));
                $("#addresstwo").change(function(even){
                    levelfour = even.target.value;
                    $("#postcode").val(renderCode(levelone,leveltwo,levelthree,levelfour));
                })
            });
        });
    });


})();

function showMessage(message) {
    $('.layout-bg').show();
    $('#Validform_msg').removeClass('zoomOut').addClass('zoomIn').show();
    $('.Validform_info').html(message);
    setTimeout(function() {
        $('#Validform_msg').removeClass('zoomIn').addClass('zoomOut');
        setTimeout(function() {
            $('#Validform_msg').hide();
        }, 500);
        $('.layout-bg').hide();
    }, 2000);
}