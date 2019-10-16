<?php

//klasa koja predstavlja jedan film
class Movie{

    protected $moveid, $title, $categories, $summary, $description;

    function __construct( $moveid, $title, $categories, $summary, $description ){
        $this->moveid = $moveid;
        $this->title = $title;
        $this->categories = $categories;
        $this->summary = $summary;
        $this->description = $description;
    }

    function __get( $prop ) { return $this->$prop; }
    function __set( $prop, $val ) { $this->$prop = $val; return $this; }
}

?>