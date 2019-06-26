import Vue from 'vue'
import App from './App.vue'
import ElementUI from 'element-ui';
import 'element-ui/lib/theme-chalk/index.css';

Vue.use(ElementUI);

new Vue({
    el: '#app',
    render: h => h(App, {
        props: {
            app_title: 'Marknotes - HTML2MD',
            app_subtitle: 'Quick HTML to markdown converter. ' +
                '<small><em>You can also specify the URL like this: ' +
                'https://html2md.avonture.be/?url=https://domain/page.html</em></small>',
            github_url: 'https://github.com/cavo789/marknotes_html2md',
            howto_title: 'How to use?',
            howto_imgsrc: 'https://raw.githubusercontent.com/cavo789/marknotes_html2md/master/image/demo.gif'
        }
    }),
});
