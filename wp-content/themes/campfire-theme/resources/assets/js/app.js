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
                autoplay: true
            });
        });

        document.addEventListener( 'wpcf7mailsent', function( event ) {
            let $target = $(event.target).find('.form-fields');
            $target.hide();
        }, false );
    }
});

require('./nav-slide');
