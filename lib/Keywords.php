<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2010 TextRank                                                     |
  +---------------------------------------------------------------------------------+
  | Redistribution and use in source and binary forms, with or without              |
  | modification, are permitted provided that the following conditions are met:     |
  | 1. Redistributions of source code must retain the above copyright               |
  |    notice, this list of conditions and the following disclaimer.                |
  |                                                                                 |
  | 2. Redistributions in binary form must reproduce the above copyright            |
  |    notice, this list of conditions and the following disclaimer in the          |
  |    documentation and/or other materials provided with the distribution.         |
  |                                                                                 |
  | 3. All advertising materials mentioning features or use of this software        |
  |    must display the following acknowledgement:                                  |
  |    This product includes software developed by César D. Rodas.                  |
  |                                                                                 |
  | 4. Neither the name of the César D. Rodas nor the                               |
  |    names of its contributors may be used to endorse or promote products         |
  |    derived from this software without specific prior written permission.        |
  |                                                                                 |
  | THIS SOFTWARE IS PROVIDED BY CÉSAR D. RODAS ''AS IS'' AND ANY                   |
  | EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED       |
  | WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE          |
  | DISCLAIMED. IN NO EVENT SHALL CÉSAR D. RODAS BE LIABLE FOR ANY                  |
  | DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES      |
  | (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;    |
  | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND     |
  | ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT      |
  | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS   |
  | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE                     |
  +---------------------------------------------------------------------------------+
  | Authors: César Rodas <crodas@php.net>                                           |
  +---------------------------------------------------------------------------------+
*/

class Keywords extends TextRank
{

    // build_graph($features, $callback) {{{
    /**
     *  Build Graph
     *
     *  Simple approach to build a graph out of a text using 3-grams
     */
    function build_graph($features, $callback)
    {
        $nsize = 3;
        $size  = count($features);
        for ($i=0; $i < $size; $i++) {
            for ($min=$i-$nsize, $max=$i+$nsize; $min < $max; $min++) {
                if (isset($features[$min]) && $min != $i) {
                    call_user_func($callback, $features[$i], $features[$min]);
                }
            }
        }
    }
    // }}}

    // filter_features(&$features) {{{
    /**
     *  Filter Feature event
     *
     *  Simple stopword cleanup if $lang is setted
     */
    function filter_features(&$features)
    {
        if ($this->lang) {
            $stopword = self::LoadStopWords($this->lang);
            foreach ($features as $id => $feature) {
                if (isset($stopword[$feature])) {
                    unset($features[$id]);
                }
            }
        }
    }
    // }}}

    // get_features($text, &$features) {{{
    /**
     *  Get Features event
     *
     *  Explode words by spaces
     *  
     *  @param string $text
     *  @param array  &$features
     *
     *  @return void 
     */
    function get_features($text, &$features)
    {
        $features = explode(" ", $text);
    }
    // }}}

    // clean_text(&$text) {{{
    /**
     *  Clean text leaving just letters from 
     *  a-z.
     *
     *  @param string &$text
     */
    function clean_text(&$text)
    {
        $text = strtolower($text);
        $text = preg_replace("/[^a-z ]/", " ", $text);
        $text = preg_replace("/ +/", " ", $text);
    }
    // }}}


    function post_ranking(&$result)
    {
        var_dump($result);die();
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
