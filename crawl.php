<?php

define('UA', 'Mozilla/5.0 (Windows NT 6.1; WOW64) ' .
    'AppleWebKit/537.36 (KHTML, like Gecko) ' .
    'Chrome/35.0.1916.153 Safari/537.36 FirePHP/4Chrome');
define('TIMEOUT', 5);
define('MAXREDIRS', 3);

class GetHTML
{
    protected $url  = '';
    protected $html = '';

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function collect_data(): string
    {
        self::retrieveHTML();
        self::miscUpdate();

        //Continue with marknotes\plugins\task\fetch\gethtml.php

        return $this->html;
    }

    /**
     * Retrieve the HTML of the remote page.
     *
     * @return bool
     */
    private function retrieveHTML(): bool
    {
        $ch = curl_init($this->url);

        curl_setopt($ch, CURLOPT_USERAGENT, UA);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, TIMEOUT);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, MAXREDIRS);

        $this->html = curl_exec($ch);

        curl_close($ch);

        return true;
    }

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
}

/**
 * Entry point.
 */

// Retrieve data posted by axios
$_POST = json_decode(file_get_contents('php://input'), true);

// Get the URL and make it readable
$url   = trim(base64_decode($_POST['url']));

// DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG
// DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG
// DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG
// DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG
if ('' == $url) {
    $url = 'https://www.marknotes.fr/docs/Windows/Changer%20son%20wallpaper.html';
}
// DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG
// DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG
// DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG
// DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG
// DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG - DEBUG

if ('' == $url) {
    die('crawl.php requires an URL to crawl');
}

$html = new GetHTML($url);
echo $html->collect_data();
