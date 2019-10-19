<?php

//klasa koja predstavlja jedan film
class Movie{

    protected $moveid, $title, $description, $rank;

    function __construct( $moveid, $title, $description, $rank){
        $this->moveid = $moveid;
        $this->title = $title;
        $this->description = $description;
        $this->rank = $rank;
    }

    function __get( $prop ) { return $this->$prop; }
    function __set( $prop, $val ) { $this->$prop = $val; return $this; }
}

?>