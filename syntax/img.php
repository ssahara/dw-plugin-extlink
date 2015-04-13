<?php
/**
 * DokuWiki Plugin ExtLink; Syntax img
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 * SYNTAX: <img src=... >
 *
 */

require_once(dirname(__FILE__).'/iframe.php');

class syntax_plugin_extlink_img extends syntax_plugin_extlink_iframe {

    protected $tagname = 'img';
    protected $entry_pattern   = '';
    protected $exit_pattern    = '';
    protected $special_pattern = '<img\b.*?>';

    protected $attributes = array(
        // 'src', 'width', 'height' are allowed, and we additionally allow
        'alt', 'id', 'class', 'style', 'title',
        'ismap', 'usemap',
    );

}
