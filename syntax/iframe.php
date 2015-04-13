<?php
/**
 * DokuWiki Plugin ExtLink; Syntax iframe
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 * SYNTAX: <iframe src=... > ... </iframe>  compatible with <iframe> tag
 *
 *         {{iframe w400 h200 > id| title}} internal page, ?do=export_xhtml
 *         {{iframe w500 h300 > url}}       external page
 *
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_extlink_iframe extends DokuWiki_Syntax_Plugin {

    protected $tagname = 'iframe';
    protected $entry_pattern   = '<iframe\b.*?\>(?=.*?</iframe>)';
    protected $exit_pattern    = '</iframe>';
    protected $special_pattern = '{{iframe\b.*?\>.*?}}';

    protected $attributes = array(
        // 'src', 'width', 'height' are allowed, and we additionally allow
        'name', 'id', 'class', 'style', 'title',
    );


    public function getType()  { return 'substition'; }
    public function getPType() { return 'normal'; }
    public function getSort()  { return 305; }

    public function connectTo($mode) {
        if (!empty($this->special_pattern)) {
            $this->Lexer->addSpecialPattern($this->special_pattern,
                $mode, substr(get_class($this), 7) );
        }
        if ($this->getConf($this->tagname.'_direct')) {
            $this->Lexer->addEntryPattern($this->entry_pattern,
                $mode, substr(get_class($this), 7) );
        }
    }
    public function postConnect() {
        if ($this->getConf($this->tagname.'_direct')) {
            $this->Lexer->addExitPattern($this->exit_pattern,
                substr(get_class($this), 7) );
        }
    }

    /**
     * handle syntax
     */
    public function handle($match, $state, $pos, Doku_Handler $handler){

        switch ($state) {
            case DOKU_LEXER_SPECIAL:   // {{iframe  }} or <img ...>
            case DOKU_LEXER_ENTER:     // <iframe ...></iframe>

                if ( (strpos($match, '<'.$this->tagname) === 0) &&
                      $this->getConf($this->tagname.'_direct') ) {
                    return array($state, $match);
                }

                $match = substr(trim($match, '{}<>'), strlen($this->tagname));

                list($params, $id) = explode('>', $match, 2);
                list($id, $title)  = explode('|', $id, 2);

                // handle parameters
                $params = trim($params);
                if (!empty($params)) {
                    $args = $this->loadHelper($this->getPluginName());
                    $opts = $args->parse($params);
                }
                if (!empty($title)) $opts['title'] = $title;
                if (!empty($id))    $opts['src']   = $id;
                return array($state, $opts);

            case DOKU_LEXER_UNMATCHED:
                // only for browsers that do not support <iframe>
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

        if ($format != 'xhtml') return false;
        list($state, $opts) = $data;

        $attrs['_tag'] = $this->tagname;
        switch($state) {
            case DOKU_LEXER_SPECIAL:   // {{iframe  }} or <img ...>
            case DOKU_LEXER_ENTER:     // <iframe ...></iframe>

                if (!is_array($opts)) return array($state, $match);

                // src attribute
                if (array_key_exists('src', $opts)) {
                    $attrs['src'] = $this->_resolveSrcUrl($opts['src']);
                }

                // width and height
                foreach( array('width', 'height') as $key) {
                    if (array_key_exists($key, $opts)) {
                        if (is_numeric($opts[$key])) {  // size without units (%, em ,..)
                            $attrs[$key] = $opts[$key];
                        } else {
                            $css = $key.':'.$opts[$key].';';
                            $opts['style'] = implode(' ', array($css, $opts['style']));
                        }
                    }
                }

                // additinal allowed attributes
                foreach($this->attributes as $key) {
                    if (array_key_exists($key, $opts)) {
                        $attrs[$key] = $opts[$key];
                    }
                }

                $renderer->doc .= $this->htmlOpenTag($attrs);

                
                if ( ($state == DOKU_LEXER_SPECIAL) &&
                     (substr($this->special_pattern, -1) != '>') ) {
                    $renderer->doc .= $this->htmlCloseTag($attrs) . DOKU_LF;
                }
                break;

            case DOKU_LEXER_EXIT:
                $renderer->doc .= $this->htmlCloseTag($attrs) . DOKU_LF;
                break;
        }
        return true;
    }


    /**
     * resolve src attribute
     */
    protected function _resolveSrcUrl($url) {
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

    protected function htmlOpenTag($attrs) {
        return '<'.$attrs['_tag'].' '.buildAttributes($attrs, true).'>';
    }
    protected function htmlCloseTag($attrs) {
        return '</'.$attrs['_tag'].'>';
    }

}
