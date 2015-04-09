<?php
/**
 * DokuWiki Plugin ExtLink; Syntax atag
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 * SYNTAX: [[!taget framename> id or url| title ]]
 *
 *         [[doku>interwiki | InterWiki]]        original DokuWiki syntax
 *         [[!doku>interwiki| **InterWiki**]]    ExLink pulgin syntax, with BOLD title
 *
 *         [[!_brank doku>interwiki]]      open in a new tab or window
 *         [[!_self  doku>interwiki]]      open in the same frame as it was clicked
 *         [[!framename doku>interwiki]]   open in a named frame
 *
 *         [[!!doku>interwiki]]              open in a new window using JavaScript
 *         [[!! w640 h300 doku>interwiki]]   in a new given window size
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_extlink_atag extends DokuWiki_Syntax_Plugin {

    // タイトルありを処理するパターン
    protected $entry_pattern = '\[\[!.*?\>[^{]*?\|(?=.*?\]\])';
    protected $exit_pattern  = '\]\]';

    // タイトルなしを処理するパターン
    protected $match_pattern = '\[\[!.*?\>[^\|\n]+?\]\]'; // no title

    public function getType()  { return 'substition'; }
    public function getAllowedTypes() { return array('formatting', 'substition', 'disabled'); }
    public function getPType() { return 'normal'; }
    public function getSort()  { return 295; } // < Doku_Parser_Mode_internallink(=300)

    public function connectTo($mode) {
        // 順番が大事
        $this->Lexer->addEntryPattern($this->entry_pattern, $mode, substr(get_class($this), 7));
        $this->Lexer->addSpecialPattern($this->match_pattern, $mode, substr(get_class($this), 7));
    }
    public function postConnect() {
        $this->Lexer->addExitPattern($this->exit_pattern, substr(get_class($this), 7));
    }

    /**
     * handle syntax
     */
    public function handle($match, $state, $pos, Doku_Handler $handler){

        switch ($state) {
            case DOKU_LEXER_SPECIAL: // タイトル指定なし
            case DOKU_LEXER_ENTER:   // タイトル指定あり
                $match = substr($match, 2);  //strip '[['
                $match = rtrim($match, ']|'); //strip ']]' or '|'

                list($params, $link) = explode('>', $match, 2);
                
                // check last part of params (shotcut of interwiki)
                $shortcut = substr($params, strrpos($params, ' ')+1);
                if (in_array($shortcut, array_keys(getInterwiki()))) {
                    $link = $shortcut.'>'.$link;
                    $params = substr($params, 0, strrpos($params, ' '));
                }
                $params = rtrim($params);
                
                // get the first param (target attribute of <a> tag)
                list($target, $params) = explode(' ', $params, 2);
                if ($target == '!!') {
                    $target = 'window';
                } else {
                    $target = substr($target, 1); // drop '!'
                }

                error_log('LINK2 handle0: target='.$target.' params='.$params.' link='.$link);

                // parameters
                if (!empty($params)) {
                    $args = $this->loadHelper($this->getPluginName());
                    $opts = $args->parse($params);
                }
                if (!empty($target)) $opts['target'] = $target;
                $opts['link']   = $link;
                return array($state, $opts);

            case DOKU_LEXER_UNMATCHED:
                $handler->_addCall('cdata', array($match), $pos);
                return false;

            case DOKU_LEXER_EXIT:
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
            case DOKU_LEXER_SPECIAL: // タイトル指定なし
            case DOKU_LEXER_ENTER:   // タイトル指定あり

                $link = '[['.$opts['link'].']]';
                $html = strip_tags(p_render($format, p_get_instructions($link), $info), '<a>');
                $html = trim($html);
                if ($state == DOKU_LEXER_ENTER) {
                    $html = substr($html, 0, strpos($html, '>')+1);
                }

                // set target attribute
                if (!empty($opts['target'])) {
                    $ptn = '/(target=[\"\']).*([\"\'])/';
                    if (preg_match($ptn, $html) === 1) {
                        $html = preg_replace($ptn, '${1}'.$opts['target'].'${2}', $html);
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
                error_log('LINK2 render0: state='.$state.' html='.$html);

                $renderer->doc.= $html;
                break;

            case DOKU_LEXER_EXIT:
                $renderer->doc.= '</a>';
                break;
        }
        return true;
    }


    private function _window_open($opts) {

        if (array_key_exists('width',  $opts)) win['width']  = $opts['width'];
        if (array_key_exists('height', $opts)) win['height'] = $opts['height'];
        $win['resizeable'] = array_key_exists('resizeable', $opts) ? $opts['resizeable'] : 1;
        $win['location'] = array_key_exists('location', $opts) ? $opts['location'] : 1;
        $win['status'] = array_key_exists('status', $opts) ? $opts['status'] : 1;
        $win['titlebar'] = array_key_exists('titlebar', $opts) ? $opts['titlebar'] : 1;
        $win['menubar'] = array_key_exists('menubar', $opts) ? $opts['menubar'] : 0;
        $win['toolbar'] = array_key_exists('toolbar', $opts) ? $opts['toolbar'] : 0;
        $win['scrollbars'] = array_key_exists('scrollbars', $opts) ? $opts['scrollbars'] : 1;

        foreach ($win as $key => $value) { $spec.= $key.'='.$value.','; }
        $spec = rtrim($spec, ',');
        //$spec = 'resizeable=1,location=1,status=1,titlebar=1,menubar=0,toolbar=0,scrollbars=1';
        //$spec.= ($width) ? ',width='.$width : '';
        //$spec.= ($height) ? ',height='.$height : '';
        $js = "javascript:void window.open(this.href,'_blank','".$spec."'); return false;";
        return $js;
    }

}
