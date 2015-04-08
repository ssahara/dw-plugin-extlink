<?php
/**
 * DokuWiki Plugin ExtLink; helper component
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Satoshi Sahara <sahara.satoshi@gmail.com>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_extlink extends DokuWiki_Plugin {

    /**
     * argument parser
     * return each named arguments as array variable
     * also parse size parameters (ex. 100x100) and element id (ex. #sample)
     *
     * @param (string) $args        arguments
     * @return (array) parsed argument
     */
    public function parse($args='') {
        $opts = array();

        // parse key = "value" or key = value foramt
        $val = '([\'"`])(?:[^\'"`]*)\g{-1}|[^\'"`\s]+'; // 前半はクォート式、後半は非クォート式
        $pattern = "/(\w+)\s*=\s*($val)/";
        preg_match_all($pattern, $args, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $opts[strtolower($m[1])] = $m[2];
            $args = str_replace($m[0], '', $args); // remove parsed substring
            msg('parse: opts['.$m[1].']='.$m[2] ,0);
        }

        // サイズ指定 （w100% h50px 形式）|（100x50 形式）
        $val = '\b([wh])([-+]?\d*(?:\.\d+)?(?:em|pt|px|%)?)'.'|';
        $val.= '\b(\d+)[xX](\d+)\b';
        $pattern = "/(?:$val)/";
        preg_match_all($pattern, $args, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            if (count($m) == 3) {
                $opts[strtolower($m[1])] = $m[2];
            } else {
                $opts['width']  = $m[3];
                $opts['height'] = $m[4];
            }
            $args = str_replace($m[0], '', $args); // remove parsed substring
        }

        // id 最初に指定したもののみ有効
        if (preg_match('/#([\w-]+)/', $args, $m)) {
            $opts['id'] = $m[1];
        }
        
        // 残り //連続する半角スペースを1つの半角スペースへ
        $args = preg_replace('/\s+/', ' ', $words);
        $opts['residue'] = trim($args);

        return $opts;
    }

}
