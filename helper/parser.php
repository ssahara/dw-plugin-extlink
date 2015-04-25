<?php
/**
 * DokuWiki Plugin ExtLink; parser helper component
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Satoshi Sahara <sahara.satoshi@gmail.com>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_extlink_parser extends DokuWiki_Plugin {

    /**
     * argument parser
     * return each named arguments as array variable
     * also parse size parameters (ex. 100x100) and element id (ex. #sample)
     *
     * How to use parser helper components from other plugin
     *
     *     $args = $this->loadHelper('extlink_parser');
     *     $opts = $args->parse($params);
     *
     * @param (string) $args        arguments
     * @return (array) parsed argument
     */
    public function parse($args='') {
        $opts = array();

        // parse key = "value" or key = value foramt
        $val = '([\'"`])(?:[^\'"`]*)\g{-1}|[^\'"`\s]+';
        $pattern = "/(\w+)\s*=\s*($val)/";
        preg_match_all($pattern, $args, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {

            if (preg_match('/^([\'"`])(.*)\g{-2}$/', $m[2], $match)) {
                $m[2] = $match[2]; // de-quote
            }

            $opts[strtolower($m[1])] = $m[2];
            $args = str_replace($m[0], '', $args); // remove parsed substring
        }

        // size (w100% h50px) | (100x50)
        $val = '\b([wh])([-+]?\d*(?:\.\d+)?(?:em|pt|px|%)?)'.'|';
        $val.= '\b(\d+)[xX](\d+)\b';
        $pattern = "/(?:$val)/";
        preg_match_all($pattern, $args, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            if (count($m) == 3) {
                $opts[strtolower($m[1])] = $m[2];
            } else {
                $opts['w'] = $m[3];
                $opts['h'] = $m[4];
            }
            $args = str_replace($m[0], '', $args); // remove parsed substring
        }
        if (!isset($opts['width'])  && isset($opts['w'])) $opts['width']  = $opts['w'];
        if (!isset($opts['height']) && isset($opts['h'])) $opts['height'] = $opts['h'];
        unset($opts['w'], $opts['h']);

        // id : "#" prefixed word (like CSS/jQuery selector)
        if (preg_match('/#([\w-]+)/', $args, $m)) {
            $opts['id'] = $m[1];
            $args = str_replace($m[0], '', $args);
        }

        // class : "." prefixed word (like CSS/jQuery selector)
        $pattern = '/\.([\w-]+)/';
        preg_match_all($pattern, $args, $matches, PREG_PATTERN_ORDER);
        if (count($matches[1]) > 0) {
            $opts['class'] = implode(' ', $matches[1]);
            foreach ($matches[0] as $m) {
                $args = str_replace($m, '', $args);
            }
        }

        // rest, reduce white spaces
        $args = preg_replace('/\s+/', ' ', $words);
        $opts['residue'] = trim($args);

        return $opts;
    }

}
