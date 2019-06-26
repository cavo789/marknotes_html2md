import axios from 'axios';

export default {
    name: "buttons_links",
    data: function () {
        return {
            buttons: []
        }
    },
    methods: {
        gotoTools(url) {
            let wnd = window.open(url);
            if (wnd == null) {
                this.$notify({
                    title: 'Warning',
                    dangerouslyUseHTMLString: true,
                    message: "<p></p>The browser has blocked the " +
                        "opening of this URL; please allow " +
                        "popups or jump to the URL manually" +
                        "<br /><br />" +
                        "<a href='" + url + "'>" + url + "</a></p>",
                    type: 'warning'
                });
            }
        }
    },
    mounted() {
        axios.get('button_links.json')
            .then((response) => {
                if (response.status === 200) {
                    console.log(response);
                    this.buttons = response.data.buttons;
                }
            })
            .catch(function (error) {
                console.log(error);
            });
    }
};

