<?php
/**
 * DokuWiki Plugin ExtLink; dropdown (Action component)
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class action_plugin_extlink_dropdown extends DokuWiki_Action_Plugin {

    /**
     * register the eventhandlers
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'metaheader_output');
    }

    /**
     * Preload javascript and stylesheet
     */
    public function metaheader_output(&$event, $param) {

        $event->data['script'][] = array(
            'type' => 'text/javascript',
            'src' => DOKU_BASE.'lib/plugins/extlink/jquery-dropdown/jquery.dropdown.min.js',
            '_data' => '',
        );

        $event->data['link'][] = array(
            'type' => 'text/css',
            'rel' => 'stylesheet',
            'href' => DOKU_BASE.'lib/plugins/extlink/jquery-dropdown/jquery.dropdown.min.css',
        );
        $event->data['link'][] = array(
            'type' => 'text/css',
            'rel' => 'stylesheet',
            'href' => DOKU_BASE.'lib/plugins/extlink/dropdown.css',
        );
    }

}
