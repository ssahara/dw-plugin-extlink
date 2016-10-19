<?php
/**
 * markdowku Plugin (https://www.dokuwiki.org/plugin:markdowku)
 * Syntax component for
 * Inline links [name](target "title")
 *
 * @license 2-clause BSD
 * @author Julian Fagir <gnrp@komkon2.de>
 * @see https://vcs.in-berlin.de/schrank21_dokuwiki/artifact/5f0271a6e6dd8f86
 */
/*
Copyright (c) 2014, Julian Fagir
gnrp@komkon2.de
All rights reserved.

Redistribution and use in source and binary forms, with or without 
modification, are permitted provided that the following conditions are met:

 * Redistributions of source code must retain the above copyright notice, this 
   list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this 
   list of conditions and the following disclaimer in the documentation and/or 
   other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE 
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL 
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR 
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, 
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE 
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/


if(!defined('DOKU_INC')) die();

class syntax_plugin_extlink_anchorsinline extends DokuWiki_Syntax_Plugin {

    protected $mode;
    protected $nested_brackets_re;

    function __construct() {
        $this->mode = substr(get_class($this), 7);
        $this->nested_brackets_re = str_repeat('(?>[^\[\]]+|\[', 6) . str_repeat('\])*', 6);
    }

    function getType()  { return 'substition'; }
    function getPType() { return 'normal'; }
    function getSort()  { return 102; }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern(
            '\['.$this->nested_brackets_re.'\]\([ \t]*<?.+?>?[ \t]*(?:[\'"].*?[\'"])?\)',
            $mode,
            $this->mode
        );
    }

    function handle($match, $state, $pos, Doku_Handler $handler) {
        if ($state == DOKU_LEXER_SPECIAL) {
            $text = preg_match(
                '/^\[('.$this->nested_brackets_re.')\]\([ \t]*<?(.+?)>?[ \t]*(?:[\'"](.*?)[\'"])?[ \t]*?\)$/',
                $match,
                $matches);
            $target = $matches[2] == '' ? $matches[3] : $matches[2];
            $title = $matches[1];

            $target = preg_replace('/^mailto:/', '', $target);
            $handler->internallink($target.'|'.$title, $state, $pos);
        }
        return true;
    }

    function render($format, Doku_Renderer $renderer, $data) {
        return true;
    }
}

