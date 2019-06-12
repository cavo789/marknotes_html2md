import Vue from 'vue'
import App from './App.vue'
import 'bulma/css/bulma.css';

new Vue({
    el: '#app',
    render: h => h(App, {
        props: {
            app_title: 'Marknotes - HTML2MD',
            app_subtitle: 'Quick HTML to markdown converter',
            github_url: 'https://github.com/cavo789/marknotes_html2md',
            howto_title: 'How to use?',
            howto_imgsrc: 'https://raw.githubusercontent.com/cavo789/marknotes_html2md/master/image/demo.gif',
        }
    }),
});
