import ClipboardJS from 'clipboard';

// @link https://www.npmjs.com/package/h2m
import h2m from 'h2m';

import axios from 'axios';

export default {
    name: "editor",
    props: {
        showEditor: {
            type: Boolean,
            required: false,
            default: true
        },
        HTML: {
            type: String,
            required: false,
            default: "<h1 id='marknotes_html2md'>marknotes_html2md</h1>\n" +
                "<blockquote>\n" +
                "<p>Quick HTML to markdown converter</p>\n" +
                "</blockquote>\n" +
                "<h2 id='table-of-contents'>Table of Contents</h2>\n" +
                "<ul>\n" +
                "<li><a href='#install'>Install</a></li>\n" +
                "<li><a href='#usage'>Usage</a></li>\n" +
                "<li><a href='#author'>Author</a></li>\n" +
                "<li><a href='#license'>License</a></li>\n" +
                "</ul>\n" +
                "<h2 id='install'>Install</h2>\n" +
                "<p>Clone this repository or click on the [Clone or download] " +
                "green button and get a copy of the program.</p>\n" +
                "<p>You can also use the " +
                "<a href='https://html2md.avonture.be'>" +
                "interface online</a> without installing anything.</p>\n" +
                "<h2 id='usage'>Usage</h2>\n" +
                "<p>Type your markdown code in the editor, the HTML " +
                "conversion is done on- the - fly.</p>\n" +
                "<p>At the bottom of the page, you have a button for " +
                "maximizing the HTML part(by hiding everything else) " +
                "or two buttons for copying in the clipboard the HTML " +
                "rendering or the HTML source code.mail f.i.</p>\n" +
                "<h2 id='author'>Author</h2>\n" +
                "<p>Christophe Avonture</p>\n" +
                "<h2 id='license'>License</h2>\n" +
                "<p><a href='LICENSE'>MIT</a></p>"

        },
        URL: {
            type: String,
            required: false,
            value: "https://www.marknotes.fr/docs/Windows/Changer%20son%20wallpaper.html"
        },
    },
    data: function () {
        return {
            URL: this.URL,
            showeditor: this.showEditor,
            clipboardDisabled: 1
        }
    },
    computed: {
        MD() {
            if (this.HTML == '') {
                return '';
            }
            var md = h2m(this.HTML);

            return md;
        }
    },
    methods: {
        changeURL() {
            if (this.URL !== '') {
                // Crawl and get the HTML of the page
                axios.post('crawl.php',
                    {
                        url: window.btoa(this.URL)
                    })
                    .then((response) => {
                        if (response.status === 200) {
                            this.HTML = response.data;
                        }
                    })
                    .catch(function (error) {
                        console.log(error);
                    });
            }
        },
        doToggle(e) {
            this.showeditor = !(this.showeditor);
            this.$emit('toggleVisibility', e);
        }
    },
    mounted() {
        // If ClipboardJS library is correctly loaded,
        if (typeof ClipboardJS === 'function') {
            // Remove the disabled attribute
            this.clipboardDisabled = 0;

            // Handle the click event on buttons
            var clipboard = new ClipboardJS('.btnClipboard');

            clipboard.on('success', function (e) {
                alert('Copied!');
                e.clearSelection();
            });
        }
    }
};
