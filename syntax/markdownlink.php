<?php
/**
 * DokuWiki Plugin ExtLink; Syntax markdownlink
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 * Usage/Example:
 *    [link name](url "title")
 *
 *    [example](https://example.com "Example site")
 *      -> <a href="https://example.com" title="Example site">example</a>
 */

if(!defined('DOKU_INC')) die();

class syntax_plugin_extlink_markdownlink extends DokuWiki_Syntax_Plugin {

    protected $mode;
    protected $pattern;
    protected $schemes = null;  // registered protocols in conf/schema.conf

    function __construct() {
        $this->mode = substr(get_class($this), 7);
        $this->pattern = '\[[^\r\n]+\]\([^\r\n]*?(?:[ \t]?"[^\r\n]*?")?[ \t]*\)';
    }

    function getType()  { return 'substition'; }
    function getPType() { return 'normal'; }
    function getSort()  { return 301; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->pattern, $mode, $this->mode);
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {

        $n = strpos($match, '](');
        $text = substr($match, 1, $n-1);
        $url = str_replace("\t",' ', trim(substr($match, $n+2, -1)) );

        // check title in string enclosed by double quaotation chars
        if (substr($url, -1) == '"') {
             $title = strstr($url, '"');
             $url = rtrim(str_replace($title, '', $url));
             $title = substr($title, 1, -1);
        } else {
            $title = '';
        }

        // use DokuWiki handler method for email address
        // eg. [send email](mailto:foo@example.com)
        if ($email = preg_replace('/^mailto:/','', $url) !== $url) {
            $handler->internallink($email.'|'.$text, $state, $pos);
            return false;
        }

        return array($state, $text, $url, $title);
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        global $conf;

        if ($format == 'metadata') return false;

        list($state, $text, $url, $title) = $data;

        // Experimental: allow formatting of anchor text
        $text = substr(p_render('xhtml', p_get_instructions($text), $info), 5, -6);

        // external url might be an attack vector, only allow registered protocols
        if (substr($url, 0, 1) !== '/') {
            if (is_null($this->schemes)) $this->schemes = getSchemes();
            $scheme = strtolower(parse_url($url, PHP_URL_SCHEME));
            if (!in_array($scheme, $this->schemes)) {
                if (empty($title)) $title = $url;
                $url = '';
            }
        }

        // render as abbreviation if url does not given
        if (empty($url)) {
                if (empty($title)) $title = $text;
                $renderer->doc .= '<abbr title="'.hsc($title).'">'.hsc($text).'</abbr>';
                return true;
        }

        /** @var Doku_Renderer_xhtml $xhtml_renderer */
        static $xhtml_renderer = null;
        if(is_null($xhtml_renderer)) {
            $xhtml_renderer = p_get_renderer('xhtml');
        }

        // prepare link format
        $link = array();
        $link['target'] = $conf['target']['extern'];
        $link['style']  = '';
        $link['pre']    = '';
        $link['suf']    = '';
        $link['more']   = '';
        $link['class']  = '';
        $link['url']    = $url;

        $link['name']  = $text;
        $link['title'] = $title ?: $url;

        if($conf['relnofollow']) $link['rel'] .= ' nofollow';
        if($conf['target']['extern']) $link['rel'] .= ' noopener';

        // output html of formatted link
        $renderer->doc .= $xhtml_renderer->_formatLink($link);
        return true;
    }
}

