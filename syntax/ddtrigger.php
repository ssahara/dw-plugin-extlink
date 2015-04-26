<?php
/**
 * DokuWiki Plugin ExtLink Dropdown; ddtrigger
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 * @see also http://labs.abeautifulsite.net/jquery-dropdown/
 *
 * SYNTAX:
 *    Trigger Link:
 *            [[dropdown>#pid .class|text]]
 *            [[dropdown>!#pid|text]]   ...dropdown disabled
 *            [[dropdown>#pid|{{image|title of image}} and text]]
 *
 *    Dropdown Content:
 *             <dropdown-panel #pid .class> ... </dropdown-panel>
 *
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_extlink_ddtrigger extends DokuWiki_Syntax_Plugin {

    protected $entry_pattern = '\[\[dropdown>!?#.*?\|(?=.*?\]\])';
    protected $exit_pattern  = '\]\]';

    protected $triggerClass  = 'plugin_dropdown_ddtrigger';

    protected $attributes = array(
        'id', 'class', 'style', 'title',
    );

    public function getType()  { return 'formatting'; }
    public function getAllowedTypes() { return array('formatting', 'substition', 'disabled'); }
    public function getPType() { return 'normal'; }
    public function getSort()  { return 299; }

    public function connectTo($mode) {
        $this->Lexer->addEntryPattern($this->entry_pattern,
            $mode, substr(get_class($this), 7)
        );
    }
    public function postConnect() {
        $this->Lexer->addExitPattern($this->exit_pattern,
            substr(get_class($this), 7)
        );
    }


    /*
     * handle syntax
     */
    public function handle($match, $state, $pos, Doku_handler $handler){
        $opts = array();

        switch ($state) {
            case DOKU_LEXER_ENTER:
                $match = substr($match, 11, -1); //strip '[[dropdown>' and '|'
                list($pid, $param) = explode(' ', trim($match), 2);

                if (!plugin_isdisabled('extlink')) {
                    $args = $this->loadHelper('extlink_parser');
                    $opts = $args->parse($param);
                }
                $opts['pid'] = trim($pid);   // panel id
                return array($state, $opts);

            case DOKU_LEXER_UNMATCHED:
                $handler->_addCall('cdata', array($match), $pos);
                return false;

            case DOKU_LEXER_EXIT:
                return array($state, $opts);
        }
        return false;
    }

    /*
     * Render output
     */
    public function render($format, Doku_renderer $renderer, $indata){

        if (empty($indata)) return false;
        list($state, $opts) = $indata;

        if ($format != 'xhtml') return false;

        switch($state) {
            case DOKU_LEXER_ENTER:
                $attrs['data-jq-dropdown'] = '#'.ltrim($opts['pid'], '#');
                $attrs['title'] = '↓dropdown';

                // class attribute
                $class = $this->triggerClass;
                if (array_key_exists('class', $opts)) {
                    $class.= ' '.$opts['class'];
                    unset($opts['class']);
                }
                if ($opts['pid'][0] == '!'){
                    $opts['pid'] = ltrim($opts['pid'], '!');
                    if (strpos($class, 'jq-dropdown-disabled') === false) {
                        $class.= ' jq-dropdown-disabled';
                    }
                }
                $attrs['class'] = $class;

                // additinal allowed attributes
                foreach($this->attributes as $key) {
                    if (array_key_exists($key, $opts)) $attrs[$key] = $opts[$key];
                }
                if (mb_substr($attrs['title'],0,1) != '↓') {
                    $attrs['title'] = '↓'.$attrs['title'];
                }

                $renderer->doc .= '<span '.buildAttributes($attrs, true).'>';
                break;

            case DOKU_LEXER_EXIT:
                $renderer->doc .= '</span>';
                break;
        }
        return true;
    }

}

