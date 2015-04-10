<?php
/**
 * DokuWiki Plugin ExtLink; Syntax img
 * Google Drawing の 埋め込みHTML をそのまま使えるようにする。
 * DokuWiki本来のシンタックスとは異なるサイズ指定方法を使えるようにする。
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 * SYNTAX: <img src=... />
 *
 *         {{example.png?200x100|title}}   DokuWiki本来のシンタックス
 *
 *         {{img w200 h100> id?nolink|title}}
 *         {{img w100 h100> url|title}}
 *
 *  HTML5でサポートされている属性のみを処理する。
 *  @see also http://www.w3schools.com/tags/tag_img.asp
 *  シンタックス内でのイメージアライメントは扱わない。
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_extlink_img extends DokuWiki_Syntax_Plugin {

    protected $match_pattern = '\<img\b.*?\>';
    protected $entry_pattern = '{{img\b.*?>/*?\|(?=.*?}})';
    protected $exit_pattern  = '}}';

    public function getType()  { return 'substition'; }
    public function getPType() { return 'normal'; }
    public function getSort()  { return 305; }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->match_pattern, $mode, substr(get_class($this), 7));
        $this->Lexer->addEntryPattern($this->entry_pattern, $mode, substr(get_class($this), 7));
    }
    public function postConnect() {
        $this->Lexer->addExitPattern($this->exit_pattern, substr(get_class($this), 7));
    }

    /**
     * handle syntax
     */
    public function handle($match, $state, $pos, Doku_Handler $handler){

        switch ($state) {
            case DOKU_LEXER_SPECIAL:   // <img ...>
                if ($this->getConf('img_direct')) {
                    return array($state, $match);
                }
            case DOKU_LEXER_ENTER:     // {{img  >   |  // タイトルは含まれない
                $match = substr(trim($match, '{}<>|'), strlen('img'));

                list($params, $id) = explode('>', $match, 2);

                // handle parameters
                $params = trim($params);
                if (!empty($params)) {
                    $args = $this->loadHelper($this->getPluginName());
                    $opts = $args->parse($params);
                }

                // handle id, check alignment (wiki-style)
                if (!empty($id)) {
                    $ralign = (bool)preg_match('/^ /',$id);
                    $lalign = (bool)preg_match('/ $/',$id);
                    if ( $lalign && $ralign ) { $align = 'center';
                    } else if ( $ralign ) {     $align = 'right';
                    } else if ( $lalign ) {     $align = 'left';
                    } else { $align = NULL; }

                    $opts['src']   = trim($id);

                    // convert $align to $opts['style']
                    if (!is_null($align)) {
                        $css = 'text-align:'.$align.';';
                        $opts['style'] = implode(' ', array($css, $opts['style']));
                    }
                }
                return array(State, $opts);

            case DOKU_LEXER_UNMATCHED:
                // ignore title of "{{img> id | title }}"
                break;
            case DOKU_LEXER_EXIT:
                return array($state, '');
        }
        return false;
    }

    /**
     * Render output
     */
    public function render($format, Doku_Renderer $renderer, $indata) {

        if (empty($indata)) return false;
        list($state, $opts) = $indata;
        if ($format != 'xhtml') return false;

        switch($state) {
            case DOKU_LEXER_SPECIAL:   // <img ...>
                if ($this->getConf('img_direct')) {
                    $renderer->doc .= $opts;
                    return true;
                }
            case DOKU_LEXER_ENTER:     // {{img  >   |  // タイトルは含まれない
                $html = '<img';

                // alt, height, ismap, src, usemap, width のほか、title, id, class, style のみ使用可能とする
                // src attribute
                if (isset($opts['src'])) {
                    $url = $this->_resolveSrcUrl($opts['src']);
                    $html.= ' src="'.$url.'"';
                }

                // width and height
                foreach( array('width', 'height') as $key) {
                    if (array_key_exists($key, $opts)) {
                        if (is_numeric($opts[$key])) {
                            $html.= ' '.$key.'='.$opts[$key];
                        } else {
                            $css = $key.':'.$opts[$key].';';
                            $opts['style'] = implode(' ', array($css, $opts['style']));
                        }
                    }
                }

                // id, class, style, title, alt
                foreach( array('id', 'class', 'style', 'title', 'alt') as $key) {
                    if (array_key_exists($key, $opts)) {
                        $html.= ' '.$key.'="'.$opts[$key].'"';
                    }
                }

                $html.= '>';
                $renderer->doc .= $html;
                break;

            case DOKU_LEXER_UNMATCHED:
                // ignore title of "{{img> id | title }}"
                break;
            case DOKU_LEXER_EXIT:
                break;
        }
        return true;
    }


    /**
     * resolve src attribute of img tags
     */
    private function _resolveSrcUrl($url) {
        global $ID;
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        } else { // assume resource as linkid, which may include section
            $linkId = $url;
            resolve_pageid(getNS($ID), $linkId, $exists);
            list($ext, $mime) = mimetype($linkId);
            if (substr($mime, 0, 5) == 'image') { // mediaID
                $url = ml($linkId);
            } elseif ($exists) { //pageID
                list($id, $section) = explode('#', $linkId, 2);
                $url = wl($id);
                $url.= ((strpos($url,'?')!==false) ? '&':'?').'do=export_xhtml';
                $url.= $section ? '#'.$section : '';
            } else {
                //msg('page not exists ('.$linkId.')',-1);
                $url = false;
            }
            return $url;
        }
    }

}
