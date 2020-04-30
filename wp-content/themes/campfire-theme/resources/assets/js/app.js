require('./bootstrap');

window.Vue = require('vue');

const app = new Vue({
    el: '#app',
    mounted() {

        $(function(){
            $('.banner-carousel').owlCarousel({
                items: 1,
                loop: true,
                nav: false,
                dots: false,
                autoplay: true,
                autoplayTimeout: 7000
            });
        });

        document.addEventListener( 'wpcf7mailsent', function( event ) {
            let $target = $(event.target).find('.form-fields');
            $target.hide();
        }, false );

        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        });
    }
});

require('./nav-slide');
