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

require_once(dirname(__FILE__).'/atag.php');

class syntax_plugin_extlink_media extends syntax_plugin_extlink_atag {

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
}
