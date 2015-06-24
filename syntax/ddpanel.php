<?php
/**
 * DokuWiki Plugin ExtLink Dropdown; ddpanel
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

class syntax_plugin_extlink_ddpanel extends DokuWiki_Syntax_Plugin {

    protected $entry_pattern = '<dropdown-panel\b.*?>(?=.*?</dropdown-panel>)';
    protected $exit_pattern  = '</dropdown-panel>';

    protected $attributes = array(
        'id', 'class', 'style', 'title',
    );

    protected $panelClass = 'jq-dropdown jq-dropdown-tip jq-dropdown-relative';


    function getType(){ return 'formatting';}
    function getAllowedTypes() { return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs'); }
    function getPType(){ return 'stack';}
    function getSort(){ return 195; }

    function accepts($mode) {
        return parent::accepts($mode);
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addEntryPattern($this->entry_pattern, $mode,
        substr(get_class($this), 7) );
    }

    function postConnect() {
        $this->Lexer->addExitPattern($this->exit_pattern,
            substr(get_class($this), 7) );
        $this->Lexer->addPattern('[ \t]*={2,}[^\n]+={2,}[ \t]*(?=\n)',
            substr(get_class($this), 7) );
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
        global $conf;
        switch ($state) {
            case DOKU_LEXER_ENTER:
            case DOKU_LEXER_SPECIAL:
                $data = strtolower(trim(substr($match,strpos($match,' '),-1)," \t\n/"));
                return array($state, $data);

            case DOKU_LEXER_UNMATCHED:
                $handler->_addCall('cdata', array($match), $pos);
                break;

            case DOKU_LEXER_MATCHED:
                // we have a == header ==, use the core header() renderer
                // (copied from core header() in inc/parser/handler.php)
                $title = trim($match);
                $level = 7 - strspn($title,'=');
                if($level < 1) $level = 1;
                $title = trim($title,'=');
                $title = trim($title);

                $handler->_addCall('header',array($title,$level,$pos), $pos);
                // close the section edit the header could open
                if ($title && $level <= $conf['maxseclevel']) {
                    $handler->addPluginCall('wrap_closesection', array(), DOKU_LEXER_SPECIAL, $pos, '');
                }
                break;

            case DOKU_LEXER_EXIT:
                return array($state, '');
        }
        return false;
    }


    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $indata) {

        if (empty($indata)) return false;
        list($state, $data) = $indata;

        if ($format == 'xhtml'){
            /** @var Doku_Renderer_xhtml $renderer */
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    // add a section edit right at the beginning of the wrap output
                    $renderer->startSectionEdit(0, 'plugin_wrap_start');
                    $renderer->finishSectionEdit();
                    // add a section edit for the end of the wrap output. This prevents the renderer
                    // from closing the last section edit so the next section button after the wrap syntax will
                    // include the whole wrap syntax
                    $renderer->startSectionEdit(0, 'plugin_wrap_end');

                    $args = $this->loadHelper('extlink_parser');
                    $opts = $args->parse($data);

                    // class attribute for dropdown panel
                    if (array_key_exists('class', $opts)) {
                        $attrs['class'] = $opts['class'].' '.$this->panelClass;
                        unset($opts['class']);
                    } else {
                        $attrs['class'] = $this->panelClass;
                    }

                    // additinal allowed attributes
                    foreach($this->attributes as $key) {
                        if (array_key_exists($key, $opts)) $attrs[$key] = $opts[$key];
                    }

                    // prevent the panel force shown in sidebar
                    $renderer->doc .= '<div class="'.substr(get_class($this), 7).'">';

                    // output dropdown panel
                    $renderer->doc .= '<div '.buildAttributes($attrs, true).'>';
                    $renderer->doc .= '<div class="jq-dropdown-panel">';
                    break;

                case DOKU_LEXER_EXIT:
                    $renderer->doc .= '</div></div>';
                    $renderer->doc .= '</div>'; // prevent the panel force shown in sidebar
                    $renderer->finishSectionEdit();
                    break;
            }
            return true;
        }
        return false;
    }

}
