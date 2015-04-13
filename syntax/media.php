<?php
/**
 * DokuWiki Plugin ExtLink; Syntax media
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 * SYNTAX: {{!taget params> id or url| title }}
 *
 *         {{example.pdf|title}}           original DokuWiki syntax
 *
 *         {{!_blank >example.pdf|title}}
 *         {{!! w700 h400 >example.pdf|title}}
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_extlink_media extends DokuWiki_Syntax_Plugin {

    // match image with it's title (not link title)
    protected $image_pattern = '{{![^\n]*?\>[^}|]*?\.(?:png|gif|jpg|jpeg) *\|.*?}}';

    // match media file link with title
    protected $entry_pattern = '{{![^\n]*?\>[^{]*?\|(?=.*?}})';
    protected $exit_pattern  = '}}';

    // match media file link without title
    protected $special_pattern = '{{![^\n]*?\>[^\|\n]+?}}'; // no title

    public function getType()  { return 'substition'; }
    public function getAllowedTypes() { return array('formatting', 'substition', 'disabled'); }
    public function getPType() { return 'normal'; }
    public function getSort()  { return 305; }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->image_pattern,
            $mode, substr(get_class($this), 7));
        $this->Lexer->addEntryPattern($this->entry_pattern,
            $mode, substr(get_class($this), 7));
        $this->Lexer->addSpecialPattern($this->special_pattern, $mode, substr(get_class($this), 7));
    }
    public function postConnect() {
        $this->Lexer->addExitPattern($this->exit_pattern,
            substr(get_class($this), 7));
    }

    /**
     * handle syntax
     */
    public function handle($match, $state, $pos, Doku_Handler $handler){
        global $ID;

        switch ($state) {
            case DOKU_LEXER_SPECIAL:   // $match ends '}}'
            case DOKU_LEXER_ENTER:     // $match ends '|'
                $match = trim($match, '{}|');

                list($params, $link) = explode('>', $match, 2);

                // get the first param (target attribute of <a> tag)
                list($target, $params) = explode(' ', $params, 2);
                if ($target == '!!') {
                    $target = 'window';
                } else {
                    $target = substr($target, 1); // drop '!'
                }

                // parameters
                if (!empty($params)) {
                    $args = $this->loadHelper($this->getPluginName());
                    $opts = $args->parse($params);
                }
                if (!empty($target)) $opts['target'] = $target;
                $opts['link']   = $link;
                return array($state, $opts);

            case DOKU_LEXER_UNMATCHED: // link title
                $handler->_addCall('cdata', array($match), $pos);
                return false;

            case DOKU_LEXER_EXIT:      // $match is '}}'
                return array($state, '');
        }
        return false;
    }

    /**
     * Render output
     */
    public function render($format, Doku_Renderer $renderer, $data) {
        global $ID, $conf;

        if ($format != 'xhtml') return false;
        list($state, $opts) = $data;

        switch($state) {
            case DOKU_LEXER_SPECIAL:
            case DOKU_LEXER_ENTER:

                $link = '{{'.$opts['link'].'}}';
                $html = strip_tags(p_render($format, p_get_instructions($link), $info), '<a><img>');
                $html = trim($html);
                if ($state == DOKU_LEXER_ENTER) {
                    $html = substr($html, 0, -4); // drop "</a>"
                }
                // separate <a> and <img> elements
                if (strpos($html, '<img') !== false) {
                    $image = strstr($html, '<img');
                    $html  = strstr($html, '<img', true);
                } else {
                    $html = substr($html, 0, strpos($html, '>')+1); // drop auto title of media
                }

                // width and hight for image
                if (isset($image)) {
                    foreach( array('width','height') as $key) {
                        if (array_key_exists($key, $opts)) {
                            if (is_numeric($opts[$key])) {
                                // overdide width or height attribure of img-tag
                                if (strpos($image, $key.'=') !== false) {
                                    $image = preg_replace('/\b'.$key.'="?\d+"?/', $key.'='.$opts[$key], $image);
                                } else {
                                    $image = str_replace('<img', '<img '.$key.'='.$opts[$key], $image);
                                }
                            } else {
                                // prepare style attribure of img-tag
                                $css = $key.':'.$opts[$key].';';
                                // append style
                                if (strpos($image, 'style="') !== false) {
                                    $image = str_replace('style="', 'style="'.$css.' ', $image);
                                } else {
                                    $image = str_replace('<img', '<img style="'.$css.'" ', $image);
                                }
                            }
                        }
                        unset($opts[$key]);
                    }
                }

                // set target attribute
                if (!empty($opts['target'])) {
                    $ptn = '/(target=)([\"\']).*\g{-1})/';
                    if (preg_match($ptn, $html) === 1) {
                        $html = preg_replace($ptn, '${1}${2}'.$opts['target'].'${2}', $html);
                    } else {
                        $html = str_replace('<a ', '<a target="'.$opts['target'].'" ', $html);
                    }
                }

                // opens the linked document in a new window
                if ($opts['target'] == 'window') {
                    //add JavaScript to open a new window
                    $js = ' onclick="'.$this->_window_open($opts).'"';
                    $html = str_replace('>', $js.'>',$html);
                    // add class
                    $ptn = '/(class=[\"\'])(.*[\"\'])/';
                    if (preg_match($ptn, $html) === 1) {
                        $html = preg_replace($ptn, '${1}openwindow ${2}', $html);
                    } else {
                        $html = str_replace('<a ', 'class="openwindown" ', $html);
                    }
                }

                $renderer->doc.= $html;
                $renderer->doc.= ($image)?:'';
                break;

            case DOKU_LEXER_EXIT:
                $renderer->doc.= '</a>';
                break;
        }
        return true;
    }


    /**
     * JavaScript to open a new window, executed as onclick event
     */
    private function _window_open($opts) {

        if (array_key_exists('width',  $opts)) $win['width']  = $opts['width'];
        if (array_key_exists('height', $opts)) $win['height'] = $opts['height'];
        $win['resizeable'] = array_key_exists('resizeable', $opts) ? $opts['resizeable'] : 1;
        $win['location']   = array_key_exists('location', $opts) ? $opts['location'] : 1;
        $win['status']     = array_key_exists('status', $opts) ? $opts['status'] : 1;
        $win['titlebar']   = array_key_exists('titlebar', $opts) ? $opts['titlebar'] : 1;
        $win['menubar']    = array_key_exists('menubar', $opts) ? $opts['menubar'] : 0;
        $win['toolbar']    = array_key_exists('toolbar', $opts) ? $opts['toolbar'] : 0;
        $win['scrollbars'] = array_key_exists('scrollbars', $opts) ? $opts['scrollbars'] : 1;

        foreach ($win as $key => $value) { $spec.= $key.'='.$value.','; }
        $spec = rtrim($spec, ',');

        $js = "javascript:void window.open(this.href,'_blank','".$spec."'); return false;";
        return $js;
    }

}
