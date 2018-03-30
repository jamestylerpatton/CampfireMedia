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
    }
});

require('./nav-slide');
