<?php

declare(strict_types = 1);

/**
 * When a page has been crawled on internet, the HTML contain a lot of
 * unneeded stuffs like the <header> part, javascript and styles,
 * footer, navigation, ...
 *
 * The objective of this helper is to clean the HTML and remove
 * stuff and try to get the content without unneeded stuff.
 *
 * Rely on the crawl.json file to get rules.
 */

namespace html2md;

use html2md\CleanHTML;

// user-agent, can be override in the crawl.json file
define('UA', 'Mozilla/5.0 (Windows NT 6.1; WOW64) ' .
    'AppleWebKit/537.36 (KHTML, like Gecko) ' .
    'Chrome/35.0.1916.153 Safari/537.36 FirePHP/4Chrome');

// in seconds, can be override in the crawl.json file
define('TIMEOUT', 5);

// maximum redirection that curl can follow,
// can be override in the crawl.json file
define('MAXREDIRS', 3);

class Crawl
{
    protected $url         = '';
    protected $html        = '';
    protected $use_session = false;
    protected $settings    = [];

    /**
     * @param string $url     URL to crawl
     * @param bool   $session If true, store the retrieved HTML in
     *                        a session variable
     */
    public function __construct(string $url, bool $useSession = true)
    {
        $this->url = $url;

        if ($this->use_session = $useSession) {
            session_start();
        }

        $this->readSettings();
    }

    public function getHTML(): string
    {
        self::retrieveHTML();
        self::miscUpdate();
        self::domCleaning();

        return $this->html;
    }

    /**
     * Read settings from crawl.json.
     *
     * @return bool
     */
    private function readSettings(): bool
    {
        if (!is_file($fname = __DIR__ . '/crawl.json')) {
            die('Sorry, file crawl.json not found, please check ' .
                'your installation.');
        }

        $json            = trim(file_get_contents($fname));
        $this->settings  = json_decode($json, true);

        if (!(isset($this->settings['timeout']))) {
            $this->settings['timeout'] = TIMEOUT;
        }

        if (!(isset($this->settings['ua']))) {
            $this->settings['ua'] = UA;
        }

        if (!(isset($this->settings['maxredir']))) {
            $this->settings['maxredir'] = MAXREDIRS;
        }

        return true;
    }

    /**
     * Retrieve the HTML of the remote page.
     *
     * @return bool
     */
    private function retrieveHTML(): bool
    {
        if ($this->use_session) {
            if (isset($_SESSION[md5($this->url)])) {
                $this->html = $_SESSION[md5($this->url)];

                return true;
            }
        }

        $ch = curl_init($this->url);

        curl_setopt($ch, CURLOPT_USERAGENT, $this->settings['ua']);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->settings['timeout']);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $this->settings['maxredir']);

        $this->html = curl_exec($ch);

        if ($this->use_session) {
            $_SESSION[md5($this->url)] = $this->html;
        }

        curl_close($ch);

        return true;
    }

    /**
     * Misc updates.
     *
     * @return bool
     */
    private function miscUpdate(): bool
    {
        // Replace a few characters because we'll be displayed
        // as a "?" in the HTML content
        $arr = [
            ['’', "'"],
            ['“', '"'],
            ['”', '"'],
            ['–', '-'],
            ['…', '...'],
        ];

        foreach ($arr as $char) {
            $this->html = str_replace($char[0], $char[1], $this->html);
        }

        // Sometimes the <a> is immediately after a character
        // like (click here<a> link </a>to ...) and it's not really
        // correct (syntax) and won't be great once converted into
        // markdown so add a space before and after an obtain :
        // like (click here <a>link</a> to ...)

        // text<a> ==> text <a>
        $regex      = '~([^\s])(<a[^>]*>)~im';
        $this->html = preg_replace($regex, '$1 $2', $this->html);

        // </a>text ==> </a> text
        $regex      = '~(<\/a>)([^\s])~im';
        $this->html = preg_replace($regex, '$1 $2', $this->html);

        // -----------------------
        // <p and <div should be on new line so the Markdown
        // conversion will give a better result
        // 1. <p> (on a new line) will be change to have two LF
        $regex      = '~\n(<[div|p][^>]*>)~im';
        $this->html = preg_replace($regex, "\n\n$1", $this->html);
        // 2.  something<p>  ==> something followed by two LF <p>
        $regex      = '~([^\n])(<[div|p][^>]*>)~im';
        $this->html = preg_replace($regex, "$1\n\n$2", $this->html);
        // -----------------------

        // Add <p> ... </p> if the line doesn't contains them
        // and contains f.i. an anchor.
        // Convert <a>....</a> on a single line to
        // <p><a>....</a></p>
        // REGEX : DON'T USE $ BUT WELL \n? (otherwise it won't work)
        $regex      = '~^(<a[^>]*>.*<\/a>\n?)~im';
        $this->html = preg_replace($regex, '<p>$1</p>', $this->html);

        return true;
    }

    /**
     * Remove from the HTML string a lot of stuff like
     * headers, navigation, footer, ... This to try to get back,
     * only, the content without "fioritures".
     *
     * @return bool
     */
    private function domCleaning(): bool
    {
        // List of nodes where the content is placed.
        // That list will allow to faster retrieved desired
        // content and not pollute content by additional
        // elements like comments, navigation, ...
        $arrContentDOM = $this->settings['content_DOM'] ?? [];

        // List of nodes that can be removed since are not
        // part of the content we want to keep
        $arrRemoveDOM = $this->settings['remove_DOM'] ?? [];

        // List of attributes that can be removed from html
        // tags once the desired content is isolated
        $arrRemoveAttribs = $this->settings['remove_Attributes'] ?? [];

        // The regex entry of plugins->options->task->fetch
        // contains search&replace expression for the content
        // f.i. Search a specific content and replace it by
        // a new value
        $arrRegex = $this->settings['regex'] ?? [];

        require_once 'clean_html.php';

        $cleanHtml = new CleanHTML($this->html, $this->url);

        $cleanHtml->setContentDOM($arrContentDOM);
        $cleanHtml->setRemoveDOM($arrRemoveDOM);
        $cleanHtml->setRemoveAttributes($arrRemoveAttribs);
        $cleanHtml->setRegex($arrRegex);

        $this->html = $cleanHtml->doIt();

        unset($cleanHtml);

        return true;
    }
}

/**
 * Entry point.
 */

// Retrieve data posted by axios
$_POST = json_decode(file_get_contents('php://input'), true);

$url = '';

if (isset($_POST['url'])) {
    // Get the URL and make it readable
    $url = trim(base64_decode($_POST['url']));
}

if ('' == $url) {
    die('crawl.php requires an URL to crawl');
}

$html = new Crawl($url);
echo $html->getHTML();
