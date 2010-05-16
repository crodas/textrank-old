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

/**
 *  TextRank main class
 *
 *
 *
 */
abstract class TextRank
{
    private static $_events;
    protected $raw_text;
    protected $text;
    protected $features;
    protected $ranking;

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
     *
     *  @return bool
     */
    final protected function triggerEvent($event_name, $params=array())
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
        return $called;
    }
    // }}}

    // addText(string $text) {{{
    /**
     *  addText
     *
     *  Add text to extract features using the TextRank
     *
     *  @events new_text, clean_text
     *
     *  @param string $text
     *
     *  @return void
     */
    final function addText($text)
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
        $params   = array($this->text, &$features);
        if ($this->triggerEvent('get_features', $params) === FALSE) {
            throw new Exception("There is not required event `get_features`");
        }
        if (!is_array($features)) {
            throw new Exception("Features returned by event `get_features` is not an array");
        }

        /**
         *  Filter Features
         *  
         *  Call event to clean up non-useful features. 
         *
         *  NOTE: Filter_feature only can use `unset` to delete elements,
         *  and it cannot re-create the array after this process (with array_values).
         */
        $this->triggerEvent('filter_features', array(&$features));
        if (!is_array($features)) {
            throw new Exception("Features returned by event `filter_features` is not an array");
        }

        /* Copy the features to the object */
        $this->features = $features;

    }
    // }}}

}

/**
 *  Simple Event handler to clean up a text (Spanish texts)
 */
TextRank::addEvent('clean_text', function (&$text) {
    $text = strtolower($text);
    $text = preg_replace("/[^a-záéíóúüñ ]/", " ", $text);
    $text = preg_replace("/ +/", " ", $text);
});

/**
 *  
 */
TextRank::addEvent('get_features', function($text, &$features) {
    $features = explode(" ", $text);
});

TextRank::addEvent('filter_features', function (&$features) {
    foreach ($features as $id => $word) {
        if (strlen($word) < 3) {
            unset($features[$id]);
        }
    }
});

class Keywords extends TextRank
{

}

$c = new Keywords;
$c->addText(<<<EOF

Al tiempo que los expertos señalaron en los últimos días que la cantidad de petróleo vertida al mar tras el hundimiento de la plataforma Deepwater Horizon era mucho mayor a la estimada, Obama prometió el viernes que no descansaría hasta que la pérdida estuviera contenida y sellada.

Ingenieros de la firma de energía de British Petroleum, utilizando robots submarinos, luchaban por implementar su táctica más reciente para contener el derrame a 1.600 metros bajo la superficie del mar.

El plan es conectar un "tubo de inserción" al oleoducto para canalizar el petróleo derramado a un buque contenedor en la superficie, pero el proceso está tomando más tiempo del esperado. "Es verdaderamente complicado debido a la  profundidad", dijo a la AFP el portavoz John Crabtree.

El tubo de inserción se considera más efectivo que un plan anterior de utilizar un "sombrero", un contenedor añadido a un tubo de sifón que iba a ser colocado sobre la grieta para recolectar y canalizar hacia afuera el petróleo.

Los expertos temen que el petróleo podría estar volcándose a un nivel de hasta 2,9 millones de galones diarios, más de diez veces más rápido que las estimaciones del Gobierno, de 210.000 galones diarios.

Estas cifras sugieren que el derrame ha eclipsado al de Exxon Valdez en 1989, el peor desastre ecológico en la historia de Estados Unidos.

Un grupo ambientalista, el Centro para la Diversidad Biológica, dijo que había notificado su intención de demandar al secretario del Interior, Ken Salazar, por ignorar las leyes de protección de mamíferos marinos.

"Bajo la mirada de Salazar, el Departamento del Interior ha tratado al Golfo de México como un área sacrificada donde las leyes son ignoradas y la protección de la vida salvaje está en el asiento trasero de los beneficios de las compañías petroleras", señaló el director de océanos de esa institución ecológica, Miyoko Sakashita.

En una declaración del viernes, el presidente Obama arremetió contra las compañías petroleras por intentar culparse mutuamente por la marea negra en el Golfo de México y juró poner fin a las relaciones "íntimas" entre la industria y las agencias públicas de control.

En un tono inusualmente duro, Obama dijo que había ordenado una reforma de "arriba a abajo" de las agencias federales encargadas de autorizar las perforaciones en el mar y anunció que se revisarían las formas en que se hacen cumplir las normas de protección ambiental.

El mandatario atacó a las tres compañías petroleras involucradas en el accidente, que dieron lo que llamó "un espectáculo ridículo" por tratar de culparse mutuamente de la tragedia ante una comisión del Senado.

"No voy a tolerar más dedos acusadores ni irresponsabilidad", dijo el mandatario tras la reunión con sus asesores. Visiblemente enojado, Obama dijo que el Gobierno federal también tenía que asumir responsabilidades y prometió un control más estricto sobre la industria petrolera.
EOF
);

var_dump($c);

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
