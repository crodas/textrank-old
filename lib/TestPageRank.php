<?php
require "PageRank.php";

$c = new Pagerank;
$c->addConnection(1, 2);
$c->addConnection(1, 4);
$c->addConnection(1, 5);
$c->addConnection(4, 5);
$c->addConnection(4, 1);
$c->addConnection(4, 3);
$c->addConnection(1, 3);
$c->addConnection(3, 1);
$c->addConnection(5, 1);
var_dump($c->calculate());

