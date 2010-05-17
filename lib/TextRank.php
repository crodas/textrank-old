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

/**
 *  Load all the needed files
 */
define('TEXTRANK_DIR', dirname(__FILE__));
require TEXTRANK_DIR."/TextRank_Ranking.php";
require TEXTRANK_DIR."/PageRank.php";
require TEXTRANK_DIR."/Keywords.php";

/**
 *  TextRank main class
 *
 *
 *
 */
abstract class TextRank
{
    private static $_events;
    private static $_stopwords;
    protected $raw_text;
    protected $text;
    protected $features;
    protected $ranking;
    protected $lang;

    function __construct()
    {
        /**
         *  Instance the ranking object
         */
        $this->triggerEvent('ranking_class', array(&$this->ranking));
        if (!$this->ranking InstanceOf TextRank_Ranking) {
            throw new Exception("Invalid ranking object");
        }
    }

    // LoadStopWords(string $lang) {{{
    /**
     *  Load a given stopwords if it exists.
     *
     *  @param string $lang
     *
     *  @return array
     */
    final protected function LoadStopWords($lang)
    {
        $lang = ucfirst($lang);
        if (!isset(self::$_stopwords[$lang])) {
            if (is_readable(TEXTRANK_DIR."/stopwords/{$lang}.txt")) {
                $list = file_get_contents(TEXTRANK_DIR."/stopwords/{$lang}.txt");
                $list = explode("\n", $list);
                $list = array_combine($list, $list);
            } else {
                $list = array();
            }
            self::$_stopwords[$lang] = $list;
        }

        return self::$_stopwords[$lang];
    }
    // }}}

    // ranking_class(&$object) {{{
    /**
     *  ranking_class default Event handler
     *
     *  Construct the default ranking object (Pagerank). This
     *  can be override in a subclass or using addEvent static
     *  method
     *
     *  @param object &$object
     *  
     *  @return void
     */
    function ranking_class(&$object)
    {
        $object = new PageRank;
    }
    // }}}

    // addEvent(string $event_name, callback $callback, $unique = False) {{{
    /**
     *  Add a callback function that will be called the 
     *  event is triggered.
     *
     *  @param string   $event_name
     *  @param callback $callback
     *
     *  @return void
     */
    final public function addEvent($event_name, $callback, $unique=False)
    {
        if (!is_callable($callback)) {
            throw new Exception("Invalid callback for event {$event_name}");
        }
        if ($unique) {
            self::$_events[$event_name] = array();
        }
        self::$_events[$event_name][] = $callback;
    }
    // }}}

    // triggerEvent(string $event_name, array $params=array()) {{{
    /**
     *  Trigger a given event
     *
     *  @param string $event_name
     *  @param array  $params
     *  @param bool   $must_run
     *
     *  @return bool
     */
    final protected function triggerEvent($event_name, $params=array(), $must_run=FALSE)
    {
        $called = FALSE;
        if (isset(self::$_events[$event_name])) {
            foreach (self::$_events[$event_name] as $callback) {
                $called = TRUE;
                $return = call_user_func_array($callback, $params);
                if ($return === FALSE) {
                    /* The event canceled other Events callbacks */
                    return TRUE;
                }
            }
        }

        if (isset($this) && is_callable(array($this, $event_name))) {
            $called = TRUE;
            call_user_func_array(array($this, $event_name), $params);
        }
        if ($must_run && !$called) {
            throw new Exception("There is not callback for event `{$event_name}`");
        }
        return $called;
    }
    // }}}

    // addText(string $text, string $lang=NULL) {{{
    /**
     *  addText
     *
     *  Add text to extract features using the TextRank
     *
     *  @events new_text, clean_text
     *
     *  @param string $text
     *  @param string $lang
     *
     *  @return void
     */
    final function addText($text, $lang=NULL)
    {
        /**
         *  new_text Event
         *
         *  This event is triggered whenever a new text
         *  is added. The event can modify the input 
         *  text.
         */
        $this->triggerEvent('new_text', array(&$text));
        $this->raw_text = $text;
        $this->text     = $text;
        $this->lang     = $lang;

        /** 
         *  clean_text Event
         *
         *  This event is useful to clean up the text
         *  that would be used to extract all features.
         */
        $this->triggerEvent('clean_text', array(&$this->text));

        /**
         *  Get features extracted from the text, these features
         *  will be used to build a graph and to apply the selected
         *  ranking algorithm
         */
        $features = array();
        $this->triggerEvent('get_features', array($this->text, &$features), TRUE);
        if (!is_array($features)) {
            throw new Exception("Features returned by event `get_features` is not an array");
        }
        /* Copy features (before filtering) to the current object */
        $this->features = $features;

        /**
         *  Filter Features
         *  
         *  Call event to clean up non-useful features. 
         */
        $this->triggerEvent('filter_features', array(&$features));
        if (!is_array($features)) {
            throw new Exception("Features returned by event `filter_features` is not an array");
        }
    
        $features = array_values($features);

        /**
         *  Build the Graph of features
         */
        $this->triggerEvent('build_graph', array($features, array($this->ranking, 'addConnection')), TRUE);

        /**
         *  Call our ranking object method
         */
        $result = $this->ranking->calculate();

        $this->triggerEvent('post_ranking', array(&$result));
    }
    // }}}

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
