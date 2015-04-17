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

require_once(dirname(__FILE__).'/object.php');

class syntax_plugin_extlink_iframe extends syntax_plugin_extlink_object {

    protected $tagname = 'iframe';
    protected $entry_pattern   = '<iframe\b.*?\>(?=.*?</iframe>)';
    protected $exit_pattern    = '</iframe>';
    protected $special_pattern = '{{iframe\b.*?\>.*?}}';

    protected $attributes = array(
        'src', 'width', 'height',
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

}
