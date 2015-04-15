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
    protected $image_pattern = '{{![^>\n]*?\>[^[\]{}|]*?\.(?:png|gif|jpg|jpeg) *\|.*?}}';

    // match media file link with title
    protected $entry_pattern = '{{![^>\n]*?\>[^[\]{}|]*?\|(?=.*?}})';
    protected $exit_pattern  = '}}';

    // match media file link without title
    protected $special_pattern = '{{![^>\n]*?\>[^[\]{}|]*?}}'; // no title

    public function getType()  { return 'formatting'; }
    public function getAllowedTypes() { return array('formatting', 'substition', 'disabled'); }
    public function getPType() { return 'normal'; }
    public function getSort()  { return 319; } // < Doku_Parser_Mode_media(=320)

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->image_pattern,
            $mode, substr(get_class($this), 7));
        $this->Lexer->addEntryPattern($this->entry_pattern,
            $mode, substr(get_class($this), 7));
        $this->Lexer->addSpecialPattern($this->special_pattern,
            $mode, substr(get_class($this), 7));
    }
    public function postConnect() {
        $this->Lexer->addExitPattern($this->exit_pattern,
            substr(get_class($this), 7));
    }

    /**
     * handle syntax
     */
    public function handle($match, $state, $pos, Doku_Handler $handler){

        switch ($state) {
            case DOKU_LEXER_SPECIAL:   // $match ends ']]' or '}}'
            case DOKU_LEXER_ENTER:     // $match ends '|'

                if ($this->getPluginComponent() == 'atag') {
                    $match = trim($match, '[]|');
                } elseif ($this->getPluginComponent() == 'media') {
                    $match = trim($match, '{}|');
                }

                list($params, $link) = explode('>', $match, 2);

                // check last part of params (shotcut of interwiki)
                if ($this->getPluginComponent() == 'atag') {
                    $shortcut = substr($params, strrpos($params, ' ')+1);
                    if (in_array($shortcut, array_keys(getInterwiki()))) {
                        $link = $shortcut.'>'.$link;
                        $params = substr($params, 0, strrpos($params, ' '));
                    }
                    $params = rtrim($params);
                }

                // get the first param (target attribute of <a> tag)
                list($target, $params) = explode(' ', $params, 2);
                if ($target == '!!') {
                    $target = 'window';
                } else {
                    $target = substr($target, 1); // drop '!'
                }

                // parameters
                if (!empty($params)) {
                    $args = $this->loadHelper($this->getPluginName().'_parser');
                    $opts = $args->parse($params);
                }
                if (!empty($target)) $opts['target'] = $target;
                $opts['link']   = $link;
                return array($state, $opts);

            case DOKU_LEXER_UNMATCHED: // link title
                $handler->_addCall('cdata', array($match), $pos);
                return false;

            case DOKU_LEXER_EXIT:      // $match is ']]' or '}}'
                return array($state, '');
        }
        return false;
    }

    /**
     * Render output
     */
    public function render($format, Doku_Renderer $renderer, $data) {

        if ($format != 'xhtml') return false;
        list($state, $opts) = $data;

        switch($state) {
            case DOKU_LEXER_SPECIAL:
            case DOKU_LEXER_ENTER:

                if ($this->getPluginComponent() == 'atag') {
                    $link = '[['.$opts['link'].']]';
                } elseif ($this->getPluginComponent() == 'media') {
                    $link = '{{'.$opts['link'].'}}';
                }

                $html = strip_tags(p_render($format, p_get_instructions($link), $info), '<a><img>');
                $html = trim($html);
                if ($state == DOKU_LEXER_ENTER) {
                    $html = substr($html, 0, -4); // drop close tag "</a>"
                }
                // separate <a> and <img> elements
                if (strpos($html, '<img') !== false) {
                    $image = strstr($html, '<img');
                    $html  = strstr($html, '<img', true);
                } else {
                    $html = substr($html, 0, strpos($html, '>')+1); // drop auto title of media
                }

                // width and hight of image
                if (isset($image)) {
                    foreach( array('width','height') as $key) {
                        if (array_key_exists($key, $opts)) {
                            if (is_numeric($opts[$key])) {
                                // overwrite width or height value of img-tag
                                $image = $this->setAttribute($image, $key, $opts[$key]);
                            } else {
                                // add style attribure of img-tag
                                $css = $key.':'.$opts[$key].';';
                                $image = $this->setAttribute($image, 'style', $css);
                            }
                        }
                        unset($opts[$key]);
                    }
                }

                // set target attribute
                if (!empty($opts['target'])) {
                    $html = $this->setAttribute($html, 'target', $opts['target']);
                }

                // open the linked document in a new window
                if ($opts['target'] == 'window') {
                    // add JavaScript to open a new window
                    $html = $this->setAttribute($html, 'onclick', $this->_window_open($opts));
                    // add class
                    $html = $this->setAttribute($html, 'class', 'openwindow', true);
                }

                $renderer->doc.= $html;
                $renderer->doc.= ($image)? $image : '';
                break;

            case DOKU_LEXER_EXIT:
                $renderer->doc.= '</a>';
                break;
        }
        return true;
    }

    /**
     * Set or Append attribute to html tag
     *
     * @param  (string) $html   subject of replacement
     * @param  (string) $key    name of attribute
     * @param  (string) $value  value of attribute
     * @param  (string) $append true if appending else overwrite
     * @return (string) replaced html
     */
    private function setAttribute($html, $key, $value, $append=false) {
        if (strpos($html, ' '.$key.'=') !== false) {
            $search = '/\b('.$key.')=([\"\'])(.*?)\g{-2}/';
            if ($append) {
                $replacement = '${1}=${2}'.$value.' ${3}${2}';
            } else {
                $replacement = '${1}=${2}'.$value.'${2}';
            }
            $html = preg_replace($search, $replacement, $html, 1);
        } elseif (strpos($html, ' ') !== false) {
            $search = strstr($html, ' ', true);
            $replacement = $search.' '.$key.'="'.$value.'"';
            $html = str_replace($search, $replacement, $html);
        } else {
            $html =rtrim($html, ' />').' '.$key.'="'.$value.'">';
        }
        return $html;
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
