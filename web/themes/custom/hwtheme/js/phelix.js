(function ($) {

    var lastScrollTop = 0;

    $(window).scroll(function(e) {
       var st = $(this).scrollTop();
       if (st > lastScrollTop) {
           $('header').addClass('scrollin-down');
           $('header').removeClass('scrollin-up');
           $('#block-hwtheme-main-menu > ul > li img').addClass('img-down');
           $('#block-hwtheme-main-menu > ul > li img').removeClass('img-up');

       } else {
           // Scrolling Up
           $('header').removeClass('scrollin-down');
           $('header').addClass('scrollin-up');
           $('#block-hwtheme-main-menu > ul > li img').addClass('img-up');
           $('#block-hwtheme-main-menu > ul > li img').removeClass('img-down');
       }
        if($(this).scrollTop() <=0) {
            // TOP
            $('header').removeClass(['scrollin-down','scrollin-up']);
            $('#block-hwtheme-main-menu > ul > li img').removeClass(['img-down', 'img-up']);
        }

        lastScrollTop = st;
    });

    /* Create link for logo menu */

    $('.logo-menu .field--name-field-menu-image > img').wrap('<a class="menu-logo" href="/"></a>');


    /* Content Parallax image effect */
    $('.field--name-field-image img').each(function(){
        var img = $(this);
        var imgParent = $(this).parent();


        function parallaxImg () {
            var speed = 1;
            var imgY = imgParent.offset().top;
            var winY = $(this).scrollTop();
            var winH = $(this).height();
            var parentH = imgParent.innerHeight();


            // The next pixel to show on screen
            var winBottom = winY + winH;

            // If block is shown on screen
            if (winBottom > imgY && winY < imgY + parentH) {
                // Number of pixels shown after block appear
                var imgBottom = ((winBottom - imgY) * speed);
                // Max number of pixels until block disappear
                var imgTop = winH + parentH;
                // Porcentage between start showing until disappearing
                var imgPercent = ((imgBottom / imgTop) * 100) + (50 - (speed * 50));
            }
            img.css({
                top: imgPercent + '%',
                transform: 'translate(-50%, -' + imgPercent + '%)'
            });
        }

        $(document).on({
            scroll: function () {
                parallaxImg();
            }, ready: function () {
                parallaxImg();
            }
        });
    });

    /* Jquery equal column heights */

    var checkloaded = setInterval(function() {
        if ($('.contain-box').length) {
            var max = 0,
                $els = $('.contain-box');
            $els.each(function() {
                max = Math.max($(this).height(), max)
                console.log("MAX: ", max);
            });
            $els.height(max);
            clearInterval(checkloaded);
        }
    }, 100);








    // var sameHeightDivs = $('.contain-box');
    // var maxHeight = 0;
    // sameHeightDivs.each(function() {
        // if ($(this).height() > maxHeight) {
        //     maxHeight = $(this).height();
        // }
        // console.log($(this).height());

        // maxHeight = Math.max(maxHeight, $(this).height());
    // });
     // console.log("Max Height: ", maxHeight);
     //sameHeightDivs.css({ height: maxHeight + 'px' });




/* Meet the team blue */

    $('.image-circle .field-content').on('mouseenter', function() {
        $(this).find('.img-responsive').before('<img class="blue-box" src="/sites/default/files/blue-box.png">');
    });

    $('.image-circle .field-content').on('mouseleave', function() {
        $(this).find('.blue-box').remove();
    });


    var $el = $('.social-home-block-contain');
    $(window).on('scroll', function () {
        var scroll = $(document).scrollTop();
        console.log('SCROLL: ' + scroll);
        $el.css({
            'background-position':'50% '+(-.1*scroll)+'px'
        });
    });



})(jQuery);

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();

        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});