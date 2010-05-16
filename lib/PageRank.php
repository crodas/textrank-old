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
 *  PageRank class
 *
 *  Simple and efficient PageRank(tm) implementation
 *  in pure PHP.
 *
 *  @author César D. Rodas <crodas@php.net>
 *  @license BSD License
 *  @package TextRank
 *  @version 1.0
 *
 */
final class PageRank extends TextRank_Ranking
{
    /**
     *  Damping factor
     *  @float
     */
    protected $damping;
    /**
     *  Graph
     *  @array
     */
    protected $graph;
    /**
     *  Array with outlinks
     *  @array
     */
    protected $outlinks;
    /**
     *  Array with nodes
     *  @array
     */
    protected $nodes;
    /**
     *  Convergence 
     *  @float
     */
    protected $convergence;

    // __construct() {{{
    /**
     *  Class constructor
     *
     *  Set default values
     *
     *  @return void
     */
    function __construct()
    {
        /* Default values */
        $this->setDamping("0.85");
        $this->setConvergence("0.001");
    }
    // }}}

    // setDamping(float $damping) {{{
    /**
     *  Set Damping factor
     *
     *  See theory/pagerank.pdf
     *
     *  @param float $damping This must be 1 < $damping > 0  
     *
     *  @return void
     */
    public function setDamping($damping)
    {
        if ($damping > 1 || $damping <= 0) {
            throw new Exception("Invalid damping factor");;
        }
        $this->damping = $damping;
    }
    // }}}

    // setConvergence(float $convergence) {{{
    /**
     *  Set Convergence
     *
     *  See theory/pagerank.pdf
     *
     *  @param float $damping This must be 1 < $convergence
     *
     *  @return void
     */
    public function setConvergence($convergence)
    {
        if ($convergence > 1) {
            throw new Exception("Invalid convergence factor");;
        }
        $this->convergence = $convergence;
    }
    // }}}

    // addConnection(int $source_node, int $dest_node) {{{
    /**
     *  addConnection
     *
     *  Add a node conection to the graph
     *
     *  @param int $source_node
     *  @param int $dest_node   
     *
     *  @return bool
     */
    public function addConnection($source_node, $dest_node)
    {
        if ($source_node == $dest_node) {
            return FALSE;
        }
        if (!isset($this->outlinks[$source_node])) {
            $this->outlinks[$source_node] = 0;
        }
        if (!isset($this->graph[$dest_node])) {
            $this->graph[$dest_node] = Array();
        }

        $this->graph[$dest_node][]     = $source_node;
        $this->outlinks[$source_node] += 1;

        /* By default values for both nodes */
        $this->nodes[$source_node]      = 0.15;
        $this->nodes[$dest_node]        = 0.15;
        return TRUE;
    }
    // }}}

    // subs(array $a, array $b) {{{
    /**
     *  Array substraction
     *
     *  @param array $a
     *  @param array $b
     *  
     *  @return array
     */
    final protected function subs($a, $b)
    {
        $array = array();
        if (count($a) != count($a)) {
            throw new Exception("Array shape  mismatch");
        }
        foreach ($a as $index => $value) {
            if (!isset($b[$index])) {
                throw new Exception("Array shape  mismatch");
            }
            $array[$index] = $value - $b[$index]; 
        }
        return $array;
    }
    // }}}

    // mult(array $a, array $b) {{{
    /**
     *  Array multiplication
     *
     *  @param array $a
     *  @param array $b
     *  
     *  @return array
     */
    final protected function mult($a, $b)
    {
        $val = 0;
        if (count($a) != count($a)) {
            throw new Exception("Array shape  mismatch");
        }
        foreach ($a as $index => $value) {
            if (!isset($b[$index])) {
                throw new Exception("Array shape  mismatch");
            }
            $val += $b[$index]  * $value;
        }
        return $val;
    }
    // }}}

    // convergence(array $current) {{{
    /**
     *  Convergence
     *
     *  Check if our pagerank converged.
     *
     *  @param array $current
     *  @param float $convergence 
     *
     *  @return bool
     */
    protected function convergence($current)
    {
        $total = count($current);
        $diff  = $this->subs($current,$this->nodes);
        return (sqrt($this->mult($diff, $diff))/$total < $this->convergence);
    }
    // }}}

    // iteration(array &$new_nodes) {{{
    /**
     *  Performs one iteration to calculate the pagerank ranking
     *  for all nodes.
     *
     *  @param array &$new_nodes
     *
     *  @return void
     */
    protected function iteration(&$new_nodes)
    {
        $graph    = & $this->graph;
        $outlinks = & $this->outlinks;
        $nodes    = & $this->nodes;
        $damping  = (1-$this->damping)/count($nodes);
        foreach ($graph as $node => $links) {
            /**
             *  Our rank is the sum of all incoming links ($links)
             *  divided by their outlinks ($outlinks), that value is 
             *  normalized by the damping factor.
             */
            $score = 0;
            foreach ($links as $node_id) {
                $score += $nodes[$node_id] / $outlinks[$node_id];
            }
            $new_nodes[$node] = $damping + $this->damping * $score; 
        }
    }
    // }}}

    // calculate() {{{
    /**
     *  Pagerank main loop
     *
     *  @return array
     */
    public function calculate()
    {
        $done  = FALSE;
        $nodes = & $this->nodes;

        for ($i=0; !$done ; $i++) {
            $new_nodes = array();
            $this->iteration($new_nodes);
            $done = $this->convergence($new_nodes);
            /* swap, replace nodes with new nodes
             * do this until the convergence is TRUE and
             * the loop ends
             */
            $nodes = $new_nodes;
        }

        arsort($nodes);
        return $nodes;
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
